<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds with modern full-schema projects.
     */
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        $salesUsers = User::whereHas('roles', fn($q) => $q->whereIn('slug', ['sales_staff', 'sales_manager', 'sales_lead', 'sales']))->get();
        if ($salesUsers->isEmpty()) {
            $salesUsers = User::take(3)->get();
        }

        $customers = Customer::all();
        $suppliers = Supplier::all();

        if ($customers->isEmpty()) {
            $this->call(CustomerSeeder::class);
            $customers = Customer::all();
        }

        $c1 = $customers->firstWhere('abv_name', 'FPT IS') ?? $customers->firstWhere('tax_code', '0101344434') ?? $customers->get(0);
        $c2 = $customers->firstWhere('abv_name', 'CMC TS') ?? $customers->firstWhere('tax_code', '0100244112') ?? $customers->get(1) ?? $c1;
        $c3 = $customers->firstWhere('abv_name', 'DTS Telecom') ?? $customers->firstWhere('tax_code', '0301988899') ?? $customers->get(2) ?? $c1;
        $c4 = $customers->firstWhere('abv_name', 'Sacombank') ?? $customers->firstWhere('tax_code', '0301103908') ?? $customers->get(3) ?? $c1;
        $c5 = $customers->firstWhere('abv_name', 'Vingroup') ?? $customers->firstWhere('tax_code', '0101245486') ?? $customers->get(4) ?? $c1;

        $sup1 = $suppliers->firstWhere('name', 'like', '%Fortinet%') ?? $suppliers->get(0);
        $sup2 = $suppliers->firstWhere('name', 'like', '%Cisco%') ?? $suppliers->get(1) ?? $sup1;
        $sup3 = $suppliers->firstWhere('name', 'like', '%Dell%') ?? $suppliers->get(2) ?? $sup1;

        $projects = [
            [
                'code' => 'PRJ-2026-0001',
                'name' => 'Dự án Hiện đại hóa Hạ tầng An ninh Mạng Datacenter - Ngân hàng VietinBank',
                'name_en' => 'VietinBank Datacenter NextGen Security Infrastructure Modernization Project',
                'customer_id' => $c1?->id,
                'customer_name' => $c1?->name ?? 'Công ty TNHH Hệ thống Thông tin FPT (FPT IS)',
                'address' => '108 Trần Hưng Đạo, Quận Hoàn Kiếm, Hà Nội & Datacenter Hòa Lạc',
                'description' => 'Cung cấp và triển khai hệ thống Firewall Thế hệ mới FortiGate 3000F HA Cluster, Cụm Core Switch 100G và giải pháp Giám sát An toàn Thông tin trung tâm.',
                'budget' => 4500000000,
                'start_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
                'end_date' => Carbon::now()->addMonths(4)->format('Y-m-d'),
                'status' => 'in_progress',
                'manager_id' => $salesUsers->get(0)?->id ?? $adminId,
                'note' => 'Dự án trọng điểm FSI Q3-Q4/2026. Hãng đã cam kết hỗ trợ giá mức cao nhất.',
                
                // End-User Details (Đầy đủ để không bao giờ bị bắt nhập bổ sung)
                'eu_name_vi' => 'Ngân hàng TMCP Công Thương Việt Nam (VietinBank)',
                'eu_name_en' => 'Vietnam Joint Stock Commercial Bank for Industry and Trade',
                'eu_name_abbr' => 'VietinBank',
                'eu_tax_code' => '0100111948',
                'eu_province' => 'Hà Nội',
                'eu_industry' => 'Tài chính - Ngân hàng (FSI)',

                // Vendor / Distributor Information
                'vendor_id' => $sup1?->id,
                'distributor_am' => 'Trần Minh Hoàng (Fortinet Partner Account Manager)',
                'assigned_team' => 'po_team',
                'intake_status' => 'registered',
                'initial_processed_at' => Carbon::now()->subDays(25),
                'initial_processed_by' => $adminId,
                'intake_note' => 'Hồ sơ dự án đầy đủ thông tin End-User và cấu hình BOM, đã phê duyệt tiếp nhận và đăng ký bảo vệ thành công trên Portal của Hãng.',
                
                // Vendor Response SLA & Deal ID
                'vendor_submitted_at' => Carbon::now()->subDays(24),
                'vendor_due_at' => Carbon::now()->addDays(15),
                'vendor_deal_id' => 'FORTI-DEAL-FSI-2026-9911',
                'vendor_quote_note' => 'Hãng Fortinet đã phê duyệt Special Price Discount 48% cho gói thiết bị chính hãng.',
                'vendor_quote_valid_until' => Carbon::now()->addDays(60)->format('Y-m-d'),

                // Sales Progress
                'stage' => 'proposal',
                'deal_type' => 'special',
                'forecast_stage' => 'commit',
                'net_to_tech_horizon' => 3800000000,
                'estimated_close_months' => 2,
                'last_sales_updated_at' => Carbon::now()->subDays(2),
                'support_request_type' => 'none',
                'support_request_note' => 'Khách hàng đã đồng ý về giải pháp kỹ thuật, đang hoàn thiện hồ sơ dự thầu.',
            ],
            [
                'code' => 'PRJ-2026-0002',
                'name' => 'Nâng cấp Hạ tầng Mạng Campus & WiFi 6E cho Toàn bộ Bệnh viện Đa khoa Quốc tế',
                'name_en' => 'Hospital-Wide Smart Network & WiFi 6E Infrastructure Upgrade Project',
                'customer_id' => $c2?->id,
                'customer_name' => $c2?->name ?? 'Tổng Công ty Công nghệ và Giải pháp CMC (CMC TS)',
                'address' => '201 Nguyễn Chí Thanh, Phường 12, Quận 5, TP.HCM',
                'description' => 'Triển khai hệ thống mạng LAN/WLAN thông minh cho bệnh viện quy mô 800 giường bệnh, bao gồm 120 bộ Access Point WiFi 6E, 25 Switch PoE+ và Hệ thống Quản trị Cloud.',
                'budget' => 2850000000,
                'start_date' => Carbon::now()->subMonths(1)->format('Y-m-d'),
                'end_date' => Carbon::now()->addMonths(5)->format('Y-m-d'),
                'status' => 'in_progress',
                'manager_id' => $salesUsers->get(1)?->id ?? $adminId,
                'note' => 'Yêu cầu thi công cuốn chiếu ngoài giờ khám bệnh, đảm bảo hệ thống HIS hoạt động liên tục.',
                
                // End-User Details
                'eu_name_vi' => 'Bệnh viện Đa khoa Quốc tế Hạnh Phúc',
                'eu_name_en' => 'Hanh Phuc International Hospital',
                'eu_name_abbr' => 'BV Hạnh Phúc',
                'eu_tax_code' => '0309876543',
                'eu_province' => 'TP.HCM',
                'eu_industry' => 'Y tế - Bệnh viện & Chăm sóc Sức khỏe',

                // Vendor / Distributor
                'vendor_id' => $sup2?->id,
                'distributor_am' => 'Lê Thanh Tú (Cisco Commercial AM)',
                'assigned_team' => 'pm_team',
                'registration_status' => 'update_status',
                'intake_status' => 'registered',
                'initial_processed_at' => Carbon::now()->subDays(20),
                'initial_processed_by' => $adminId,
                'intake_note' => 'Đã xác thực không trùng dự án, đối tác SI CMC TS được cấp quyền đăng ký bảo vệ giá độc quyền.',
                
                // Vendor Response SLA
                'vendor_submitted_at' => Carbon::now()->subDays(19),
                'vendor_due_at' => Carbon::now()->addDays(20),
                'vendor_deal_id' => 'CISCO-HOSPITAL-2026-3344',
                'vendor_quote_note' => 'Hãng đã cấp giá OIP/Hunting Discount 52% cho dòng Catalyst 9200L & AP Catalyst 9100.',
                'vendor_quote_valid_until' => Carbon::now()->addDays(45)->format('Y-m-d'),

                // Sales Progress
                'stage' => 'negotiation',
                'deal_type' => 'special',
                'forecast_stage' => 'best_case',
                'net_to_tech_horizon' => 2400000000,
                'estimated_close_months' => 1,
                'last_sales_updated_at' => Carbon::now()->subDays(3),
                'support_request_type' => 'none',
                'support_request_note' => 'Đang thương thảo phụ lục hợp đồng về thời gian giao hàng thành 2 đợt.',
            ],
            [
                'code' => 'PRJ-2026-0003',
                'name' => 'Triển khai Cụm Máy chủ Ảo hóa & Sao lưu Dữ liệu Trung tâm - Tập đoàn Vingroup',
                'name_en' => 'Vingroup Enterprise Virtualization & Centralized Backup System Deployment',
                'customer_id' => $c5?->id,
                'customer_name' => $c5?->name ?? 'Tập đoàn Vingroup - Công ty CP',
                'address' => 'Số 7, Đường Bằng Lăng 1, KĐT Vinhomes Riverside, Long Biên, Hà Nội',
                'description' => 'Cung cấp 12 Máy chủ Dell PowerEdge R750xs, Hệ thống Lưu trữ SAN All-Flash 150TB và giải pháp VMware vSphere Enterprise Plus cùng phần mềm Veeam Backup.',
                'budget' => 6200000000,
                'start_date' => Carbon::now()->subMonths(3)->format('Y-m-d'),
                'end_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
                'status' => 'completed',
                'manager_id' => $salesUsers->get(0)?->id ?? $adminId,
                'note' => 'Dự án đã nghiệm thu thành công 100%, khách hàng đánh giá xuất sắc.',
                
                // End-User Details
                'eu_name_vi' => 'Tập đoàn Vingroup - Công ty Cổ phần',
                'eu_name_en' => 'Vingroup Joint Stock Company',
                'eu_name_abbr' => 'Vingroup',
                'eu_tax_code' => '0101245486',
                'eu_province' => 'Hà Nội',
                'eu_industry' => 'Tập đoàn Đa ngành - Bất động sản & Công nghệ',

                // Vendor / Distributor
                'vendor_id' => $sup3?->id,
                'distributor_am' => 'Ngô Quốc Anh (Dell Technologies Enterprise Lead)',
                'assigned_team' => 'pm_team',

                // Registration & Intake SLA
                'registration_status' => 'closed_won',
                'intake_status' => 'registered',
                'initial_processed_at' => Carbon::now()->subDays(90),
                'initial_processed_by' => $adminId,
                'intake_note' => 'Dự án đã hoàn tất toàn bộ quy trình, bàn giao nghiệm thu và xuất hóa đơn đầy đủ.',
                
                // Vendor Response SLA
                'vendor_submitted_at' => Carbon::now()->subDays(88),
                'vendor_due_at' => Carbon::now()->subDays(70),
                'vendor_deal_id' => 'DELL-CORP-2026-7788',
                'vendor_quote_note' => 'Hãng Dell đã duyệt giá dự án đặc biệt và xuất hàng đúng tiến độ.',
                'vendor_quote_valid_until' => Carbon::now()->addDays(90)->format('Y-m-d'),

                // Sales Progress
                'stage' => 'closed_won',
                'deal_type' => 'special',
                'forecast_stage' => 'closed',
                'net_to_tech_horizon' => 5500000000,
                'estimated_close_months' => 0,
                'last_sales_updated_at' => Carbon::now()->subDays(5),
                'support_request_type' => 'none',
                'support_request_note' => 'Dự án đã nghiệm thu thành công và thanh toán 100%.',
            ],
            [
                'code' => 'PRJ-2026-0004',
                'name' => 'Triển khai Hệ thống Giám sát An ninh Mạng SIEM/SOC & EDR - Ngân hàng Sacombank',
                'name_en' => 'Sacombank Centralized SIEM/SOC & Endpoint Detection and Response Project',
                'customer_id' => $c4?->id,
                'customer_name' => $c4?->name ?? 'Ngân hàng TMCP Sài Gòn Thương Tín (Sacombank)',
                'address' => '266-268 Nam Kỳ Khởi Nghĩa, Phường Võ Thị Sáu, Quận 3, TP.HCM',
                'description' => 'Triển khai giải pháp phân tích an ninh tập trung SIEM thu thập log cho 300 máy chủ và 5,000 endpoint EDR toàn hệ thống hội sở và chi nhánh.',
                'budget' => 5200000000,
                'start_date' => Carbon::now()->subMonths(1)->format('Y-m-d'),
                'end_date' => Carbon::now()->addMonths(6)->format('Y-m-d'),
                'status' => 'in_progress',
                'manager_id' => $salesUsers->get(0)?->id ?? $adminId,
                'note' => 'Dự án tuân thủ nghiêm ngặt quy định an toàn thông tin Thông tư 09/2020 của Ngân hàng Nhà nước.',
                
                // End-User Details
                'eu_name_vi' => 'Ngân hàng TMCP Sài Gòn Thương Tín (Sacombank)',
                'eu_name_en' => 'Saigon Thuong Tin Commercial Joint Stock Bank',
                'eu_name_abbr' => 'Sacombank',
                'eu_tax_code' => '0301103908',
                'eu_province' => 'TP.HCM',
                'eu_industry' => 'Tài chính - Ngân hàng (FSI)',

                // Vendor / Distributor
                'vendor_id' => $sup1?->id,
                'distributor_am' => 'Trần Minh Hoàng (Fortinet PAM)',
                'assigned_team' => 'po_team',

                // Registration & Intake SLA
                'registration_status' => 'update_status',
                'intake_status' => 'registered',
                'initial_processed_at' => Carbon::now()->subDays(18),
                'initial_processed_by' => $adminId,
                'intake_note' => 'Hồ sơ đã được phê duyệt bảo vệ thương vụ độc quyền cho ngân hàng Sacombank.',
                
                // Vendor Response SLA
                'vendor_submitted_at' => Carbon::now()->subDays(17),
                'vendor_due_at' => Carbon::now()->addDays(25),
                'vendor_deal_id' => 'FORTI-SOC-2026-5566',
                'vendor_quote_note' => 'Đã duyệt giá License FortiSIEM và FortiEDR 3 năm kèm dịch vụ 24/7 FortiCare.',
                'vendor_quote_valid_until' => Carbon::now()->addDays(60)->format('Y-m-d'),

                // Sales Progress
                'stage' => 'proposal',
                'deal_type' => 'special',
                'forecast_stage' => 'commit',
                'net_to_tech_horizon' => 4600000000,
                'estimated_close_months' => 2,
                'last_sales_updated_at' => Carbon::now()->subDays(1),
                'support_request_type' => 'none',
                'support_request_note' => 'Đang cùng đội Kỹ thuật hoàn tất tài liệu POC giai đoạn 2.',
            ],
            [
                'code' => 'PRJ-2026-0005',
                'name' => 'Cung cấp Thiết bị Mạng SD-WAN & Tường lửa Chi nhánh - DTS Telecom',
                'name_en' => 'DTS Telecom Branch SD-WAN & Secure Edge Network Deployment',
                'customer_id' => $c3?->id,
                'customer_name' => $c3?->name ?? 'Công ty Cổ phần Công nghệ Truyền thông DTS',
                'address' => '287B Điện Biên Phủ, Phường Võ Thị Sáu, Quận 3, TP.HCM',
                'description' => 'Cung cấp 65 thiết bị FortiGate 60F và 40F triển khai mô hình mạng WAN điều khiển bằng phần mềm (Secure SD-WAN) cho chuỗi văn phòng giao dịch phân tán.',
                'budget' => 1950000000,
                'start_date' => Carbon::now()->subMonths(1)->format('Y-m-d'),
                'end_date' => Carbon::now()->addMonths(3)->format('Y-m-d'),
                'status' => 'in_progress',
                'manager_id' => $salesUsers->get(2)?->id ?? $adminId,
                'note' => 'Khách hàng yêu cầu giao hàng phân đoạn thành 3 đợt theo tiến độ mở điểm mới.',
                
                // End-User Details
                'eu_name_vi' => 'Công ty Cổ phần Bán lẻ Kỹ thuật số FPT (FPT Shop)',
                'eu_name_en' => 'FPT Digital Retail Joint Stock Company',
                'eu_name_abbr' => 'FPT Retail',
                'eu_tax_code' => '0311609355',
                'eu_province' => 'TP.HCM',
                'eu_industry' => 'Bán lẻ & Chuỗi Cửa hàng',

                // Vendor / Distributor
                'vendor_id' => $sup1?->id,
                'distributor_am' => 'Trần Minh Hoàng (Fortinet PAM)',
                'assigned_team' => 'po_team',

                // Registration & Intake SLA
                'registration_status' => 'update_status',
                'intake_status' => 'registered',
                'initial_processed_at' => Carbon::now()->subDays(12),
                'initial_processed_by' => $adminId,
                'intake_note' => 'Đã duyệt bảo vệ giá số lượng lớn cho chuỗi cửa hàng bán lẻ.',
                
                // Vendor Response SLA
                'vendor_submitted_at' => Carbon::now()->subDays(11),
                'vendor_due_at' => Carbon::now()->addDays(18),
                'vendor_deal_id' => 'FORTI-SDWAN-2026-1122',
                'vendor_quote_note' => 'Đã có giá Volume Discount đặc biệt cho 65 box FortiGate 60F/40F.',
                'vendor_quote_valid_until' => Carbon::now()->addDays(50)->format('Y-m-d'),

                // Sales Progress
                'stage' => 'negotiation',
                'deal_type' => 'standard',
                'forecast_stage' => 'commit',
                'net_to_tech_horizon' => 1700000000,
                'estimated_close_months' => 1,
                'last_sales_updated_at' => Carbon::now()->subDays(2),
                'support_request_type' => 'none',
                'support_request_note' => 'Đã chốt giá và thời gian giao hàng đợt 1 vào đầu tháng tới.',
            ],
        ];

        // Cập nhật tất cả các dự án cũ trong database nếu thiếu thông tin End-User hoặc registration_status
        $allExisting = Project::all();
        foreach ($allExisting as $existingProj) {
            $customer = $existingProj->customer ?? $c1;
            $vendor = $existingProj->vendor ?? $sup1;
            
            $updateFields = [];
            if (empty($existingProj->eu_name_vi)) {
                $updateFields['eu_name_vi'] = $customer?->name ?? 'Doanh nghiệp Tiêu chuẩn Việt Nam';
                $updateFields['eu_name_en'] = $customer?->name_en ?? 'Standard Vietnam Enterprise';
                $updateFields['eu_name_abbr'] = $customer?->abv_name ?? 'Enterprise';
                $updateFields['eu_tax_code'] = $customer?->tax_code ?? '0301999888';
                $updateFields['eu_province'] = 'TP.HCM';
                $updateFields['eu_industry'] = 'Công nghệ Thông tin & Viễn thông';
            }

            if (empty($existingProj->vendor_id) && $vendor) {
                $updateFields['vendor_id'] = $vendor->id;
                $updateFields['distributor_am'] = 'Trần Minh Hoàng (Partner Account Manager)';
                $updateFields['assigned_team'] = 'po_team';
            }

            if (in_array($existingProj->registration_status, [null, '', 'submitted', 'incomplete', 'pending'])) {
                $updateFields['registration_status'] = 'update_status';
                $updateFields['intake_status'] = 'registered';
                $updateFields['initial_processed_at'] = Carbon::now()->subDays(10);
                $updateFields['initial_processed_by'] = $adminId;
                $updateFields['intake_note'] = 'Hồ sơ dự án đã được bổ sung đầy đủ thông tin End-User và cấu hình kỹ thuật.';
            }

            if (empty($existingProj->vendor_deal_id)) {
                $updateFields['vendor_deal_id'] = 'DEAL-' . date('Y') . '-' . str_pad($existingProj->id, 4, '0', STR_PAD_LEFT);
                $updateFields['vendor_submitted_at'] = Carbon::now()->subDays(10);
                $updateFields['vendor_due_at'] = Carbon::now()->addDays(20);
                $updateFields['vendor_quote_note'] = 'Hãng đã xác nhận duyệt bảo vệ thương vụ cho dự án này.';
                $updateFields['vendor_quote_valid_until'] = Carbon::now()->addDays(60)->format('Y-m-d');
            }

            if (empty($existingProj->last_sales_updated_at)) {
                $updateFields['last_sales_updated_at'] = Carbon::now()->subDays(2);
            }

            if (!empty($updateFields)) {
                $existingProj->update($updateFields);
            }
        }

        // Tạo/Cập nhật các dự án mẫu mới
        foreach ($projects as $projData) {
            Project::updateOrCreate(
                ['code' => $projData['code']],
                $projData
            );
        }

        $this->command->info('Đã tạo & chuẩn hóa toàn bộ dữ liệu Dự án (Projects) theo đầy đủ các trường hiện tại!');
    }
}
