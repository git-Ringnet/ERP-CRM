<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckProjectUpdateDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:check-update-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra dự án đến hạn update 30 ngày hoặc quá hạn 90 ngày tự động chuyển Expired';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Đang kiểm tra tiến độ các dự án...');

        $now = now();
        $activeProjects = Project::whereIn('registration_status', ['update_status', 'registered', 'vendor_quoted'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        $remindedCount = 0;
        $expiredCount = 0;

        foreach ($activeProjects as $project) {
            $lastUpdate = $project->last_sales_updated_at ?? $project->created_at;
            $daysSinceUpdate = $now->diffInDays($lastUpdate);

            // 1. Check for 90 days (3 cycles) auto-expiry
            if ($daysSinceUpdate >= 90 || $project->missed_update_count >= 3) {
                $project->update([
                    'registration_status' => 'expired',
                    'status' => 'cancelled',
                    'note' => ($project->note ? $project->note . "\n" : '') . "[" . $now->format('d/m/Y H:i') . "] Tự động chuyển Expired do quá 90 ngày không cập nhật tiến độ.",
                ]);
                $expiredCount++;
                $this->warn("Dự án #{$project->code} đã tự động chuyển Expired do 90 ngày không update.");
                continue;
            }

            // 2. Check for 30 days monthly reminder threshold
            if ($daysSinceUpdate >= 30) {
                $lastReminded = $project->last_sales_reminded_at;
                // Remind at most once per week if still unupdated
                if (!$lastReminded || $now->diffInDays($lastReminded) >= 7) {
                    $project->increment('sales_reminder_count');
                    $project->increment('missed_update_count');
                    $project->update(['last_sales_reminded_at' => $now]);

                    $notificationService->notifyProjectSalesUpdateRequested($project);
                    $remindedCount++;
                    $this->info("Đã gửi thông báo nhắc Sales cập nhật cho dự án #{$project->code}.");
                }
            }
        }

        $this->info("Hoàn tất: Đã nhắc nhở {$remindedCount} dự án, chuyển Expired {$expiredCount} dự án.");
        return 0;
    }
}
