<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Import;
use App\Models\ImportItem;
use App\Models\Export;
use App\Models\ExportItem;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\ProductItem;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionService
{
    protected $inventoryService;
    protected $productItemService;

    public function __construct(InventoryService $inventoryService, ProductItemService $productItemService)
    {
        $this->inventoryService = $inventoryService;
        $this->productItemService = $productItemService;
    }

    /**
     * Validate if sufficient stock exists for export.
     */
    public function validateStock(int $productId, int $warehouseId, int $quantity): bool
    {
        // Lock the balance while an export is being approved. Without this,
        // two approval requests can both read the same balance before either
        // one subtracts it.
        $inventory = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        $actualStock = $this->inventoryService->calculateStockFromItems($productId, $warehouseId);
        $recordedStock = (int) ($inventory?->stock ?? 0);

        // Product items are the operational source of truth for a physical
        // export. Reconcile the summary whenever it has drifted, rather than
        // validating a stale Inventory value.
        if ($recordedStock !== $actualStock) {
            $this->inventoryService->resyncStockFromItems($productId, $warehouseId);
        }

        return $this->inventoryService->hasSufficientStock($productId, $warehouseId, $quantity);
    }

    /**
     * Resolve the physical source warehouse for an export line. New vouchers
     * persist it on export_items; legacy vouchers derive it from their chosen
     * serials so they can still be approved correctly.
     */
    private function resolveExportItemWarehouseId(ExportItem $item, ?int $fallbackWarehouseId): ?int
    {
        if ($item->warehouse_id) {
            return (int) $item->warehouse_id;
        }

        $serialIds = json_decode((string) $item->serial_number, true);
        if (is_array($serialIds) && !empty($serialIds)) {
            $warehouseId = ProductItem::whereIn('id', $serialIds)->value('warehouse_id');
            if ($warehouseId) {
                return (int) $warehouseId;
            }
        }

        return $fallbackWarehouseId ? (int) $fallbackWarehouseId : null;
    }

    /**
     * Hàng runrate: quyền giữ hàng gắn với Sales + model + số lượng, không gắn với S/N.
     * Kho quét S/N nào thì xuất đúng S/N đó (PO nguồn tự xác định theo S/N), còn suất giữ
     * được dịch chuyển sang thiết bị khác cùng model để:
     *  - Sales của đơn giảm đúng số lượng đang giữ;
     *  - Người đang giữ S/N vừa quét (nếu là Sales khác) không bị mất suất giữ.
     */
    private function rebalanceRunrateHoldsForScannedItems(array $productItemIds, Export $transaction, bool $consumeSalesHold): void
    {
        $runrateWarehouseId = \App\Models\Warehouse::where('code', 'WH_RUNRATE')->value('id');
        if (!$runrateWarehouseId) {
            return;
        }

        $salespersonName = null;
        if ($transaction->reference_type === 'sale' && $transaction->reference_id) {
            $sale = \App\Models\Sale::with('user')->find($transaction->reference_id);
            $salespersonName = $sale?->user?->name;
        }

        $scannedItems = ProductItem::with('product')
            ->whereIn('id', $productItemIds)
            ->where('warehouse_id', $runrateWarehouseId)
            ->where('status', ProductItem::STATUS_IN_STOCK)
            ->lockForUpdate()
            ->get();

        // Không lấy thiết bị đang được xuất ở các dòng khác của cùng phiếu để làm suất đổi
        $excludeIds = $transaction->items
            ->flatMap(fn ($exportItem) => (array) (json_decode((string) $exportItem->serial_number, true) ?: []))
            ->merge($productItemIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $userIdByName = fn (?string $name) => $name ? \App\Models\User::where('name', $name)->value('id') : null;

        foreach ($scannedItems as $scanned) {
            $currentHolder = trim((string) $scanned->borrower);
            if ($salespersonName && $currentHolder === $salespersonName) {
                continue; // Đúng thiết bị Sales của đơn đang giữ
            }

            $sameModel = fn () => ProductItem::where('product_id', $scanned->product_id)
                ->where('warehouse_id', $scanned->warehouse_id)
                ->where('status', ProductItem::STATUS_IN_STOCK)
                ->whereNotIn('id', $excludeIds)
                ->notCqLockedForOthers() // Không dùng hàng yêu cầu CQ đặt riêng cho đơn để đổi suất giữ
                // Ưu tiên thiết bị cùng PO (phiếu nhập) với S/N vừa quét để suất giữ không bị chia sang PO khác
                ->orderByRaw('CASE WHEN import_id <=> ? THEN 0 ELSE 1 END', [$scanned->import_id])
                ->orderBy('id')
                ->lockForUpdate();

            // 1. Sales của đơn đang giữ thiết bị khác cùng model: nhường suất đó cho người đang
            //    giữ S/N vừa quét (hoặc trả về kho nếu S/N vừa quét chưa ai giữ).
            //    Chỉ trừ suất giữ khi dòng bán là hàng có sẵn trong kho (bán từ suất giữ).
            $salesHeld = ($salespersonName && $consumeSalesHold)
                ? $sameModel()->where('borrower', $salespersonName)->first()
                : null;
            if ($salesHeld) {
                $salesHeld->update(['borrower' => $currentHolder ?: null]);
                \App\Models\ProductItemBorrowLog::create([
                    'product_item_id' => $salesHeld->id,
                    'from_user_id' => $userIdByName($salespersonName),
                    'to_user_id' => $userIdByName($currentHolder ?: null),
                    'ticket_id' => null,
                    'note' => mb_substr("Xuất kho {$transaction->code}: kho quét S/N {$scanned->sku}, chuyển suất giữ của {$salespersonName}" . ($currentHolder ? " cho {$currentHolder}" : ' về kho'), 0, 255),
                ]);
                continue;
            }

            if ($currentHolder === '') {
                continue;
            }

            // 2. S/N vừa quét đang được Sales khác giữ: bù cho họ một thiết bị cùng model chưa ai giữ.
            $free = $sameModel()->where(fn ($q) => $q->whereNull('borrower')->orWhere('borrower', ''))->first();
            if (!$free) {
                $productCode = $scanned->product?->code ?? "ID {$scanned->product_id}";
                throw new Exception("Serial {$scanned->sku} ({$productCode}) đang được {$currentHolder} giữ và kho không còn thiết bị cùng model chưa ai giữ để bù. Vui lòng quét serial khác hoặc tạo ticket chuyển quyền giữ hàng.");
            }

            $free->update(['borrower' => $currentHolder]);
            \App\Models\ProductItemBorrowLog::create([
                'product_item_id' => $free->id,
                'from_user_id' => null,
                'to_user_id' => $userIdByName($currentHolder),
                'ticket_id' => null,
                'note' => mb_substr("Xuất kho {$transaction->code}: kho quét S/N {$scanned->sku} đang được {$currentHolder} giữ, bù thiết bị cùng model", 0, 255),
            ]);
        }
    }

    /**
     * Process import transaction.
     * Requirements: 4.2, 4.3, 7.1
     */
    public function processImport(array $data, ?Import $existingTransaction = null): Import
    {
        DB::beginTransaction();

        try {
            // Create or update transaction
            if ($existingTransaction) {
                $transaction = $existingTransaction;
                $transaction->update(['status' => 'completed']);
            } else {
                $transaction = Import::create([
                    'code' => $data['code'] ?? Import::generateCode(),
                    'warehouse_id' => $data['warehouse_id'] ?? null,
                    'supplier_id' => $data['supplier_id'] ?? null,
                    'date' => $data['date'],
                    'employee_id' => $data['employee_id'] ?? auth()->id(),
                    'total_qty' => 0,
                    'shipping_cost' => $data['shipping_cost'] ?? 0,
                    'loading_cost' => $data['loading_cost'] ?? 0,
                    'inspection_cost' => $data['inspection_cost'] ?? 0,
                    'other_cost' => $data['other_cost'] ?? 0,
                    'total_service_cost' => $data['total_service_cost'] ?? 0,
                    'discount_percent' => $data['discount_percent'] ?? 0,
                    'vat_percent' => $data['vat_percent'] ?? 8,
                    'reference_type' => $data['reference_type'] ?? null,
                    'reference_id' => $data['reference_id'] ?? null,
                    'po_code' => $data['po_code'] ?? null,
                    'shipping_allocation_id' => $data['shipping_allocation_id'] ?? null,
                    'note' => $data['note'] ?? null,
                    'status' => 'pending',
                ]);
            }

            $totalQty = 0;

            // Process items (only create transaction items if new transaction)
            if (!$existingTransaction) {
                foreach ($data['items'] as $item) {
                    // Support both 'serials' and 'skus' keys for compatibility
                    $serials = $item['serials'] ?? $item['skus'] ?? [];

                    // Parse serial_list if provided (textarea input)
                    if (empty($serials) && !empty($item['serial_list'])) {
                        $serials = preg_split('/[\n,]+/', $item['serial_list']);
                        $serials = array_map('trim', $serials);
                    }

                    // Filter out empty serials
                    $serials = array_values(array_filter($serials, fn($s) => !empty(trim($s))));

                    // Calculate warehouse price (cost + service cost per unit)
                    // Priority: Use shipping allocation if available, otherwise use service cost
                    $additionalCost = 0;
                    if (isset($data['shipping_allocation_id']) && $data['shipping_allocation_id']) {
                        // Will be calculated after transaction is created
                        $additionalCost = 0;
                    } else {
                        $additionalCost = $transaction->getServiceCostPerUnit();
                    }
                    $warehousePrice = ($item['cost'] ?? 0) + $additionalCost;

                    $warrantyMonths = $item['warranty_months'] ?? null;
                    $expiryDate = $item['expiry_date'] ?? null;

                    // Auto-calculate expiry date if missing but warranty is present
                    if (!$expiryDate && $warrantyMonths && isset($data['date'])) {
                        try {
                            $expiryDate = \Carbon\Carbon::parse($data['date'])->addMonths((int)$warrantyMonths)->format('Y-m-d');
                        } catch (\Exception $e) {
                            // Ignore parsing errors
                        }
                    }

                    ImportItem::create([
                        'import_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $item['warehouse_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? null,
                        // Store serials as JSON for later use when approving
                        'serial_number' => !empty($serials) ? json_encode($serials) : null,
                        'cost' => $item['cost'] ?? $item['cost_usd'] ?? 0,
                        'warehouse_price' => $warehousePrice,
                        'comments' => $item['comments'] ?? null,
                        'warranty_months' => $warrantyMonths,
                        'expiry_date' => $expiryDate,
                    ]);
                    $totalQty += $item['quantity'];
                }
                $transaction->update(['total_qty' => $totalQty]);
                
                // Update warehouse_price for items if using shipping allocation
                if ($transaction->usesShippingAllocation()) {
                    foreach ($transaction->items as $item) {
                        $allocatedCost = $transaction->getAllocatedCostForProduct($item->product_id);
                        $item->update([
                            'warehouse_price' => $item->cost + $allocatedCost
                        ]);
                    }
                }
            }

            // Create ProductItems and update inventory when approving
            if ($existingTransaction) {
                if ($transaction->reference_type === 'purchase_order' && $transaction->reference_id) {
                    $po = \App\Models\PurchaseOrder::find($transaction->reference_id);
                    if ($po) {
                        app(\App\Services\PurchaseImportSyncService::class)->syncImportSerialsFromPO($po);
                        $transaction->refresh();
                    }
                }

                // Chỉ xử lý các items chưa được đồng bộ (processed_at is NULL)
                foreach ($transaction->items()->whereNull('processed_at')->get() as $item) {
                    // Backward compatibility: old pending imports may already
                    // contain a service PO line. Mark it processed without
                    // creating ProductItems or changing stock.
                    if ($transaction->reference_type === 'purchase_order'
                        && $item->comments
                        && preg_match('/\[POItem:(\d+)\]/', $item->comments, $matches)) {
                        $poItem = \App\Models\PurchaseOrderItem::with('saleOrderRequestItem.saleItem')->find((int) $matches[1]);
                        if ($poItem?->saleOrderRequestItem?->saleItem?->is_service) {
                            $item->update(['processed_at' => now()]);
                            continue;
                        }
                    }

                    // Use item's warehouse_id if available, otherwise use transaction's warehouse_id
                    $warehouseId = $item->warehouse_id ?? $transaction->warehouse_id;

                    if (!$warehouseId) {
                        throw new Exception("Không tìm thấy kho nhập cho sản phẩm {$item->product->name}");
                    }

                    // Parse serials from JSON stored in serial_number
                    $serials = [];
                    if (!empty($item->serial_number)) {
                        $decoded = json_decode($item->serial_number, true);
                        if (is_array($decoded)) {
                            $serials = $decoded;
                        } elseif (is_string($item->serial_number) && !empty(trim($item->serial_number))) {
                            // Single serial stored as string
                            $serials = [$item->serial_number];
                        }
                    }

                    // Filter out already registered serials in the system for this product
                    if (!empty($serials)) {
                        $existingSerials = \App\Models\ProductItem::where('product_id', $item->product_id)
                            ->whereIn('sku', $serials)
                            ->pluck('sku')
                            ->toArray();
                        $serials = array_values(array_diff($serials, $existingSerials));
                    }

                    // Check if this import is linked to a PO and detect if it was created from a preload ticket
                    $borrowerName = null;
                    if ($transaction->reference_type === 'purchase_order') {
                        $po = \App\Models\PurchaseOrder::find($transaction->reference_id);
                        if ($po) {
                            $poItem = null;

                            // 1. Try to find by PO Item ID embedded in comments
                            if ($item->comments && preg_match('/\[POItem:(\d+)\]/', $item->comments, $matches)) {
                                $poItemId = (int)$matches[1];
                                $poItem = \App\Models\PurchaseOrderItem::find($poItemId);
                            }

                            // 2. Fallback to generic product matching if not found
                            if (!$poItem) {
                                $poItem = $po->items()
                                    ->where('product_id', $item->product_id)
                                    ->first();
                            }
                            if (!$poItem && $item->product) {
                                $poItem = $po->items()
                                    ->where('product_name', $item->product->name)
                                    ->first();
                            }

                            if ($poItem && $poItem->saleOrderRequestItem) {
                                $pr = $poItem->saleOrderRequestItem->saleOrderRequest;
                                if ($pr && $pr->ticket && $pr->ticket->type === 'preload') {
                                    $borrowerName = $pr->ticket->user?->name;
                                }
                            }
                        }
                    }

                    // Create product items with serials
                    $priceData = [
                        'comments' => $item->comments ?? null,
                        'warranty_months' => $item->warranty_months,
                        'expiry_date' => $item->expiry_date,
                        'borrower' => $borrowerName,
                    ];

                    $this->productItemService->createItemsFromImport(
                        $item->product_id,
                        $item->quantity,
                        $serials,
                        $priceData,
                        $warehouseId,
                        $transaction->id
                    );

                    // Update inventory - add stock
                    $this->inventoryService->updateStock(
                        $item->product_id,
                        $warehouseId,
                        $item->quantity,
                        'add',
                        $item->warranty_months,
                        $item->expiry_date
                    );

                    // Update average cost if cost is provided (using warehouse_price to include service costs)
                    if ($item->warehouse_price > 0) {
                        $avgCost = $this->inventoryService->calculateAverageCost(
                            $item->product_id,
                            $warehouseId,
                            $item->warehouse_price,
                            $item->quantity
                        );
                        $this->inventoryService->updateAverageCost(
                            $item->product_id,
                            $warehouseId,
                            $avgCost
                        );
                    }

                    // Đánh dấu đã xử lý dòng này
                    $item->update(['processed_at' => now()]);
                }
            }

            DB::commit();

            return $transaction->fresh(['items.product', 'items.warehouse', 'warehouse', 'employee', 'supplier']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process export transaction.
     * Requirements: 7.3
     */
    public function processExport(array $data, ?Export $existingTransaction = null): Export
    {
        DB::beginTransaction();

        try {
            // Khóa phiếu và kiểm tra lại trạng thái: bấm duyệt 2 lần / 2 người cùng duyệt không được trừ kho 2 lần
            if ($existingTransaction) {
                $lockedStatus = Export::whereKey($existingTransaction->id)->lockForUpdate()->value('status');
                if ($lockedStatus === 'completed') {
                    throw new Exception('Phiếu xuất ' . $existingTransaction->code . ' đã được xuất kho trước đó.');
                }
            }

            // Get transaction items for validation
            $items = $existingTransaction ? $existingTransaction->items : collect($data['items']);

            // Get warehouse_id from first item or existing transaction
            $warehouseId = $existingTransaction
                ? $existingTransaction->warehouse_id
                : ($data['warehouse_id'] ?? $data['items'][0]['warehouse_id'] ?? null);

            // Validate total demand per product and source warehouse. The same
            // product can appear on multiple lines of a voucher; validating
            // each line separately can overstate what is available.
            if ($existingTransaction) {
                $stockRequirements = [];

                foreach ($items as $item) {
                    $productId = is_array($item) ? $item['product_id'] : $item->product_id;
                    $quantity = is_array($item) ? $item['quantity'] : $item->quantity;
                    $itemWarehouseId = is_array($item)
                        ? ($item['warehouse_id'] ?? $warehouseId)
                        : $this->resolveExportItemWarehouseId($item, $warehouseId);

                    if (!$itemWarehouseId) {
                        throw new Exception('Phiếu xuất chưa xác định kho xuất cho sản phẩm cần giao.');
                    }

                    // Check if it's a service item linked to a Sale
                    $isService = false;
                    if ($existingTransaction->reference_type === 'sale' && $existingTransaction->reference_id) {
                        $saleItem = \App\Models\SaleItem::where('sale_id', $existingTransaction->reference_id)
                            ->where('product_id', $productId)
                            ->first();
                        if ($saleItem && $saleItem->is_service) {
                            $isService = true;
                        }
                    }

                    if ($isService) {
                        continue;
                    }

                    $key = $productId . ':' . $itemWarehouseId;
                    if (!isset($stockRequirements[$key])) {
                        $stockRequirements[$key] = [
                            'product_id' => (int) $productId,
                            'warehouse_id' => (int) $itemWarehouseId,
                            'quantity' => 0,
                        ];
                    }
                    $stockRequirements[$key]['quantity'] += (int) $quantity;
                }

                foreach ($stockRequirements as $requirement) {
                    if (!$this->validateStock($requirement['product_id'], $requirement['warehouse_id'], $requirement['quantity'])) {
                        $productName = \App\Models\Product::find($requirement['product_id'])->name ?? 'Unknown';
                        $warehouseName = \App\Models\Warehouse::find($requirement['warehouse_id'])->name ?? 'Chưa xác định kho';
                        $available = $this->inventoryService->getCurrentStock($requirement['product_id'], $requirement['warehouse_id']);

                        throw new Exception("Không đủ tồn kho cho sản phẩm: {$productName} tại kho {$warehouseName}. Khả dụng: {$available}; yêu cầu: {$requirement['quantity']}.");
                    }
                }
            }

            // Create or update transaction
            if ($existingTransaction) {
                $transaction = $existingTransaction;
                $transaction->update(['status' => 'completed']);
            } else {
                $transaction = Export::create([
                    'code' => $data['code'] ?? Export::generateCode(),
                    'warehouse_id' => $warehouseId,
                    'date' => $data['date'],
                    'employee_id' => $data['employee_id'] ?? auth()->id(),
                    'project_id' => $data['project_id'] ?? null,
                    'customer_id' => $data['customer_id'] ?? null,
                    'contact_id' => $data['contact_id'] ?? null,
                    'total_qty' => 0,
                    'reference_type' => $data['reference_type'] ?? null,
                    'reference_id' => $data['reference_id'] ?? null,
                    'note' => $data['note'] ?? null,
                    'status' => 'pending',
                ]);
            }

            $totalQty = 0;

            // Process items (only create if new transaction)
            if (!$existingTransaction) {
                foreach ($data['items'] as $item) {
                    $itemWarehouseId = $item['warehouse_id'] ?? $warehouseId;

                    // Get current inventory to record cost
                    $inventory = Inventory::where('product_id', $item['product_id'])
                        ->where('warehouse_id', $itemWarehouseId)
                        ->first();

                    // Store selected product_item_ids as JSON (unique values only)
                    $productItemIds = $item['product_item_ids'] ?? [];
                    $productItemIds = array_filter($productItemIds, fn($id) => !empty($id));
                    $productItemIds = array_unique($productItemIds);

                    ExportItem::create([
                        'export_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $itemWarehouseId,
                        'quantity' => $item['quantity'],
                        'requested_quantity' => $item['requested_quantity'] ?? null,
                        'unit' => $item['unit'] ?? null,
                        // Store selected serial IDs as JSON
                        'serial_number' => !empty($productItemIds) ? json_encode(array_values($productItemIds)) : null,
                        'comments' => $item['comments'] ?? null,
                        'is_liquidation' => isset($item['is_liquidation']) ? (bool) $item['is_liquidation'] : false,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'total' => $item['total'] ?? (($item['unit_price'] ?? 0) * $item['quantity']),
                    ]);
                    $totalQty += $item['quantity'];
                }
                $transaction->update(['total_qty' => $totalQty]);
            }

            // Update inventory and product items when approving
            // Requirements: 7.3
            if ($existingTransaction) {
                // Hàng nhập từ PO đặt trước chưa liên kết SO/P&L đã duyệt: không được xuất
                $blockedImportIds = \App\Models\PurchaseOrder::exportBlockedImportIds();
                $notBlocked = function ($q) use ($blockedImportIds) {
                    if (!empty($blockedImportIds)) {
                        $q->where(fn ($w) => $w->whereNull('import_id')->orWhereNotIn('import_id', $blockedImportIds));
                    }
                };
                $blockedMessage = function (array $itemIds) {
                    $codes = \App\Models\PurchaseOrder::whereIn('id', \App\Models\Import::whereIn('id',
                        ProductItem::whereIn('id', $itemIds)->pluck('import_id'))->where('reference_type', 'purchase_order')->pluck('reference_id'))
                        ->pluck('code')->unique()->implode(', ');
                    return "Không thể xuất hàng thuộc PO {$codes}: PO đặt trước chưa liên kết Đơn hàng bán có P&L đã duyệt. Vui lòng liên kết SO cho PO trước khi xuất kho.";
                };

                foreach ($transaction->items as $item) {
                    $itemWarehouseId = $this->resolveExportItemWarehouseId($item, $transaction->warehouse_id);
                    if (!$itemWarehouseId) {
                        throw new Exception('Phiếu xuất chưa xác định kho xuất cho sản phẩm cần giao.');
                    }

                    // Check if it's a service item linked to a Sale
                    $isService = false;
                    $saleItem = null;
                    if ($transaction->reference_type === 'sale' && $transaction->reference_id) {
                        $saleItem = \App\Models\SaleItem::where('sale_id', $transaction->reference_id)
                            ->where('product_id', $item->product_id)
                            ->first();
                        if ($saleItem && $saleItem->is_service) {
                            $isService = true;
                        }
                    }

                    if ($isService) {
                        // Skip updating stock and serials for service items
                        continue;
                    }

                    // Update inventory - subtract stock
                    $this->inventoryService->updateStock(
                        $item->product_id,
                        $itemWarehouseId,
                        $item->quantity,
                        'subtract'
                    );

                    // Parse product_item_ids from serial_number JSON
                    $productItemIds = [];
                    if (!empty($item->serial_number)) {
                        $decoded = json_decode($item->serial_number, true);
                        if (is_array($decoded)) {
                            $productItemIds = $decoded;
                        }
                    }

                    // S/N đã chọn phải đúng mã hàng, còn trong kho xuất và không vượt số lượng dòng,
                    // nếu không phiếu sẽ xuất lệch số lượng / S/N so với lựa chọn.
                    if (!empty($productItemIds)) {
                        $productItemIds = array_values(array_unique(array_map('intval', $productItemIds)));
                        $productCode = $item->product?->code ?? "ID {$item->product_id}";
                        if (count($productItemIds) > (int) $item->quantity) {
                            throw new Exception("Sản phẩm {$productCode}: số S/N đã chọn (" . count($productItemIds) . ") nhiều hơn số lượng xuất ({$item->quantity}).");
                        }
                        $validIds = ProductItem::whereIn('id', $productItemIds)
                            ->where('product_id', $item->product_id)
                            ->where('warehouse_id', $itemWarehouseId)
                            ->whereIn('status', [ProductItem::STATUS_IN_STOCK, ProductItem::STATUS_LIQUIDATION])
                            ->pluck('id')->all();
                        $invalidIds = array_diff($productItemIds, $validIds);
                        if (!empty($invalidIds)) {
                            $invalidSkus = ProductItem::whereIn('id', $invalidIds)->pluck('sku')->implode(', ');
                            throw new Exception("Sản phẩm {$productCode}: S/N đã chọn không còn trong kho xuất hoặc sai mã hàng: {$invalidSkus}. Vui lòng chọn lại S/N.");
                        }
                    }

                    if (!empty($productItemIds) && !empty($blockedImportIds)) {
                        $blockedSelected = ProductItem::whereIn('id', $productItemIds)->whereIn('import_id', $blockedImportIds)->pluck('id')->all();
                        if (!empty($blockedSelected)) {
                            throw new Exception($blockedMessage($blockedSelected));
                        }
                    }

                    // Hàng runrate yêu cầu CQ / hàng dự án: bắt buộc xuất đúng thiết bị thuộc PO đặt cho đơn này;
                    // dòng khác không được lấy thiết bị đã khoá cho đơn khác.
                    $cqSaleId = $transaction->reference_type === 'sale' ? (int) $transaction->reference_id : null;
                    $isCqLine = $cqSaleId && $saleItem && in_array($saleItem->deal_item_type, ProductItem::PO_LOCKED_DEAL_ITEM_TYPES, true)
                        // Chỉ bắt buộc khi hệ thống xác định được thiết bị của PO đặt cho đơn (PO có liên kết yêu cầu đặt hàng)
                        && ProductItem::where('product_id', $item->product_id)->cqLocked($cqSaleId)->exists();
                    $cqScope = function ($q) use ($isCqLine, $cqSaleId) {
                        if (!$cqSaleId) {
                            return; // Phiếu xuất không gắn đơn bán: không áp khoá CQ
                        }
                        $isCqLine ? $q->cqLocked($cqSaleId) : $q->notCqLockedForOthers($cqSaleId);
                    };
                    if ($cqSaleId && !empty($productItemIds)) {
                        $cqAllowedIds = ProductItem::whereIn('id', $productItemIds)->where($cqScope)->pluck('id')->all();
                        $cqWrongIds = array_diff(array_map('intval', $productItemIds), $cqAllowedIds);
                        if (!empty($cqWrongIds)) {
                            $wrongSkus = ProductItem::whereIn('id', $cqWrongIds)->pluck('sku')->implode(', ');
                            throw new Exception($isCqLine
                                ? "Dòng hàng dự án / runrate yêu cầu CQ chỉ được xuất đúng thiết bị thuộc PO đặt cho đơn này. Serial không hợp lệ: {$wrongSkus}."
                                : "Serial {$wrongSkus} là hàng dự án / yêu cầu CQ đã đặt riêng cho đơn bán khác, không được xuất cho đơn này.");
                        }
                    }

                    // Update selected product items status to 'sold' and link to transaction
                    if (!empty($productItemIds)) {
                        $this->rebalanceRunrateHoldsForScannedItems(
                            $productItemIds,
                            $transaction,
                            $saleItem ? (bool) $saleItem->is_from_stock : false
                        );

                        ProductItem::whereIn('id', $productItemIds)->update([
                            'status' => ProductItem::STATUS_SOLD,
                            'export_id' => $transaction->id,
                            'borrower' => null, // Suất giữ đã được dịch chuyển ở bước trên
                        ]);
                        $remainingQty = $item->quantity - count($productItemIds);
                    } else {
                        $remainingQty = $item->quantity;
                    }

                    // For remaining quantity (not selected serials), update NOSKU items first, then others
                    $remainingQty = $item->quantity - count($productItemIds);

                    // Determine target status based on is_liquidation flag
                    $targetStatus = (!empty($item->is_liquidation) && $item->is_liquidation)
                        ? ProductItem::STATUS_LIQUIDATION
                        : ProductItem::STATUS_IN_STOCK;

                    // Prioritize items borrowed/held by the salesperson associated with this Sale Order
                    $borrowedItems = [];
                    if ($transaction->reference_type === 'sale' && $remainingQty > 0) {
                        $sale = \App\Models\Sale::find($transaction->reference_id);
                        if ($sale) {
                            $salespersonName = $sale->employee?->name ?? $sale->user?->name;
                            if ($salespersonName) {
                                $borrowedItems = ProductItem::where('product_id', $item->product_id)
                                    ->where('warehouse_id', $itemWarehouseId)
                                    ->where('status', $targetStatus)
                                    ->where('borrower', $salespersonName)
                                    ->whereNotIn('id', $productItemIds)
                                    ->where($notBlocked)
                                    ->where($cqScope)
                                    ->limit($remainingQty)
                                    ->pluck('id')
                                    ->toArray();

                                if (!empty($borrowedItems)) {
                                    ProductItem::whereIn('id', $borrowedItems)->update([
                                        'status' => ProductItem::STATUS_SOLD,
                                        'export_id' => $transaction->id,
                                        'borrower' => null, // Clear borrower name to release reservation
                                    ]);
                                    $remainingQty -= count($borrowedItems);
                                }
                            }
                        }
                    }

                    if ($remainingQty > 0) {
                        // 1. Try No Serial items with correct status
                        $noSkuItems = ProductItem::where('product_id', $item->product_id)
                            ->where('warehouse_id', $itemWarehouseId)
                            ->where('status', $targetStatus)
                            ->whereNotIn('id', array_merge($productItemIds, $borrowedItems))
                            ->where($notBlocked)
                            ->where($cqScope)
                            ->noSerial()
                            ->limit($remainingQty)
                            ->pluck('id')
                            ->toArray();

                        if (!empty($noSkuItems)) {
                            ProductItem::whereIn('id', $noSkuItems)->update([
                                'status' => ProductItem::STATUS_SOLD,
                                'export_id' => $transaction->id,
                                'borrower' => null, // Clear borrower name if any
                            ]);
                            $remainingQty -= count($noSkuItems);
                        }
                    }

                    if ($remainingQty > 0) {
                        // 2. Try any items with correct status.
                        // Không tự lấy thiết bị đang được Sales khác giữ: trước đây bước này lấy đại thiết bị bất kỳ
                        // (không ai quét, không dịch chuyển suất giữ) nên xuất lệch S/N và Sales khác mất suất giữ.
                        $lineSalesperson = $transaction->reference_type === 'sale'
                            ? (\App\Models\Sale::find($transaction->reference_id)?->employee?->name ?? \App\Models\Sale::find($transaction->reference_id)?->user?->name)
                            : null;
                        $notHeldByOthers = function ($q) use ($lineSalesperson) {
                            $q->where(function ($b) use ($lineSalesperson) {
                                $b->whereNull('borrower')->orWhere('borrower', '');
                                if ($lineSalesperson) {
                                    $b->orWhere('borrower', $lineSalesperson);
                                }
                            });
                        };
                        $otherItems = ProductItem::where('product_id', $item->product_id)
                            ->where('warehouse_id', $itemWarehouseId)
                            ->where('status', $targetStatus)
                            ->whereNotIn('id', array_merge($productItemIds, $borrowedItems, $noSkuItems ?? []))
                            ->where($notBlocked)
                            ->where($cqScope)
                            ->where($notHeldByOthers)
                            ->limit($remainingQty)
                            ->pluck('id')
                            ->toArray();

                        if (!empty($otherItems)) {
                            ProductItem::whereIn('id', $otherItems)->update([
                                'status' => ProductItem::STATUS_SOLD,
                                'export_id' => $transaction->id,
                                'borrower' => null, // Clear borrower name if any
                            ]);
                            $remainingQty -= count($otherItems);
                        }
                    }

                    if ($remainingQty > 0) {
                        $heldByOthersLeft = ProductItem::where('product_id', $item->product_id)
                            ->where('warehouse_id', $itemWarehouseId)
                            ->where('status', $targetStatus)
                            ->whereNotIn('id', $productItemIds)
                            ->whereNotNull('borrower')->where('borrower', '!=', '')
                            ->exists();
                        if ($heldByOthersLeft) {
                            $productCode = $item->product?->code ?? "ID {$item->product_id}";
                            throw new Exception("Sản phẩm {$productCode}: mới quét " . count($productItemIds) . "/{$item->quantity} S/N, phần còn thiếu ({$remainingQty}) chỉ còn thiết bị đang được Sales khác giữ. Vui lòng sửa phiếu và quét đủ S/N trước khi duyệt.");
                        }
                    }

                    if ($remainingQty > 0 && $isCqLine) {
                        $productCode = $item->product?->code ?? "ID {$item->product_id}";
                        throw new Exception("Sản phẩm {$productCode} là hàng dự án / runrate yêu cầu CQ: kho không còn đủ thiết bị thuộc PO đặt cho đơn này (thiếu {$remainingQty}).");
                    }

                    if ($remainingQty > 0 && !empty($blockedImportIds)) {
                        $blockedLeft = ProductItem::where('product_id', $item->product_id)
                            ->where('warehouse_id', $itemWarehouseId)
                            ->where('status', $targetStatus)
                            ->whereIn('import_id', $blockedImportIds)
                            ->limit($remainingQty)
                            ->pluck('id')
                            ->all();
                        if (!empty($blockedLeft)) {
                            throw new Exception($blockedMessage($blockedLeft));
                        }
                    }
                }
            }

            DB::commit();

            return $transaction->fresh(['items.product', 'warehouse', 'employee']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Xóa phiếu xuất đã duyệt (chỉ Admin): hoàn tồn kho, trả S/N về kho, gỡ liên kết yêu cầu hóa đơn
     * và đưa đơn bán về "đã duyệt" nếu không còn phiếu xuất hoàn thành nào khác.
     * Hàng runrate bán từ suất giữ được trả lại suất giữ cho Sales của đơn.
     *
     * @return array Ảnh chụp S/N đã hoàn để ghi lịch sử
     */
    public function revertCompletedExport(Export $export): array
    {
        return DB::transaction(function () use ($export) {
            $lockedStatus = Export::whereKey($export->id)->lockForUpdate()->value('status');
            if ($lockedStatus !== 'completed') {
                throw new Exception("Phiếu xuất {$export->code} không ở trạng thái đã duyệt.");
            }

            $sale = $export->reference_type === 'sale' && $export->reference_id
                ? \App\Models\Sale::find($export->reference_id)
                : null;
            $salespersonName = $sale ? ($sale->employee?->name ?? $sale->user?->name) : null;
            $runrateWarehouseId = (int) \App\Models\Warehouse::where('code', 'WH_RUNRATE')->value('id');

            $restored = [];
            foreach ($export->items as $item) {
                $saleItem = $sale ? $sale->items()->where('product_id', $item->product_id)->first() : null;
                if ($saleItem && $saleItem->is_service) {
                    continue; // Dịch vụ không trừ kho khi duyệt
                }

                $itemWarehouseId = $this->resolveExportItemWarehouseId($item, $export->warehouse_id);
                $this->inventoryService->updateStock($item->product_id, $itemWarehouseId, $item->quantity, 'add');

                $restoreHold = $saleItem && $saleItem->is_from_stock && $salespersonName
                    && (int) $itemWarehouseId === $runrateWarehouseId;

                $productItems = ProductItem::where('export_id', $export->id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->get();
                foreach ($productItems as $productItem) {
                    $productItem->update([
                        'status' => $item->is_liquidation ? ProductItem::STATUS_LIQUIDATION : ProductItem::STATUS_IN_STOCK,
                        'export_id' => null,
                        'borrower' => $restoreHold ? $salespersonName : null,
                    ]);
                    if ($restoreHold) {
                        \App\Models\ProductItemBorrowLog::create([
                            'product_item_id' => $productItem->id,
                            'from_user_id' => null,
                            'to_user_id' => \App\Models\User::where('name', $salespersonName)->value('id'),
                            'ticket_id' => null,
                            'note' => mb_substr("Xóa phiếu xuất đã duyệt {$export->code}: trả {$productItem->sku} về kho, hoàn suất giữ cho {$salespersonName}", 0, 255),
                        ]);
                    }
                    $restored[] = $productItem->sku;
                }
            }

            \App\Models\InvoiceRequest::where('export_id', $export->id)->update(['export_id' => null]);

            $export->items()->delete();
            $export->delete();

            if ($sale && in_array($sale->status, ['shipping', 'completed'], true)
                && !$sale->exports()->where('status', 'completed')->exists()) {
                $sale->update(['status' => 'approved']);
            }

            return $restored;
        });
    }

    /**
     * Xóa phiếu nhập đã duyệt (chỉ Admin): xóa thiết bị đã nhập, trừ tồn kho và cập nhật lại SL đã nhận của PO.
     * Chặn nếu có thiết bị đã xuất / chuyển / nằm trên phiếu xuất chưa hoàn thành.
     *
     * @return array{serials: array, held: array} S/N đã xóa và S/N đang có Sales giữ (để ghi lịch sử)
     */
    public function revertCompletedImport(Import $import): array
    {
        return DB::transaction(function () use ($import) {
            $lockedStatus = Import::whereKey($import->id)->lockForUpdate()->value('status');
            if ($lockedStatus !== 'completed') {
                throw new Exception("Phiếu nhập {$import->code} không ở trạng thái đã duyệt.");
            }

            $items = ProductItem::where('import_id', $import->id)->lockForUpdate()->get();

            $used = $items->reject(fn ($item) => in_array($item->status, [ProductItem::STATUS_IN_STOCK, ProductItem::STATUS_LIQUIDATION], true) && !$item->export_id);
            if ($used->isNotEmpty()) {
                throw new Exception("Không thể xóa: {$used->count()} thiết bị của phiếu đã xuất / chuyển kho ("
                    . $used->pluck('sku')->take(10)->implode(', ') . ($used->count() > 10 ? ', ...' : '') . '). Vui lòng xóa các phiếu xuất liên quan trước.');
            }

            $itemIds = $items->pluck('id')->map(fn ($id) => (int) $id)->all();
            $pendingExports = ExportItem::with('export:id,code')
                ->whereNotNull('serial_number')
                ->whereHas('export', fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
                ->get()
                ->filter(fn ($line) => !empty(array_intersect($itemIds, array_map('intval', json_decode((string) $line->serial_number, true) ?: []))));
            if ($pendingExports->isNotEmpty()) {
                throw new Exception('Không thể xóa: thiết bị của phiếu đang được chọn trên phiếu xuất chưa hoàn thành ('
                    . $pendingExports->pluck('export.code')->unique()->implode(', ') . '). Vui lòng gỡ S/N khỏi các phiếu đó trước.');
            }

            $held = $items->filter(fn ($item) => trim((string) $item->borrower) !== '')
                ->map(fn ($item) => "{$item->sku} ({$item->borrower})")->values()->all();
            $serials = $items->pluck('sku')->all();
            $affected = $items->map(fn ($item) => [(int) $item->product_id, (int) $item->warehouse_id])->unique()->values();

            ProductItem::whereIn('id', $itemIds)->delete();
            foreach ($affected as [$productId, $warehouseId]) {
                $this->inventoryService->resyncStockFromItems($productId, $warehouseId);
            }

            $importLines = $import->items()->get();
            $import->items()->delete();
            $import->delete();

            // SL đã nhận của PO tính lại từ các phiếu nhập đã duyệt còn lại
            if ($import->reference_type === 'purchase_order' && $import->reference_id) {
                $po = \App\Models\PurchaseOrder::with('items')->find($import->reference_id);
                if ($po) {
                    foreach ($importLines as $line) {
                        $poItem = null;
                        if ($line->comments && preg_match('/\[POItem:(\d+)\]/', $line->comments, $matches)) {
                            $poItem = $po->items->firstWhere('id', (int) $matches[1]);
                        }
                        $poItem ??= $po->items->firstWhere('product_id', $line->product_id);
                        if (!$poItem) {
                            continue;
                        }

                        $poItemTag = "[POItem:{$poItem->id}]";
                        $received = (int) \App\Models\ImportItem::whereHas('import', fn ($q) => $q->where('status', 'completed'))
                            ->where(fn ($q) => $q->where('comments', 'like', "%{$poItemTag}%")
                                ->orWhere(fn ($sq) => $sq->where('product_id', $poItem->product_id)->where('comments', 'not like', '%[POItem:%')))
                            ->sum('quantity');

                        $poItem->received_quantity = min($poItem->quantity, $received);
                        $poItem->status = $poItem->received_quantity >= $poItem->quantity
                            ? 'received'
                            : ($poItem->received_quantity > 0 ? 'shipping' : 'ordered');
                        $poItem->save();
                    }

                    $po->load('items');
                    if (in_array($po->status, ['received', 'partial_received'], true)) {
                        $allReceived = $po->items->every(fn ($i) => in_array($i->status, ['received', 'cancelled'], true));
                        $po->update(['status' => $allReceived
                            ? 'received'
                            : ($po->items->contains(fn ($i) => $i->received_quantity > 0) ? 'partial_received' : 'shipping')]);
                    }
                }
            }

            return ['serials' => $serials, 'held' => $held];
        });
    }

    /**
     * Process transfer transaction.
     * Requirements: 7.3
     */
    public function processTransfer(array $data, ?Transfer $existingTransaction = null): Transfer
    {
        DB::beginTransaction();

        try {
            // Get transaction items for validation
            $items = $existingTransaction ? $existingTransaction->items : collect($data['items']);
            // Get warehouse from first item or existing transaction
            $sourceWarehouseId = $existingTransaction
                ? $existingTransaction->from_warehouse_id
                : ($data['items'][0]['warehouse_id'] ?? $data['from_warehouse_id'] ?? null);
            $destWarehouseId = $existingTransaction
                ? $existingTransaction->to_warehouse_id
                : ($data['items'][0]['to_warehouse_id'] ?? $data['to_warehouse_id'] ?? null);

            // Validate stock for all items first (only when approving)
            if ($existingTransaction) {
                foreach ($items as $item) {
                    $productId = is_array($item) ? $item['product_id'] : $item->product_id;
                    $quantity = is_array($item) ? $item['quantity'] : $item->quantity;
                    if (!$this->validateStock($productId, $sourceWarehouseId, $quantity)) {
                        $productName = \App\Models\Product::find($productId)->name ?? 'Unknown';
                        throw new Exception("Không đủ tồn kho cho sản phẩm: {$productName} tại kho nguồn");
                    }
                }
            }

            // Create or update transaction
            if ($existingTransaction) {
                $transaction = $existingTransaction;
                $transaction->update(['status' => 'completed']);
            } else {
                $transaction = Transfer::create([
                    'code' => $data['code'] ?? Transfer::generateCode(),
                    'from_warehouse_id' => $sourceWarehouseId,
                    'to_warehouse_id' => $destWarehouseId,
                    'date' => $data['date'],
                    'employee_id' => $data['employee_id'] ?? auth()->id(),
                    'total_qty' => 0,
                    'note' => $data['note'] ?? null,
                    'status' => 'pending',
                ]);
            }

            $totalQty = 0;

            // Process items (only create if new transaction)
            if (!$existingTransaction) {
                foreach ($data['items'] as $item) {
                    // Get current inventory to record cost
                    $itemWarehouseId = $item['warehouse_id'] ?? $sourceWarehouseId;
                    $inventory = Inventory::where('product_id', $item['product_id'])
                        ->where('warehouse_id', $itemWarehouseId)
                        ->first();

                    $cost = $inventory ? $inventory->avg_cost : 0;

                    // Store selected product_item_ids as JSON (unique values only)
                    $productItemIds = $item['product_item_ids'] ?? [];
                    $productItemIds = array_filter($productItemIds, fn($id) => !empty($id));
                    $productItemIds = array_unique($productItemIds);

                    TransferItem::create([
                        'transfer_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? null,
                        'serial_number' => !empty($productItemIds) ? json_encode(array_values($productItemIds)) : null,
                        'comments' => $item['comments'] ?? null,
                    ]);
                    $totalQty += $item['quantity'];
                }
                $transaction->update(['total_qty' => $totalQty]);
            }

            // Update inventory and product items when approving
            if ($existingTransaction) {
                foreach ($transaction->items as $item) {
                    // Get current inventory to record cost
                    $inventory = Inventory::where('product_id', $item->product_id)
                        ->where('warehouse_id', $transaction->from_warehouse_id)
                        ->first();

                    $cost = $inventory ? $inventory->avg_cost : 0;

                    // Update source warehouse - subtract stock
                    $this->inventoryService->updateStock(
                        $item->product_id,
                        $transaction->from_warehouse_id,
                        $item->quantity,
                        'subtract'
                    );

                    // Update destination warehouse - add stock
                    $this->inventoryService->updateStock(
                        $item->product_id,
                        $transaction->to_warehouse_id,
                        $item->quantity,
                        'add'
                    );

                    // Parse product_item_ids from serial_number JSON
                    $productItemIds = [];
                    if (!empty($item->serial_number)) {
                        $decoded = json_decode($item->serial_number, true);
                        if (is_array($decoded)) {
                            $productItemIds = $decoded;
                        }
                    }

                    // Update selected product items - change warehouse
                    if (!empty($productItemIds)) {
                        ProductItem::whereIn('id', $productItemIds)
                            ->update([
                                'warehouse_id' => $transaction->to_warehouse_id,
                            ]);
                    }

                    // For remaining quantity (not selected serials), transfer NOSKU items
                    $remainingQty = $item->quantity - count($productItemIds);
                    if ($remainingQty > 0) {
                        $noSkuItems = ProductItem::where('product_id', $item->product_id)
                            ->where('warehouse_id', $transaction->from_warehouse_id)
                            ->where('status', ProductItem::STATUS_IN_STOCK)
                            ->noSerial()
                            ->limit($remainingQty)
                            ->pluck('id')
                            ->toArray();

                        if (!empty($noSkuItems)) {
                            ProductItem::whereIn('id', $noSkuItems)
                                ->update([
                                    'warehouse_id' => $transaction->to_warehouse_id,
                                ]);
                        }
                    }

                    // Update average cost in destination warehouse
                    if ($cost > 0) {
                        $avgCost = $this->inventoryService->calculateAverageCost(
                            $item->product_id,
                            $transaction->to_warehouse_id,
                            $cost,
                            $item->quantity
                        );
                        $this->inventoryService->updateAverageCost(
                            $item->product_id,
                            $transaction->to_warehouse_id,
                            $avgCost
                        );
                    }
                }
            }

            DB::commit();

            return $transaction->fresh(['items.product', 'fromWarehouse', 'toWarehouse', 'employee']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
