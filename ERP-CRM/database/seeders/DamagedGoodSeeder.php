<?php

namespace Database\Seeders;

use App\Models\DamagedGood;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DamagedGoodSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $users = User::all();

        if ($products->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No products or users found. Skipping DamagedGoodSeeder.');
            return;
        }

        $types = ['damaged', 'liquidation'];
        $statuses = ['pending', 'approved', 'rejected', 'processed'];
        
        $reasons = [
            'damaged' => [
                'Hư hỏng do vận chuyển',
                'Hư hỏng trong quá trình sản xuất',
                'Hư hỏng do bảo quản không đúng cách',
                'Hư hỏng do thiên tai',
                'Hư hỏng do lỗi kỹ thuật',
            ],
            'liquidation' => [
                'Hàng tồn kho lâu',
                'Hàng lỗi thời',
                'Hàng không bán được',
                'Hàng sắp hết hạn',
                'Thay đổi chiến lược kinh doanh',
            ],
        ];

        $solutions = [
            'Tiêu hủy theo quy định',
            'Bán thanh lý với giá thấp',
            'Sửa chữa và tái sử dụng',
            'Tái chế nguyên liệu',
            'Quyên góp từ thiện',
            'Trả lại nhà cung cấp',
        ];

        // Create 15-20 damaged goods records
        for ($i = 0; $i < rand(15, 20); $i++) {
            $type = $types[array_rand($types)];
            $product = $products->random();
            $quantity = rand(1, 50);
            $originalValue = $product->price * $quantity;
            $recoveryRate = rand(0, 70) / 100; // 0-70% recovery
            $recoveryValue = $originalValue * $recoveryRate;
            $status = $statuses[array_rand($statuses)];
            $discoveryDate = now()->subDays(rand(1, 90));

            $damagedGood = new DamagedGood([
                'type' => $type,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'original_value' => $originalValue,
                'recovery_value' => $recoveryValue,
                'reason' => $reasons[$type][array_rand($reasons[$type])],
                'status' => $status,
                'discovery_date' => $discoveryDate,
                'discovered_by' => $users->random()->id,
                'solution' => in_array($status, ['approved', 'processed']) ? $solutions[array_rand($solutions)] : null,
                'note' => rand(0, 1) ? 'Ghi chú mẫu cho báo cáo hàng hư hỏng/thanh lý' : null,
            ]);

            $damagedGood->code = $damagedGood->generateCode();
            $damagedGood->save();
        }

        $this->command->info('DamagedGoodSeeder completed successfully.');
    }
}
