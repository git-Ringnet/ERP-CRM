<?php

namespace App\Services;

use App\Models\MarketingRequest;
use App\Models\MarketingRequestComment;
use App\Models\Notification;
use App\Models\TechnicalTicket;
use App\Models\TechnicalTicketAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Yêu cầu phối hợp Marketing gửi team Kỹ thuật → tự tạo Ticket kỹ thuật (loại Event / Speaker).
 * Kỹ thuật xử lý hoàn toàn trên ticket; trạng thái được đồng bộ ngược về yêu cầu Marketing.
 */
class MarketingTechnicalTicketService
{
    /**
     * Trạng thái ticket kỹ thuật → trạng thái yêu cầu Marketing.
     */
    private const STATUS_MAP = [
        'open'        => 'received',
        'assigned'    => 'in_progress',
        'in_progress' => 'in_progress',
        'waiting'     => 'in_progress',
        'completed'   => 'completed',
        'closed'      => 'completed',
    ];

    public function shouldCreateTicket(MarketingRequest $request): bool
    {
        return strtolower((string) $request->support_team) === 'technical'
            && !$request->technical_ticket_id
            && $request->marketing_event_id;
    }

    /**
     * Tạo ticket kỹ thuật cho yêu cầu Marketing và gửi thông báo cho Kỹ thuật.
     */
    public function createForRequest(MarketingRequest $request, ?User $creator = null): ?TechnicalTicket
    {
        if (!$this->shouldCreateTicket($request)) {
            return $request->technicalTicket;
        }

        $request->loadMissing(['event', 'ticket']);
        $event = $request->event;
        $creatorId = $creator?->id ?? $request->ticket?->created_by;
        $selfPickup = !in_array($request->pic_type, ['lead', 'assistant'], true);
        $teamLead = $this->resolveTechnicalLead();
        $priority = 'medium';

        $deadline = $request->effective_deadline;
        $descLines = array_filter([
            'Yêu cầu phối hợp từ Marketing (' . $request->code . ')',
            'Sự kiện: ' . ($event->title ?? '') . ($event->code ? " ({$event->code})" : ''),
            $event?->event_date ? 'Ngày sự kiện: ' . \Carbon\Carbon::parse($event->event_date)->format('d/m/Y') : null,
            $event?->location ? 'Địa điểm: ' . $event->location : null,
            'Nội dung: ' . $request->support_content_label,
            $deadline ? 'Hạn Marketing yêu cầu: ' . $deadline->format('d/m/Y H:i') : null,
            $request->description ? "\nMô tả:\n" . $request->description : null,
        ]);

        $ticket = TechnicalTicket::create([
            'code'           => TechnicalTicket::generateCode(),
            'title'          => '[Marketing] ' . ($event->title ?? 'Sự kiện') . ' – ' . $request->support_content_label,
            'description'    => implode("\n", $descLines),
            'status'         => 'open',
            'work_type'      => 'event',
            'priority'       => $priority,
            'created_by'     => $creatorId,
            'team_lead_id'   => $teamLead?->id,
            'department'     => 'Marketing',
            'project_name'   => $event->title ?? null,
            'sla_deadline'   => TechnicalTicket::calculateSlaDeadline($priority),
            'ticket_details' => [
                'marketing_request_id' => $request->id,
                'marketing_event_id'   => $request->marketing_event_id,
                'allow_self_pickup'    => $selfPickup,
                'marketing_deadline'   => $deadline?->toDateTimeString(),
            ],
        ]);

        $request->forceFill(['technical_ticket_id' => $ticket->id])->saveQuietly();

        $this->copyAttachments($request, $ticket, $creatorId);

        MarketingRequestComment::create([
            'marketing_request_id' => $request->id,
            'user_id'              => $creatorId,
            'comment'              => "Đã tạo Ticket kỹ thuật {$ticket->code}. Kỹ thuật xử lý trên ticket, trạng thái tự cập nhật về đây.",
        ]);

        $this->notifyTechnicalTeam($ticket, $teamLead, $creatorId);

        return $ticket;
    }

