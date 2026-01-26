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
        return $this->inventoryService->hasSufficientStock($productId, $warehouseId, $quantity);
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
                    'vat_percent' => $data['vat_percent'] ?? 10,
                    'reference_type' => $data['reference_type'] ?? null,
                    'reference_id' => $data['reference_id'] ?? null,
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
                foreach ($transaction->items as $item) {
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

                    // Create product items with serials
                    $priceData = [
                        'comments' => $item->comments ?? null,
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
                        'add'
                    );

                    // Update average cost if cost is provided
                    if ($item->cost > 0) {
                        $avgCost = $this->inventoryService->calculateAverageCost(
                            $item->product_id,
                            $warehouseId,
                            $item->cost,
                            $item->quantity
                        );
                        $this->inventoryService->updateAverageCost(
                            $item->product_id,
                            $warehouseId,
                            $avgCost
                        );
                    }
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
            // Get transaction items for validation
            $items = $existingTransaction ? $existingTransaction->items : collect($data['items']);

            // Get warehouse_id from first item or existing transaction
            $warehouseId = $existingTransaction
                ? $existingTransaction->warehouse_id
                : ($data['warehouse_id'] ?? $data['items'][0]['warehouse_id'] ?? null);

            // Validate stock for all items first (only when approving)
            if ($existingTransaction) {
                foreach ($items as $item) {
                    $productId = is_array($item) ? $item['product_id'] : $item->product_id;
                    $quantity = is_array($item) ? $item['quantity'] : $item->quantity;
                    $itemWarehouseId = is_array($item) ? ($item['warehouse_id'] ?? $warehouseId) : $warehouseId;
                    if (!$this->validateStock($productId, $itemWarehouseId, $quantity)) {
                        $productName = \App\Models\Product::find($productId)->name ?? 'Unknown';
                        throw new Exception("Không đủ tồn kho cho sản phẩm: {$productName}");
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
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? null,
                        // Store selected serial IDs as JSON
                        'serial_number' => !empty($productItemIds) ? json_encode(array_values($productItemIds)) : null,
                        'comments' => $item['comments'] ?? null,
                        'is_liquidation' => isset($item['is_liquidation']) ? (bool) $item['is_liquidation'] : false,
                    ]);
                    $totalQty += $item['quantity'];
                }
                $transaction->update(['total_qty' => $totalQty]);
            }

            // Update inventory and product items when approving
            // Requirements: 7.3
            if ($existingTransaction) {
                foreach ($transaction->items as $item) {
                    // Update inventory - subtract stock
                    $this->inventoryService->updateStock(
                        $item->product_id,
                        $transaction->warehouse_id,
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

                    // Update selected product items status to 'sold' and link to transaction
                    if (!empty($productItemIds)) {
                        ProductItem::whereIn('id', $productItemIds)->update([
                            'status' => ProductItem::STATUS_SOLD,
                            'export_id' => $transaction->id,
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

                    if ($remainingQty > 0) {
                        // 1. Try NOSKU items with correct status
                        $noSkuItems = ProductItem::where('product_id', $item->product_id)
                            ->where('warehouse_id', $transaction->warehouse_id)
                            ->where('status', $targetStatus)
                            ->where('sku', 'like', 'NOSKU%')
                            ->limit($remainingQty)
                            ->pluck('id')
                            ->toArray();

                        if (!empty($noSkuItems)) {
                            ProductItem::whereIn('id', $noSkuItems)->update([
                                'status' => ProductItem::STATUS_SOLD,
                                'export_id' => $transaction->id,
                            ]);
                            $remainingQty -= count($noSkuItems);
                        }
                    }

                    if ($remainingQty > 0) {
                        // 2. Try any items with correct status
                        $otherItems = ProductItem::where('product_id', $item->product_id)
                            ->where('warehouse_id', $transaction->warehouse_id)
                            ->where('status', $targetStatus)
                            ->whereNotIn('id', array_merge($productItemIds, $noSkuItems ?? []))
                            ->limit($remainingQty)
                            ->pluck('id')
                            ->toArray();

                        if (!empty($otherItems)) {
                            ProductItem::whereIn('id', $otherItems)->update([
                                'status' => ProductItem::STATUS_SOLD,
                                'export_id' => $transaction->id,
                            ]);
                            $remainingQty -= count($otherItems);
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
                            ->where('sku', 'like', 'NOSKU%')
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
