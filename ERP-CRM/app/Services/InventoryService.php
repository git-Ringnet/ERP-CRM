<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\ProductItem;
use Illuminate\Support\Collection;

class InventoryService
{
    /**
     * Update stock for a product in a warehouse.
     *
     * @param int $productId
     * @param int $warehouseId
     * @param int $quantity
     * @param string $operation 'add', 'subtract', or 'set'
     * @param int|null $warrantyMonths
     * @param string|null $expiryDate
     * @return Inventory
     */
    public function updateStock(
        int $productId,
        int $warehouseId,
        int $quantity,
        string $operation = 'add',
        ?int $warrantyMonths = null,
        ?string $expiryDate = null
    ): Inventory {
        $inventory = Inventory::firstOrCreate(
            [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ],
            [
                'stock' => 0,
                'min_stock' => 0,
                'avg_cost' => 0,
            ]
        );

        $inventory->updateStock($quantity, $operation);

        // Update metadata if provided
        $updateData = [];
        if ($warrantyMonths !== null) {
            $updateData['warranty_months'] = $warrantyMonths;
        }
        if ($expiryDate !== null) {
            $updateData['expiry_date'] = $expiryDate;
        }

        if (!empty($updateData)) {
            $inventory->update($updateData);
        }

        return $inventory->fresh();
    }

    /**
     * Calculate average cost for inventory.
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $newCost
     * @param int $newQuantity
     * @return float
     */
    public function calculateAverageCost(int $productId, int $warehouseId, float $newCost, int $newQuantity): float
    {
        $inventory = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$inventory || $inventory->stock == 0) {
            return $newCost;
        }

        // Note: This method is called AFTER updateStock, so $inventory->stock already 
        // includes the $newQuantity. We need to calculate the old stock.
        $oldStock = max(0, $inventory->stock - $newQuantity);

        $currentValue = $oldStock * $inventory->avg_cost;
        $newValue = $newQuantity * $newCost;

        return ($currentValue + $newValue) / $inventory->stock;
    }

    /**
     * Update average cost for inventory.
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $avgCost
     * @return void
     */
    public function updateAverageCost(int $productId, int $warehouseId, float $avgCost): void
    {
        Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->update(['avg_cost' => $avgCost]);
    }

    /**
     * Check if stock is low.
     *
     * @param int $productId
     * @param int $warehouseId
     * @return bool
     */
    public function checkLowStock(int $productId, int $warehouseId): bool
    {
        $inventory = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$inventory) {
            return false;
        }

        return $inventory->is_low_stock;
    }

    /**
     * Get items expiring within specified days.
     *
     * @param int $days
     * @return Collection
     */
    public function getExpiringItems(int $days = 30): Collection
    {
        return Inventory::with(['product', 'warehouse'])
            ->expiringSoon($days)
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get low stock items.
     *
     * @return Collection
     */
    public function getLowStockItems(): Collection
    {
        return Inventory::with(['product', 'warehouse'])
            ->lowStock()
            ->orderBy('stock', 'asc')
            ->get();
    }

    /**
     * Get inventory summary for a warehouse.
     *
     * @param int $warehouseId
     * @return array
     */
    public function getWarehouseSummary(int $warehouseId): array
    {
        $inventories = Inventory::where('warehouse_id', $warehouseId)->get();

        return [
            'total_items' => $inventories->count(),
            'total_stock' => $inventories->sum('stock'),
            'total_value' => $inventories->sum(function ($inv) {
                return $inv->total_value;
            }),
            'low_stock_count' => $inventories->filter(function ($inv) {
                return $inv->is_low_stock;
            })->count(),
            'expiring_soon_count' => $inventories->filter(function ($inv) {
                return $inv->is_expiring_soon;
            })->count(),
        ];
    }

    /**
     * Check if sufficient stock exists.
     *
     * @param int $productId
     * @param int $warehouseId
     * @param int $quantity
     * @return bool
     */
    public function hasSufficientStock(int $productId, int $warehouseId, int $quantity): bool
    {
        $inventory = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (!$inventory) {
            return false;
        }

        return $inventory->hasSufficientStock($quantity);
    }

    /**
     * Get current stock level.
     *
     * @param int $productId
     * @param int $warehouseId
     * @return int
     */
    public function getCurrentStock(int $productId, int $warehouseId): int
    {
        $inventory = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $inventory ? $inventory->stock : 0;
    }

    /**
     * Calculate stock from product_items where status is 'in_stock'
     * Requirements: 7.4
     *
     * @param int $productId
     * @param int|null $warehouseId
     * @return int
     */
    public function calculateStockFromItems(int $productId, ?int $warehouseId = null): int
    {
        $query = ProductItem::where('product_id', $productId)
            ->where('status', ProductItem::STATUS_IN_STOCK);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->sum('quantity');
    }

    /**
     * Resync stock in the inventories table from ProductItem actual count (status = in_stock)
     *
     * @param int $productId
     * @param int|null $warehouseId
     * @return void
     */
    public function resyncStockFromItems(int $productId, ?int $warehouseId = null): void
    {
        if ($warehouseId) {
            $warehouses = [$warehouseId];
        } else {
            $warehouses = \App\Models\Warehouse::pluck('id')->toArray();
        }

        foreach ($warehouses as $wId) {
            $actualStock = ProductItem::where('product_id', $productId)
                ->where('warehouse_id', $wId)
                ->where('status', ProductItem::STATUS_IN_STOCK)
                ->sum('quantity');

            Inventory::updateOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $wId],
                ['stock' => (int)$actualStock]
            );
        }
    }

    /**
     * Get stock summary for a product across all warehouses
     * Requirements: 7.4
     *
     * @param int $productId
     * @return array
     */
    public function getProductStockSummary(int $productId): array
    {
        $items = ProductItem::where('product_id', $productId)
            ->selectRaw('warehouse_id, status, SUM(quantity) as total_quantity')
            ->groupBy('warehouse_id', 'status')
            ->get();

        $summary = [
            'total' => 0,
            'in_stock' => 0,
            'sold' => 0,
            'damaged' => 0,
            'transferred' => 0,
            'by_warehouse' => [],
        ];

        foreach ($items as $item) {
            $summary['total'] += $item->total_quantity;
            $summary[$item->status] = ($summary[$item->status] ?? 0) + $item->total_quantity;

            if ($item->warehouse_id) {
                if (!isset($summary['by_warehouse'][$item->warehouse_id])) {
                    $summary['by_warehouse'][$item->warehouse_id] = [
                        'in_stock' => 0,
                        'sold' => 0,
                        'damaged' => 0,
                        'transferred' => 0,
                    ];
                }
                $summary['by_warehouse'][$item->warehouse_id][$item->status] = $item->total_quantity;
            }
        }

        return $summary;
    }
}
