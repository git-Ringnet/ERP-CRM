<?php

namespace App\Services;

use App\Models\Inventory;
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
     * @return Inventory
     */
    public function updateStock(int $productId, int $warehouseId, int $quantity, string $operation = 'add'): Inventory
    {
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

        $currentValue = $inventory->stock * $inventory->avg_cost;
        $newValue = $newQuantity * $newCost;
        $totalQuantity = $inventory->stock + $newQuantity;

        if ($totalQuantity == 0) {
            return 0;
        }

        return ($currentValue + $newValue) / $totalQuantity;
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
}
