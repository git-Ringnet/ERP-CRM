<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
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
     * Generate unique transaction code.
     */
    public function generateTransactionCode(string $type): string
    {
        return InventoryTransaction::generateCode($type);
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
    public function processImport(array $data, ?InventoryTransaction $existingTransaction = null): InventoryTransaction
    {
        DB::beginTransaction();

        try {
            // Create or update transaction
            if ($existingTransaction) {
                $transaction = $existingTransaction;
                $transaction->update(['status' => 'completed']);
            } else {
                $transaction = InventoryTransaction::create([
                    'code' => $data['code'] ?? $this->generateTransactionCode('import'),
                    'type' => 'import',
                    'warehouse_id' => $data['warehouse_id'],
                    'date' => $data['date'],
                    'employee_id' => $data['employee_id'] ?? auth()->id(),
                    'total_qty' => 0,
                    'reference_type' => $data['reference_type'] ?? null,
                    'reference_id' => $data['reference_id'] ?? null,
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
                    // Filter out empty serials
                    $serials = array_values(array_filter($serials, fn ($s) => !empty(trim($s))));

                    InventoryTransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? null,
                        // Store serials as JSON for later use when approving
                        'serial_number' => !empty($serials) ? json_encode($serials) : null,
                        'cost' => $item['cost'] ?? $item['cost_usd'] ?? 0,
                        'description' => $item['description'] ?? null,
                        'comments' => $item['comments'] ?? null,
                    ]);
                    $totalQty += $item['quantity'];

                    // NOTE: ProductItem will be created when transaction is APPROVED, not here
                }
                $transaction->update(['total_qty' => $totalQty]);
            }

            // Create ProductItems and update inventory when approving
            if ($existingTransaction) {
                foreach ($transaction->items as $item) {
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
                        $transaction->warehouse_id,
                        $transaction->id
                    );

                    // Update inventory - add stock
                    $this->inventoryService->updateStock(
                        $item->product_id,
                        $transaction->warehouse_id,
                        $item->quantity,
                        'add'
                    );

                    // Update average cost if cost is provided
                    if ($item->cost > 0) {
                        $avgCost = $this->inventoryService->calculateAverageCost(
                            $item->product_id,
                            $transaction->warehouse_id,
                            $item->cost,
                            $item->quantity
                        );
                        $this->inventoryService->updateAverageCost(
                            $item->product_id,
                            $transaction->warehouse_id,
                            $avgCost
                        );
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
     * Process export transaction.
     * Requirements: 7.3
     */
    public function processExport(array $data, ?InventoryTransaction $existingTransaction = null): InventoryTransaction
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
                        throw new Exception("Insufficient stock for product ID {$productId}");
                    }
                }
            }

            // Create or update transaction
            if ($existingTransaction) {
                $transaction = $existingTransaction;
                $transaction->update(['status' => 'completed']);
            } else {
                $transaction = InventoryTransaction::create([
                    'code' => $data['code'] ?? $this->generateTransactionCode('export'),
                    'type' => 'export',
                    'warehouse_id' => $warehouseId,
                    'date' => $data['date'],
                    'employee_id' => $data['employee_id'] ?? auth()->id(),
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

                    InventoryTransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? null,
                        'serial_number' => $item['serial_number'] ?? null,
                        'comments' => $item['comments'] ?? null,
                        'cost' => $inventory ? $inventory->avg_cost : 0,
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

                    // Update product items status to 'sold'
                    // Get product items that are in_stock for this product and warehouse
                    $productItemIds = $item['product_item_ids'] ?? [];
                    if (!empty($productItemIds)) {
                        $this->productItemService->updateItemsStatus($productItemIds, ProductItem::STATUS_SOLD);
                    } else {
                        // If no specific items selected, update first available items
                        $availableItems = ProductItem::where('product_id', $item->product_id)
                            ->where('warehouse_id', $transaction->warehouse_id)
                            ->where('status', ProductItem::STATUS_IN_STOCK)
                            ->limit($item->quantity)
                            ->pluck('id')
                            ->toArray();
                        
                        if (!empty($availableItems)) {
                            $this->productItemService->updateItemsStatus($availableItems, ProductItem::STATUS_SOLD);
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
    public function processTransfer(array $data, ?InventoryTransaction $existingTransaction = null): InventoryTransaction
    {
        DB::beginTransaction();

        try {
            // Get transaction items for validation
            $items = $existingTransaction ? $existingTransaction->items : collect($data['items']);
            $warehouseId = $existingTransaction ? $existingTransaction->warehouse_id : $data['warehouse_id'];

            // Validate stock for all items first (only when approving)
            if ($existingTransaction) {
                foreach ($items as $item) {
                    $productId = is_array($item) ? $item['product_id'] : $item->product_id;
                    $quantity = is_array($item) ? $item['quantity'] : $item->quantity;
                    if (!$this->validateStock($productId, $warehouseId, $quantity)) {
                        throw new Exception("Insufficient stock for product ID {$productId} in source warehouse");
                    }
                }
            }

            // Create or update transaction
            if ($existingTransaction) {
                $transaction = $existingTransaction;
                $transaction->update(['status' => 'completed']);
            } else {
                $transaction = InventoryTransaction::create([
                    'code' => $data['code'] ?? $this->generateTransactionCode('transfer'),
                    'type' => 'transfer',
                    'warehouse_id' => $data['warehouse_id'],
                    'to_warehouse_id' => $data['to_warehouse_id'],
                    'date' => $data['date'],
                    'employee_id' => $data['employee_id'] ?? auth()->id(),
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
                    // Get current inventory to record cost
                    $inventory = Inventory::where('product_id', $item['product_id'])
                        ->where('warehouse_id', $data['warehouse_id'])
                        ->first();

                    $cost = $inventory ? $inventory->avg_cost : 0;

                    InventoryTransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? null,
                        'serial_number' => $item['serial_number'] ?? null,
                        'cost' => $cost,
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
                        ->where('warehouse_id', $transaction->warehouse_id)
                        ->first();

                    $cost = $inventory ? $inventory->avg_cost : 0;

                    // Update source warehouse - subtract stock
                    $this->inventoryService->updateStock(
                        $item->product_id,
                        $transaction->warehouse_id,
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

                    // Update product items - change warehouse and status
                    $productItemIds = $item['product_item_ids'] ?? [];
                    if (!empty($productItemIds)) {
                        ProductItem::whereIn('id', $productItemIds)
                            ->update([
                                'warehouse_id' => $transaction->to_warehouse_id,
                                'status' => ProductItem::STATUS_TRANSFERRED,
                            ]);
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

            return $transaction->fresh(['items.product', 'warehouse', 'toWarehouse', 'employee']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
