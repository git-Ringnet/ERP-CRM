<?php

namespace App\Services;

use App\Models\Import;
use App\Models\ImportItem;
use App\Models\ProductItem;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sửa phiếu nhập kho đã duyệt (chỉ Quản lý kho / Quản trị viên): điều chỉnh tồn kho theo phần chênh lệch.
 *
 * Mỗi dòng phiếu nhập (import_items) gắn với các thiết bị (product_items) cùng import_id + mã hàng (+ ghi chú dòng
 * khi phiếu có nhiều dòng cùng mã). Quy tắc:
 *  - Thêm dòng / tăng số lượng / thêm serial: tạo thêm thiết bị.
 *  - Xoá dòng / giảm số lượng / bỏ serial: chỉ gỡ được thiết bị còn trong kho, chưa ai giữ; ưu tiên thiết bị không serial.
 *  - Đổi serial: đổi số serial trên thiết bị còn trong kho.
 *  - Không đổi mã hàng / kho của dòng đã có thiết bị xuất đi (xoá dòng và thêm dòng mới nếu cần).
 */
class ApprovedImportEditService
{
    public const EDITOR_ROLES = ['super_admin', 'admin', 'warehouse_manager'];

    public function __construct(private InventoryService $inventoryService)
    {
    }

    public static function canEdit(?User $user): bool
    {
        return $user && $user->hasAnyRole(self::EDITOR_ROLES);
    }

    /**
     * @param  array  $data  Dữ liệu đã validate từ ImportRequest (items có thể kèm id dòng cũ)
     * @return array<int, string> Mô tả các thay đổi tồn kho
     */
    public function update(Import $import, array $data): array
    {
        if ($import->status !== 'completed') {
            throw new Exception('Chỉ áp dụng cho phiếu nhập đã duyệt.');
        }
        if ($import->items()->whereNull('processed_at')->exists()) {
            throw new Exception('Phiếu còn dòng chưa nhập kho. Vui lòng duyệt phiếu trước khi sửa.');
        }

        $changes = [];
        $affected = [];

        DB::transaction(function () use ($import, $data, &$changes, &$affected) {
            $existing = $import->items()->lockForUpdate()->get()->keyBy('id');
            $postedIds = [];

            foreach ($data['items'] as $row) {
                $serials = $this->parseSerials($row);
                $id = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;

                if ($id && $existing->has($id)) {
                    $postedIds[] = $id;
                    $changes = array_merge($changes, $this->updateLine($import, $existing[$id], $existing, $row, $serials, $affected));
                } else {
                    $changes = array_merge($changes, $this->addLine($import, $row, $serials, $affected));
                }
            }

            foreach ($existing->except($postedIds) as $removed) {
                $changes = array_merge($changes, $this->removeLine($import, $removed, $existing, $affected));
            }

            $import->update([
                'total_qty' => $import->items()->sum('quantity'),
                'note' => $data['note'] ?? $import->note,
                'date' => $data['date'] ?? $import->date,
                'supplier_id' => $data['supplier_id'] ?? $import->supplier_id,
                'employee_id' => $data['employee_id'] ?? $import->employee_id,
            ]);

            foreach ($affected as $productId => $warehouses) {
                foreach (array_keys($warehouses) as $warehouseId) {
                    $this->inventoryService->resyncStockFromItems($productId, $warehouseId);
                }
            }
        });

        return $changes;
    }

    /**
     * Số lượng và serial thực tế của từng dòng (theo thiết bị đã nhập), dùng để hiển thị form sửa.
     * Serial lưu trên dòng phiếu có thể thiếu (serial gắn sau qua PO) nên không dùng trực tiếp.
     *
     * @return array<int, array{quantity: int, serials: array<int, string>}> theo id dòng
     */
    public function lineSnapshot(Import $import): array
    {
        $lines = $import->items()->with('product')->get()->keyBy('id');
        $snapshot = [];
        foreach ($lines as $line) {
            try {
                $items = $this->lineItems($import, $line, $lines, false);
            } catch (Exception $e) {
                continue; // Dòng không phân biệt được: giữ số liệu trên phiếu
            }
            $snapshot[$line->id] = [
                'quantity' => $items->count(),
                'serials' => $items->reject(fn ($i) => $this->isNoSerial($i))->pluck('sku')->values()->all(),
            ];
        }
        return $snapshot;
    }

