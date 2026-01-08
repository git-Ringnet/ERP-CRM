<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Support\Facades\DB;

class ProductItemService
{
    /**
     * Check for duplicate serials before creating
     * Returns array of duplicate serials if found
     */
    public function checkDuplicateSerials(int $productId, array $skus): array
    {
        if (empty($skus)) {
            return [];
        }

        $existingSkus = ProductItem::where('product_id', $productId)
            ->whereIn('sku', $skus)
            ->pluck('sku')
            ->toArray();

        return $existingSkus;
    }

    /**
     * Create product items from import transaction
     * Requirements: 3.2, 3.3
     */
    public function createItemsFromImport(
        int $productId,
        int $quantity,
        array $skus,
        array $priceData,
        int $warehouseId,
        int $importId
    ): array {
        $items = [];

        // Check for duplicate serials first
        $duplicates = $this->checkDuplicateSerials($productId, $skus);
        if (!empty($duplicates)) {
            $product = Product::find($productId);
            $productName = $product ? $product->name : "ID: {$productId}";
            $duplicateList = implode(', ', $duplicates);
            throw new \Exception("Serial đã tồn tại trong hệ thống cho sản phẩm '{$productName}': {$duplicateList}");
        }
        
        DB::transaction(function () use (
            $productId,
            $quantity,
            $skus,
            $priceData,
            $warehouseId,
            $importId,
            &$items
        ) {
            // If SKUs provided, create one item per SKU
            if (!empty($skus)) {
                foreach ($skus as $sku) {
                    $items[] = ProductItem::create([
                        'product_id' => $productId,
                        'warehouse_id' => $warehouseId,
                        'import_id' => $importId,
                        'sku' => $sku,
                        'quantity' => 1,
                        'description' => $priceData['description'] ?? null,
                        'cost_usd' => $priceData['cost_usd'] ?? 0,
                        'price_tiers' => $priceData['price_tiers'] ?? null,
                        'comments' => $priceData['comments'] ?? null,
                        'status' => ProductItem::STATUS_IN_STOCK,
                    ]);
                }
            }

            // Create remaining items with auto-generated NOSKU
            $remainingQty = $quantity - count($skus);
            for ($i = 0; $i < $remainingQty; $i++) {
                // Use microtime + random to ensure uniqueness
                $uniqueId = substr(md5(uniqid(mt_rand(), true)), 0, 8);
                $noSku = ProductItem::NO_SKU_PREFIX . '_' . $productId . '_' . $importId . '_' . $uniqueId;
                $items[] = ProductItem::create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'import_id' => $importId,
                    'sku' => $noSku,
                    'quantity' => 1,
                    'description' => $priceData['description'] ?? null,
                    'cost_usd' => $priceData['cost_usd'] ?? 0,
                    'price_tiers' => $priceData['price_tiers'] ?? null,
                    'comments' => $priceData['comments'] ?? null,
                    'status' => ProductItem::STATUS_IN_STOCK,
                ]);
            }
        });
        
        return $items;
    }

    /**
     * Update status of multiple product items
     * Requirements: 4.3
     */
    public function updateItemsStatus(array $itemIds, string $status): int
    {
        return ProductItem::whereIn('id', $itemIds)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
    }

    /**
     * Get available items for a product in a warehouse
     * Requirements: 4.4
     */
    public function getAvailableItems(int $productId, int $warehouseId, int $quantity): array
    {
        return ProductItem::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', ProductItem::STATUS_IN_STOCK)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc') // FIFO
            ->limit($quantity)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Transfer items between warehouses
     * Requirements: 5.2
     */
    public function transferItems(array $itemIds, int $toWarehouseId): int
    {
        return ProductItem::whereIn('id', $itemIds)->update([
            'warehouse_id' => $toWarehouseId,
            'updated_at' => now(),
        ]);
    }

    /**
     * Adjust item quantity
     * Requirements: 6.1
     */
    public function adjustQuantity(int $itemId, int $newQuantity): bool
    {
        $item = ProductItem::findOrFail($itemId);
        $item->quantity = $newQuantity;
        
        // If quantity is 0, mark as out of stock
        if ($newQuantity <= 0) {
            $item->status = ProductItem::STATUS_OUT_OF_STOCK;
        }
        
        return $item->save();
    }

    /**
     * Get total quantity for a product across all warehouses
     * Requirements: 7.1
     */
    public function getTotalQuantity(int $productId): int
    {
        return ProductItem::where('product_id', $productId)
            ->where('status', ProductItem::STATUS_IN_STOCK)
            ->sum('quantity');
    }

    /**
     * Get quantity for a product in specific warehouse
     * Requirements: 7.2
     */
    public function getWarehouseQuantity(int $productId, int $warehouseId): int
    {
        return ProductItem::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', ProductItem::STATUS_IN_STOCK)
            ->sum('quantity');
    }
}
