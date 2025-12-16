<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PriceListSeeder extends Seeder
{
    /**
     * Seed bảng giá với dữ liệu hấp dẫn cho demo
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Lấy danh sách khách hàng và sản phẩm
        $customers = DB::table('customers')->get();
        $products = DB::table('products')->get();
        
        // Bảng giá mẫu
        $priceLists = [
            [
                'code' => 'BG-2024-001',
                'name' => '🔥 Bảng giá Tết Ất Tỵ 2025',
                'description' => 'Chương trình khuyến mãi lớn nhất năm - Giảm giá sốc lên đến 30% cho tất cả sản phẩm IT',
                'type' => 'promotion',
                'customer_id' => null,
                'start_date' => Carbon::create(2024, 12, 15),
                'end_date' => Carbon::create(2025, 2, 28),
                'discount_percent' => 15,
                'is_active' => true,
                'priority' => 100,
            ],
            [
                'code' => 'BG-2024-002',
                'name' => '🌟 Bảng giá VIP Platinum',
                'description' => 'Dành riêng cho khách hàng VIP với mức ưu đãi đặc biệt - Cam kết giá tốt nhất thị trường',
                'type' => 'customer',
                'customer_id' => $customers->first()?->id,
                'start_date' => Carbon::create(2024, 1, 1),
                'end_date' => Carbon::create(2025, 12, 31),
                'discount_percent' => 20,
                'is_active' => true,
                'priority' => 90,
            ],
            [
                'code' => 'BG-2024-003',
                'name' => '💼 Bảng giá Doanh nghiệp',
                'description' => 'Giải pháp công nghệ toàn diện cho doanh nghiệp - Hỗ trợ tài chính linh hoạt',
                'type' => 'wholesale',
                'customer_id' => null,
                'start_date' => Carbon::create(2024, 1, 1),
                'end_date' => null,
                'discount_percent' => 12,
                'is_active' => true,
                'priority' => 80,
            ],
            [
                'code' => 'BG-2024-004',
                'name' => '📋 Bảng giá chuẩn Q4/2024',
                'description' => 'Bảng giá niêm yết chính thức - Đảm bảo chất lượng, giá cả cạnh tranh',
                'type' => 'standard',
                'customer_id' => null,
                'start_date' => Carbon::create(2024, 10, 1),
                'end_date' => Carbon::create(2024, 12, 31),
                'discount_percent' => 0,
                'is_active' => true,
                'priority' => 50,
            ],
            [
                'code' => 'BG-2024-005',
                'name' => '🎄 Black Friday & Cyber Monday',
                'description' => 'Siêu sale cuối năm - Cơ hội vàng sở hữu thiết bị công nghệ cao cấp',
                'type' => 'promotion',
                'customer_id' => null,
                'start_date' => Carbon::create(2024, 11, 25),
                'end_date' => Carbon::create(2024, 12, 5),
                'discount_percent' => 25,
                'is_active' => true,
                'priority' => 95,
            ],
            [
                'code' => 'BG-2024-006',
                'name' => '🏢 Đối tác Chiến lược Gold',
                'description' => 'Chương trình đặc biệt cho đối tác chiến lược - Hỗ trợ marketing, kỹ thuật 24/7',
                'type' => 'customer',
                'customer_id' => $customers->skip(1)->first()?->id,
                'start_date' => Carbon::create(2024, 1, 1),
                'end_date' => Carbon::create(2025, 12, 31),
                'discount_percent' => 18,
                'is_active' => true,
                'priority' => 85,
            ],
            [
                'code' => 'BG-2024-007',
                'name' => '🔒 Bảng giá Giải pháp An ninh mạng',
                'description' => 'Chuyên biệt FortiGate, FortiSwitch, FortiAP - Bảo mật toàn diện cho doanh nghiệp',
                'type' => 'wholesale',
                'customer_id' => null,
                'start_date' => Carbon::create(2024, 6, 1),
                'end_date' => null,
                'discount_percent' => 10,
                'is_active' => true,
                'priority' => 75,
            ],
            [
                'code' => 'BG-2024-008',
                'name' => '🖥️ Combo Văn phòng Thông minh',
                'description' => 'Gói thiết bị văn phòng trọn bộ - Laptop + Màn hình + Phụ kiện với giá ưu đãi',
                'type' => 'promotion',
                'customer_id' => null,
                'start_date' => Carbon::create(2024, 9, 1),
                'end_date' => Carbon::create(2025, 3, 31),
                'discount_percent' => 15,
                'is_active' => true,
                'priority' => 70,
            ],
        ];
        
        foreach ($priceLists as $priceList) {
            $priceList['created_at'] = $now;
            $priceList['updated_at'] = $now;
            $priceListId = DB::table('price_lists')->insertGetId($priceList);
            
            // Tạo items cho từng bảng giá
            $this->createPriceListItems($priceListId, $products, $priceList['type'], $now);
        }
    }
    
    private function createPriceListItems($priceListId, $products, $type, $now): void
    {
        // Giá gốc cho từng sản phẩm
        $basePrices = [
            'SP001' => 18500000,  // Laptop Dell
            'SP002' => 450000,    // Chuột Logitech
            'SP003' => 2500000,   // Bàn phím Keychron
            'SP004' => 125000000, // FortiGate 60F
            'SP005' => 285000000, // FortiGate 100F
            'SP006' => 5200000,   // Màn hình LG
            'SP007' => 45000000,  // FortiSwitch
            'SP008' => 18500000,  // FortiAP
        ];
        
        foreach ($products as $product) {
            $basePrice = $basePrices[$product->code] ?? 1000000;
            
            // Giá thay đổi theo loại bảng giá
            $priceMultiplier = match($type) {
                'standard' => 1.0,
                'promotion' => 0.92,
                'wholesale' => 0.95,
                'customer' => 0.90,
                default => 1.0,
            };
            
            // Item với số lượng tối thiểu = 1
            DB::table('price_list_items')->insert([
                'price_list_id' => $priceListId,
                'product_id' => $product->id,
                'price' => $basePrice * $priceMultiplier,
                'min_quantity' => 1,
                'discount_percent' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            
            // Giá sỉ số lượng lớn (>=10)
            if ($type === 'wholesale' || $type === 'standard') {
                DB::table('price_list_items')->insert([
                    'price_list_id' => $priceListId,
                    'product_id' => $product->id,
                    'price' => $basePrice * $priceMultiplier * 0.95,
                    'min_quantity' => 10,
                    'discount_percent' => 3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