    private function isNoSerial(ProductItem $item): bool
    {
        return str_starts_with($item->sku, ProductItem::NO_SKU_PREFIX)
            || str_starts_with($item->sku, ProductItem::OLD_NO_SKU_PREFIX)
            || $item->sku === '';
    }

    private function parseSerials(array $row): array
    {
        $serials = $row['serials'] ?? [];
        if (empty($serials) && !empty($row['serial_list'])) {
            $serials = preg_split('/[\n,]+/', $row['serial_list']);
        }
        return array_values(array_unique(array_filter(array_map('trim', (array) $serials), fn ($s) => $s !== '')));
    }

    /**
     * Thiết bị thuộc dòng phiếu nhập.
     */
    private function lineItems(Import $import, ImportItem $line, Collection $allLines, bool $lock = true): Collection
    {
        $sameProduct = $allLines->where('product_id', $line->product_id);
        $query = ProductItem::where('import_id', $import->id)->where('product_id', $line->product_id);

        if ($sameProduct->count() > 1) {
            $comments = $sameProduct->pluck('comments')->map(fn ($c) => (string) $c);
            if ($comments->unique()->count() < $comments->count()) {
                throw new Exception("Phiếu có nhiều dòng cùng mã hàng {$line->product?->code} không phân biệt được (ghi chú trùng). Không thể sửa số lượng / serial của các dòng này.");
            }
            $query->where('comments', $line->comments);
        }

        return $query->when($lock, fn ($q) => $q->lockForUpdate())->orderBy('id')->get();
    }

    private function isFree(ProductItem $item): bool
    {
        return $item->status === ProductItem::STATUS_IN_STOCK && trim((string) $item->borrower) === '';
    }

    private function noSerialSku(int $productId, int $importId): string
    {
        return ProductItem::NO_SKU_PREFIX . '_' . $productId . '_' . $importId . '_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
    }

    /**
     * S/N không bắt buộc (hàng không có S/N để trống) nhưng đã nhập thì phải đủ số lượng.
     */
    private function assertFullSerials(string $productCode, int $serialCount, int $qty): void
    {
        if ($serialCount > 0 && $serialCount !== $qty) {
            throw new Exception("{$productCode}: đã nhập {$serialCount} S/N nhưng số lượng là {$qty}. Nhập đủ {$qty} S/N, hoặc để trống nếu hàng không có S/N.");
        }
    }

    private function assertSerialsNotUsed(int $productId, array $serials, string $productCode): void
    {
        if (empty($serials)) {
            return;
        }
        $dups = ProductItem::where('product_id', $productId)->whereIn('sku', $serials)->pluck('sku')->all();
        if (!empty($dups)) {
            throw new Exception("Serial đã tồn tại trong hệ thống cho {$productCode}: " . implode(', ', $dups));
        }
    }

    private function createItems(Import $import, int $productId, int $warehouseId, array $skus, int $noSerialQty, array $row): void
    {
        $base = [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'import_id' => $import->id,
            'quantity' => 1,
            'comments' => $row['comments'] ?? null,
            'warranty_months' => $row['warranty_months'] ?? null,
            'expiry_date' => $row['expiry_date'] ?? null,
            'status' => ProductItem::STATUS_IN_STOCK,
        ];
        foreach ($skus as $sku) {
            ProductItem::create($base + ['sku' => $sku]);
        }
        for ($i = 0; $i < $noSerialQty; $i++) {
            ProductItem::create($base + ['sku' => $this->noSerialSku($productId, $import->id)]);
        }
    }

