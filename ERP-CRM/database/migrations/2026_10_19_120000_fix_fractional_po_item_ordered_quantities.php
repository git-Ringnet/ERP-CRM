<?php

use App\Models\PurchaseOrderItem;
use App\Models\SaleOrderRequest;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Dòng PO được tạo khi dòng yêu cầu đặt hàng còn số lẻ (VD 0.99 – đã sửa ở
     * 2026_10_15_160000_fix_fractional_sor_quantities_and_statuses) vẫn giữ số lượng đặt lẻ,
     * khiến Gom đơn hiện phần "còn thiếu" 0.01 với giá trị sai (VD 701$ → 7.01$).
     * Đưa số lượng đặt về số lượng nguyên của dòng PO (cột quantity) nếu lệch < 0.1,
     * ngược lại làm tròn; sau đó tính lại trạng thái các yêu cầu liên quan.
     */
    public function up(): void
    {
        $sorIds = [];

        PurchaseOrderItem::whereRaw('ordered_quantity <> FLOOR(ordered_quantity)')
            ->with('saleOrderRequestItem')
            ->get()
            ->each(function (PurchaseOrderItem $item) use (&$sorIds) {
                $ordered = (float) $item->ordered_quantity;
                $item->ordered_quantity = abs((float) $item->quantity - $ordered) < 0.1
                    ? (float) $item->quantity
                    : max(1, round($ordered));
                $item->saveQuietly();

                if ($item->saleOrderRequestItem?->sale_order_request_id) {
                    $sorIds[] = $item->saleOrderRequestItem->sale_order_request_id;
                }
            });

        SaleOrderRequest::whereIn('id', array_unique($sorIds))->get()->each->checkAndUpdateStatus();
    }

    public function down(): void
    {
        // Sửa dữ liệu, không hoàn tác.
    }
};
