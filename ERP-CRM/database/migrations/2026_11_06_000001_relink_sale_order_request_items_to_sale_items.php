<?php

use App\Models\Sale;
use App\Models\SaleOrderRequestItem;
use Illuminate\Database\Migrations\Migration;

/**
 * Nối lại sale_item_id của dòng Yêu cầu đặt hàng bị mất liên kết do đơn hàng bán được sửa
 * (dòng hàng bị xoá/tạo lại) → % Margin, phân loại SP ở Gom đơn hiển thị sai.
 */
return new class extends Migration
{
    public function up(): void
    {
        $saleIds = SaleOrderRequestItem::query()
            ->where(fn ($q) => $q->whereNull('sale_item_id')->orWhereDoesntHave('saleItem'))
            ->with('saleOrderRequest:id,sale_id')
            ->get()
            ->pluck('saleOrderRequest.sale_id')
            ->filter()
            ->unique();

        Sale::whereIn('id', $saleIds)->get()->each(fn (Sale $sale) => SaleOrderRequestItem::relinkForSale($sale));
    }

    public function down(): void
    {
        // Không hoàn tác: chỉ bổ sung liên kết bị thiếu.
    }
};
