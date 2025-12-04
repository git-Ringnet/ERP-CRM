<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'code' => 'KH001',
                'name' => 'Công ty TNHH ABC',
                'email' => 'contact@abc.com.vn',
                'phone' => '0901234567',
                'address' => '123 Nguyễn Huệ, Quận 1, TP.HCM',
                'type' => 'vip',
                'tax_code' => '0123456789',
                'website' => 'https://abc.com.vn',
                'contact_person' => 'Nguyễn Văn A',
                'debt_limit' => 500000000,
                'debt_days' => 30,
                'note' => 'Khách hàng VIP, ưu tiên giao hàng',
            ],
            [
                'code' => 'KH002',
                'name' => 'Công ty CP XYZ',
                'email' => 'info@xyz.vn',
                'phone' => '0912345678',
                'address' => '456 Lê Lợi, Quận 3, TP.HCM',
                'type' => 'vip',
                'tax_code' => '0987654321',
                'website' => 'https://xyz.vn',
                'contact_person' => 'Trần Thị B',
                'debt_limit' => 300000000,
                'debt_days' => 45,
                'note' => 'Đối tác chiến lược',
            ],
            [
                'code' => 'KH003',
                'name' => 'Cửa hàng Minh Phát',
                'email' => 'minhphat@gmail.com',
                'phone' => '0923456789',
                'address' => '789 Trần Hưng Đạo, Quận 5, TP.HCM',
                'type' => 'normal',
                'tax_code' => null,
                'website' => null,
                'contact_person' => 'Lê Văn C',
                'debt_limit' => 50000000,
                'debt_days' => 15,
                'note' => null,
            ],
            [
                'code' => 'KH004',
                'name' => 'Siêu thị Đại Việt',
                'email' => 'daiviet@email.com',
                'phone' => '0934567890',
                'address' => '321 Hai Bà Trưng, Quận 1, TP.HCM',
                'type' => 'vip',
                'tax_code' => '1234567890',
                'website' => 'https://daiviet.com',
                'contact_person' => 'Phạm Thị D',
                'debt_limit' => 1000000000,
                'debt_days' => 60,
                'note' => 'Khách hàng lớn, thanh toán đúng hạn',
            ],
            [
                'code' => 'KH005',
                'name' => 'Shop Online Hạnh Phúc',
                'email' => 'hanhphuc.shop@gmail.com',
                'phone' => '0945678901',
                'address' => '654 Võ Văn Tần, Quận 3, TP.HCM',
                'type' => 'normal',
                'tax_code' => null,
                'website' => 'https://hanhphuc.shop',
                'contact_person' => 'Hoàng Văn E',
                'debt_limit' => 20000000,
                'debt_days' => 7,
                'note' => 'Bán hàng online',
            ],
        ];

        $now = Carbon::now();
        foreach ($customers as $customer) {
            $customer['created_at'] = $now;
            $customer['updated_at'] = $now;
            DB::table('customers')->insert($customer);
        }
    }
}