    private function addLine(Import $import, array $row, array $serials, array &$affected): array
    {
        $qty = (int) $row['quantity'];
        $productId = (int) $row['product_id'];
        $warehouseId = (int) $row['warehouse_id'];
        $productCode = \App\Models\Product::whereKey($productId)->value('code') ?? "ID {$productId}";

        $this->assertFullSerials($productCode, count($serials), $qty);
        $this->assertSerialsNotUsed($productId, $serials, $productCode);

        $import->items()->create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => $qty,
            'serial_number' => !empty($serials) ? json_encode($serials) : null,
            'cost' => $row['cost'] ?? 0,
            'warehouse_price' => $row['cost'] ?? 0,
            'comments' => $row['comments'] ?? null,
            'warranty_months' => $row['warranty_months'] ?? null,
            'expiry_date' => $row['expiry_date'] ?? null,
            'processed_at' => now(),
        ]);
        $this->createItems($import, $productId, $warehouseId, $serials, $qty - count($serials), $row);
        $affected[$productId][$warehouseId] = true;

        return ["Thêm dòng {$productCode}: +{$qty}" . (count($serials) ? ' (' . count($serials) . ' serial)' : '')];
    }

    private function removeLine(Import $import, ImportItem $line, Collection $allLines, array &$affected): array
    {
        $productCode = $line->product?->code ?? "ID {$line->product_id}";
        $items = $this->lineItems($import, $line, $allLines);
        $blocked = $items->reject(fn ($i) => $this->isFree($i));
        if ($blocked->isNotEmpty()) {
            throw new Exception("Không xoá được dòng {$productCode}: {$blocked->count()} thiết bị đã xuất / đang được giữ (" . $blocked->pluck('sku')->take(5)->implode(', ') . ').');
        }

        ProductItem::whereIn('id', $items->pluck('id'))->delete();
        $line->delete();
        foreach ($items->pluck('warehouse_id')->unique() as $wId) {
            $affected[$line->product_id][$wId] = true;
        }

        return ["Xoá dòng {$productCode}: -{$items->count()}"];
    }

    private function updateLine(Import $import, ImportItem $line, Collection $allLines, array $row, array $newSerials, array &$affected): array
    {
        $productCode = $line->product?->code ?? "ID {$line->product_id}";
        $newQty = (int) $row['quantity'];
        $newWarehouseId = (int) $row['warehouse_id'];

        if ((int) $row['product_id'] !== (int) $line->product_id) {
            throw new Exception("Dòng {$productCode} đã nhập kho, không đổi mã hàng được. Hãy xoá dòng và thêm dòng mới.");
        }
        if (count($newSerials) > $newQty) {
            throw new Exception("{$productCode}: số serial (" . count($newSerials) . ") nhiều hơn số lượng ({$newQty}).");
        }

        $items = $this->lineItems($import, $line, $allLines);
        $changes = [];

        // Đổi kho: chỉ khi mọi thiết bị của dòng còn trong kho, chưa ai giữ
        if ($newWarehouseId !== (int) $line->warehouse_id) {
            if ($items->contains(fn ($i) => !$this->isFree($i))) {
                throw new Exception("Dòng {$productCode} có thiết bị đã xuất / đang được giữ, không đổi kho được.");
            }
            foreach ($items->pluck('warehouse_id')->unique() as $wId) {
                $affected[$line->product_id][$wId] = true;
            }
            ProductItem::whereIn('id', $items->pluck('id'))->update(['warehouse_id' => $newWarehouseId]);
            $items->each(fn ($i) => $i->warehouse_id = $newWarehouseId);
            $changes[] = "Chuyển dòng {$productCode} sang kho mới";
        }

        $isNoSerial = fn (ProductItem $i) => $this->isNoSerial($i);
        $currentSerials = $items->reject($isNoSerial)->pluck('sku')->all();
        $removedSerials = array_values(array_diff($currentSerials, $newSerials));
        $addedSerials = array_values(array_diff($newSerials, $currentSerials));
        // Dòng cũ đang thiếu S/N vẫn lưu được nếu không đổi danh sách S/N; đổi thì phải đủ số lượng
        if (!empty($addedSerials) || !empty($removedSerials)) {
            $this->assertFullSerials($productCode, count($newSerials), $newQty);
        }
        $this->assertSerialsNotUsed($line->product_id, $addedSerials, $productCode);

        $removedItems = $items->whereIn('sku', $removedSerials);
        $blocked = $removedItems->reject(fn ($i) => $this->isFree($i));
        if ($blocked->isNotEmpty()) {
            throw new Exception("{$productCode}: không bỏ được serial đã xuất / đang được giữ: " . $blocked->pluck('sku')->implode(', '));
        }

        // Thiết bị có thể dùng lại / gỡ bỏ: serial bị bỏ trước, rồi thiết bị không serial còn trong kho, chưa ai giữ
        $slots = $removedItems->values()->concat($items->filter($isNoSerial)->filter(fn ($i) => $this->isFree($i))->values());
        $delta = $newQty - $items->count();

        // Gán serial mới vào các thiết bị dùng lại
        $toAssign = $addedSerials;
        $renamed = 0;
        while (!empty($toAssign) && $slots->isNotEmpty()) {
            $slot = $slots->shift();
            $slot->update(['sku' => array_shift($toAssign)]);
            $renamed++;
        }

        if ($delta > 0) {
            if (count($toAssign) > $delta) {
                throw new Exception("{$productCode}: không đủ thiết bị để gán " . count($toAssign) . ' serial mới (thiết bị cũ đã xuất / đang được giữ).');
            }
            $this->createItems($import, $line->product_id, $newWarehouseId, $toAssign, $delta - count($toAssign), $row);
            $changes[] = "{$productCode}: +{$delta}";
            $toAssign = [];
        } elseif ($delta < 0) {
            $removeCount = -$delta;
            // Chỉ gỡ được thiết bị trong slot (serial bị bỏ chưa dùng lại, hoặc không serial)
            if ($slots->count() < $removeCount) {
                throw new Exception("{$productCode}: không giảm được {$removeCount} cái, chỉ còn {$slots->count()} thiết bị trong kho có thể gỡ (không serial hoặc serial đã bỏ khỏi danh sách).");
            }
            $toRemove = $slots->take($removeCount);
            ProductItem::whereIn('id', $toRemove->pluck('id'))->delete();
            $slots = $slots->slice($removeCount)->values();
            $changes[] = "{$productCode}: -{$removeCount}";
        }

        if (!empty($toAssign)) {
            throw new Exception("{$productCode}: không đủ thiết bị trong kho để gán serial mới: " . implode(', ', $toAssign));
        }

        // Serial bị bỏ mà không dùng lại / không gỡ: chuyển thành thiết bị không serial
        foreach ($slots as $slot) {
            if (in_array($slot->sku, $removedSerials, true)) {
                $slot->update(['sku' => $this->noSerialSku($line->product_id, $import->id)]);
            }
        }
        if ($renamed || !empty($removedSerials)) {
            $changes[] = "{$productCode}: đổi serial (+" . count($addedSerials) . ' / -' . count($removedSerials) . ')';
        }

        // Giữ thẻ [POItem:id] (liên kết dòng PO, dùng để khoá hàng dự án / CQ) dù ghi chú bị sửa
        $comments = $row['comments'] ?? $line->comments;
        if (preg_match('/\[POItem:\d+\]/', (string) $line->comments, $tag) && !str_contains((string) $comments, $tag[0])) {
            $comments = trim($tag[0] . ' ' . $comments);
        }

        // Thông tin dòng; bảo hành / hạn dùng áp cho thiết bị còn trong kho (không đổi ghi chú thiết bị)
        $line->update([
            'warehouse_id' => $newWarehouseId,
            'quantity' => $newQty,
            'serial_number' => !empty($newSerials) ? json_encode($newSerials) : null,
            'cost' => $row['cost'] ?? $line->cost,
            'warehouse_price' => $row['cost'] ?? $line->warehouse_price,
            'comments' => $comments,
            'warranty_months' => $row['warranty_months'] ?? null,
            'expiry_date' => $row['expiry_date'] ?? null,
        ]);
        ProductItem::whereIn('id', $items->pluck('id'))
            ->where('status', ProductItem::STATUS_IN_STOCK)
            ->update([
                'warranty_months' => $row['warranty_months'] ?? null,
                'expiry_date' => $row['expiry_date'] ?? null,
            ]);

        $affected[$line->product_id][$newWarehouseId] = true;
        return $changes;
    }
}
