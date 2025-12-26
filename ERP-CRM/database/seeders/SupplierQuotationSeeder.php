<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SupplierQuotation;
use App\Models\SupplierQuotationItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\User;

class SupplierQuotationSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Supplier::all();
        $products = Product::all();
        $requests = PurchaseRequest::all();
        $user = User::first();

        if ($suppliers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Cần có nhà cung cấp và sản phẩm trước.');
            return;
        }

        $statuses = ['pending', 'selected', 'rejected'];
        
        // Tạo 30 báo giá NCC để test phân trang
        for ($i = 1; $i <= 30; $i++) {
            $supplier = $suppliers->random();
            
            $quotation = SupplierQuotation::create([
                'code' => 'SQ' . date('Ymd') . str_pad($i, 4, '0', STR_PAD_LEFT),
                'purchase_request_id' => $requests->isNotEmpty() && rand(0, 1) ? $requests->random()->id : null,
                'supplier_id' => $supplier->id,
                'quotation_date' => now()->subDays(rand(0, 30)),
                'valid_until' => now()->addDays(rand(15, 60)),
                'subtotal' => 0,
                'discount_percent' => rand(0, 10),
                'discount_amount' => 0,
                'shipping_cost' => rand(0, 1) ? rand(100000, 500000) : 0,
                'vat_percent' => 10,
                'vat_amount' => 0,
                'total' => 0,
                'delivery_days' => rand(3, 14),
                'payment_terms' => ['Thanh toán ngay', 'Trả chậm 30 ngày', 'Trả chậm 60 ngày'][array_rand(['Thanh toán ngay', 'Trả chậm 30 ngày', 'Trả chậm 60 ngày'])],
                'warranty' => rand(6, 24) . ' tháng',
                'status' => $statuses[array_rand($statuses)],
                'note' => rand(0, 1) ? "Báo giá từ $supplier->name" : null,
                'created_by' => $user?->id,
            ]);

            // Thêm 2-6 sản phẩm
            $itemCount = rand(2, 6);
            $selectedProducts = $products->random(min($itemCount, $products->count()));
            $subtotal = 0;

            foreach ($selectedProducts as $product) {
                $quantity = rand(10, 100);
                $unitPrice = $product->price * (1 - rand(5, 25) / 100);
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                SupplierQuotationItem::create([
                    'supplier_quotation_id' => $quotation->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit' => $product->unit ?? 'Cái',
                    'unit_price' => $unitPrice,
                    'total' => $total,
                    'note' => rand(0, 1) ? 'Hàng chính hãng' : null,
                ]);
            }

            // Tính toán tổng
            $discountAmount = $subtotal * $quotation->discount_percent / 100;
            $afterDiscount = $subtotal - $discountAmount + $quotation->shipping_cost;
            $vatAmount = $afterDiscount * $quotation->vat_percent / 100;
            $total = $afterDiscount + $vatAmount;

            $quotation->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total' => $total,
            ]);
        }

        $this->command->info('Đã tạo 30 báo giá nhà cung cấp.');
    }
}
