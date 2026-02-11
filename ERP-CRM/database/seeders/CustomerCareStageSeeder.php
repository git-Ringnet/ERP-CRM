<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomerCareStage;
use App\Models\Customer;
use App\Models\User;
use App\Models\CareMilestone;
use App\Models\CommunicationLog;

class CustomerCareStageSeeder extends Seeder
{
    public function run(): void
    {
        // Get first customer and user for demo
        $customer = Customer::first();
        $user = User::first();

        if (!$customer || !$user) {
            $this->command->warn('No customers or users found. Please create them first.');
            return;
        }

        // Create a sample care stage
        $careStage = CustomerCareStage::create([
            'customer_id' => $customer->id,
            'stage' => 'active',
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $user->id,
            'start_date' => now(),
            'target_completion_date' => now()->addDays(30),
            'completion_percentage' => 40,
            'notes' => 'Khách hàng quan trọng, cần chăm sóc tích cực.',
            'next_action' => 'Gọi điện thoại follow-up tiến độ dự án',
            'next_action_due_at' => now()->addDays(2),
            'next_action_completed' => false,
            'created_by' => $user->id,
        ]);

        // Add milestones
        CareMilestone::create([
            'customer_care_stage_id' => $careStage->id,
            'title' => 'Gọi điện giới thiệu',
            'description' => 'Liên hệ lần đầu với khách hàng',
            'due_date' => now()->subDays(5),
            'order' => 1,
            'is_completed' => true,
            'completed_at' => now()->subDays(4),
            'completed_by' => $user->id,
        ]);

        CareMilestone::create([
            'customer_care_stage_id' => $careStage->id,
            'title' => 'Gửi báo giá',
            'description' => 'Chuẩn bị và gửi báo giá chi tiết',
            'due_date' => now()->addDays(5),
            'order' => 2,
            'is_completed' => false,
        ]);

        CareMilestone::create([
            'customer_care_stage_id' => $careStage->id,
            'title' => 'Họp presentation',
            'description' => 'Trình bày giải pháp cho khách hàng',
            'due_date' => now()->addDays(15),
            'order' => 3,
            'is_completed' => false,
        ]);

        // Add communication logs
        CommunicationLog::create([
            'customer_care_stage_id' => $careStage->id,
            'user_id' => $user->id,
            'type' => 'call',
            'subject' => 'Cuộc gọi giới thiệu sản phẩm',
            'description' => 'Đã giới thiệu các tính năng chính của sản phẩm. Khách hàng tỏ ra rất quan tâm đến module quản lý kho.',
            'sentiment' => 'positive',
            'duration_minutes' => 30,
            'occurred_at' => now()->subDays(3),
        ]);

        CommunicationLog::create([
            'customer_care_stage_id' => $careStage->id,
            'user_id' => $user->id,
            'type' => 'email',
            'subject' => 'Gửi tài liệu giới thiệu',
            'description' => 'Đã gửi brochure và case study của các khách hàng tương tự.',
            'sentiment' => 'neutral',
            'occurred_at' => now()->subDays(2),
        ]);

        CommunicationLog::create([
            'customer_care_stage_id' => $careStage->id,
            'user_id' => $user->id,
            'type' => 'meeting',
            'subject' => 'Họp khảo sát nhu cầu',
            'description' => 'Họp với team IT của khách hàng để hiểu rõ yêu cầu kỹ thuật. Một số vấn đề về tích hợp với hệ thống hiện tại cần giải quyết.',
            'sentiment' => 'neutral',
            'duration_minutes' => 90,
            'occurred_at' => now()->subDays(1),
        ]);

        $this->command->info('✅ Created 1 customer care stage with 3 milestones and 3 communication logs');
    }
}
