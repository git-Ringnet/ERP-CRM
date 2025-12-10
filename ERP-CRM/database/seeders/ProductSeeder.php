<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Requirements: 1.4, 3.1 - Simplified product schema
     */
    public function run(): void
    {
        $products = [
            [
                'code' => 'SP001',
                'name' => 'Laptop Dell Inspiron 15',
                'category' => 'A',
                'unit' => 'Cái',
                'description' => 'Laptop Dell Inspiron 15 3000 Series, Core i5, RAM 8GB, SSD 256GB',
                'note' => 'Bảo hành 12 tháng',
            ],
            [
                'code' => 'SP002',
                'name' => 'Chuột không dây Logitech M185',
                'category' => 'B',
                'unit' => 'Cái',
                'description' => 'Chuột không dây Logitech M185, kết nối USB',
                'note' => null,
            ],
            [
                'code' => 'SP003',
                'name' => 'Bàn phím cơ Keychron K2',
                'category' => 'B',
                'unit' => 'Cái',
                'description' => 'Bàn phím cơ Keychron K2, switch Gateron Brown',
                'note' => 'Bảo hành 24 tháng',
            ],
            [
                'code' => 'SP004',
                'name' => 'FortiGate 60F',
                'category' => 'C',
                'unit' => 'Cái',
                'description' => 'FortiGate 60F Next Generation Firewall',
                'note' => 'Hardware + License',
            ],
            [
                'code' => 'SP005',
                'name' => 'FortiGate 100F',
                'category' => 'C',
                'unit' => 'Cái',
                'description' => 'FortiGate 100F Next Generation Firewall',
                'note' => 'Hardware + License',
            ],
            [
                'code' => 'SP006',
                'name' => 'Màn hình LG 24 inch',
                'category' => 'A',
                'unit' => 'Cái',
                'description' => 'Màn hình LG 24 inch Full HD IPS',
                'note' => 'Bảo hành 36 tháng',
            ],
            [
                'code' => 'SP007',
                'name' => 'FortiSwitch 124E',
                'category' => 'D',
                'unit' => 'Cái',
                'description' => 'FortiSwitch 124E 24-port Gigabit Switch',
                'note' => null,
            ],
            [
                'code' => 'SP008',
                'name' => 'FortiAP 231F',
                'category' => 'E',
                'unit' => 'Cái',
                'description' => 'FortiAP 231F Indoor Wireless Access Point',
                'note' => null,
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
