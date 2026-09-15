<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Kiểm tra lịch làm việc hết hạn mỗi giờ
        $schedule->command('work-schedules:check-deadlines')->hourly();
        
        // Gửi nhắc nhở hành động sắp đến hạn mỗi 15 phút
        $schedule->command('reminders:send-action-due')->everyFifteenMinutes();

        // Kiểm tra và gửi cảnh báo ticket kỹ thuật sắp hết hạn SLA (trước 2h) mỗi 5 phút
        $schedule->command('tickets:check-sla-deadlines')->everyFiveMinutes();

        // Fetch tỷ giá hối đoái từ Vietcombank mỗi ngày lúc 8h sáng
        $schedule->command('exchange-rates:fetch')->dailyAt('08:00');

        // Kiểm tra hạn thanh toán đơn hàng và thông báo mỗi ngày lúc 8h sáng
        $schedule->command('sales:check-payment-due')->dailyAt('08:00');

        // Kiểm tra hạn thanh toán từng đợt (milestones) mỗi ngày lúc 8h sáng
        $schedule->command('payment:check-due-dates')->dailyAt('08:00');

        // Tự động sao lưu toàn diện hệ thống (Database + File đính kèm mọi ổ đĩa) - Đang set mỗi phút 1 lần để test
        $schedule->command('backup:system --scope=full')
            ->everyMinute()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/backup.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
