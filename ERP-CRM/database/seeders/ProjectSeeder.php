<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectSeeder extends Seeder
{
    /**
     * Seed dự án với dữ liệu demo hấp dẫn
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        $customers = DB::table('customers')->get();
        $users = DB::table('users')->first();
        
        $projects = [
            [
                'code' => 'DA-2024-001',
                'name' => '🏢 Triển khai Hệ thống IT - Vincom Center Landmark 81',
                'customer_id' => $customers->first()?->id,
                'customer_name' => 'Công ty TNHH ABC',
                'address' => '720A Điện Biên Phủ, Phường 22, Quận Bình Thạnh, TP.HCM',
                'description' => 'Dự án triển khai toàn bộ hạ tầng IT cho tòa nhà văn phòng cao cấp bao gồm: Hệ thống mạng Core, Firewall NGF, WiFi Mesh, Camera AI, Access Control.',
                'budget' => 2500000000,
                'start_date' => Carbon::create(2024, 9, 1),
                'end_date' => Carbon::create(2025, 3, 31),
                'status' => 'in_progress',
                'manager_id' => $users?->id,
                'note' => 'Dự án trọng điểm Q4/2024 - Khách hàng VIP chiến lược',
            ],
            [
                'code' => 'DA-2024-002', 
                'name' => '🏥 Nâng cấp Hệ thống CNTT - Bệnh viện Đa khoa Quốc tế',
                'customer_id' => $customers->skip(1)->first()?->id,
                'customer_name' => 'Công ty CP XYZ',
                'address' => '201 Nguyễn Chí Thanh, Quận 5, TP.HCM',
                'description' => 'Nâng cấp hệ thống bảo mật và mạng LAN/WLAN cho bệnh viện 500 giường bệnh. Yêu cầu uptime 99.99%, không làm gián đoạn hoạt động khám chữa bệnh.',
                'budget' => 1850000000,
                'start_date' => Carbon::create(2024, 10, 15),
                'end_date' => Carbon::create(2025, 2, 28),
                'status' => 'in_progress',
                'manager_id' => $users?->id,
                'note' => 'Triển khai ngoài giờ hành chính (sau 20h)',
            ],
            [
                'code' => 'DA-2024-003',
                'name' => '🏫 Smart Campus - Đại học Công nghệ Thông tin',
                'customer_id' => $customers->skip(3)->first()?->id,
                'customer_name' => 'Siêu thị Đại Việt',
                'address' => 'Khu phố 6, P.Linh Trung, TP.Thủ Đức, TP.HCM',
                'description' => 'Xây dựng hệ thống WiFi thông minh cho khuôn viên đại học, phục vụ 15,000 sinh viên và giảng viên. Tích hợp xác thực LDAP và portal sinh viên.',
                'budget' => 980000000,
                'start_date' => Carbon::create(2024, 7, 1),
                'end_date' => Carbon::create(2024, 11, 30),
                'status' => 'completed',
                'manager_id' => $users?->id,
                'note' => 'Dự án thành công xuất sắc - Khách hàng rất hài lòng',
            ],
            [
                'code' => 'DA-2024-004',
                'name' => '🏭 Industry 4.0 - Nhà máy Samsung HCMC',
                'customer_id' => $customers->first()?->id,
                'customer_name' => 'Công ty TNHH ABC',
                'address' => 'KCN Công nghệ cao, Quận 9, TP.HCM',
                'description' => 'Triển khai hệ thống mạng công nghiệp OT/IT cho dây chuyền sản xuất tự động. Yêu cầu bảo mật cấp độ cao theo tiêu chuẩn IEC 62443.',
                'budget' => 4200000000,
                'start_date' => Carbon::create(2024, 6, 1),
                'end_date' => Carbon::create(2024, 12, 31),
                'status' => 'completed',
                'manager_id' => $users?->id,
                'note' => 'Dự án lớn nhất năm - Lợi nhuận cao',
            ],
            [
                'code' => 'DA-2025-001',
                'name' => '🌆 Smart City - Khu đô thị Vinhomes Grand Park',
                'customer_id' => $customers->skip(1)->first()?->id,
                'customer_name' => 'Công ty CP XYZ',
                'address' => 'Nguyễn Xiển, TP.Thủ Đức, TP.HCM',
                'description' => 'Xây dựng hạ tầng thành phố thông minh: Giám sát AI, Chiếu sáng thông minh, Parking system, Environmental sensors.',
                'budget' => 8500000000,
                'start_date' => Carbon::create(2025, 1, 1),
                'end_date' => Carbon::create(2026, 6, 30),
                'status' => 'planning',
                'manager_id' => $users?->id,
                'note' => 'Dự án tiềm năng lớn nhất 2025 - Đang thương thảo hợp đồng',
            ],
            [
                'code' => 'DA-2024-005',
                'name' => '🏪 Chuỗi Cửa hàng Bách Hóa Xanh - Toàn quốc',
                'customer_id' => $customers->skip(2)->first()?->id,
                'customer_name' => 'Cửa hàng Minh Phát',
                'address' => 'Toàn quốc - 150 cửa hàng',
                'description' => 'Cung cấp và lắp đặt thiết bị POS, mạng, camera cho 150 cửa hàng mới. Yêu cầu triển khai đồng loạt trong 2 tháng.',
                'budget' => 1250000000,
                'start_date' => Carbon::create(2024, 8, 1),
                'end_date' => Carbon::create(2024, 10, 31),
                'status' => 'completed',
                'manager_id' => $users?->id,
                'note' => 'Roll-out thành công 100% đúng tiến độ',
            ],
            [
                'code' => 'DA-2024-006',
                'name' => '🔒 SOC Center - Ngân hàng BIDV',
                'customer_id' => $customers->skip(3)->first()?->id,
                'customer_name' => 'Siêu thị Đại Việt',
                'address' => '35 Hàng Vôi, Hoàn Kiếm, Hà Nội',
                'description' => 'Xây dựng Trung tâm Giám sát An ninh mạng (SOC) với SIEM, Threat Intelligence, Incident Response. Đạt chuẩn PCI-DSS.',
                'budget' => 5600000000,
                'start_date' => Carbon::create(2024, 11, 1),
                'end_date' => Carbon::create(2025, 5, 31),
                'status' => 'in_progress',
                'manager_id' => $users?->id,
                'note' => 'Dự án bảo mật cao cấp - Team chuyên biệt 10 người',
            ],
            [
                'code' => 'DA-2024-007',
                'name' => '🛫 Hệ thống IT - Sân bay Quốc tế Tân Sơn Nhất',
                'customer_id' => $customers->first()?->id,
                'customer_name' => 'Công ty TNHH ABC',
                'address' => 'Sân bay Tân Sơn Nhất, Quận Tân Bình, TP.HCM',
                'description' => 'Nâng cấp hệ thống WiFi công cộng và hạ tầng mạng backbone cho nhà ga quốc tế T2. Phục vụ 20 triệu hành khách/năm.',
                'budget' => 3200000000,
                'start_date' => Carbon::create(2024, 12, 1),
                'end_date' => null,
                'status' => 'on_hold',
                'manager_id' => $users?->id,
                'note' => 'Tạm dừng chờ phê duyệt ngân sách bổ sung',
            ],
            [
                'code' => 'DA-2024-008',
                'name' => '❌ Data Center - FPT Telecom',
                'customer_id' => $customers->skip(4)->first()?->id,
                'customer_name' => 'Shop Online Hạnh Phúc',
                'address' => 'KCN Quang Minh, Mê Linh, Hà Nội',
                'description' => 'Xây dựng mới Data Center Tier-3 với hệ thống mạng SDN, Storage SAN, Backup & DR site.',
                'budget' => 12000000000,
                'start_date' => Carbon::create(2024, 4, 1),
                'end_date' => Carbon::create(2024, 8, 31),
                'status' => 'cancelled',
                'manager_id' => $users?->id,
                'note' => 'Dự án hủy do thay đổi chiến lược của khách hàng',
            ],
        ];
        
        foreach ($projects as $project) {
            $project['created_at'] = $now;
            $project['updated_at'] = $now;
            DB::table('projects')->insert($project);
        }
    }
}
