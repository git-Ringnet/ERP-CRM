<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Import;
use App\Models\ImportItem;
use App\Models\Warehouse;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PurchaseImportSyncService - Handles automatic import creation when PO is received
 * 
 * This service syncs Purchase Order module with Warehouse Import module:
 * - When a PO is received, automatically creates an Import record
 * - Links the Import to the PO via reference_type and reference_id
 * - Adds inventory when Import is completed
 */
class PurchaseImportSyncService
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Create import record when PO is received
     * 
     * @param PurchaseOrder $purchaseOrder
     * @param int|null $warehouseId - If not provided, uses default warehouse
     * @param bool $autoComplete - If true, auto-complete import and add inventory
     * @return Import|null
     */
    public function createImportFromPO(PurchaseOrder $purchaseOrder, ?int $warehouseId = null, bool $autoComplete = false): ?Import
    {
        // Check if import already exists for this PO
        $existingImport = Import::where('reference_type', 'purchase_order')
            ->where('reference_id', $purchaseOrder->id)
            ->first();

        if ($existingImport) {
            Log::info("Import already exists for PO #{$purchaseOrder->id}");
            return $existingImport;
        }

        // Get warehouse - use provided, or default, or first active
        if (!$warehouseId) {
            $warehouseId = $this->getDefaultWarehouseId();
        }

        if (!$warehouseId) {
            Log::warning("No warehouse available for PO #{$purchaseOrder->id}");
            return null;
        }

        DB::beginTransaction();
        try {
            // Create import record
            $import = Import::create([
                'code' => Import::generateCode(),
                'warehouse_id' => $warehouseId,
                'date' => $purchaseOrder->actual_delivery ?? now(),
                'employee_id' => auth()->id(),
                'total_qty' => 0,
                'reference_type' => 'purchase_order',
                'reference_id' => $purchaseOrder->id,
                'note' => "Tự động tạo từ đơn mua hàng {$purchaseOrder->code} - NCC: {$purchaseOrder->supplier->name}",
                'status' => 'pending',
            ]);

            $totalQty = 0;

            // Create import items from PO items
            foreach ($purchaseOrder->items as $poItem) {
                // Use received_quantity if available, otherwise use ordered quantity
                $quantity = $poItem->received_quantity ?? $poItem->quantity;

                ImportItem::create([
                    'import_id' => $import->id,
                    'product_id' => $poItem->product_id,
                    'quantity' => $quantity,
                    'unit' => $poItem->unit,
                    'serial_number' => null,
                    'cost' => $poItem->unit_price,
                    'comments' => "Từ PO {$purchaseOrder->code} - {$poItem->product_name}",
                ]);
                $totalQty += $quantity;
            }

            $import->update(['total_qty' => $totalQty]);

            // Auto-complete if requested
            if ($autoComplete) {
                $this->transactionService->processImport([
                    'warehouse_id' => $warehouseId,
                ], $import);

                Log::info("Auto-completed Import #{$import->code} from PO #{$purchaseOrder->code}");
            }

            DB::commit();

            Log::info("Created Import #{$import->code} from PO #{$purchaseOrder->code}");
            return $import;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create import from PO #{$purchaseOrder->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get default warehouse ID from settings or first active warehouse
     */
    protected function getDefaultWarehouseId(): ?int
    {
        // Try to get from settings
        $defaultWarehouse = Setting::where('key', 'default_warehouse_id')->first();
        if ($defaultWarehouse && $defaultWarehouse->value) {
            return (int) $defaultWarehouse->value;
        }

        // Fall back to first active warehouse
        $warehouse = Warehouse::where('status', 'active')->first();
        return $warehouse?->id;
    }

    /**
     * Check if PO has linked import
     */
    public function hasImport(PurchaseOrder $purchaseOrder): bool
    {
        return Import::where('reference_type', 'purchase_order')
            ->where('reference_id', $purchaseOrder->id)
            ->exists();
    }

    /**
     * Get import linked to PO
     */
    public function getImport(PurchaseOrder $purchaseOrder): ?Import
    {
        return Import::where('reference_type', 'purchase_order')
            ->where('reference_id', $purchaseOrder->id)
            ->first();
    }
}