    /**
     * Đồng bộ trạng thái / người xử lý từ ticket kỹ thuật về yêu cầu Marketing.
     */
    public function syncFromTicket(TechnicalTicket $ticket): void
    {
        $request = MarketingRequest::where('technical_ticket_id', $ticket->id)->first();
        if (!$request) {
            return;
        }

        $newStatus = self::STATUS_MAP[$ticket->status] ?? $request->status;
        $newAssignee = $ticket->assigned_to ?: null;

        $oldAssignee = $request->assigned_to;
        $oldStatus = $request->status;

        $request->status = $newStatus;
        $request->assigned_to = $newAssignee;
        $request->completed_at = $newStatus === 'completed' ? ($request->completed_at ?? now()) : null;

        if (!$request->isDirty()) {
            return;
        }
        $request->save();

        // Ghi lịch sử bên Marketing
        $note = [];
        if ((int) $oldAssignee !== (int) $newAssignee && $newAssignee) {
            $note[] = 'Người xử lý: ' . (User::find($newAssignee)->name ?? '');
        }
        if ($oldStatus !== $newStatus) {
            $note[] = 'Ticket ' . $ticket->code . ' → ' . $ticket->status_label_vi;
        }
        if ($note) {
            MarketingRequestComment::create([
                'marketing_request_id' => $request->id,
                'user_id'              => auth()->id() ?? $ticket->created_by,
                'comment'              => '[Ticket kỹ thuật] ' . implode('. ', $note) . '.',
            ]);
        }

        // Báo người tạo bên Marketing khi có người nhận / được phân công
        // (hoàn thành / đóng ticket đã được module Ticket kỹ thuật thông báo cho người tạo)
        if ($newAssignee && (int) $oldAssignee !== (int) $newAssignee
            && $ticket->created_by && (int) $ticket->created_by !== (int) auth()->id()) {
            Notification::create([
                'user_id' => $ticket->created_by,
                'type'    => 'marketing_collaboration',
                'title'   => (User::find($newAssignee)->name ?? 'Kỹ thuật') . ' đang xử lý: ' . $request->support_content_label,
                'message' => 'Ticket ' . $ticket->code . ' · Sự kiện "' . ($request->event->title ?? '') . '"',
                'link'    => route('technical-tickets.show', $ticket->id),
                'icon'    => 'fas fa-user-check',
                'color'   => 'blue',
            ]);
        }
    }

    private function resolveTechnicalLead(): ?User
    {
        return User::where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->where('slug', 'technical_lead'))
            ->orderBy('id')
            ->first();
    }

    /**
     * Copy file đính kèm của yêu cầu Marketing sang ticket kỹ thuật (để Kỹ thuật xem ngay trên ticket).
     */
    private function copyAttachments(MarketingRequest $request, TechnicalTicket $ticket, ?int $uploaderId): void
    {
        foreach ((array) $request->attachment_path as $file) {
            $src = $file['path'] ?? null;
            if (!$src || !Storage::disk('public')->exists($src)) {
                continue;
            }
            $name = $file['name'] ?? basename($src);
            $dest = 'technical_tickets/' . $ticket->id . '/' . time() . '_' . $name;
            Storage::put($dest, Storage::disk('public')->get($src));

            TechnicalTicketAttachment::create([
                'technical_ticket_id' => $ticket->id,
                'file_path'           => $dest,
                'file_name'           => $name,
                'file_size'           => Storage::size($dest),
                'document_type'       => 'Khác',
                'uploaded_by'         => $uploaderId,
            ]);
        }
    }

    /**
     * Thông báo giống luồng tạo ticket kỹ thuật thông thường:
     * - Lead chính được chỉ định;
     * - "Cả team tự nhận" → mọi kỹ thuật có quyền pickup; ngược lại → Technical Lead phân công.
     */
    private function notifyTechnicalTeam(TechnicalTicket $ticket, ?User $teamLead, ?int $creatorId): void
    {
        $link = route('technical-tickets.show', $ticket->id);
        $notified = [];

        $send = function (int $userId, string $title, string $message, string $icon, string $color) use (&$notified, $link, $creatorId) {
            if ($userId === (int) $creatorId || isset($notified[$userId])) {
                return;
            }
            $notified[$userId] = true;
            Notification::create([
                'user_id' => $userId,
                'type'    => 'technical_ticket',
                'title'   => $title,
                'message' => $message,
                'link'    => $link,
                'icon'    => $icon,
                'color'   => $color,
                'is_read' => false,
            ]);
        };

        $label = "{$ticket->code} - {$ticket->title}";

        if ($ticket->isSelfPickupAllowed()) {
            foreach (TechnicalTicket::technicalStaffQuery()->get() as $u) {
                if ($ticket->canUserPickup($u)) {
                    $send($u->id, 'Ticket Kỹ thuật mới từ Marketing (Tự nhận)', "Sẵn sàng pickup: {$label}", 'ticket-alt', 'blue');
                }
            }
        } else {
            $leads = User::where('status', 'active')
                ->whereHas('roles', fn ($q) => $q->where('slug', 'technical_lead'))
                ->get();
            foreach ($leads as $u) {
                $send($u->id, 'Phân công Ticket Kỹ thuật từ Marketing', "Cần phân công kỹ sư: {$label}", 'exclamation-circle', 'orange');
            }
        }

        if ($teamLead) {
            $send($teamLead->id, 'Bạn được chỉ định phụ trách Ticket Kỹ thuật mới', "Bạn là Lead chính phụ trách ticket: {$label}.", 'user-tie', 'purple');
        }
    }
}
