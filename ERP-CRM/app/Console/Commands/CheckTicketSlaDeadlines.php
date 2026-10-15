<?php

namespace App\Console\Commands;

use App\Models\TechnicalTicket;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckTicketSlaDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:check-sla-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nhắc Lead khi Ticket kỹ thuật chưa được pickup/phân công sắp hết hạn SLA (trước 2 giờ) hoặc đã quá hạn';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Đang kiểm tra hạn SLA các Ticket kỹ thuật...');

        $now = Carbon::now();
        $warningWindow = Carbon::now()->addHours(2);

        // SLA = thời gian pickup/phân công: chỉ nhắc các ticket chưa được pickup
        $activeTickets = TechnicalTicket::with(['assignedEngineers', 'teamLead'])
            ->whereNotIn('status', ['completed', 'closed'])
            ->whereNotNull('sla_deadline')
            ->whereNull('picked_up_at')
            ->get();

        $warningCount = 0;
        $overdueCount = 0;

        // Get all technical leads
        $techLeads = User::where('status', 'active')
            ->whereHas('roles', function ($q) {
                $q->where('slug', 'technical_lead');
            })->get();

        foreach ($activeTickets as $ticket) {
            $deadline = $ticket->sla_deadline;

            // Determine recipient IDs: Ticket Leads (chính + phụ) + General Tech Leads
            $recipientIds = $ticket->getAllLeadIds();
            foreach ($techLeads as $lead) {
                $recipientIds[] = $lead->id;
            }
            $recipientIds = array_unique(array_filter($recipientIds));

            // Case 1: Deadline within the next 2 hours
            if ($deadline->isFuture() && $deadline->lessThanOrEqualTo($warningWindow)) {
                $hoursLeft = round($now->diffInMinutes($deadline) / 60, 1);

                foreach ($recipientIds as $userId) {
                    // Check if already notified for this warning within the last 3 hours
                    $alreadyNotified = Notification::where('user_id', $userId)
                        ->where('type', 'technical_ticket_sla_warning')
                        ->where('link', route('technical-tickets.show', $ticket->id))
                        ->where('created_at', '>=', $now->copy()->subHours(3))
                        ->exists();

                    if (!$alreadyNotified) {
                        Notification::create([
                            'user_id' => $userId,
                            'type' => 'technical_ticket_sla_warning',
                            'title' => 'Cảnh báo SLA: Ticket sắp hết hạn pickup',
                            'message' => "Ticket {$ticket->code} - {$ticket->title} chưa được pickup/phân công, còn khoảng {$hoursLeft} giờ (Hạn SLA: {$deadline->format('H:i d/m/Y')}). Vui lòng phân công kịp thời.",
                            'link' => route('technical-tickets.show', $ticket->id),
                            'icon' => 'clock',
                            'color' => 'yellow',
                            'is_read' => false,
                        ]);
                        $warningCount++;
                    }
                }
            }

            // Case 2: Ticket is overdue
            if ($deadline->isPast()) {
                foreach ($recipientIds as $userId) {
                    // Check if already notified overdue today
                    $alreadyNotified = Notification::where('user_id', $userId)
                        ->where('type', 'technical_ticket_sla_overdue')
                        ->where('link', route('technical-tickets.show', $ticket->id))
                        ->whereDate('created_at', $now->toDateString())
                        ->exists();

                    if (!$alreadyNotified) {
                        Notification::create([
                            'user_id' => $userId,
                            'type' => 'technical_ticket_sla_overdue',
                            'title' => 'Cảnh báo SLA: Ticket quá hạn pickup',
                            'message' => "Ticket {$ticket->code} - {$ticket->title} đã quá hạn SLA pickup/phân công ({$deadline->format('H:i d/m/Y')}) nhưng chưa có kỹ sư nhận.",
                            'link' => route('technical-tickets.show', $ticket->id),
                            'icon' => 'exclamation-triangle',
                            'color' => 'red',
                            'is_read' => false,
                        ]);
                        $overdueCount++;
                    }
                }
            }
        }

        $this->info("Hoàn tất: Đã gửi {$warningCount} cảnh báo sắp đến hạn và {$overdueCount} thông báo quá hạn SLA.");
        return 0;
    }
}
