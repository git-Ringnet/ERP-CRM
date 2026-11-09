<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ghi chú đơn bán từng lưu mã PO nháp ("[PO Team]: PO ... đã được đặt hàng").
 * Thay bằng mã PO hiện tại khi đơn chỉ còn đúng một PO chưa được nhắc trong ghi chú.
 */
return new class extends Migration
{
    private const PATTERN = '/\[PO Team\]: PO (.+?) đã được đặt hàng/u';

    public function up(): void
    {
        $codes = DB::table('purchase_orders')->pluck('code')->flip();

        DB::table('sales')
            ->where('note', 'like', '%[PO Team]: PO %đã được đặt hàng%')
            ->orderBy('id')
            ->get(['id', 'note'])
            ->each(function ($sale) use ($codes) {
                preg_match_all(self::PATTERN, (string) $sale->note, $matches);
                $staleCodes = collect($matches[1])->unique()
                    ->reject(fn ($code) => $codes->has($code) || str_contains($sale->note, "PO {$code} đã được trả về"))
                    ->values();

                if ($staleCodes->count() !== 1) {
                    return;
                }

                $unmentioned = $this->currentPoCodes($sale->id)
                    ->reject(fn ($code) => str_contains($sale->note, $code))
                    ->values();

                if ($unmentioned->count() !== 1) {
                    return;
                }

                DB::table('sales')->where('id', $sale->id)->update([
                    'note' => str_replace(
                        "[PO Team]: PO {$staleCodes[0]} ",
                        "[PO Team]: PO {$unmentioned[0]} ",
                        $sale->note
                    ),
                ]);
            });
    }

    private function currentPoCodes(int $saleId)
    {
        $viaRequests = DB::table('purchase_order_items as poi')
            ->join('purchase_orders as po', 'po.id', '=', 'poi.purchase_order_id')
            ->join('sale_order_request_items as sori', 'sori.id', '=', 'poi.sale_order_request_item_id')
            ->join('sale_order_requests as sor', 'sor.id', '=', 'sori.sale_order_request_id')
            ->where('sor.sale_id', $saleId)
            ->pluck('po.code');

        return $viaRequests
            ->merge(DB::table('purchase_orders')->where('sale_id', $saleId)->pluck('code'))
            ->unique();
    }

    public function down(): void
    {
        // Không khôi phục mã PO nháp đã thay thế.
    }
};
