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

            $importItemsWarehouseIds = $import->items()->pluck('warehouse_id')->filter()->unique();
            $mainImportWarehouseId = $importItemsWarehouseIds->count() === 1 ? $importItemsWarehouseIds->first() : null;
            $import->update([
                'warehouse_id' => $mainImportWarehouseId ?? $warehouseId,
                'total_qty' => $totalQty
            ]);

            // Auto-complete if requested
            if ($autoComplete) {
                $this->transactionService->processImport([
                    'warehouse_id' => $mainImportWarehouseId ?? $warehouseId,
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

            $importItemsWarehouseIds = $import->items()->pluck('warehouse_id')->filter()->unique();
            $mainImportWarehouseId = $importItemsWarehouseIds->count() === 1 ? $importItemsWarehouseIds->first() : null;
            $import->update([
                'warehouse_id' => $mainImportWarehouseId ?? $warehouseId,
                'total_qty' => $import->items()->sum('quantity')
            ]);

            if ($autoComplete) {
                $this->transactionService->processImport([
                    'warehouse_id' => $mainImportWarehouseId ?? $warehouseId
                ], $import);
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
        // 1. Đã chọn Type là License khi tạo Yêu cầu đặt hàng từ Sales/PR
        if ($poItem->saleOrderRequestItem && strtolower(trim((string)$poItem->saleOrderRequestItem->type)) === 'license') {
            return true;
        }

        // 2. Mã sản phẩm ở Đặt hàng với hãng có upload file license
        if (!empty($poItem->license_file)) {
            $decoded = json_decode($poItem->license_file, true);
            if (!empty($decoded) && is_array($decoded) && count($decoded) > 0) {
                return true;
            } elseif (is_string($poItem->license_file) && !empty(trim($poItem->license_file))) {
                return true;
            }
        }

        return false;
    }

    protected function isFortinetVendor(?\App\Models\SaleOrderRequestItem $sorItem, PurchaseOrderItem $poItem): bool
    {
        $vendorName = '';
        if ($sorItem) {
            $vendorName = $sorItem->vendor ?: ($sorItem->vendorModel?->name ?? '');
        }
        if (empty($vendorName) && $poItem->purchaseOrder && $poItem->purchaseOrder->supplier) {
            $vendorName = $poItem->purchaseOrder->supplier->name;
        }
        return !empty($vendorName) && stripos($vendorName, 'Fortinet') !== false;
    }

    /**
     * Resolve correct warehouse based on PO item origin & attributes.
     * 
     * Classification Rules:
     * 1. License: Type = License (ở Yêu cầu đặt hàng) OR có file license được upload ở Đặt hàng với hãng → Kho license (WH_LICENSE)
     * 2. Runrate:
     *    - Đặt từ Ticket → Kho runrate (WH_RUNRATE)
     *    - PO team tự tạo (Standalone PO - không gắn SOR) → Kho runrate (WH_RUNRATE)
     *    - Yêu cầu đặt hàng đối với hãng Fortinet + HW + KHÔNG tích vào ô CQ (needs_cq = false) → Kho runrate (WH_RUNRATE)
     *    - Với các hãng khác (không phải Fortinet): KHÔNG có thông tin EU (eu_name_mst rỗng) → Kho runrate (WH_RUNRATE)
     * 3. Dự án: Hàng được đặt cho dự án (SOR từ Sale Order) không thuộc các TH trên → Kho dự án (WH_PROJECT)
     */
    public function resolveWarehouseForPoItem(PurchaseOrderItem $poItem, ?int $defaultWarehouseId = null): ?int
    {
        // 1. License → WH_LICENSE (ưu tiên cao nhất: Type=License hoặc có file license uploaded)
        if ($this->isLicenseItem($poItem)) {
            $licenseWh = \App\Models\Warehouse::where('code', 'WH_LICENSE')->first();
            if ($licenseWh) {
                return $licenseWh->id;
            }
        }

        $sorItem = $poItem->saleOrderRequestItem;
        $sor = $sorItem?->saleOrderRequest;

        // 2a. PO team tự tạo (Standalone PO - không có Yêu cầu đặt hàng / SOR) → WH_RUNRATE
        if (!$sorItem || !$sor) {
            $runrateWh = \App\Models\Warehouse::where('code', 'WH_RUNRATE')->first();
            if ($runrateWh) {
                return $runrateWh->id;
            }
        }

        // 2b. Đặt từ ticket (source_type = ticket hoặc ticket_id có giá trị) → WH_RUNRATE
        if ($sor->source_type === 'ticket' || $sor->ticket_id) {
            $runrateWh = \App\Models\Warehouse::where('code', 'WH_RUNRATE')->first();
            if ($runrateWh) {
                return $runrateWh->id;
            }
        }

        // 2c & 2d: Kiểm tra theo Hãng (Vendor), Type, CQ và thông tin EU
        $isFortinet = $this->isFortinetVendor($sorItem, $poItem);
        $type = strtoupper(trim((string)($sorItem->type ?? '')));
        $needsCq = (bool)($sorItem->needs_cq ?? false);
        $euInfo = trim((string)($sorItem->eu_name_mst ?? ''));

        if ($isFortinet) {
            // 2c. Đối với hãng Fortinet + HW + KHÔNG tích vào ô CQ → WH_RUNRATE
            if ($type === 'HW' && !$needsCq) {
                $runrateWh = \App\Models\Warehouse::where('code', 'WH_RUNRATE')->first();
                if ($runrateWh) {
                    return $runrateWh->id;
                }
            }
        } else {
            // 2d. Với các hãng khác (không phải Fortinet): KHÔNG có thông tin EU → WH_RUNRATE
            if (empty($euInfo)) {
                $runrateWh = \App\Models\Warehouse::where('code', 'WH_RUNRATE')->first();
                if ($runrateWh) {
                    return $runrateWh->id;
                }
            }
        }

        // 3. Hàng được đặt cho dự án (SOR từ sale_order) → WH_PROJECT
        if ($sor->source_type === 'sale_order' || $sor->sale_id) {
            $projectWh = \App\Models\Warehouse::where('code', 'WH_PROJECT')->first();
            if ($projectWh) {
                return $projectWh->id;
            }
        }

        // 4. Fallback: WH_RUNRATE hoặc default
        $runrateWh = \App\Models\Warehouse::where('code', 'WH_RUNRATE')->first();
        return $runrateWh ? $runrateWh->id : $defaultWarehouseId;
    }

    /**
     * Sync serial numbers from PO items to linked Import items and ProductItems
     */
    public function syncImportSerialsFromPO(PurchaseOrder $purchaseOrder): void
    {
        $imports = Import::where('reference_type', 'purchase_order')
            ->where('reference_id', $purchaseOrder->id)
            ->get();

        if ($imports->isEmpty()) {
            return;
        }

        foreach ($imports as $import) {
            foreach ($purchaseOrder->items as $poItem) {
                $poItemSerials = $poItem->serial_number;
                $poSerialsArray = [];
                if (!empty($poItemSerials)) {
                    $decoded = is_array($poItemSerials) ? $poItemSerials : json_decode($poItemSerials, true);
                    if (is_array($decoded)) {
                        $poSerialsArray = array_values(array_filter($decoded, fn($s) => !empty(trim((string)$s))));
                    } elseif (is_string($poItemSerials) && !empty(trim($poItemSerials))) {
                        $poSerialsArray = [trim($poItemSerials)];
                    }
                }

                // Find matching ImportItem
                $poItemTag = "[POItem:{$poItem->id}]";
                $importItem = $import->items()
                    ->where('comments', 'like', "%{$poItemTag}%")
                    ->first();

                if (!$importItem && $poItem->product_id) {
                    $importItem = $import->items()
                        ->where('product_id', $poItem->product_id)
                        ->where('comments', 'not like', '%[POItem:%')
                        ->first();
                }

                if ($importItem) {
                    $serialJson = !empty($poSerialsArray) ? json_encode($poSerialsArray) : null;
                    $correctWarehouseId = $this->resolveWarehouseForPoItem($poItem, $import->warehouse_id);

                    $updateData = [];
                    if ($importItem->serial_number !== $serialJson) {
                        $updateData['serial_number'] = $serialJson;
                    }
                    if ($correctWarehouseId && $importItem->warehouse_id !== $correctWarehouseId) {
                        $updateData['warehouse_id'] = $correctWarehouseId;
                    }

                    if (!empty($updateData)) {
                        $importItem->update($updateData);
                    }

                    // If import is completed, update ProductItem records in warehouse if placeholder SKUs or wrong warehouse exist
                    if ($import->status === 'completed' && $poItem->product_id) {
                        $productItems = \App\Models\ProductItem::where('import_id', $import->id)
                            ->where('product_id', $poItem->product_id)
                            ->get();

                        if ($correctWarehouseId) {
                            foreach ($productItems as $pi) {
                                if ($pi->warehouse_id !== $correctWarehouseId) {
                                    $pi->update(['warehouse_id' => $correctWarehouseId]);
                                }
                            }
                        }

                        if (!empty($poSerialsArray)) {
                            $existingRealSkus = $productItems->filter(function ($pi) {
                                return !str_starts_with($pi->sku, \App\Models\ProductItem::NO_SKU_PREFIX)
                                    && !str_starts_with($pi->sku, \App\Models\ProductItem::OLD_NO_SKU_PREFIX)
                                    && !empty($pi->sku);
                            })->pluck('sku')->toArray();

                            $serialsToAssign = array_diff($poSerialsArray, $existingRealSkus);

                            $placeholderItems = $productItems->filter(function ($pi) {
                                return str_starts_with($pi->sku, \App\Models\ProductItem::NO_SKU_PREFIX)
                                    || str_starts_with($pi->sku, \App\Models\ProductItem::OLD_NO_SKU_PREFIX)
                                    || empty($pi->sku);
                            });

                            foreach ($placeholderItems as $pi) {
                                if (empty($serialsToAssign)) break;
                                $nextSerial = array_shift($serialsToAssign);
                                $pi->update(['sku' => $nextSerial]);
                            }
                        }

                        // Resync inventory table stock count
                        app(\App\Services\InventoryService::class)->resyncStockFromItems($poItem->product_id);
                    }
                }
            }

            // Update main import warehouse_id
            $importItemsWarehouseIds = $import->items()->pluck('warehouse_id')->filter()->unique();
            $mainImportWarehouseId = $importItemsWarehouseIds->count() === 1 ? $importItemsWarehouseIds->first() : null;
            if ($mainImportWarehouseId && $import->warehouse_id !== $mainImportWarehouseId) {
                $import->update(['warehouse_id' => $mainImportWarehouseId]);
            }
        }
    }
}

