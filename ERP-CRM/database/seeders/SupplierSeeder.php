<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'NCC001',
                'name' => 'Công ty TNHH Thương mại Hoàng Long',
                'email' => 'hoanglong@supplier.vn',
                'phone' => '0281234567',
                'address' => '100 Nguyễn Thị Minh Khai, Quận 1, TP.HCM',
                'tax_code' => '0301234567',
                'website' => 'https://hoanglong.vn',
                'contact_person' => 'Nguyễn Hoàng Long',
                'payment_terms' => 30,
                'product_type' => 'Điện tử, Phụ kiện',
                'note' => 'Nhà cung cấp chính thiết bị điện tử',
            ],
            [
                'code' => 'NCC002',
                'name' => 'Công ty CP Vật liệu Xây dựng Miền Nam',
                'email' => 'miennam.vlxd@gmail.com',
                'phone' => '0282345678',
                'address' => '200 Cách Mạng Tháng 8, Quận 10, TP.HCM',
                'tax_code' => '0302345678',
                'website' => null,
                'contact_person' => 'Trần Minh Tuấn',
                'payment_terms' => 45,
                'product_type' => 'Vật liệu xây dựng',
                'note' => 'Giao hàng nhanh trong nội thành',
            ],
            [
                'code' => 'NCC003',
                'name' => 'Nhà máy Sản xuất Bao bì Tân Phú',
                'email' => 'tanphu.packaging@email.com',
                'phone' => '0283456789',
                'address' => 'KCN Tân Phú, Bình Dương',
                'tax_code' => '3701234567',
                'website' => 'https://tanphupack.com',
                'contact_person' => 'Lê Thị Hương',
                'payment_terms' => 15,
                'product_type' => 'Bao bì, Đóng gói',
                'note' => null,
            ],
            [
                'code' => 'NCC004',
                'name' => 'Công ty TNHH Thực phẩm Sạch Việt',
                'email' => 'sachviet@food.vn',
                'phone' => '0284567890',
                'address' => '50 Lý Thường Kiệt, Quận Tân Bình, TP.HCM',
                'tax_code' => '0304567890',
                'website' => 'https://sachviet.vn',
                'contact_person' => 'Phạm Văn Đức',
                'payment_terms' => 7,
                'product_type' => 'Thực phẩm, Nông sản',
                'note' => 'Cung cấp thực phẩm organic',
            ],
            [
                'code' => 'NCC005',
                'name' => 'Công ty Nhập khẩu Máy móc Châu Á',
                'email' => 'asia.machinery@import.vn',
                'phone' => '0285678901',
                'address' => '88 Điện Biên Phủ, Quận Bình Thạnh, TP.HCM',
                'tax_code' => '0305678901',
                'website' => 'https://asiamachinery.vn',
                'contact_person' => 'Võ Minh Khoa',
                'payment_terms' => 60,
                'product_type' => 'Máy móc công nghiệp',
                'note' => 'Nhập khẩu từ Nhật Bản, Hàn Quốc',
            ],
        ];

        $now = Carbon::now();
        foreach ($suppliers as $supplier) {
            $supplier['created_at'] = $now;
            $supplier['updated_at'] = $now;
            DB::table('suppliers')->insert($supplier);
        }
    }
}
