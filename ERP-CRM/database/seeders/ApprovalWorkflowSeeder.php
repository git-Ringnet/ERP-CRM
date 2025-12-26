<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalLevel;

class ApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Quy trình duyệt báo giá - 2 cấp
        $quotationWorkflow = ApprovalWorkflow::create([
            'name' => 'Quy trình duyệt báo giá',
            'document_type' => 'quotation',
            'description' => 'Quy trình phê duyệt báo giá trước khi gửi khách hàng',
            'is_active' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $quotationWorkflow->id,
            'level' => 1,
            'name' => 'Trưởng phòng',
            'approver_type' => 'role',
            'approver_value' => 'manager',
            'is_required' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $quotationWorkflow->id,
            'level' => 2,
            'name' => 'Giám đốc',
            'approver_type' => 'role',
            'approver_value' => 'director',
            'is_required' => true,
        ]);

        // Quy trình duyệt đơn hàng - 2 cấp
        $orderWorkflow = ApprovalWorkflow::create([
            'name' => 'Quy trình duyệt đơn hàng',
            'document_type' => 'order',
            'description' => 'Quy trình phê duyệt đơn hàng trước khi xuất kho',
            'is_active' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $orderWorkflow->id,
            'level' => 1,
            'name' => 'Trưởng phòng',
            'approver_type' => 'role',
            'approver_value' => 'manager',
            'is_required' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $orderWorkflow->id,
            'level' => 2,
            'name' => 'Giám đốc',
            'approver_type' => 'role',
            'approver_value' => 'director',
            'is_required' => true,
        ]);

        // Quy trình duyệt hợp đồng - 3 cấp
        $contractWorkflow = ApprovalWorkflow::create([
            'name' => 'Quy trình duyệt hợp đồng',
            'document_type' => 'contract',
            'description' => 'Quy trình phê duyệt hợp đồng trước khi ký kết',
            'is_active' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $contractWorkflow->id,
            'level' => 1,
            'name' => 'Trưởng phòng',
            'approver_type' => 'role',
            'approver_value' => 'manager',
            'is_required' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $contractWorkflow->id,
            'level' => 2,
            'name' => 'Giám đốc',
            'approver_type' => 'role',
            'approver_value' => 'director',
            'is_required' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $contractWorkflow->id,
            'level' => 3,
            'name' => 'Pháp chế',
            'approver_type' => 'role',
            'approver_value' => 'legal',
            'is_required' => true,
        ]);

        // Quy trình duyệt đơn mua hàng - 3 cấp
        $purchaseWorkflow = ApprovalWorkflow::create([
            'name' => 'Quy trình duyệt đơn mua hàng',
            'document_type' => 'purchase',
            'description' => 'Quy trình phê duyệt đơn mua hàng từ nhà cung cấp',
            'is_active' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $purchaseWorkflow->id,
            'level' => 1,
            'name' => 'Trưởng phòng mua hàng',
            'approver_type' => 'role',
            'approver_value' => 'manager',
            'is_required' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $purchaseWorkflow->id,
            'level' => 2,
            'name' => 'Kế toán',
            'approver_type' => 'role',
            'approver_value' => 'accountant',
            'is_required' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $purchaseWorkflow->id,
            'level' => 3,
            'name' => 'Giám đốc',
            'approver_type' => 'role',
            'approver_value' => 'director',
            'is_required' => true,
        ]);

        // Quy trình duyệt phiếu chi - 2 cấp
        $paymentWorkflow = ApprovalWorkflow::create([
            'name' => 'Quy trình duyệt phiếu chi',
            'document_type' => 'payment',
            'description' => 'Quy trình phê duyệt phiếu chi thanh toán',
            'is_active' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $paymentWorkflow->id,
            'level' => 1,
            'name' => 'Kế toán trưởng',
            'approver_type' => 'role',
            'approver_value' => 'accountant',
            'is_required' => true,
        ]);

        ApprovalLevel::create([
            'workflow_id' => $paymentWorkflow->id,
            'level' => 2,
            'name' => 'Giám đốc',
            'approver_type' => 'role',
            'approver_value' => 'director',
            'is_required' => true,
        ]);

        // Thêm 10 quy trình nữa để test phân trang
        for ($i = 1; $i <= 10; $i++) {
            $workflow = ApprovalWorkflow::create([
                'name' => "Quy trình duyệt mẫu $i",
                'document_type' => "custom_type_$i",
                'description' => "Quy trình phê duyệt mẫu số $i để test phân trang",
                'is_active' => $i % 3 != 0, // Một số quy trình tắt
            ]);

            // Random 2-4 cấp duyệt
            $levels = rand(2, 4);
            $roles = ['manager', 'director', 'accountant', 'legal'];
            
            for ($j = 1; $j <= $levels; $j++) {
                ApprovalLevel::create([
                    'workflow_id' => $workflow->id,
                    'level' => $j,
                    'name' => "Cấp duyệt $j",
                    'approver_type' => 'role',
                    'approver_value' => $roles[array_rand($roles)],
                    'is_required' => true,
                ]);
            }
        }
    }
}
