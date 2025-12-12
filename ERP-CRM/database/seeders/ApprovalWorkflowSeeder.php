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
    }
}
