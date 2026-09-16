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
        $userId = $users ? $users->id : 1;

        if ($customers->isEmpty()) {
            $custId = DB::table('customers')->insertGetId([
                'code' => 'KH-DEFAULT',
                'name' => 'Công ty TNHH Giải Pháp Công Nghệ',
                'type' => 'si',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $customers = DB::table('customers')->where('id', $custId)->get();
        }

        $cCount = $customers->count();
        $cust = fn($idx) => $customers[$idx % $cCount];
        
        $projects = [
            [
                'code' => 'DA-2026-001',
                'name' => '🏢 Triển khai Hệ thống IT - Vincom Center Landmark 81',
                'customer_id' => $cust(0)->id,
                'customer_name' => $cust(0)->name ?? 'Công ty TNHH ABC',
                'address' => '720A Điện Biên Phủ, Phường 22, Quận Bình Thạnh, TP.HCM',
                'description' => 'Dự án triển khai toàn bộ hạ tầng IT cho tòa nhà văn phòng cao cấp bao gồm: Hệ thống mạng Core, Firewall NGF, WiFi Mesh, Camera AI, Access Control.',
                'budget' => 2500000000,
                'start_date' => Carbon::now()->subMonths(3),
                'end_date' => Carbon::now()->addMonths(3),
                'status' => 'in_progress',
                'manager_id' => $userId,
                'note' => 'Dự án trọng điểm - Khách hàng VIP chiến lược',
            ],
            [
                'code' => 'DA-2026-002', 
                'name' => '🏥 Nâng cấp Hệ thống CNTT - Bệnh viện Đa khoa Quốc tế',
                'customer_id' => $cust(1)->id,
                'customer_name' => $cust(1)->name ?? 'Công ty CP XYZ',
                'address' => '201 Nguyễn Chí Thanh, Quận 5, TP.HCM',
                'description' => 'Nâng cấp hệ thống bảo mật và mạng LAN/WLAN cho bệnh viện 500 giường bệnh. Yêu cầu uptime 99.99%, không làm gián đoạn hoạt động khám chữa bệnh.',
                'budget' => 1850000000,
                'start_date' => Carbon::now()->subMonths(2),
                'end_date' => Carbon::now()->addMonths(4),
                'status' => 'in_progress',
                'manager_id' => $userId,
                'note' => 'Triển khai ngoài giờ hành chính (sau 20h)',
            ],
            [
                'code' => 'DA-2026-003',
                'name' => '🏫 Smart Campus - Đại học Công nghệ Thông tin',
                'customer_id' => $cust(2)->id,
                'customer_name' => $cust(2)->name ?? 'Siêu thị Đại Việt',
                'address' => 'Khu phố 6, P.Linh Trung, TP.Thủ Đức, TP.HCM',
                'description' => 'Xây dựng hệ thống WiFi thông minh cho khuôn viên đại học, phục vụ 15,000 sinh viên và giảng viên. Tích hợp xác thực LDAP và portal sinh viên.',
                'budget' => 980000000,
                'start_date' => Carbon::now()->subMonths(5),
                'end_date' => Carbon::now()->subDays(10),
                'status' => 'completed',
                'manager_id' => $userId,
                'note' => 'Dự án thành công xuất sắc - Khách hàng rất hài lòng',
            ],
            [
                'code' => 'DA-2026-004',
                'name' => '🏭 Industry 4.0 - Nhà máy Samsung HCMC',
                'customer_id' => $cust(3)->id,
                'customer_name' => $cust(3)->name ?? 'Công ty TNHH ABC',
                'address' => 'KCN Công nghệ cao, Quận 9, TP.HCM',
                'description' => 'Triển khai hệ thống mạng công nghiệp OT/IT cho dây chuyền sản xuất tự động. Yêu cầu bảo mật cấp độ cao theo tiêu chuẩn IEC 62443.',
                'budget' => 4200000000,
                'start_date' => Carbon::now()->subMonths(6),
                'end_date' => Carbon::now()->subMonths(1),
                'status' => 'completed',
                'manager_id' => $userId,
                'note' => 'Dự án lớn nhất năm - Lợi nhuận cao',
            ],
            [
                'code' => 'DA-2026-005',
                'name' => '🌆 Smart City - Khu đô thị Vinhomes Grand Park',
                'customer_id' => $cust(4)->id,
                'customer_name' => $cust(4)->name ?? 'Công ty CP XYZ',
                'address' => 'Nguyễn Xiển, TP.Thủ Đức, TP.HCM',
                'description' => 'Xây dựng hạ tầng thành phố thông minh: Giám sát AI, Chiếu sáng thông minh, Parking system, Environmental sensors.',
                'budget' => 8500000000,
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addMonths(12),
                'status' => 'planning',
                'manager_id' => $userId,
                'note' => 'Dự án tiềm năng lớn nhất - Đang thương thảo hợp đồng',
            ],
        ];

        foreach ($projects as $project) {
            $project['created_at'] = $now;
            $project['updated_at'] = $now;
            
            $existing = DB::table('projects')->where('code', $project['code'])->first();
            if ($existing) {
                DB::table('projects')->where('id', $existing->id)->update($project);
            } else {
                DB::table('projects')->insert($project);
            }
        }

        $this->command->info('Đã tạo thành công dữ liệu mẫu Dự án (Projects)!');
    }
}
