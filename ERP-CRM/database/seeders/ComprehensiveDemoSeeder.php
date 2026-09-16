<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Opportunity;
use App\Models\MarketingEvent;
use App\Models\MarketingTicket;
use App\Models\MarketingRequest;
use App\Models\MarketingItem;
use App\Models\MarketingItemTransaction;
use App\Models\MarketingSupplierFund;
use App\Models\MarketingSupplierTransaction;
use App\Models\TechnicalTicket;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\PurchaseRequest;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ComprehensiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;
        
        $salesUser = User::whereHas('roles', fn($q) => $q->where('slug', 'sales_staff'))->first() ?? $admin;
        $techUser = User::whereHas('roles', fn($q) => $q->where('slug', 'technical_engineer'))->first() ?? $admin;
        $mktUser = User::whereHas('roles', fn($q) => $q->where('slug', 'marketing'))->first() ?? $admin;
        $bodUser = User::whereHas('roles', fn($q) => $q->where('slug', 'director'))->first() ?? $admin;

        $customers = Customer::take(5)->get();
        $suppliers = Supplier::take(5)->get();

        $c1 = $customers->get(0) ?? Customer::first();
        $c2 = $customers->get(1) ?? $c1;
        $c3 = $customers->get(2) ?? $c1;
        
        $sup1 = $suppliers->get(0) ?? Supplier::first();
        $sup2 = $suppliers->get(1) ?? $sup1;

        $mktItems = MarketingItem::where('status', 'active')->get();
        $itemBook = $mktItems->firstWhere('code', 'MKT-GIFT-001') ?? $mktItems->first();
        $itemPen = $mktItems->firstWhere('code', 'MKT-GIFT-002') ?? $mktItems->skip(1)->first();
        $itemThermo = $mktItems->firstWhere('code', 'MKT-GIFT-003') ?? $mktItems->skip(2)->first();

        // =========================================================================
        // 1. CƠ HỘI & TICKET PHỐI HỢP (OPPORTUNITIES & TICKETS)
        // =========================================================================
        
        // Mẫu 1: Cơ hội đã BOD duyệt, CẦN QUÀ TẶNG, CHƯA PHÂN BỔ TỒN KHO (để test cảnh báo & nút phân bổ ngay)
        $opp1 = Opportunity::updateOrCreate(
            ['name' => 'Demo giải pháp SD-WAN & NextGen Firewall cho Ngân hàng VietinBank'],
            [
                'customer_type' => 'si',
                'customer_id' => $c1?->id,
                'activity_type' => 'demo_offline',
                'activity_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:30',
                'duration_minutes' => 150,
                'description' => 'Trình bày giải pháp bảo mật hạ tầng mạng phân tán cho khối giao dịch và chi nhánh.',
                'materials_required' => 'Chuẩn bị 2 tài liệu brochure giải pháp, 1 switch PoE test lab và quà tặng đối tác.',
                'giveaway' => '5 phần quà tặng doanh nghiệp (Sổ da + Bút ký) và 10 cuốn catalogue giải pháp.',
                'giveaway_status' => 'approved',
                'status' => 'confirmed',
                'needs_technical' => true,
                'technical_user_id' => $techUser->id,
                'potential_rating' => '75',
                'assigned_to' => $salesUser->id,
                'created_by' => $salesUser->id,
            ]
        );

        // Tạo Ticket Kỹ thuật cho Opp 1
        TechnicalTicket::firstOrCreate(
            ['opportunity_id' => $opp1->id],
            [
                'code' => TechnicalTicket::generateCode(),
                'title' => 'Phối hợp kỹ thuật demo: ' . $opp1->name,
                'description' => $opp1->description,
                'status' => 'assigned',
                'work_type' => 'event',
                'priority' => 'high',
                'opportunity_id' => $opp1->id,
                'customer_id' => $opp1->customer_id,
                'assigned_to' => $techUser->id,
                'team_lead_id' => $techUser->id,
                'created_by' => $adminId,
                'department' => 'Technical',
                'sla_deadline' => now()->addDays(3),
            ]
        );

        // Tạo Ticket Marketing cho Opp 1 (Chưa xuất quà)
        $ticket1 = MarketingTicket::firstOrCreate(
            ['opportunity_id' => $opp1->id],
            [
                'type' => 'others',
                'status' => 'in_progress',
                'created_by' => $adminId,
            ]
        );

        MarketingRequest::firstOrCreate(
            ['opportunity_id' => $opp1->id, 'support_content' => 'giveaway'],
            [
                'marketing_ticket_id' => $ticket1->id,
                'opportunity_id' => $opp1->id,
                'support_team' => 'marketing',
                'pic_type' => 'all',
                'assigned_to' => $mktUser->id,
                'support_content' => 'giveaway',
                'support_content_other' => 'Chuẩn bị quà tặng đã duyệt',
                'description' => "Hoạt động: " . $opp1->name . "\nKhách hàng: " . ($c1?->name ?? 'VietinBank') . "\nQuà tặng/Budget: " . $opp1->giveaway . "\nNgày hoạt động: " . now()->addDays(5)->format('d/m/Y') . "\nYêu cầu chuẩn bị chung: " . $opp1->materials_required,
                'deadline' => now()->addDays(5),
                'status' => 'in_progress',
            ]
        );

        // Mẫu 2: Cơ hội đã BOD duyệt, ĐÃ XUẤT KHO VẬT PHẨM QUÀ TẶNG
        $opp2 = Opportunity::updateOrCreate(
            ['name' => 'Hội thảo chuyên đề Zero Trust & Cloud Security cùng DTS Group'],
            [
                'customer_type' => 'si',
                'customer_id' => $c2?->id,
                'activity_type' => 'demo_offline',
                'activity_date' => now()->addDays(3)->format('Y-m-d'),
                'start_time' => '14:00',
                'end_time' => '17:00',
                'duration_minutes' => 180,
                'description' => 'Hội thảo kỹ thuật chuyên sâu về mô hình bảo mật không tin cậy (Zero Trust Network Access).',
                'materials_required' => 'Standee hội thảo, bộ kit demo trực tiếp giải pháp.',
                'giveaway' => 'Sổ tay bìa da cao cấp (x3 Cuốn), Bút ký kim loại cao cấp mạ vàng (x3 Cây)',
                'giveaway_status' => 'approved',
                'status' => 'confirmed',
                'needs_technical' => true,
                'technical_user_id' => $techUser->id,
                'potential_rating' => '90',
                'assigned_to' => $salesUser->id,
                'created_by' => $salesUser->id,
            ]
        );

        $ticket2 = MarketingTicket::firstOrCreate(
            ['opportunity_id' => $opp2->id],
            [
                'type' => 'others',
                'status' => 'in_progress',
                'created_by' => $adminId,
            ]
        );

        $mReq2 = MarketingRequest::firstOrCreate(
            ['opportunity_id' => $opp2->id, 'support_content' => 'giveaway'],
            [
                'marketing_ticket_id' => $ticket2->id,
                'opportunity_id' => $opp2->id,
                'support_team' => 'marketing',
                'pic_type' => 'all',
                'assigned_to' => $mktUser->id,
                'support_content' => 'giveaway',
                'support_content_other' => 'Chuẩn bị quà tặng đã duyệt',
                'description' => "Hoạt động: " . $opp2->name . "\nKhách hàng: " . ($c2?->name ?? 'DTS Group') . "\nQuà tặng/Budget: " . $opp2->giveaway . "\nNgày hoạt động: " . now()->addDays(3)->format('d/m/Y'),
                'deadline' => now()->addDays(3),
                'status' => 'in_progress',
            ]
        );

        // Xuất quà tồn kho cho Opp 2
        if ($itemBook && $itemPen && $opp2->marketingItemTransactions()->count() === 0) {
            MarketingItemTransaction::create([
                'marketing_item_id' => $itemBook->id,
                'type' => 'export',
                'quantity' => 3,
                'remaining_stock' => max(0, $itemBook->stock_quantity - 3),
                'opportunity_id' => $opp2->id,
                'created_by' => $mktUser->id,
                'reference_code' => 'EXP-TKT-MKT-2026-0002',
                'note' => 'Xuất quà tặng cho Ticket ' . ($ticket2->code ?? 'TKT-002') . ' - ' . $opp2->name,
            ]);
            $itemBook->decrement('stock_quantity', 3);

            MarketingItemTransaction::create([
                'marketing_item_id' => $itemPen->id,
                'type' => 'export',
                'quantity' => 3,
                'remaining_stock' => max(0, $itemPen->stock_quantity - 3),
                'opportunity_id' => $opp2->id,
                'created_by' => $mktUser->id,
                'reference_code' => 'EXP-TKT-MKT-2026-0002',
                'note' => 'Xuất quà tặng cho Ticket ' . ($ticket2->code ?? 'TKT-002') . ' - ' . $opp2->name,
            ]);
            $itemPen->decrement('stock_quantity', 3);
        }

        // =========================================================================
        // 2. SỰ KIỆN MARKETING & QUỸ HÃNG (MARKETING EVENTS & FUNDS)
        // =========================================================================
        $mktEvent1 = MarketingEvent::updateOrCreate(
            ['title' => 'Hội nghị Khách hàng Chiến lược & Đối tác Q3-2026 (Partner Summit)'],
            [
                'description' => 'Sự kiện thường niên quy tụ hơn 80 đại diện từ các đối tác SI, Tier-1 và khách hàng doanh nghiệp tiêu chuẩn.',
                'event_date' => now()->addDays(20)->format('Y-m-d'),
                'location' => 'Khách sạn Sheraton Saigon, Quận 1, TP.HCM',
                'budget' => 250000000,
                'actual_cost' => 220000000,
                'status' => 'approved',
                'scope' => 'external',
                'is_public_to_sales' => 1,
                'vendor_id' => $sup1?->id,
                'partner_cooperation' => 'yes',
                'partner_info' => 'Hãng Fortinet tài trợ 50% chi phí tổ chức (MDF)',
                'organize_type' => 'workshop',
                'start_time' => '13:30',
                'end_time' => '18:00',
                'target_audience_count' => 80,
                'created_by' => $mktUser->id,
                'approved_by' => $bodUser->id,
                'approved_at' => now(),
            ]
        );

        // Gán khách hàng tham gia sự kiện
        if ($c1 && $c2) {
            $mktEvent1->customers()->syncWithoutDetaching([
                $c1->id => ['status' => 'confirmed', 'notes' => 'Giám đốc IT và Trưởng phòng Hạ tầng tham dự'],
                $c2->id => ['status' => 'registered', 'notes' => '2 đại diện khối giải pháp'],
            ]);
        }

        // Quỹ Hãng (Supplier Fund)
        $fund1 = MarketingSupplierFund::updateOrCreate(
            ['name' => 'Quỹ Hãng Fortinet MDF Q3-2026'],
            [
                'supplier_id' => $sup1?->id ?? 1,
                'quarter' => 'Q3',
                'year' => 2026,
                'amount' => 180000000,
                'note' => 'Chương trình tài trợ chi phí sự kiện, hội thảo và đào tạo kỹ thuật Q3-2026.',
                'created_by' => $mktUser->id,
            ]
        );

        // =========================================================================
        // 3. DỰ ÁN (PROJECTS)
        // =========================================================================
        $project1 = Project::updateOrCreate(
            ['code' => 'PRJ-2026-0001'],
            [
                'name' => 'Dự án Hiện đại hóa Trung tâm Dữ liệu Ngân hàng VietinBank',
                'customer_id' => $c1?->id,
                'customer_name' => $c1?->name ?? 'VietinBank',
                'status' => 'in_progress',
                'budget' => 1850000000,
                'start_date' => now()->subDays(15)->format('Y-m-d'),
                'end_date' => now()->addMonths(3)->format('Y-m-d'),
                'description' => 'Cung cấp và triển khai hệ thống chuyển mạch Core Switch 100G, Firewall HA Cluster và giải pháp giám sát an ninh mạng.',
                'manager_id' => $salesUser->id,
                'note' => 'Dự án trọng điểm Q3-Q4/2026',
            ]
        );

        // =========================================================================
        // 4. MUA HÀNG (PURCHASE REQUESTS & PURCHASE ORDERS)
        // =========================================================================
        $pr1 = PurchaseRequest::updateOrCreate(
            ['code' => 'PR-2026-001'],
            [
                'title' => 'Yêu cầu đặt hàng thiết bị mạng Switch Cisco & Module quang dự án VietinBank',
                'priority' => 'high',
                'status' => 'sent',
                'deadline' => now()->addDays(14),
                'requirements' => 'Hàng nhập khẩu chính hãng bảo hành 36 tháng, CO/CQ đầy đủ.',
                'note' => 'Dự án VietinBank cần giao trước ngày 20 tới.',
                'created_by' => $salesUser->id,
            ]
        );

        $po1 = PurchaseOrder::updateOrCreate(
            ['code' => 'PO-2026-001'],
            [
                'supplier_id' => $sup1?->id ?? 1,
                'order_date' => now()->subDays(5)->format('Y-m-d'),
                'expected_delivery' => now()->addDays(20)->format('Y-m-d'),
                'subtotal' => 680000000,
                'vat_percent' => 10,
                'vat_amount' => 68000000,
                'total' => 748000000,
                'paid_amount' => 300000000,
                'debt_amount' => 448000000,
                'payment_status' => 'partial',
                'status' => 'approved',
                'created_by' => $adminId,
                'note' => 'Đã xác nhận đơn hàng với nhà phân phối chính thức.',
            ]
        );

        $this->command->info('Đã tạo thành công bộ dữ liệu mẫu toàn diện cho tất cả các module!');
    }
}
