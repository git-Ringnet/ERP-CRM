<?php

use App\Models\Export;
use App\Models\ProductItem;
use App\Models\ProductItemBorrowLog;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Phiếu xuất kho runrate trước bản "đổi suất giữ khi xuất" (TransactionService::rebalanceRunrateHoldsForScannedItems):
     * kho quét S/N do Sales khác giữ / chưa ai giữ thì S/N đó bị xuất nhưng suất giữ của Sales của đơn vẫn còn nguyên,
     * còn Sales đang giữ S/N bị quét thì mất suất (VD EXP00008 / SO-20261023-0016).
     * Áp lại đúng quy tắc của bản mới cho các thiết bị đã xuất:
     *  - S/N do Sales khác giữ: chuyển 1 suất cùng model của Sales của đơn cho người đó (không còn thì bù thiết bị chưa ai giữ).
     *  - S/N chưa ai giữ: trả 1 suất cùng model của Sales của đơn về kho.
     * Chỉ dùng suất giữ có từ trước khi xuất. Cuối cùng xoá người giữ trên các thiết bị đã xuất.
     */
    public function up(): void
    {
        $runrateWarehouseId = Warehouse::where('code', 'WH_RUNRATE')->value('id');
        if (!$runrateWarehouseId) {
            return;
        }

        $userIdByName = fn (?string $name) => $name ? User::where('name', $name)->value('id') : null;

        DB::transaction(function () use ($runrateWarehouseId, $userIdByName) {
            $exports = Export::where('reference_type', 'sale')
                ->where('status', 'completed')
                ->whereHas('items')
                ->orderBy('id')
                ->get();

            foreach ($exports as $export) {
                // Đã điều chỉnh, hoặc phiếu xuất bằng bản mới (đã tự đổi suất giữ lúc xuất): bỏ qua
                $alreadyRebalanced = ProductItemBorrowLog::where('note', 'like', "Điều chỉnh xuất kho {$export->code}:%")
                    ->orWhere('note', 'like', "Xuất kho {$export->code}:%")
                    ->exists();
                if ($alreadyRebalanced) {
                    continue;
                }

                $sale = Sale::with('user')->find($export->reference_id);
                $salespersonName = $sale?->user?->name;

                // Chỉ S/N kho đã quét (lưu trên dòng phiếu xuất). Thiết bị hệ thống tự lấy thêm đã được
                // trừ từ suất giữ của Sales của đơn (và xoá người giữ) nên không cần điều chỉnh.
                $scannedIds = $export->items
                    ->flatMap(fn ($exportItem) => (array) (json_decode((string) $exportItem->serial_number, true) ?: []))
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->all();
                if (empty($scannedIds)) {
                    continue;
                }

                $soldItems = ProductItem::with('product')
                    ->whereIn('id', $scannedIds)
                    ->where('export_id', $export->id)
                    ->where('warehouse_id', $runrateWarehouseId)
                    ->where('status', ProductItem::STATUS_SOLD)
                    ->orderBy('id')
                    ->get();

                foreach ($soldItems as $sold) {
                    $holder = trim((string) $sold->borrower);
                    if (!$salespersonName || $holder === $salespersonName) {
                        continue;
                    }

                    $consumeSalesHold = (bool) SaleItem::where('sale_id', $sale->id)
                        ->where('product_id', $sold->product_id)
                        ->value('is_from_stock');

                    $sameModel = fn () => ProductItem::where('product_id', $sold->product_id)
                        ->where('warehouse_id', $runrateWarehouseId)
                        ->where('status', ProductItem::STATUS_IN_STOCK)
                        ->notCqLockedForOthers()
                        ->orderBy('id');

                    // Suất giữ phải có từ trước khi xuất (không lấy suất Sales giữ mới sau đó)
                    $salesHeld = $consumeSalesHold
                        ? $sameModel()->where('borrower', $salespersonName)
                            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                                ->from('product_item_borrow_logs as l')
                                ->whereColumn('l.product_item_id', 'product_items.id')
                                ->where('l.created_at', '>', $export->updated_at))
                            ->first()
                        : null;

                    if ($salesHeld) {
                        $salesHeld->update(['borrower' => $holder ?: null]);
                        ProductItemBorrowLog::create([
                            'product_item_id' => $salesHeld->id,
                            'from_user_id' => $userIdByName($salespersonName),
                            'to_user_id' => $userIdByName($holder ?: null),
                            'ticket_id' => null,
                            'note' => mb_substr("Điều chỉnh xuất kho {$export->code}: kho đã xuất S/N {$sold->sku}, chuyển suất giữ của {$salespersonName}" . ($holder ? " cho {$holder}" : ' về kho'), 0, 255),
                        ]);
                        continue;
                    }

                    if ($holder === '') {
                        continue;
                    }

                    $free = $sameModel()->where(fn ($q) => $q->whereNull('borrower')->orWhere('borrower', ''))->first();
                    if (!$free) {
                        Log::warning("Rebalance holds {$export->code}: không còn thiết bị {$sold->product?->code} chưa ai giữ để bù cho {$holder} (S/N {$sold->sku})");
                        continue;
                    }

                    $free->update(['borrower' => $holder]);
                    ProductItemBorrowLog::create([
                        'product_item_id' => $free->id,
                        'from_user_id' => null,
                        'to_user_id' => $userIdByName($holder),
                        'ticket_id' => null,
                        'note' => mb_substr("Điều chỉnh xuất kho {$export->code}: kho đã xuất S/N {$sold->sku} đang được {$holder} giữ, bù thiết bị cùng model", 0, 255),
                    ]);
                }
            }

            // Thiết bị đã xuất không còn ai giữ
            ProductItem::where('warehouse_id', $runrateWarehouseId)
                ->where('status', ProductItem::STATUS_SOLD)
                ->whereNotNull('borrower')
                ->where('borrower', '<>', '')
                ->update(['borrower' => null]);
        });
    }

    public function down(): void
    {
        // Không hoàn tác (điều chỉnh dữ liệu, đã ghi log chuyển giữ)
    }
};
