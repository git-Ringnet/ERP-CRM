<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salesUser = User::whereHas('roles', fn($q) => $q->whereIn('slug', ['sales_staff', 'sales_manager', 'sales_lead', 'sales']))->first() 
            ?? User::first();
        $amName = $salesUser ? $salesUser->name : 'Nguyễn Văn Nam';

        $customers = [
            [
                'name' => 'Công ty TNHH Hệ thống Thông tin FPT (FPT IS)',
                'name_en' => 'FPT Information System Company Limited',
                'abv_name' => 'FPT IS',
                'email' => 'contact@fpt-is.com.vn',
                'phone' => '024-7300-7300',
                'address' => 'Tòa nhà Keangnam Landmark 72, Đường Phạm Hùng, Quận Nam Từ Liêm, Hà Nội',
                'type' => 'vip',
                'tax_code' => '0101344434',
                'website' => 'https://fpt-is.com',
                'debt_limit' => 2000000000,
                'debt_days' => 45,
                'am' => $amName,
                'note' => 'Đối tác Tích hợp Hệ thống (SI) chiến lược cấp Tier-1 toàn quốc.',
            ],
            [
                'name' => 'Tổng Công ty Công nghệ và Giải pháp CMC (CMC TS)',
                'name_en' => 'CMC Technology and Solution Corporation',
                'abv_name' => 'CMC TS',
                'email' => 'info@cmcts.com.vn',
                'phone' => '024-3795-8666',
                'address' => 'Tòa nhà CMC, Phố Duy Tân, Phường Dịch Vọng Hậu, Quận Cầu Giấy, Hà Nội',
                'type' => 'vip',
                'tax_code' => '0100244112',
                'website' => 'https://cmcts.com.vn',
                'debt_limit' => 1500000000,
                'debt_days' => 45,
                'am' => $amName,
                'note' => 'Đối tác SI lớn, chuyên mảng giải pháp Cloud, Bảo mật mạng và Data Center.',
            ],
            [
                'name' => 'Công ty Cổ phần Công nghệ Truyền thông DTS',
                'name_en' => 'DTS Communication Technologies Corporation',
                'abv_name' => 'DTS Telecom',
                'email' => 'contact@dts.com.vn',
                'phone' => '028-3848-1234',
                'address' => '287B Điện Biên Phủ, Phường Võ Thị Sáu, Quận 3, TP.HCM',
                'type' => 'vip',
                'tax_code' => '0301988899',
                'website' => 'https://dts.vn',
                'debt_limit' => 1200000000,
                'debt_days' => 30,
                'am' => $amName,
                'note' => 'Đối tác SI hàng đầu mảng Viễn thông và Ngân hàng tại khu vực phía Nam.',
            ],
            [
                'name' => 'Công ty Cổ phần Dịch vụ Công nghệ Tin học HPT',
                'name_en' => 'HPT Vietnam Corporation',
                'abv_name' => 'HPT Corp',
                'email' => 'info@hpt.vn',
                'phone' => '028-3826-6206',
                'address' => 'Lô E2a-3, Đường D1, Khu Công nghệ Cao, TP. Thủ Đức, TP.HCM',
                'type' => 'vip',
                'tax_code' => '0301444555',
                'website' => 'https://hpt.vn',
                'debt_limit' => 1000000000,
                'debt_days' => 30,
                'am' => $amName,
                'note' => 'Chuyên mảng Tích hợp Hệ thống, Dịch vụ An ninh Thông tin và Chuyển đổi số.',
            ],
            [
                'name' => 'Công ty Cổ phần Công nghệ Sao Bắc Đẩu / SVTECH',
                'name_en' => 'SVTECH Technology Joint Stock Company',
                'abv_name' => 'SVTECH',
                'email' => 'contact@svtech.com.vn',
                'phone' => '028-3948-7575',
                'address' => 'Tầng 6, Tòa nhà IC, 82 Duy Tân, Cầu Giấy, Hà Nội & Chi nhánh TP.HCM',
                'type' => 'vip',
                'tax_code' => '0101166778',
                'website' => 'https://svtech.com.vn',
                'debt_limit' => 1500000000,
                'debt_days' => 45,
                'am' => $amName,
                'note' => 'Đối tác SI chuyên mảng Telco (Viettel, VNPT, Mobifone) và Ngân hàng lớn.',
            ],
            [
                'name' => 'Ngân hàng TMCP Sài Gòn Thương Tín (Sacombank)',
                'name_en' => 'Saigon Thuong Tin Commercial Joint Stock Bank',
                'abv_name' => 'Sacombank',
                'email' => 'it-procurement@sacombank.com',
                'phone' => '028-3526-6060',
                'address' => '266-268 Nam Kỳ Khởi Nghĩa, Phường Võ Thị Sáu, Quận 3, TP.HCM',
                'type' => 'vip',
                'tax_code' => '0301103908',
                'website' => 'https://sacombank.com.vn',
                'debt_limit' => 3000000000,
                'debt_days' => 60,
                'am' => $amName,
                'note' => 'Khách hàng Ngân hàng VIP, thường xuyên mua License bảo mật và Firewall định kỳ.',
            ],
            [
                'name' => 'Ngân hàng TMCP Công Thương Việt Nam (VietinBank)',
                'name_en' => 'Vietnam Joint Stock Commercial Bank for Industry and Trade',
                'abv_name' => 'VietinBank',
                'email' => 'procurement@vietinbank.vn',
                'phone' => '024-3942-1030',
                'address' => '108 Trần Hưng Đạo, Quận Hoàn Kiếm, Hà Nội',
                'type' => 'vip',
                'tax_code' => '0100111948',
                'website' => 'https://vietinbank.vn',
                'debt_limit' => 4000000000,
                'debt_days' => 60,
                'am' => $amName,
                'note' => 'Khách hàng Ngân hàng trọng điểm Quốc gia mảng Datacenter và Core Network.',
            ],
            [
                'name' => 'Tập đoàn Vingroup - Công ty CP',
                'name_en' => 'Vingroup Joint Stock Company',
                'abv_name' => 'Vingroup',
                'email' => 'it-support@vingroup.net',
                'phone' => '024-3974-9999',
                'address' => 'Số 7, Đường Bằng Lăng 1, Khu đô thị Vinhomes Riverside, Long Biên, Hà Nội',
                'type' => 'vip',
                'tax_code' => '0101245486',
                'website' => 'https://vingroup.net',
                'debt_limit' => 5000000000,
                'debt_days' => 60,
                'am' => $amName,
                'note' => 'Tập đoàn đa ngành lớn nhất Việt Nam (VinFast, Vinhomes, VinBigData).',
            ],
        ];

        foreach ($customers as $data) {
            Customer::updateOrCreate(
                ['tax_code' => $data['tax_code']],
                $data
            );
        }

        $this->command->info('Đã tạo thành công danh sách Khách hàng Doanh nghiệp / SI chuẩn hóa!');
    }
}
