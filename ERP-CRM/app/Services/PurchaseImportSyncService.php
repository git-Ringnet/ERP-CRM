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
                'code' => $purchaseOrder->code,
                'warehouse_id' => $warehouseId,
                'supplier_id' => $purchaseOrder->supplier_id,
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

                // Resolve product_id: use PO item's product_id, or look up by name
                $productId = $poItem->product_id;
                if (!$productId && $poItem->product_name) {
                    $product = \App\Models\Product::where('name', $poItem->product_name)->first();
                    $productId = $product?->id;
                }

                // Skip items without a valid product_id (cannot import without product)
                if (!$productId) {
                    Log::warning("Skipping PO item '{$poItem->product_name}' - no matching product found in database");
                    continue;
                }

                $itemWarehouseId = $this->resolveWarehouseForPoItem($poItem, $warehouseId);

                ImportItem::create([
                    'import_id' => $import->id,
                    'product_id' => $productId,
                    'warehouse_id' => $itemWarehouseId,
                    'quantity' => $quantity,
                    'unit' => $poItem->unit,
                    'serial_number' => $poItem->serial_number,
                    'cost' => $poItem->unit_price * ($purchaseOrder->exchange_rate ?? 1),
                    'comments' => "[POItem:{$poItem->id}] Từ PO {$purchaseOrder->code} - {$poItem->product_name}",
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

    public function createPartialImportFromPO(PurchaseOrder $purchaseOrder, array $receivedQtys, ?int $warehouseId = null, bool $autoComplete = false): ?Import
    {
        if (!$warehouseId) {
            $warehouseId = $this->getDefaultWarehouseId();
        }

        if (!$warehouseId) {
            Log::warning("No warehouse available for PO #{$purchaseOrder->id}");
            return null;
        }

        DB::beginTransaction();
        try {
            $import = Import::where('reference_type', 'purchase_order')
                ->where('reference_id', $purchaseOrder->id)
                ->first();

            if (!$import) {
                $import = Import::create([
                    'code' => $purchaseOrder->code,
                    'warehouse_id' => $warehouseId,
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'date' => now(),
                    'employee_id' => auth()->id(),
                    'total_qty' => 0,
                    'reference_type' => 'purchase_order',
                    'reference_id' => $purchaseOrder->id,
                    'note' => "Nhập hàng từ đơn mua hàng {$purchaseOrder->code}",
                    'status' => 'pending',
                ]);
            } else {
                // Nếu đã có phiếu nhập, chuyển về pending để có thể duyệt đợt mới
                $import->update(['status' => 'pending']);
            }

            $batchQty = 0;
            foreach ($purchaseOrder->items as $poItem) {
                $qty = (float) ($receivedQtys[$poItem->id] ?? 0);
                if ($qty <= 0) continue;

                $productId = $poItem->product_id;
                if (!$productId && $poItem->product_name) {
                    $product = \App\Models\Product::where('name', $poItem->product_name)->first();
                    $productId = $product?->id;
                }

                if (!$productId) continue;

                $itemWarehouseId = $this->resolveWarehouseForPoItem($poItem, $warehouseId);

                ImportItem::create([
                    'import_id' => $import->id,
                    'product_id' => $productId,
                    'warehouse_id' => $itemWarehouseId,
                    'quantity' => $qty,
                    'unit' => $poItem->unit,
                    'serial_number' => $poItem->serial_number,
                    'cost' => $poItem->unit_price * ($purchaseOrder->exchange_rate ?? 1),
                    'comments' => "[POItem:{$poItem->id}] Từ PO {$purchaseOrder->code} (Nhận đợt ngày " . now()->format('d/m/Y') . ")",
                ]);
                $batchQty += $qty;
            }

            if ($batchQty <= 0) {
                DB::rollBack();
                return null;
            }

            $import->update(['total_qty' => $import->items->sum('quantity')]);

            if ($autoComplete) {
                $this->transactionService->processImport(['warehouse_id' => $warehouseId], $import);
            }

            DB::commit();
            return $import;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create partial import from PO #{$purchaseOrder->id}: " . $e->getMessage());
            throw $e;
        }
    }

    protected function isLicenseItem(PurchaseOrderItem $poItem): bool
    {
        $productCode = $poItem->product?->code;
        $productName = $poItem->product_name ?: ($poItem->product->name ?? '');

        if ($productCode && (
            str_starts_with($productCode, 'FC-') || 
            stripos($productCode, 'license') !== false || 
            stripos($productName, 'license') !== false || 
            stripos($productCode, 'e-license') !== false || 
            stripos($productName, 'e-license') !== false || 
            stripos($productCode, 'subscription') !== false || 
            stripos($productName, 'subscription') !== false || 
            stripos($productCode, 'renewal') !== false || 
            stripos($productName, 'renewal') !== false
        )) {
            return true;
        }

        if ($poItem->saleOrderRequestItem && $poItem->saleOrderRequestItem->type === 'License') {
            return true;
        }

        return false;
    }

    /**
     * Resolve correct warehouse based on PO item properties or linked SO type.
     */
    protected function resolveWarehouseForPoItem(PurchaseOrderItem $poItem, ?int $defaultWarehouseId = null): ?int
    {
        // 1. If it's a License item, it always goes to Kho License
        if ($this->isLicenseItem($poItem)) {
            $licenseWh = \App\Models\Warehouse::where('code', 'WH_LICENSE')->first();
            if ($licenseWh) {
                return $licenseWh->id;
            }
        }

        // 2. If PO is linked to a Sale Order, check SO type
        $po = $poItem->purchaseOrder;
        if ($po && $po->sale) {
            if ($po->sale->type === 'project') {
                $projectWh = \App\Models\Warehouse::where('code', 'WH_PROJECT')->first();
                if ($projectWh) {
                    return $projectWh->id;
                }
            } elseif ($po->sale->type === 'retail') {
                $runrateWh = \App\Models\Warehouse::where('code', 'WH_RUNRATE')->first();
                if ($runrateWh) {
                    return $runrateWh->id;
                }
            }
        }

        // 3. If PO item is linked to a Preload Ticket, route to Kho Runrate
        if ($poItem->saleOrderRequestItem) {
            $pr = $poItem->saleOrderRequestItem->saleOrderRequest;
            if ($pr && $pr->ticket && $pr->ticket->type === 'preload') {
                $runrateWh = \App\Models\Warehouse::where('code', 'WH_RUNRATE')->first();
                if ($runrateWh) {
                    return $runrateWh->id;
                }
            }
        }

        // 4. Fallback to default warehouse ID passed in
        return $defaultWarehouseId;
    }
}
