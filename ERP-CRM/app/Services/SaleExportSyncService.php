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
        $export = $this->getExport($sale);

        if (!$export) {
            return;
        }

        if ($export->status === 'cancelled') {
            return;
        }

        DB::beginTransaction();
        try {
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

            DB::commit();
            Log::info("Cancelled Export #{$export->code} because Sale #{$sale->code} was cancelled");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to cancel export for Sale #{$sale->id}: " . $e->getMessage());
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
                $existingExport->update(['status' => 'pending']);
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
                'code' => Export::generateCode(),
                'warehouse_id' => $warehouseId,
                'project_id' => $sale->project_id,
                'date' => $sale->date,
                'employee_id' => auth()->id(),
                'total_qty' => 0,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'note' => "Tự động tạo từ đơn hàng {$sale->code}",
                'status' => 'pending',
            ]);

            $totalQty = 0;

            // Create export items from sale items
            foreach ($sale->items as $saleItem) {
                ExportItem::create([
                    'export_id' => $export->id,
                    'product_id' => $saleItem->product_id,
                    'quantity' => $saleItem->quantity,
                    'unit' => null,
                    'serial_number' => null,
                    'comments' => "Từ đơn hàng {$sale->code} - {$saleItem->product_name}",
                ]);
                $totalQty += $saleItem->quantity;
            }

            $export->update(['total_qty' => $totalQty]);

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
        if (empty($neededItems)) {
            return null;
        }

        // Get all active warehouses
        $warehouses = Warehouse::where('status', 'active')->get();

        foreach ($warehouses as $warehouse) {
            $hasSufficientStock = true;

            // Check stock for each needed item
            foreach ($neededItems as $productId => $quantity) {
                $inventory = \App\Models\Inventory::where('warehouse_id', $warehouse->id)
                    ->where('product_id', $productId)
                    ->first();

                $currentStock = $inventory ? $inventory->stock : 0;

                if ($currentStock < $quantity) {
                    $hasSufficientStock = false;
                    break;
                }
            }

            // If this warehouse satisfies all items, return it immediately
            if ($hasSufficientStock) {
                return $warehouse->id;
            }
        }

        return null;
    }

    /**
     * Get default warehouse ID from settings or first active warehouse
     * Verifies that the warehouse is actually active
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
}
