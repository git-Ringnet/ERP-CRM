<?php

use App\Models\SaleOrderRequest;
use App\Models\SaleOrderRequestItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * 1. Dòng yêu cầu đặt hàng bị nhập số lẻ do ô số lượng cũ step=0.01 (VD 4 -> 4.02, 1 -> 0.99):
     *    đưa về số lượng trên đơn bán nếu lệch < 0.1, ngược lại làm tròn.
     * 2. Tính lại trạng thái SOR: SOR bị kẹt "Hoàn thành" sau khi sửa giảm số lượng PO
     *    sẽ quay lại "Đang xử lý" để đặt tiếp phần còn thiếu.
     */
    public function up(): void
    {
        SaleOrderRequestItem::whereRaw('quantity <> FLOOR(quantity)')
            ->with('saleItem')
            ->get()
            ->each(function (SaleOrderRequestItem $item) {
                $saleQty = $item->saleItem?->quantity;
                $newQty = ($saleQty !== null && abs((float) $saleQty - (float) $item->quantity) < 0.1)
                    ? (float) $saleQty
                    : max(1, round((float) $item->quantity));
                $item->quantity = $newQty;
                $item->saveQuietly();
            });

        SaleOrderRequest::whereIn('status', [SaleOrderRequest::STATUS_PROCESSING, SaleOrderRequest::STATUS_COMPLETED])
            ->get()
            ->each->checkAndUpdateStatus();
    }

    public function down(): void
    {
        // Sửa dữ liệu, không hoàn tác.
    }
};
