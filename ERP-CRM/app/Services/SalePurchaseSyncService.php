<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalePurchaseSyncService
{
    /**
     * Tự động tạo PO nháp từ đơn bán hàng đã duyệt
     * 
     * @param Sale $sale
     * @return PurchaseOrder|null
     */
    public function createPurchaseOrderFromSale(Sale $sale): ?PurchaseOrder
    {
        try {
            // Kiểm tra xem đã có PO cho đơn bán này chưa để tránh tạo trùng
            if (PurchaseOrder::where('sale_id', $sale->id)->exists()) {
                Log::info("PO already exists for Sale #{$sale->id}");
                return PurchaseOrder::where('sale_id', $sale->id)->first();
            }

            // Tìm nhà cung cấp từ sản phẩm đầu tiên hoặc dùng mặc định
            $supplierId = $this->findSupplierForSale($sale);

            if (!$supplierId) {
                Log::warning("Không thể tự động tạo PO cho Sale {$sale->code}: Không có nhà cung cấp nào trong hệ thống.");
                return null;
            }

            DB::beginTransaction();

            $po = PurchaseOrder::create([
                'code' => PurchaseOrder::generateCode(),
                'supplier_id' => $supplierId,
                'sale_id' => $sale->id,
                'order_date' => now(),
                'status' => 'draft',
                'currency_id' => $sale->currency_id ?? 1,
                'exchange_rate' => $sale->exchange_rate ?? 1,
                'created_by' => auth()->id() ?? $sale->user_id,
                'note' => "Tự động tạo từ đơn bán hàng: " . $sale->code,
                'subtotal' => 0,
                'total' => 0,
            ]);

            $subtotal = 0;
            foreach ($sale->items as $saleItem) {
                $sellingPrice = $saleItem->price ?: 0;
                $itemTotal = $saleItem->quantity * $sellingPrice;
                
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $saleItem->product_id,
                    'product_name' => $saleItem->product_name,
                    'quantity' => $saleItem->quantity,
                    'unit_price' => $sellingPrice,
                    'total' => $itemTotal,
                ]);
                $subtotal += $itemTotal;
            }

            $po->update([
                'subtotal' => $subtotal,
                'total' => $subtotal, 
            ]);

            DB::commit();

            Log::info("Auto-created PO #{$po->code} from Sale #{$sale->code}");

            // Thông báo cho Salesperson và BOD
            $this->sendNotifications($sale, $po);

            return $po;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Lỗi tự động tạo PO từ Sale #{$sale->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Tìm nhà cung cấp phù hợp cho đơn hàng
     */
    protected function findSupplierForSale(Sale $sale): ?int
    {
        // Thử tìm từ sản phẩm đầu tiên có trong báo giá NCC
        $firstItem = $sale->items->first();
        if ($firstItem && $firstItem->product_id) {
            $product = Product::with('supplierPriceListItems.priceList')->find($firstItem->product_id);
            if ($product && $product->supplierPriceListItems->isNotEmpty()) {
                return $product->supplierPriceListItems->first()->priceList->supplier_id;
            }
        }

        // Nếu không có, lấy NCC đầu tiên
        return Supplier::first()?->id;
    }

    /**
     * Gửi thông báo
     */
    protected function sendNotifications(Sale $sale, PurchaseOrder $po): void
    {
        $usersToNotify = array_filter(array_unique([$sale->user_id, auth()->id()]));
        
        foreach ($usersToNotify as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type'    => 'purchase_order',
                'title'   => 'Đã tự động tạo PO nháp',
                'message' => "Đơn hàng {$sale->code} đã được duyệt. Một bản nháp PO {$po->code} đã được tạo tự động cho bộ phận mua hàng.",
                'link'    => route('purchase-orders.show', $po->id),
                'icon'    => 'fas fa-file-invoice-dollar',
                'color'   => 'blue',
                'data'    => ['po_id' => $po->id, 'po_code' => $po->code, 'sale_id' => $sale->id],
            ]);
        }
    }
}
