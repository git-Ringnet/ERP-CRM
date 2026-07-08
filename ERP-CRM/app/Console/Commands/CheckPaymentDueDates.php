<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalePaymentSchedule;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;

class CheckPaymentDueDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:check-due-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check payment milestone due dates and send alerts for due soon/overdue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        
        $schedules = SalePaymentSchedule::with('sale.user')
            ->whereNotIn('status', ['paid', 'waived', 'exception_approved'])
            ->whereNotNull('due_date')
            ->get();

        $this->info('Checking ' . $schedules->count() . ' active payment milestones...');

        $accountants = User::whereHas('roles', function ($q) {
            $q->whereIn('slug', ['accountant', 'admin', 'super_admin']);
        })->get();

        $salesManagers = User::whereHas('roles', function ($q) {
            $q->whereIn('slug', ['sales_manager', 'director', 'admin', 'super_admin']);
        })->get();

        foreach ($schedules as $ms) {
            $dueDate = Carbon::parse($ms->due_date);
            $sale = $ms->sale;
            if (!$sale) {
                continue;
            }

            $salesPerson = $sale->user;
            $diffInDays = $today->diffInDays($dueDate, false); // positive if due date is in the future, negative if past due

            if ($diffInDays < 0) {
                // OVERDUE
                $overdueDays = abs($diffInDays);
                $this->info("Milestone {$ms->milestone_name} of Sale {$sale->code} is OVERDUE by {$overdueDays} days.");
                
                // Update status to overdue
                if ($ms->status !== 'overdue') {
                    $ms->status = 'overdue';
                    $ms->save();
                }

                $message = "Đợt thanh toán \"{$ms->milestone_name}\" của đơn hàng {$sale->code} đã QUÁ HẠN {$overdueDays} ngày (Hạn thanh toán: " . $dueDate->format('d/m/Y') . ").";

                // Notify Sales Person
                if ($salesPerson) {
                    $this->sendNotification($salesPerson->id, 'payment_alert', 'CẢNH BÁO QUÁ HẠN THANH TOÁN', $message, $sale->id, 'fas fa-exclamation-triangle', 'red');
                }

                // Notify Sales Managers and Accountants
                foreach ($salesManagers->merge($accountants) as $user) {
                    if ($salesPerson && $user->id === $salesPerson->id) {
                        continue;
                    }
                    $this->sendNotification($user->id, 'payment_alert', 'CẢNH BÁO QUÁ HẠN THANH TOÁN (ADMIN)', $message, $sale->id, 'fas fa-exclamation-triangle', 'red');
                }

            } elseif ($diffInDays >= 0 && $diffInDays <= 3) {
                // DUE SOON (0 to 3 days)
                $this->info("Milestone {$ms->milestone_name} of Sale {$sale->code} is due in {$diffInDays} days.");
                
                $dueLabel = $diffInDays === 0 ? "hôm nay" : "trong {$diffInDays} ngày tới";
                $message = "Đợt thanh toán \"{$ms->milestone_name}\" của đơn hàng {$sale->code} sẽ đến hạn {$dueLabel} (Hạn thanh toán: " . $dueDate->format('d/m/Y') . ").";

                // Notify Sales Person
                if ($salesPerson) {
                    $this->sendNotification($salesPerson->id, 'payment_alert', 'Nhắc nhở hạn thanh toán', $message, $sale->id, 'fas fa-clock', 'yellow');
                }

                // Notify Accountants
                foreach ($accountants as $acc) {
                    if ($salesPerson && $acc->id === $salesPerson->id) {
                        continue;
                    }
                    $this->sendNotification($acc->id, 'payment_alert', 'Nhắc nhở hạn thanh toán', $message, $sale->id, 'fas fa-clock', 'yellow');
                }
            }
        }

        $this->info('Payment due check completed.');
        return Command::SUCCESS;
    }

    private function sendNotification(int $userId, string $type, string $title, string $message, int $saleId, string $icon, string $color): void
    {
        // Avoid duplicate notifications on the same day for the same milestone and user
        $exists = Notification::where('user_id', $userId)
            ->where('title', $title)
            ->where('link', route('sales.show', $saleId))
            ->whereDate('created_at', Carbon::today())
            ->exists();

        if (!$exists) {
            Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => route('sales.show', $saleId),
                'icon' => $icon,
                'color' => $color,
            ]);
        }
    }
}
