<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Export;
use App\Models\ExportItem;
use App\Models\Warehouse;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SaleExportSyncService - Handles automatic export creation when sale is approved
 * 
 * This service syncs Sale module with Warehouse Export module:
 * - When a Sale is approved, automatically creates an Export record
 * - Links the Export to the Sale via reference_type and reference_id
 * - Deducts inventory when Export is completed
 */
class SaleExportSyncService
{
    protected TransactionService $transactionService;
    protected \App\Services\InventoryService $inventoryService;

    public function __construct(TransactionService $transactionService, \App\Services\InventoryService $inventoryService)
    {
        $this->transactionService = $transactionService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Cancel export when sale is cancelled
     * If export was completed, revert inventory changes
     */
    public function cancelExportFromSale(Sale $sale): void
    {
        $exports = Export::where('reference_type', 'sale')
            ->where('reference_id', $sale->id)
            ->get();

        if ($exports->isEmpty()) {
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($exports as $export) {
                if ($export->status === 'cancelled') {
                    continue;
                }

                // If export was completed, we need to revert inventory
                if ($export->status === 'completed') {
                    foreach ($export->items as $item) {
                        // 1. Re-add stock to inventory
                        $this->inventoryService->updateStock(
                            $item->product_id,
                            $export->warehouse_id,
                            $item->quantity,
                            'add'
                        );

                        // 2. Revert ProductItems (if any were marked as sold)
                        \App\Models\ProductItem::where('export_id', $export->id)
                            ->where('product_id', $item->product_id)
                            ->update([
                                'status' => \App\Models\ProductItem::STATUS_IN_STOCK,
                                'export_id' => null
                            ]);
                    }

                    Log::info("Reverted inventory for cancelled Export #{$export->code} (Sale #{$sale->code})");
                }

                // Update status to cancelled
                $export->update(['status' => 'cancelled']);
                Log::info("Cancelled Export #{$export->code} because Sale #{$sale->code} was cancelled");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to cancel exports for Sale #{$sale->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create export record when sale is approved
     * 
     * @param Sale $sale
     * @param int|null $warehouseId - If not provided, uses default warehouse
     * @param bool $autoComplete - If true, auto-complete export and deduct inventory
     * @return Export|null
     */
    public function createExportFromSale(Sale $sale, ?int $warehouseId = null, bool $autoComplete = false): ?Export
    {
        // Check if export already exists for this sale
        $existingExport = Export::where('reference_type', 'sale')
            ->where('reference_id', $sale->id)
            ->first();

        if ($existingExport) {
            // If existing export was cancelled, we might want to reactivate or create new?
            // For now, if it's cancelled, let's allow creating a new one or reactivating
            if ($existingExport->status === 'cancelled') {
                // Determine if we should reuse or create new. 
                // Simplest is to update status to pending and reuse if structure matches, 
                // but safer to just update it to pending and let user process it again.
                $existingExport->update(['status' => 'draft']);
                return $existingExport;
            }

            Log::info("Export already exists for Sale #{$sale->id}");
            return $existingExport;
        }

        // Get warehouse - use provided, or find optimal, or default
        if (!$warehouseId) {
            // Priority: Find warehouse with sufficient stock
            $warehouseId = $this->findOptimalWarehouse($sale);
        }

        if (!$warehouseId) {
            // Fallback: Default warehouse setting or first active
            $warehouseId = $this->getDefaultWarehouseId();
        }

        if (!$warehouseId) {
            Log::warning("No warehouse available for Sale #{$sale->id}");
            return null;
        }

        DB::beginTransaction();
        try {
            // Create export record
            $export = Export::create([
                'code' => $sale->code,
                'warehouse_id' => $warehouseId,
                'project_id' => $sale->project_id,
                'customer_id' => $sale->customer_id,
                'date' => $sale->date,
                'employee_id' => auth()->id(),
                'total_qty' => 0,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'note' => "Tự động tạo từ đơn hàng {$sale->code}",
                'status' => 'draft',
            ]);

            $totalQty = 0;

            // Create export items from sale items
            foreach ($sale->items as $saleItem) {
                ExportItem::create([
                    'export_id' => $export->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $saleItem->quantity,
                    'is_liquidation' => $saleItem->is_liquidation,
                    'unit' => null,
                    'serial_number' => null,
                    'comments' => "Từ đơn hàng {$sale->code} - {$saleItem->product_name}",
                    'unit_price' => $saleItem->price,
                    'total' => $saleItem->total,
                ]);
                $totalQty += $saleItem->quantity;
            }

            $export->update(['total_qty' => $totalQty]);

            // Auto sync serials/ProductItems from inventory for this sale
            $this->syncExportSerialsFromSale($sale);

            // Auto-complete if requested and auto_sync is enabled
            if ($autoComplete) {
                $this->transactionService->processExport([
                    'warehouse_id' => $warehouseId,
                ], $export);

                Log::info("Auto-completed Export #{$export->code} from Sale #{$sale->code}");
            }

            DB::commit();

            Log::info("Created Export #{$export->code} from Sale #{$sale->code}");
            return $export;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create export from Sale #{$sale->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find a warehouse that has sufficient stock for all items in the sale
     */
    protected function findOptimalWarehouse(Sale $sale): ?int
    {
        // Get all items and their quantities
        $neededItems = $sale->items->pluck('quantity', 'product_id')->toArray();

        // Check each active warehouse
        $warehouses = Warehouse::active()->get();

        foreach ($warehouses as $warehouse) {
            $hasStock = true;

            foreach ($neededItems as $productId => $neededQty) {
                $stock = $this->inventoryService->getStock($productId, $warehouse->id);
                if ($stock < $neededQty) {
                    $hasStock = false;
                    break;
                }
            }

            if ($hasStock) {
                return $warehouse->id;
            }
        }

        return null;
    }

    /**
     * Get default warehouse ID from settings or first active warehouse
     */
    protected function getDefaultWarehouseId(): ?int
    {
        // Try to get from settings
        $defaultWarehouseSetting = Setting::where('key', 'default_warehouse_id')->first();

        if ($defaultWarehouseSetting && $defaultWarehouseSetting->value) {
            $warehouseId = (int) $defaultWarehouseSetting->value;

            // Validate that this warehouse exists and is active
            $isActive = Warehouse::where('id', $warehouseId)
                ->where('status', 'active')
                ->exists();

            if ($isActive) {
                return $warehouseId;
            }
        }

        // Fall back to first active warehouse
        $warehouse = Warehouse::where('status', 'active')->first();
        return $warehouse?->id;
    }

    /**
     * Check if sale has linked export
     */
    public function hasExport(Sale $sale): bool
    {
        return Export::where('reference_type', 'sale')
            ->where('reference_id', $sale->id)
            ->exists();
    }

    /**
     * Get export linked to sale
     */
    public function getExport(Sale $sale): ?Export
    {
        return Export::where('reference_type', 'sale')
            ->where('reference_id', $sale->id)
            ->first();
    }

    /**
     * Automatically sync/allocate available serial numbers (ProductItems) for a Sale's export items
     */
    public function syncExportSerialsFromSale(Sale $sale): void
    {
        $exports = Export::where('reference_type', 'sale')
            ->where('reference_id', $sale->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        if ($exports->isEmpty()) {
            return;
        }

        foreach ($exports as $export) {
            $warehouseId = $export->warehouse_id;

            foreach ($export->items as $exportItem) {
                $neededQty = $exportItem->quantity;
                if ($neededQty <= 0) continue;

                $productId = $exportItem->product_id;
                $allocatedIds = [];

                // Priority 1: ProductItems imported specifically for this Sale or Project
                $saleProjectItems = \App\Models\ProductItem::where('product_id', $productId)
                    ->where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
                    ->whereHas('import.purchaseOrder.items.saleOrderRequestItem.saleOrderRequest', function ($q) use ($sale) {
                        $q->where('sale_id', $sale->id);
                        if ($sale->project_id) {
                            $q->orWhereHas('sale', function ($sq) use ($sale) {
                                $sq->where('project_id', $sale->project_id);
                            });
                        }
                    })
                    ->limit($neededQty)
                    ->pluck('id')
                    ->toArray();

                $allocatedIds = array_merge($allocatedIds, $saleProjectItems);

                // Priority 2: Reserved/borrowed items by salesperson if still needed
                if (count($allocatedIds) < $neededQty) {
                    $remaining = $neededQty - count($allocatedIds);
                    $salespersonName = $sale->employee?->name ?? $sale->user?->name;
                    if ($salespersonName) {
                        $borrowedItems = \App\Models\ProductItem::where('product_id', $productId)
                            ->where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
                            ->where('borrower', $salespersonName)
                            ->whereNotIn('id', $allocatedIds)
                            ->limit($remaining)
                            ->pluck('id')
                            ->toArray();

                        $allocatedIds = array_merge($allocatedIds, $borrowedItems);
                    }
                }

                // Priority 3: Real serial items in stock
                if (count($allocatedIds) < $neededQty) {
                    $remaining = $neededQty - count($allocatedIds);
                    $realSerialItems = \App\Models\ProductItem::where('product_id', $productId)
                        ->where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
                        ->hasSerial()
                        ->whereNotIn('id', $allocatedIds)
                        ->when($warehouseId, function ($q) use ($warehouseId) {
                            $q->orderByRaw("warehouse_id = {$warehouseId} DESC");
                        })
                        ->limit($remaining)
                        ->pluck('id')
                        ->toArray();

                    $allocatedIds = array_merge($allocatedIds, $realSerialItems);
                }

                // Priority 4: Fallback to any in-stock items (including NOSERIAL)
                if (count($allocatedIds) < $neededQty) {
                    $remaining = $neededQty - count($allocatedIds);
                    $otherItems = \App\Models\ProductItem::where('product_id', $productId)
                        ->where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
                        ->whereNotIn('id', $allocatedIds)
                        ->when($warehouseId, function ($q) use ($warehouseId) {
                            $q->orderByRaw("warehouse_id = {$warehouseId} DESC");
                        })
                        ->limit($remaining)
                        ->pluck('id')
                        ->toArray();

                    $allocatedIds = array_merge($allocatedIds, $otherItems);
                }

                // Update exportItem serial_number if changed
                $serialJson = !empty($allocatedIds) ? json_encode(array_values($allocatedIds)) : null;
                if ($exportItem->serial_number !== $serialJson) {
                    $exportItem->update([
                        'serial_number' => $serialJson
                    ]);
                }
            }
        }
    }
}
