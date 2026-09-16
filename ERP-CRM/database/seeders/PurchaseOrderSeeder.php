<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierQuotation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Str;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Supplier::all();
        $products = Product::all();
        $quotations = SupplierQuotation::all();
        $user = User::first();

        if ($suppliers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Cần có nhà cung cấp và sản phẩm trước.');
            return;
        }

        $statuses = ['draft', 'pending_approval', 'approved', 'shipping', 'partial_received', 'received', 'cancelled'];
        $paymentTerms = ['immediate', 'cod', 'net15', 'net30', 'net45', 'net60'];
        $lastNum = (int) PurchaseOrder::count();
        
        // Tạo 10 đơn mua hàng
        for ($i = 1; $i <= 10; $i++) {
            $code = 'PO' . date('Ymd') . str_pad($lastNum + $i, 4, '0', STR_PAD_LEFT);
            if (PurchaseOrder::where('code', $code)->exists()) {
                continue;
            }

            $supplier = $suppliers->random();
            $status = $statuses[array_rand($statuses)];
            $isApproved = in_array($status, ['approved', 'shipping', 'partial_received', 'received']);
            $isSent = in_array($status, ['shipping', 'partial_received', 'received']);
            $isConfirmed = in_array($status, ['shipping', 'partial_received', 'received']);
            
            $order = PurchaseOrder::create([
                'code' => $code,
                'supplier_id' => $supplier->id,
                'supplier_quotation_id' => $quotations->isNotEmpty() && rand(0, 1) ? $quotations->random()->id : null,
                'order_date' => now()->subDays(rand(0, 60)),
                'expected_delivery' => now()->addDays(rand(7, 30)),
                'actual_delivery' => $status === 'received' ? now()->subDays(rand(1, 5)) : null,
                'delivery_address' => 'Số ' . rand(1, 100) . ', Đường ' . rand(1, 50) . ', Quận 1, TP.HCM',
                'subtotal' => 0,
                'discount_percent' => rand(0, 10),
                'discount_amount' => 0,
                'shipping_cost' => rand(0, 1) ? rand(100000, 500000) : 0,
                'other_cost' => rand(0, 1) ? rand(50000, 200000) : 0,
                'vat_percent' => 10,
                'vat_amount' => 0,
                'total' => 0,
                'payment_terms' => $paymentTerms[array_rand($paymentTerms)],
                'status' => $status,
                'note' => rand(0, 1) ? "Đơn mua hàng từ $supplier->name" : null,
                'created_by' => $user?->id,
                'approved_by' => $isApproved ? $user?->id : null,
                'approved_at' => $isApproved ? now()->subDays(rand(5, 15)) : null,
                'sent_at' => $isSent ? now()->subDays(rand(3, 10)) : null,
                'confirmed_at' => $isConfirmed ? now()->subDays(rand(1, 5)) : null,
            ]);

            // Thêm 2-4 sản phẩm
            $itemCount = min(rand(2, 4), $products->count());
            $selectedProducts = $products->random($itemCount);
            $subtotal = 0;

            foreach ($selectedProducts as $product) {
                $quantity = rand(5, 50);
                $basePrice = $product->price ?? rand(1000000, 15000000);
                $unitPrice = $basePrice * (1 - rand(5, 20) / 100);
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                $receivedQty = 0;
                if ($status === 'received') {
                    $receivedQty = $quantity;
                } elseif ($status === 'partial_received') {
                    $receivedQty = rand(1, max(1, $quantity - 1));
                }

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => Str::limit($product->name, 190),
                    'quantity' => $quantity,
                    'received_quantity' => $receivedQty,
                    'unit' => $product->unit ?? 'Cái',
                    'unit_price' => $unitPrice,
                    'total' => $total,
                    'note' => null,
                ]);
            }

            // Tính toán tổng
            $discountAmount = $subtotal * $order->discount_percent / 100;
            $afterDiscount = $subtotal - $discountAmount + $order->shipping_cost + $order->other_cost;
            $vatAmount = $afterDiscount * $order->vat_percent / 100;
            $totalAmount = $afterDiscount + $vatAmount;

            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total' => $totalAmount,
            ]);
        }

        $this->command->info('Đã tạo thành công dữ liệu mẫu Đơn mua hàng (Purchase Orders)!');
    }
}
