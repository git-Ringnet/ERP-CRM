<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'code' => 'SP001',
                'name' => 'Laptop Dell Inspiron 15',
                'category' => 'Điện tử',
                'unit' => 'Cái',
                'price' => 15000000,
                'cost' => 12000000,
                'stock' => 50,
                'min_stock' => 10,
                'max_stock' => 100,
                'management_type' => 'serial',
                'auto_generate_serial' => true,
                'serial_prefix' => 'DELL',
                'expiry_months' => null,
                'track_expiry' => false,
                'description' => 'Laptop Dell Inspiron 15 3000 Series, Core i5, RAM 8GB, SSD 256GB',
                'note' => 'Bảo hành 12 tháng',
            ],
            [
                'code' => 'SP002',
                'name' => 'Chuột không dây Logitech M185',
                'category' => 'Phụ kiện',
                'unit' => 'Cái',
                'price' => 150000,
                'cost' => 100000,
                'stock' => 200,
                'min_stock' => 50,
                'max_stock' => 500,
                'management_type' => 'normal',
                'auto_generate_serial' => false,
                'serial_prefix' => null,
                'expiry_months' => null,
                'track_expiry' => false,
                'description' => 'Chuột không dây Logitech M185, kết nối USB',
                'note' => null,
            ],
            [
                'code' => 'SP003',
                'name' => 'Bàn phím cơ Keychron K2',
                'category' => 'Phụ kiện',
                'unit' => 'Cái',
                'price' => 2500000,
                'cost' => 2000000,
                'stock' => 30,
                'min_stock' => 5,
                'max_stock' => 50,
                'management_type' => 'serial',
                'auto_generate_serial' => true,
                'serial_prefix' => 'KEY',
                'expiry_months' => null,
                'track_expiry' => false,
                'description' => 'Bàn phím cơ Keychron K2, switch Gateron Brown',
                'note' => 'Bảo hành 24 tháng',
            ],
            [
                'code' => 'SP004',
                'name' => 'Thuốc kháng sinh Amoxicillin 500mg',
                'category' => 'Dược phẩm',
                'unit' => 'Hộp',
                'price' => 50000,
                'cost' => 35000,
                'stock' => 500,
                'min_stock' => 100,
                'max_stock' => 1000,
                'management_type' => 'lot',
                'auto_generate_serial' => false,
                'serial_prefix' => null,
                'expiry_months' => 36,
                'track_expiry' => true,
                'description' => 'Thuốc kháng sinh Amoxicillin 500mg, hộp 10 viên',
                'note' => 'Cần theo dõi hạn sử dụng',
            ],
            [
                'code' => 'SP005',
                'name' => 'Gạo ST25 cao cấp',
                'category' => 'Thực phẩm',
                'unit' => 'Kg',
                'price' => 35000,
                'cost' => 28000,
                'stock' => 1000,
                'min_stock' => 200,
                'max_stock' => 2000,
                'management_type' => 'lot',
                'auto_generate_serial' => false,
                'serial_prefix' => null,
                'expiry_months' => 12,
                'track_expiry' => true,
                'description' => 'Gạo ST25 cao cấp, xuất xứ Sóc Trăng',
                'note' => 'Gạo ngon nhất thế giới 2019',
            ],
            [
                'code' => 'SP006',
                'name' => 'Màn hình LG 24 inch',
                'category' => 'Điện tử',
                'unit' => 'Cái',
                'price' => 3500000,
                'cost' => 3000000,
                'stock' => 40,
                'min_stock' => 10,
                'max_stock' => 80,
                'management_type' => 'serial',
                'auto_generate_serial' => true,
                'serial_prefix' => 'LG',
                'expiry_months' => null,
                'track_expiry' => false,
                'description' => 'Màn hình LG 24 inch Full HD IPS',
                'note' => 'Bảo hành 36 tháng',
            ],
            [
                'code' => 'SP007',
                'name' => 'Bút bi Thiên Long TL-079',
                'category' => 'Văn phòng phẩm',
                'unit' => 'Cây',
                'price' => 3000,
                'cost' => 2000,
                'stock' => 5000,
                'min_stock' => 1000,
                'max_stock' => 10000,
                'management_type' => 'normal',
                'auto_generate_serial' => false,
                'serial_prefix' => null,
                'expiry_months' => null,
                'track_expiry' => false,
                'description' => 'Bút bi Thiên Long TL-079, mực xanh',
                'note' => null,
            ],
            [
                'code' => 'SP008',
                'name' => 'Sữa tươi Vinamilk 1L',
                'category' => 'Thực phẩm',
                'unit' => 'Hộp',
                'price' => 32000,
                'cost' => 28000,
                'stock' => 300,
                'min_stock' => 50,
                'max_stock' => 500,
                'management_type' => 'lot',
                'auto_generate_serial' => false,
                'serial_prefix' => null,
                'expiry_months' => 6,
                'track_expiry' => true,
                'description' => 'Sữa tươi tiệt trùng Vinamilk 100% 1L',
                'note' => 'Bảo quản lạnh sau khi mở nắp',
            ],
        ];

        $now = Carbon::now();
        foreach ($products as $product) {
            $product['created_at'] = $now;
            $product['updated_at'] = $now;
            DB::table('products')->insert($product);
        }
    }
}
