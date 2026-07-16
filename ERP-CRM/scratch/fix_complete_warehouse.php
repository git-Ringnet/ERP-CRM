<?php
/**
 * Complete fix for all import warehouse assignments.
 * Updates ImportItem, ProductItem, and Inventory (stock column).
 * 
 * Run: php scratch/fix_complete_warehouse.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$syncService = app(\App\Services\PurchaseImportSyncService::class);

$imports = \App\Models\Import::where('reference_type', 'purchase_order')
    ->orderBy('id')
    ->get();

echo "=== Complete warehouse fix for " . $imports->count() . " import(s) ===\n\n";

$totalFixed = 0;

foreach ($imports as $import) {
    $po = \App\Models\PurchaseOrder::with([
        'items.saleOrderRequestItem.saleOrderRequest.sale',
        'items.saleOrderRequestItem.saleOrderRequest.ticket'
    ])->find($import->reference_id);

    if (!$po) continue;

    echo "Import #{$import->id} ({$import->code}) [status={$import->status}]:\n";

    foreach ($import->items as $importItem) {
        $oldWhId = $importItem->warehouse_id;

        // Find matching PO item
        $poItem = null;
        if ($importItem->comments && preg_match('/\[POItem:(\d+)\]/', $importItem->comments, $matches)) {
            $poItem = \App\Models\PurchaseOrderItem::with([
                'saleOrderRequestItem.saleOrderRequest.sale',
                'saleOrderRequestItem.saleOrderRequest.ticket'
            ])->find((int)$matches[1]);
        }
        if (!$poItem) {
            $poItem = $po->items->firstWhere('product_id', $importItem->product_id);
        }
        if (!$poItem) {
            echo "  ⚠ Item #{$importItem->id}: No matching PO item\n";
            continue;
        }

        $newWhId = $syncService->resolveWarehouseForPoItem($poItem, $oldWhId);

        if ($newWhId == $oldWhId) {
            $newWh = \App\Models\Warehouse::find($newWhId);
            echo "  ⏩ Item #{$importItem->id}: already correct ({$newWh->name})\n";
            continue;
        }

        $oldWh = \App\Models\Warehouse::find($oldWhId);
        $newWh = \App\Models\Warehouse::find($newWhId);

        DB::beginTransaction();
        try {
            // 1. Update ImportItem warehouse
            $importItem->update(['warehouse_id' => $newWhId]);
            echo "  ✅ ImportItem #{$importItem->id}: {$oldWh->name} → {$newWh->name}\n";

            // 2. If completed (processed), also move ProductItems and Inventory
            if ($importItem->processed_at) {
                // Move ProductItems
                $movedPi = \App\Models\ProductItem::where('import_id', $import->id)
                    ->where('product_id', $importItem->product_id)
                    ->where('warehouse_id', $oldWhId)
                    ->update(['warehouse_id' => $newWhId]);
                echo "    📦 Moved {$movedPi} ProductItem(s)\n";

                // Subtract from old warehouse inventory (uses 'stock' column)
                $oldInv = \App\Models\Inventory::where('product_id', $importItem->product_id)
                    ->where('warehouse_id', $oldWhId)
                    ->first();
                if ($oldInv) {
                    $oldStock = $oldInv->stock;
                    $oldInv->stock = max(0, $oldInv->stock - $importItem->quantity);
                    $oldInv->save();
                    echo "    📉 Old WH ({$oldWh->name}) stock: {$oldStock} → {$oldInv->stock}\n";
                }

                // Add to new warehouse inventory
                $newInv = \App\Models\Inventory::firstOrCreate(
                    ['product_id' => $importItem->product_id, 'warehouse_id' => $newWhId],
                    ['stock' => 0, 'avg_cost' => $importItem->warehouse_price ?? $importItem->cost ?? 0]
                );
                $oldNewStock = $newInv->stock;
                $newInv->stock += $importItem->quantity;
                $newInv->save();
                echo "    📈 New WH ({$newWh->name}) stock: {$oldNewStock} → {$newInv->stock}\n";
            }

            DB::commit();
            $totalFixed++;
        } catch (\Exception $e) {
            DB::rollBack();
            echo "    ❌ Error: {$e->getMessage()}\n";
        }
    }

    // Update import main warehouse
    $import->refresh();
    $importItemsWarehouseIds = $import->items()->pluck('warehouse_id')->filter()->unique();
    $mainWhId = $importItemsWarehouseIds->count() === 1 ? $importItemsWarehouseIds->first() : null;
    if ($mainWhId && $mainWhId != $import->warehouse_id) {
        $import->update(['warehouse_id' => $mainWhId]);
        $mainWh = \App\Models\Warehouse::find($mainWhId);
        echo "  📦 Import main WH → {$mainWh->name}\n";
    }

    // Fix trạng thái: nếu tất cả items đã processed nhưng status vẫn pending → sửa thành completed
    $unprocessedCount = $import->items()->whereNull('processed_at')->count();
    $totalItemCount = $import->items()->count();
    if ($totalItemCount > 0 && $unprocessedCount === 0 && $import->status === 'pending') {
        $import->update(['status' => 'completed']);
        echo "  🔄 Status: pending → completed (tất cả items đã được xử lý)\n";
    }

    echo "\n";
}

echo "=== Done! Fixed {$totalFixed} item(s) ===\n";

// Final verification
echo "\n=== FINAL VERIFICATION: Inventory by Warehouse ===\n";
$warehouses = \App\Models\Warehouse::where('status', 'active')->get();
foreach ($warehouses as $wh) {
    $invCount = \App\Models\Inventory::where('warehouse_id', $wh->id)->where('stock', '>', 0)->count();
    $totalStock = \App\Models\Inventory::where('warehouse_id', $wh->id)->sum('stock');
    if ($invCount > 0) {
        echo "\n{$wh->name} ({$wh->code}): {$invCount} products, total stock: {$totalStock}\n";
        \App\Models\Inventory::where('warehouse_id', $wh->id)
            ->where('stock', '>', 0)
            ->with('product')
            ->get()
            ->each(function ($inv) {
                echo "  - " . ($inv->product ? $inv->product->name : '?') . ": {$inv->stock}\n";
            });
    }
}
