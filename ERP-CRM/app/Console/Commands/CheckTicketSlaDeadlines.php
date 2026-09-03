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
    protected $description = 'Kiểm tra và gửi thông báo nhắc nhở khi Ticket kỹ thuật sắp hết hạn SLA (trước 2 giờ) hoặc đã quá hạn';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Đang kiểm tra hạn SLA các Ticket kỹ thuật...');

        $now = Carbon::now();
        $warningWindow = Carbon::now()->addHours(2);

        // Fetch active tickets with an SLA deadline
        $activeTickets = TechnicalTicket::with(['assignedEngineers', 'teamLead'])
            ->whereNotIn('status', ['completed', 'closed'])
            ->whereNotNull('sla_deadline')
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

            // Determine recipient IDs: Assigned Engineers + Ticket Team Lead + General Tech Leads
            $recipientIds = $ticket->assignedEngineers->pluck('id')->toArray();
            if ($ticket->team_lead_id) {
                $recipientIds[] = $ticket->team_lead_id;
            }
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
                            'title' => 'Cảnh báo SLA: Ticket sắp đến hạn',
                            'message' => "Ticket {$ticket->code} - {$ticket->title} còn khoảng {$hoursLeft} giờ (Hạn: {$deadline->format('H:i d/m/Y')}). Vui lòng xử lý kịp thời.",
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
                            'title' => 'Cảnh báo SLA: Ticket đã quá hạn',
                            'message' => "Ticket {$ticket->code} - {$ticket->title} đã quá hạn SLA ({$deadline->format('H:i d/m/Y')}).",
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
