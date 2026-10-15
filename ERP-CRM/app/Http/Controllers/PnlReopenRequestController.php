<?php

namespace App\Http\Controllers;

use App\Models\ApprovalHistory;
use App\Models\Notification;
use App\Models\PnlReopenRequest;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sales gửi yêu cầu BOD mở lại P&L đã duyệt (kèm lý do) để bổ sung đơn hàng.
 * BOD duyệt -> P&L về "Cần chỉnh sửa"; BOD từ chối -> giữ nguyên, Sales nhận lý do.
 */
class PnlReopenRequestController extends Controller
{
    public function store(Request $request, Sale $sale)
    {
        $user = auth()->user();
        $isSalesOfOrder = in_array($user->id, $sale->salesRecipientIds(), true);
        if (!$isSalesOfOrder && !$user->hasAnyRole(['sales_manager', 'admin', 'super_admin'])) {
            return back()->with('error', 'Chỉ Sales phụ trách đơn hàng được gửi yêu cầu mở lại P&L.');
        }

        if ($sale->pl_status !== 'approved' || $sale->status === 'cancelled') {
            return back()->with('error', 'Chỉ gửi yêu cầu mở lại khi P&L đang ở trạng thái Đã duyệt.');
        }

        if ($sale->pnlReopenRequests()->where('status', PnlReopenRequest::STATUS_PENDING)->exists()) {
            return back()->with('error', 'Đơn hàng đã có yêu cầu mở lại P&L đang chờ BOD duyệt.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:1000',
        ], [
            'reason.required' => 'Vui lòng nhập lý do cần mở lại P&L.',
            'reason.min' => 'Lý do cần ít nhất 3 ký tự.',
        ]);

        $reopen = $sale->pnlReopenRequests()->create([
            'requested_by' => $user->id,
            'reason' => trim($validated['reason']),
            'status' => PnlReopenRequest::STATUS_PENDING,
        ]);

        $reviewers = User::whereHas('roles', fn ($q) => $q->whereIn('slug', PnlReopenRequest::REVIEWER_ROLES))
            ->where('id', '!=', $user->id)
            ->get();
        foreach ($reviewers as $reviewer) {
            Notification::create([
                'user_id' => $reviewer->id,
                'type' => 'pnl_reopen_request',
                'title' => 'Yêu cầu mở lại P&L đã duyệt',
                'message' => "{$user->name} yêu cầu mở lại P&L đơn hàng {$sale->code} để bổ sung đơn hàng. Lý do: \"{$reopen->reason}\"",
                'link' => route('sales.show', $sale->id) . '#pnl',
                'icon' => 'fas fa-unlock-alt',
                'color' => 'orange',
            ]);
        }

        return back()->with('success', 'Đã gửi yêu cầu mở lại P&L tới BOD. Bạn sẽ nhận thông báo khi BOD xử lý.');
    }

    public function approve(Request $request, PnlReopenRequest $reopenRequest)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(PnlReopenRequest::REVIEWER_ROLES)) {
            return back()->with('error', 'Chỉ BOD được duyệt yêu cầu mở lại P&L.');
        }
        if ($reopenRequest->status !== PnlReopenRequest::STATUS_PENDING) {
            return back()->with('error', 'Yêu cầu này đã được xử lý.');
        }

        $sale = $reopenRequest->sale;
        $note = trim((string) $request->input('review_note', ''));

        DB::transaction(function () use ($reopenRequest, $sale, $user, $note) {
            $reopenRequest->update([
                'status' => PnlReopenRequest::STATUS_APPROVED,
                'reviewed_by' => $user->id,
                'review_note' => $note ?: null,
                'reviewed_at' => now(),
            ]);

            if ($sale->pl_status === 'approved') {
                $sale->update([
                    'pl_status' => 'need_revision',
                    'status' => 'pending',
                ]);

                ApprovalHistory::recordPnlRevision(
                    $sale,
                    $user,
                    "BOD duyệt yêu cầu mở lại P&L để bổ sung đơn hàng: {$reopenRequest->reason}" . ($note ? " | Ghi chú BOD: {$note}" : '')
                );
            }

            $this->notifySales(
                $reopenRequest,
                $sale,
                'BOD đã duyệt mở lại P&L',
                "{$user->name} đã duyệt yêu cầu mở lại P&L đơn hàng {$sale->code}. P&L đã chuyển về \"Cần chỉnh sửa\" để bổ sung đơn hàng và gửi duyệt lại." . ($note ? " Ghi chú: \"{$note}\"" : ''),
                'fas fa-unlock-alt',
                'green'
            );
        });

        return back()->with('success', 'Đã duyệt yêu cầu. P&L đã mở lại ở trạng thái Cần chỉnh sửa.');
    }

    public function reject(Request $request, PnlReopenRequest $reopenRequest)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(PnlReopenRequest::REVIEWER_ROLES)) {
            return back()->with('error', 'Chỉ BOD được xử lý yêu cầu mở lại P&L.');
        }
        if ($reopenRequest->status !== PnlReopenRequest::STATUS_PENDING) {
            return back()->with('error', 'Yêu cầu này đã được xử lý.');
        }

        $validated = $request->validate([
            'review_note' => 'required|string|min:3|max:1000',
        ], [
            'review_note.required' => 'Vui lòng nhập lý do từ chối.',
        ]);

        $sale = $reopenRequest->sale;
        $reopenRequest->update([
            'status' => PnlReopenRequest::STATUS_REJECTED,
            'reviewed_by' => $user->id,
            'review_note' => trim($validated['review_note']),
            'reviewed_at' => now(),
        ]);

        $this->notifySales(
            $reopenRequest,
            $sale,
            'BOD từ chối mở lại P&L',
            "{$user->name} đã từ chối yêu cầu mở lại P&L đơn hàng {$sale->code}. P&L giữ nguyên trạng thái Đã duyệt. Lý do: \"{$reopenRequest->review_note}\"",
            'fas fa-times-circle',
            'red'
        );

        return back()->with('success', 'Đã từ chối yêu cầu mở lại P&L.');
    }

    private function notifySales(PnlReopenRequest $reopenRequest, Sale $sale, string $title, string $message, string $icon, string $color): void
    {
        $recipientIds = collect([$reopenRequest->requested_by])
            ->merge($sale->salesRecipientIds())
            ->filter()
            ->unique()
            ->reject(fn ($id) => (int) $id === (int) auth()->id());

        foreach ($recipientIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => 'pnl_reopen_result',
                'title' => $title,
                'message' => $message,
                'link' => route('sales.show', $sale->id) . '#pnl',
                'icon' => $icon,
                'color' => $color,
            ]);
        }
    }
}
