<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalLevel;

class ApprovalWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Dọn workflows không còn dùng (document_type sai từ seeder cũ hoặc đã loại bỏ)
        $obsoleteTypes = ['order', 'contract', 'purchase', 'payment', 'quotation'];
        foreach ($obsoleteTypes as $type) {
            $old = ApprovalWorkflow::where('document_type', $type)->first();
            if ($old) {
                $old->levels()->delete();
                $old->delete();
            }
        }

        // =======================================================
        // 2. QUY TRÌNH DUYỆT P&L + HỢP ĐỒNG (document_type = 'sale_pnl')
        //    Dùng tại: SaleController::submitPnL()
        //
        //    Theo sơ đồ quy trình (ảnh 2):
        //    Sales Team lập HĐMB/XNĐH + File P&L
        //    → Legal Team review Cấp 1 (kiểm tra điều khoản, policy)
        //    → BOD review Cấp 2 (phê duyệt cuối cùng)
        // =======================================================
        $pnlWf = ApprovalWorkflow::updateOrCreate(
            ['document_type' => 'sale_pnl'],
            [
                'name'        => 'Quy trình duyệt P&L & Hợp đồng bán hàng',
                'description' => 'Sales Team gửi P&L → Legal Team review → BOD phê duyệt',
                'is_active'   => true,
            ]
        );
        $pnlWf->levels()->delete();

        ApprovalLevel::create([
            'workflow_id'    => $pnlWf->id,
            'level'          => 1,
            'name'           => 'Legal Team review hợp đồng & P&L',
            'approver_type'  => 'role',
            'approver_value' => 'legal_team',
            'min_amount'     => null,
            'max_amount'     => null,
            'is_required'    => true,
        ]);

        ApprovalLevel::create([
            'workflow_id'    => $pnlWf->id,
            'level'          => 2,
            'name'           => 'BOD phê duyệt P&L & hợp đồng cuối cùng',
            'approver_type'  => 'role',
            'approver_value' => 'director',
            'min_amount'     => null,
            'max_amount'     => null,
            'is_required'    => true,
        ]);

        // =======================================================
        // 3. QUY TRÌNH DUYỆT NGÂN SÁCH MARKETING (document_type = 'marketing_budget')
        //    Dùng tại: MarketingEventController::submitApproval()
        //
        //    Theo sơ đồ quy trình (ảnh 1):
        //    Marketing Team tạo kế hoạch sự kiện
        //    → BOD/Legal Team xét duyệt ngân sách (diamond "Xin duyệt ngân sách")
        //    Cấp 1: Sales Manager (nếu ngân sách nhỏ ≤ 50tr, tự duyệt đủ)
        //    Cấp 2: BOD (bắt buộc cho mọi ngân sách)
        // =======================================================
        $mktWf = ApprovalWorkflow::updateOrCreate(
            ['document_type' => 'marketing_budget'],
            [
                'name'        => 'Quy trình duyệt Ngân sách Marketing',
                'description' => 'Marketing Team gửi kế hoạch → BOD hoặc Legal Team xét duyệt',
                'is_active'   => true,
            ]
        );
        $mktWf->levels()->delete();

        ApprovalLevel::create([
            'workflow_id'    => $mktWf->id,
            'level'          => 1,
            'name'           => 'BOD hoặc Legal Team xét duyệt ngân sách',
            'approver_type'  => 'role',
            'approver_value' => 'director,legal_team',
            'min_amount'     => null,
            'max_amount'     => null,
            'is_required'    => true,
        ]);
    }
}
