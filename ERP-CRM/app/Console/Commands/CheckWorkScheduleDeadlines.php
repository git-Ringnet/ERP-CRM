<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkSchedule;
use App\Services\NotificationService;
use Carbon\Carbon;

class CheckWorkScheduleDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'work-schedules:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra và gửi thông báo cho các lịch làm việc sắp hết hạn hoặc đã hết hạn';

    protected $notificationService;

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
        $this->info('Đang kiểm tra lịch làm việc...');

        // Kiểm tra các lịch sắp hết hạn (trong vòng 24 giờ tới)
        $this->checkUpcomingSchedules();

        // Kiểm tra các lịch đã hết hạn
        $this->checkExpiredSchedules();

        $this->info('Hoàn thành kiểm tra!');
    }

    /**
     * Kiểm tra các lịch sắp hết hạn trong 24 giờ tới
     */
    protected function checkUpcomingSchedules()
    {
        $now = Carbon::now();
        $tomorrow = Carbon::now()->addDay();

        $upcomingSchedules = WorkSchedule::with(['creator', 'participants'])
            ->where('status', '!=', 'completed')
            ->whereNotNull('end_datetime')
            ->whereBetween('end_datetime', [$now, $tomorrow])
            ->get();

        foreach ($upcomingSchedules as $schedule) {
            // Lấy danh sách người nhận thông báo
            $recipientIds = $this->getRecipientIds($schedule);

            // Gửi thông báo
            $this->notificationService->notifyWorkScheduleUpcoming($schedule, $recipientIds);
            
            $this->line("✓ Đã gửi thông báo sắp hết hạn cho lịch: {$schedule->title}");
        }

        $this->info("Đã kiểm tra {$upcomingSchedules->count()} lịch sắp hết hạn.");
    }

    /**
     * Kiểm tra các lịch đã hết hạn
     */
    protected function checkExpiredSchedules()
    {
        $now = Carbon::now();

        $expiredSchedules = WorkSchedule::with(['creator', 'participants'])
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'overdue')
            ->whereNotNull('end_datetime')
            ->where('end_datetime', '<', $now)
            ->get();

        foreach ($expiredSchedules as $schedule) {
            // Cập nhật trạng thái thành overdue
            $schedule->update(['status' => 'overdue']);

            // Lấy danh sách người nhận thông báo
            $recipientIds = $this->getRecipientIds($schedule);

            // Gửi thông báo
            $this->notificationService->notifyWorkScheduleExpired($schedule, $recipientIds);
            
            $this->line("✓ Đã gửi thông báo hết hạn cho lịch: {$schedule->title}");
        }

        $this->info("Đã kiểm tra {$expiredSchedules->count()} lịch hết hạn.");
    }

    /**
     * Lấy danh sách ID người nhận thông báo
     */
    protected function getRecipientIds($schedule)
    {
        $recipientIds = [];

        if ($schedule->type === 'group') {
            // Lịch nhóm: gửi cho tất cả participants
            $recipientIds = $schedule->participants->pluck('id')->toArray();
        } else {
            // Lịch cá nhân: gửi cho người tạo
            $recipientIds = [$schedule->created_by];
        }

        return $recipientIds;
    }
}
