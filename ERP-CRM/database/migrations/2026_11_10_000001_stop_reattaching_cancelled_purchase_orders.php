<?php

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SaleOrderRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bỏ luồng "PO hủy chờ Sales chỉnh sửa rồi tự khôi phục": PO đã hủy là hủy hẳn,
 * yêu cầu đặt hàng duyệt lại sẽ vào Gom đơn để tạo PO mới.
 *
 * - Giữ revision_sale_order_request_ids để khi yêu cầu được duyệt lại, PO Team
 *   vẫn được báo đây là đơn làm lại từ PO cũ nào.
 * - PO đang treo mà dòng đã mất liên kết (Sales sửa yêu cầu -> dòng yêu cầu bị tạo
 *   lại -> PO hiện SO "N/A"): nối lại với dòng yêu cầu hiện tại theo S/N / mã sản
 *   phẩm, không khớp được thì ghi SO thẳng vào PO.
 */
return new class extends Migration
{
    public function up(): void
    {
        $normalize = fn ($v) => strtoupper(trim((string) $v));
        $serials = function ($raw) {
            preg_match_all('/[A-Z0-9]{8,}/', strtoupper((string) $raw), $m);
            return array_values(array_unique($m[0]));
        };

        DB::transaction(function () use ($normalize, $serials) {
            $pos = PurchaseOrder::where('awaiting_revision', true)->with('items')->get();

            foreach ($pos as $po) {
                $sors = SaleOrderRequest::withTrashed()
                    ->whereIn('id', array_map('intval', $po->revision_sale_order_request_ids ?? []))
                    ->get();
                $requestItems = $sors->filter(fn ($s) => !$s->trashed())
                    ->flatMap(fn ($s) => $s->items()->where('is_cancelled', false)->get());

                foreach ($po->items->whereNull('sale_order_request_item_id') as $poItem) {
                    $poSerials = $serials($poItem->serial_number . ' ' . $poItem->product_name);
                    $match = $requestItems->first(fn ($ri) => !empty(array_intersect($poSerials, $serials($ri->serial_number))))
                        ?? $requestItems->first(fn ($ri) => $normalize($ri->part_number) !== ''
                            && $normalize($ri->part_number) === $normalize($poItem->product_name)
                            && (!$ri->vendor_id || !$po->supplier_id || (int) $ri->vendor_id === (int) $po->supplier_id));

                    if ($match) {
                        PurchaseOrderItem::whereKey($poItem->id)->update(['sale_order_request_item_id' => $match->id]);
                    }
                }

                $saleId = $sors->pluck('sale_id')->filter()->first();
                $stillUnlinked = PurchaseOrderItem::where('purchase_order_id', $po->id)->whereNull('sale_order_request_item_id')->exists();
                if (!$po->sale_id && $saleId && $stillUnlinked && DB::table('sales')->where('id', $saleId)->exists()) {
                    PurchaseOrder::whereKey($po->id)->update(['sale_id' => $saleId]);
                }
            }

            DB::table('purchase_orders')
                ->where('awaiting_revision', true)
                ->update(['awaiting_revision' => false]);
        });
    }

    public function down(): void
    {
        DB::table('purchase_orders')
            ->where('status', 'cancelled')
            ->whereNotNull('revision_sale_order_request_ids')
            ->update(['awaiting_revision' => true]);
    }
};
