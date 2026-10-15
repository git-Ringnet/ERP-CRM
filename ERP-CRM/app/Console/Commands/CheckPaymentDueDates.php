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
     *
     * Mỗi người nhận chỉ nhận 1 thông báo tổng hợp/ngày, liệt kê các đợt quá hạn
     * và sắp tới hạn (0-3 ngày) thay vì một thông báo cho từng đợt.
     */
    public function handle()
    {
        $today = Carbon::today();

        // Tính lại hạn từng đợt theo lộ trình (giao hàng / xuất HĐ / duyệt P&L)
        // để không sót mốc nào trước khi gửi cảnh báo.
        \App\Models\Sale::where('status', '!=', 'cancelled')
            ->whereHas('paymentSchedules', fn ($q) => $q->whereNotIn('status', ['paid', 'waived', 'exception_approved']))
            ->each(fn ($sale) => $sale->updateMilestoneDueDates());

        $schedules = SalePaymentSchedule::with('sale.user')
            ->whereNotIn('status', ['paid', 'waived', 'exception_approved'])
            ->whereNotNull('due_date')
            ->whereHas('sale', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->get();

        $this->info('Checking ' . $schedules->count() . ' active payment milestones...');

        $accountantIds = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['accountant', 'admin', 'super_admin']))->pluck('id');
        $managerIds = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['sales_manager', 'director', 'admin', 'super_admin']))->pluck('id');

        // [userId => ['overdue' => [scheduleId => line], 'due_soon' => [...], 'sale_ids' => [...]]]
        $digests = [];
        $add = function (int $userId, string $bucket, SalePaymentSchedule $ms, string $line) use (&$digests) {
            $digests[$userId][$bucket][$ms->id] = $line;
            $digests[$userId]['sale_ids'][$ms->sale_id] = true;
        };

        foreach ($schedules as $ms) {
            $sale = $ms->sale;
            if (!$sale) {
                continue;
            }

            $dueDate = Carbon::parse($ms->due_date)->startOfDay();
            $diffInDays = (int) $today->diffInDays($dueDate, false); // âm = đã quá hạn
            $salesPersonId = $sale->user_id;

            if ($diffInDays < 0) {
                $overdueDays = abs($diffInDays);
                $this->info("Milestone {$ms->milestone_name} of Sale {$sale->code} is OVERDUE by {$overdueDays} days.");

                if ($ms->status !== 'overdue') {
                    $ms->status = 'overdue';
                    $ms->save();
                }

                $line = "{$sale->code} - {$ms->milestone_name}: quá hạn {$overdueDays} ngày (hạn {$dueDate->format('d/m/Y')})";
                if ($salesPersonId) {
                    $add($salesPersonId, 'overdue', $ms, $line);
                }
                foreach ($managerIds->merge($accountantIds)->unique() as $userId) {
                    $add($userId, 'overdue', $ms, $line);
                }
            } elseif ($diffInDays <= 3) {
                $this->info("Milestone {$ms->milestone_name} of Sale {$sale->code} is due in {$diffInDays} days.");

                $dueLabel = $diffInDays === 0 ? 'đến hạn hôm nay' : "còn {$diffInDays} ngày";
                $line = "{$sale->code} - {$ms->milestone_name}: {$dueLabel} (hạn {$dueDate->format('d/m/Y')})";
                if ($salesPersonId) {
                    $add($salesPersonId, 'due_soon', $ms, $line);
                }
                foreach ($accountantIds as $userId) {
                    $add($userId, 'due_soon', $ms, $line);
                }
            }
        }

        $sent = 0;
        foreach ($digests as $userId => $digest) {
            if ($this->sendDigest($userId, $digest['overdue'] ?? [], $digest['due_soon'] ?? [], array_keys($digest['sale_ids'] ?? []))) {
                $sent++;
            }
        }

        $this->info("Payment due check completed. Sent {$sent} digest notifications.");
        return Command::SUCCESS;
    }

    /**
     * Gửi 1 thông báo tổng hợp/ngày cho mỗi người (không gửi lại nếu đã gửi trong ngày).
     */
    private function sendDigest(int $userId, array $overdue, array $dueSoon, array $saleIds): bool
    {
        if (empty($overdue) && empty($dueSoon)) {
            return false;
        }

        $alreadySent = Notification::where('user_id', $userId)
            ->where('type', 'payment_alert')
            ->whereDate('created_at', Carbon::today())
            ->exists();
        if ($alreadySent) {
            return false;
        }

        $listLines = function (array $lines, int $limit = 10): string {
            $shown = array_slice(array_values($lines), 0, $limit);
            $text = implode("
", array_map(fn ($l) => '• ' . $l, $shown));
            if (count($lines) > $limit) {
                $text .= "
• ... và " . (count($lines) - $limit) . ' đợt khác';
            }
            return $text;
        };

        $parts = [];
        if (!empty($overdue)) {
            $parts[] = 'QUÁ HẠN (' . count($overdue) . " đợt):
" . $listLines($overdue);
        }
        if (!empty($dueSoon)) {
            $parts[] = 'SẮP TỚI HẠN (' . count($dueSoon) . " đợt):
" . $listLines($dueSoon);
        }

        $title = !empty($overdue)
            ? 'CẢNH BÁO: ' . count($overdue) . ' đợt thanh toán quá hạn' . (!empty($dueSoon) ? ', ' . count($dueSoon) . ' đợt sắp tới hạn' : '')
            : 'Nhắc nhở: ' . count($dueSoon) . ' đợt thanh toán sắp tới hạn';

        Notification::create([
            'user_id' => $userId,
            'type' => 'payment_alert',
            'title' => $title,
            'message' => implode("

", $parts),
            'link' => count($saleIds) === 1 ? route('sales.show', $saleIds[0]) : route('sales.index'),
            'icon' => !empty($overdue) ? 'fas fa-exclamation-triangle' : 'fas fa-clock',
            'color' => !empty($overdue) ? 'red' : 'yellow',
        ]);

        return true;
    }
}
