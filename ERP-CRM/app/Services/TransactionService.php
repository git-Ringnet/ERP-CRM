<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
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

            // Process items (only create if new transaction)
            if (!$existingTransaction) {
                foreach ($data['items'] as $item) {
                    InventoryTransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? null,
                        'serial_number' => $item['serial_number'] ?? null,
                        'cost' => $item['cost'] ?? 0,
                    ]);
                    $totalQty += $item['quantity'];
                }
                $transaction->update(['total_qty' => $totalQty]);
            }

            // Update inventory when approving
            if ($existingTransaction) {
                foreach ($transaction->items as $item) {
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
     */
    public function processExport(array $data, ?InventoryTransaction $existingTransaction = null): InventoryTransaction
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

            // Process items (only create if new transaction)
            if (!$existingTransaction) {
                foreach ($data['items'] as $item) {
                    // Get current inventory to record cost
                    $inventory = Inventory::where('product_id', $item['product_id'])
                        ->where('warehouse_id', $data['warehouse_id'])
                        ->first();

                    InventoryTransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? null,
                        'serial_number' => $item['serial_number'] ?? null,
                        'cost' => $inventory ? $inventory->avg_cost : 0,
                    ]);
                    $totalQty += $item['quantity'];
                }
                $transaction->update(['total_qty' => $totalQty]);
            }

            // Update inventory when approving
            if ($existingTransaction) {
                foreach ($transaction->items as $item) {
                    // Update inventory - subtract stock
                    $this->inventoryService->updateStock(
                        $item->product_id,
                        $transaction->warehouse_id,
                        $item->quantity,
                        'subtract'
                    );
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

            // Update inventory when approving
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
