<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Notification;
use App\Services\NotificationService;
use Carbon\Carbon;

class CheckPaymentDue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:check-payment-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra hạn thanh toán các đơn hàng và gửi thông báo cho salesperson (chống spam 24h)';

    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bắt đầu kiểm tra hạn thanh toán các đơn hàng...');

        // Quét các đơn hàng chưa thanh toán hết và không bị hủy, đã xuất hóa đơn
        $sales = Sale::query()
            ->with('customer')
            ->whereNotNull('invoice_date')
            ->where('payment_status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->get();

        $dueSoonCount = 0;
        $overdueCount = 0;
        $skippedCount = 0;

        $today = Carbon::today();

        foreach ($sales as $sale) {
            if (!$sale->user_id) {
                continue;
            }

            $debtDays = $sale->customer?->debt_days ?? 30;
            $dueDate = Carbon::parse($sale->invoice_date)->addDays($debtDays)->startOfDay();
            $daysUntilDue = $today->diffInDays($dueDate, false);

            if ($daysUntilDue >= 0 && $daysUntilDue <= 3) {
                // Sắp tới hạn (hoặc tới hạn hôm nay)
                // Chống spam: kiểm tra thông báo tương tự đã được gửi trong 24 giờ qua chưa
                $alreadyNotified = Notification::query()
                    ->where('user_id', $sale->user_id)
                    ->where('type', 'payment_due_soon')
                    ->where('data->sale_id', $sale->id)
                    ->where('created_at', '>=', now()->subHours(24))
                    ->exists();

                if ($alreadyNotified) {
                    $skippedCount++;
                    continue;
                }

                $this->notificationService->notifyPaymentDueSoon($sale);
                $this->line("✓ Đã gửi thông báo sắp tới hạn cho đơn hàng #{$sale->code} (Hạn: {$dueDate->toDateString()})");
                $dueSoonCount++;
            } elseif ($daysUntilDue < 0) {
                // Quá hạn
                $overdueDays = abs($daysUntilDue);

                // Chống spam: kiểm tra thông báo tương tự đã được gửi trong 24 giờ qua chưa
                $alreadyNotified = Notification::query()
                    ->where('user_id', $sale->user_id)
                    ->where('type', 'payment_overdue')
                    ->where('data->sale_id', $sale->id)
                    ->where('created_at', '>=', now()->subHours(24))
                    ->exists();

                if ($alreadyNotified) {
                    $skippedCount++;
                    continue;
                }

                $this->notificationService->notifyPaymentOverdue($sale, $overdueDays);
                $this->line("✓ Đã gửi thông báo quá hạn ({$overdueDays} ngày) cho đơn hàng #{$sale->code} (Hạn: {$dueDate->toDateString()})");
                $overdueCount++;
            }
        }

        $this->info("Hoàn thành! Gửi {$dueSoonCount} thông báo sắp tới hạn, {$overdueCount} thông báo quá hạn, bỏ qua {$skippedCount} (tránh spam).");
        return 0;
    }
}
