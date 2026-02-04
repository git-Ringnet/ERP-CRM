<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = \App\Models\User::first() ?? \App\Models\User::factory()->create();
        $users = \App\Models\User::where('id', '!=', $admin->id)->limit(3)->get();
        if ($users->isEmpty()) {
            $users = \App\Models\User::factory(3)->create();
        }

        // 1. Overdue High Priority Task (Red)
        \App\Models\WorkSchedule::create([
            'title' => 'Báo cáo tài chính Q1 (Quá hạn)',
            'description' => 'Cần hoàn thành gấp báo cáo tài chính để gửi ban giám đốc.',
            'start_datetime' => now()->subDays(2),
            'end_datetime' => now()->subDays(1),
            'type' => 'group',
            'status' => 'overdue',
            'priority' => 'high',
            'created_by' => $admin->id,
        ])->participants()->attach($users->pluck('id'));

        // 2. Upcoming Deadline (Yellow/Red warning)
        \App\Models\WorkSchedule::create([
            'title' => 'Gửi đề xuất marketing',
            'description' => 'Hoàn thiện slide và gửi cho khách hàng trước 5h chiều.',
            'start_datetime' => now()->addHours(2),
            'end_datetime' => now()->addHours(5),
            'type' => 'personal',
            'status' => 'in_progress',
            'priority' => 'high',
            'created_by' => $admin->id,
        ]);

        // 3. Normal Group Meeting (Purple)
        \App\Models\WorkSchedule::create([
            'title' => 'Họp team hàng tuần',
            'description' => 'Review tiến độ dự án và phân công task mới.',
            'start_datetime' => now()->addDays(1)->setHour(9)->setMinute(0),
            'end_datetime' => now()->addDays(1)->setHour(10)->setMinute(30),
            'type' => 'group',
            'status' => 'new',
            'priority' => 'medium',
            'created_by' => $admin->id,
        ])->participants()->attach($users->first()->id);

        // 4. Low Priority Personal Task (Blue/Green)
        \App\Models\WorkSchedule::create([
            'title' => 'Kiểm kê văn phòng phẩm',
            'description' => 'Đặt mua thêm giấy in và bút viết.',
            'start_datetime' => now()->addDays(3)->setHour(14),
            'end_datetime' => now()->addDays(3)->setHour(15),
            'type' => 'personal',
            'status' => 'new',
            'priority' => 'low',
            'created_by' => $admin->id,
        ]);

        // 5. Multi-day Event
        \App\Models\WorkSchedule::create([
            'title' => 'Training nhân viên mới',
            'description' => 'Đào tạo quy trình làm việc và sử dụng hệ thống.',
            'start_datetime' => now()->addDays(4)->setHour(8),
            'end_datetime' => now()->addDays(6)->setHour(17),
            'type' => 'group',
            'status' => 'new',
            'priority' => 'medium',
            'created_by' => $admin->id,
        ])->participants()->attach($users->pluck('id'));
    }
}
