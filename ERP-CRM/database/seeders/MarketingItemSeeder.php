<?php

namespace Database\Seeders;

use App\Models\MarketingItem;
use App\Models\MarketingItemTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class MarketingItemSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        $items = [
            [
                'code' => 'MKT-GIFT-001',
                'name' => 'Sổ tay bìa da cao cấp A5 (Khắc logo)',
                'category' => 'gift',
                'unit' => 'Cuốn',
                'stock_quantity' => 150,
                'min_stock_alert' => 20,
                'unit_cost' => 85000,
                'description' => 'Sổ tay bìa da PU sang trọng, ép kim logo doanh nghiệp, 200 trang giấy Kraft vàng chống lóa.',
            ],
            [
                'code' => 'MKT-GIFT-002',
                'name' => 'Bút ký kim loại cao cấp mạ vàng',
                'category' => 'gift',
                'unit' => 'Cây',
                'stock_quantity' => 200,
                'min_stock_alert' => 30,
                'unit_cost' => 120000,
                'description' => 'Bút bi kim loại ngòi 0.5mm, hộp đựng nhung sang trọng dùng tặng đối tác VIP và SI.',
            ],
            [
                'code' => 'MKT-GIFT-003',
                'name' => 'Bình giữ nhiệt Inox 304 (500ml)',
                'category' => 'gift',
                'unit' => 'Bình',
                'stock_quantity' => 80,
                'min_stock_alert' => 15,
                'unit_cost' => 175000,
                'description' => 'Bình giữ nhiệt hiển thị nhiệt độ cảm ứng LCD, giữ nhiệt nóng lạnh 12h, in logo công ty.',
            ],
            [
                'code' => 'MKT-GIFT-004',
                'name' => 'Bộ Giftset Công nghệ (Sạc dự phòng + Chuột không dây)',
                'category' => 'gift',
                'unit' => 'Bộ',
                'stock_quantity' => 45,
                'min_stock_alert' => 10,
                'unit_cost' => 450000,
                'description' => 'Bộ quà tặng VIP dành cho khách hàng dự án lớn, đối tác ký kết hợp đồng.',
            ],
            [
                'code' => 'MKT-CLOTH-001',
                'name' => 'Áo thun Polo thương hiệu (Size L/XL)',
                'category' => 'clothing',
                'unit' => 'Cái',
                'stock_quantity' => 120,
                'min_stock_alert' => 25,
                'unit_cost' => 130000,
                'description' => 'Áo polo vải cá sấu cotton 4 chiều, thêu logo ngực áo và tay áo.',
            ],
            [
                'code' => 'MKT-PUB-001',
                'name' => 'Catalogue & Brochure Giải pháp Doanh nghiệp',
                'category' => 'publication',
                'unit' => 'Cuốn',
                'stock_quantity' => 500,
                'min_stock_alert' => 50,
                'unit_cost' => 15000,
                'description' => 'Tài liệu in màu Couche 300gsm, giới thiệu năng lực và hệ sinh thái giải pháp.',
            ],
            [
                'code' => 'MKT-EQP-001',
                'name' => 'Standee chân cuốn nhôm sự kiện (0.8m x 2m)',
                'category' => 'equipment',
                'unit' => 'Bộ',
                'stock_quantity' => 12,
                'min_stock_alert' => 3,
                'unit_cost' => 280000,
                'description' => 'Standee nhôm cuốn phục vụ các buổi hội thảo, demo offline tại khách hàng.',
            ],
        ];

        foreach ($items as $data) {
            $item = MarketingItem::updateOrCreate(
                ['code' => $data['code']],
                $data
            );

            // Create initial import transaction if no transactions exist
            if ($item->transactions()->count() === 0) {
                MarketingItemTransaction::create([
                    'marketing_item_id' => $item->id,
                    'type' => 'import',
                    'quantity' => $item->stock_quantity,
                    'remaining_stock' => $item->stock_quantity,
                    'created_by' => $adminId,
                    'reference_code' => 'INIT-IMPORT-' . $item->code,
                    'note' => 'Nhập kho ban đầu',
                ]);
            }
        }
    }
}
