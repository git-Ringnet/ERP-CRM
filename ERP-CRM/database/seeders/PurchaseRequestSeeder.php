<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseRequestSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $suppliers = Supplier::all();
        $user = User::first();

        if ($products->isEmpty() || $suppliers->isEmpty()) {
            $this->command->warn('Cần có sản phẩm và nhà cung cấp trước.');
            return;
        }

        $statuses = ['draft', 'sent', 'received', 'converted', 'cancelled'];
        $priorities = ['normal', 'high', 'urgent'];
        
        // Tạo 25 yêu cầu báo giá NCC để test phân trang
        for ($i = 1; $i <= 25; $i++) {
            $status = $statuses[array_rand($statuses)];
            
            $request = PurchaseRequest::create([
                'code' => 'PR' . date('Ymd') . str_pad($i, 4, '0', STR_PAD_LEFT),
                'title' => "Yêu cầu báo giá số $i - " . ['Thiết bị văn phòng', 'Linh kiện máy tính', 'Vật tư tiêu hao', 'Thiết bị mạng'][array_rand(['Thiết bị văn phòng', 'Linh kiện máy tính', 'Vật tư tiêu hao', 'Thiết bị mạng'])],
                'deadline' => now()->addDays(rand(7, 30)),
                'priority' => $priorities[array_rand($priorities)],
                'status' => $status,
                'requirements' => "Yêu cầu báo giá chi tiết, bao gồm:\n- Giá đơn vị\n- Thời gian giao hàng\n- Điều khoản thanh toán\n- Bảo hành",
                'note' => rand(0, 1) ? "Ghi chú cho yêu cầu $i" : null,
                'created_by' => $user?->id,
                'sent_at' => in_array($status, ['sent', 'received', 'converted']) ? now()->subDays(rand(1, 10)) : null,
            ]);

            // Thêm 2-4 nhà cung cấp cho mỗi yêu cầu (dùng DB facade)
            $supplierCount = rand(2, 4);
            $selectedSuppliers = $suppliers->random(min($supplierCount, $suppliers->count()));
            
            foreach ($selectedSuppliers as $supplier) {
                DB::table('purchase_request_suppliers')->insert([
                    'purchase_request_id' => $request->id,
                    'supplier_id' => $supplier->id,
                    'sent_at' => in_array($status, ['sent', 'received', 'converted']) ? now()->subDays(rand(1, 5)) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Thêm 2-5 sản phẩm cho mỗi yêu cầu
            $itemCount = rand(2, 5);
            $selectedProducts = $products->random(min($itemCount, $products->count()));

            foreach ($selectedProducts as $product) {
                PurchaseRequestItem::create([
                    'purchase_request_id' => $request->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => rand(5, 50),
                    'unit' => $product->unit ?? 'Cái',
                    'specifications' => rand(0, 1) ? 'Hàng chính hãng, có bảo hành' : null,
                ]);
            }
        }

        $this->command->info('Đã tạo 25 yêu cầu báo giá NCC.');
    }
}
