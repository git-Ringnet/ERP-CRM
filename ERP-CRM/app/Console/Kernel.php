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
