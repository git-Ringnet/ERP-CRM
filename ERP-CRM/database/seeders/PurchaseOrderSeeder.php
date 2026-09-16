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

        $statuses = ['approved', 'shipping', 'received', 'confirmed', 'sent', 'pending_approval', 'draft'];
        $paymentTerms = ['immediate', 'cod', 'net15', 'net30', 'net45', 'net60'];
        $lastNum = (int) PurchaseOrder::count();
        $sales = \App\Models\Sale::all();
        
        // Tạo 10 đơn mua hàng đa dạng
        for ($i = 1; $i <= 10; $i++) {
            $code = 'PO-' . date('Ym') . '-' . str_pad($lastNum + $i, 4, '0', STR_PAD_LEFT);
            if (PurchaseOrder::where('code', $code)->exists()) {
                continue;
            }

            $supplier = $suppliers->random();
            $status = $statuses[array_rand($statuses)];
            $isApproved = in_array($status, ['approved', 'shipping', 'partial_received', 'received', 'confirmed', 'sent']);
            $isSent = in_array($status, ['shipping', 'partial_received', 'received', 'confirmed', 'sent']);
            $isConfirmed = in_array($status, ['shipping', 'partial_received', 'received', 'confirmed']);
            $sale = $sales->isNotEmpty() && rand(0, 1) ? $sales->random() : null;
            $orderDate = now()->subDays(rand(5, 45));
            
            $order = PurchaseOrder::create([
                'code' => $code,
                'supplier_id' => $supplier->id,
                'supplier_quotation_id' => $quotations->isNotEmpty() && rand(0, 1) ? $quotations->random()->id : null,
                'sale_id' => $sale?->id,
                'order_date' => $orderDate,
                'expected_delivery' => (clone $orderDate)->addDays(rand(10, 25)),
                'actual_delivery' => ($status === 'received') ? (clone $orderDate)->addDays(rand(7, 20)) : null,
                'delivery_address' => 'Kho phân phối & Datacenter, Đường Song Hành, TP. Thủ Đức, TP.HCM',
                'subtotal' => 0,
                'discount_percent' => rand(0, 5),
                'discount_amount' => 0,
                'shipping_cost' => rand(0, 1) ? rand(200000, 1000000) : 0,
                'other_cost' => 0,
                'vat_percent' => 10,
                'vat_amount' => 0,
                'total' => 0,
                'paid_amount' => 0,
                'debt_amount' => 0,
                'payment_status' => 'unpaid',
                'payment_terms' => $paymentTerms[array_rand($paymentTerms)],
                'status' => $status,
                'note' => "Đơn đặt hàng thiết bị & linh kiện chính hãng từ nhà phân phối {$supplier->name}",
                'created_by' => $user?->id,
                'approved_by' => $isApproved ? $user?->id : null,
                'approved_at' => $isApproved ? (clone $orderDate)->addHours(rand(2, 24)) : null,
                'sent_at' => $isSent ? (clone $orderDate)->addDays(rand(1, 3)) : null,
                'confirmed_at' => $isConfirmed ? (clone $orderDate)->addDays(rand(2, 4)) : null,
            ]);

            // Thêm 2-4 sản phẩm
            $itemCount = min(rand(2, 4), $products->count());
            $selectedProducts = $products->random($itemCount);
            $subtotal = 0;

            foreach ($selectedProducts as $product) {
                $quantity = rand(2, 20);
                $basePrice = $product->price ?? rand(2000000, 25000000);
                $unitPrice = $basePrice * (1 - rand(5, 15) / 100);
                $total = $quantity * $unitPrice;
                $subtotal += $total;

                $receivedQty = 0;
                if ($status === 'received') {
                    $receivedQty = $quantity;
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
                    'note' => 'Bảo hành chính hãng 24-36 tháng theo tiêu chuẩn nhà sản xuất',
                ]);
            }

            // Tính toán tổng tiền
            $discountAmount = $subtotal * $order->discount_percent / 100;
            $afterDiscount = $subtotal - $discountAmount + $order->shipping_cost + $order->other_cost;
            $vatAmount = $afterDiscount * $order->vat_percent / 100;
            $totalAmount = $afterDiscount + $vatAmount;

            // Tính toán thanh toán
            $paidAmount = 0;
            $paymentStatus = 'unpaid';
            if ($status === 'received') {
                $paidAmount = $totalAmount;
                $paymentStatus = 'paid';
            } elseif (in_array($status, ['approved', 'shipping', 'confirmed'])) {
                $paidAmount = rand(0, 1) ? round($totalAmount * 0.4, -4) : 0;
                $paymentStatus = ($paidAmount > 0) ? 'partial' : 'unpaid';
            }
            $debtAmount = max(0, $totalAmount - $paidAmount);

            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total' => $totalAmount,
                'paid_amount' => $paidAmount,
                'debt_amount' => $debtAmount,
                'payment_status' => $paymentStatus,
            ]);

            // Tạo lịch sử thanh toán cho nhà cung cấp nếu có paid_amount > 0
            if ($paidAmount > 0) {
                \App\Models\SupplierPaymentHistory::create([
                    'purchase_order_id' => $order->id,
                    'supplier_id' => $supplier->id,
                    'amount' => $paidAmount,
                    'currency' => 'VND',
                    'exchange_rate' => 1,
                    'payment_method' => 'bank_transfer',
                    'reference_number' => 'UNC-' . date('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    'payment_date' => (clone $orderDate)->addDays(rand(3, 10)),
                    'note' => "Ủy nhiệm chi thanh toán cho Đơn mua hàng {$order->code}",
                    'created_by' => $user?->id ?? 1,
                ]);
            }
        }

        $this->command->info('Đã tạo thành công dữ liệu mẫu Đơn mua hàng (Purchase Orders) và Lịch sử thanh toán NCC!');
    }
}

