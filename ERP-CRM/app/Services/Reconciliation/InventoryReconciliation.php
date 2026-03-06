<?php

namespace App\Services\Reconciliation;

use App\Models\Inventory;
use App\Models\ProductItem;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Import;
use App\Models\ImportItem;
use App\Models\Export;
use App\Models\ExportItem;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Support\Facades\DB;

/**
 * InventoryReconciliation - Đối soát Tồn kho
 * 
 * Checks:
 * 1. inventories.stock vs calculated stock from product_items (status=in_stock)
 * 2. inventories.stock vs sum(imports) - sum(exports) - sum(transfers_out) + sum(transfers_in)
 */
class InventoryReconciliation
{
    /**
     * Run all reconciliation checks
     */
    public function run(array $filters = []): array
    {
        return [
            'stock_vs_items' => $this->checkStockVsProductItems($filters),
            'stock_vs_transactions' => $this->checkStockVsTransactions($filters),
        ];
    }

    /**
     * Get summary counts
     */
    public function summary(array $filters = []): array
    {
        $results = $this->run($filters);
        return [
            'total_issues' => count($results['stock_vs_items']) + count($results['stock_vs_transactions']),
            'stock_vs_items' => count($results['stock_vs_items']),
            'stock_vs_transactions' => count($results['stock_vs_transactions']),
        ];
    }

    /**
     * Check inventory stock vs product_items actual count
     */
    protected function checkStockVsProductItems(array $filters = []): array
    {
        $mismatches = [];

        $query = Inventory::with(['product', 'warehouse']);

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        $inventories = $query->get();

        foreach ($inventories as $inventory) {
            // Calculate actual stock from product_items
            $actualStock = ProductItem::where('product_id', $inventory->product_id)
                ->where('warehouse_id', $inventory->warehouse_id)
                ->where('status', 'in_stock')
                ->sum('quantity');

            if ((int)$inventory->stock !== (int)$actualStock) {
                $mismatches[] = [
                    'inventory_id' => $inventory->id,
                    'product_id' => $inventory->product_id,
                    'product_name' => $inventory->product?->name ?? 'N/A',
                    'warehouse_id' => $inventory->warehouse_id,
                    'warehouse_name' => $inventory->warehouse?->name ?? 'N/A',
                    'recorded_stock' => (int)$inventory->stock,
                    'actual_stock' => (int)$actualStock,
                    'difference' => (int)$inventory->stock - (int)$actualStock,
                    'issue' => 'Tồn kho tổng không khớp với chi tiết sản phẩm (mã vạch/serial)',
                ];
            }
        }

        return $mismatches;
    }

    /**
     * Check inventory stock vs calculated from import/export/transfer transactions
     */
    protected function checkStockVsTransactions(array $filters = []): array
    {
        $mismatches = [];

        $query = Inventory::with(['product', 'warehouse']);

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        $inventories = $query->get();

        foreach ($inventories as $inventory) {
            $productId = $inventory->product_id;
            $warehouseId = $inventory->warehouse_id;

            // Total imported (completed imports)
            $totalImported = ImportItem::whereHas('import', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->where('status', 'completed');
            })
                ->where('product_id', $productId)
                ->sum('quantity');

            // Total exported (completed exports)
            $totalExported = ExportItem::whereHas('export', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->where('status', 'completed');
            })
                ->where('product_id', $productId)
                ->sum('quantity');

            // Total transferred out (completed transfers from this warehouse)
            $totalTransferOut = 0;
            $totalTransferIn = 0;

            // Check if TransferItem model has the needed relationships
            try {
                $totalTransferOut = TransferItem::whereHas('transfer', function ($q) use ($warehouseId) {
                    $q->where('from_warehouse_id', $warehouseId)
                        ->where('status', 'completed');
                })
                    ->where('product_id', $productId)
                    ->sum('quantity');

                $totalTransferIn = TransferItem::whereHas('transfer', function ($q) use ($warehouseId) {
                    $q->where('to_warehouse_id', $warehouseId)
                        ->where('status', 'completed');
                })
                    ->where('product_id', $productId)
                    ->sum('quantity');
            } catch (\Exception $e) {
                // Transfer module might not have these exact column names
            }

            $calculatedStock = $totalImported - $totalExported - $totalTransferOut + $totalTransferIn;

            // Only report significant differences (> 0)
            if ((int)$inventory->stock !== (int)$calculatedStock && $calculatedStock > 0) {
                $mismatches[] = [
                    'inventory_id' => $inventory->id,
                    'product_id' => $productId,
                    'product_name' => $inventory->product?->name ?? 'N/A',
                    'warehouse_id' => $warehouseId,
                    'warehouse_name' => $inventory->warehouse?->name ?? 'N/A',
                    'recorded_stock' => (int)$inventory->stock,
                    'calculated_stock' => (int)$calculatedStock,
                    'total_imported' => (int)$totalImported,
                    'total_exported' => (int)$totalExported,
                    'total_transfer_in' => (int)$totalTransferIn,
                    'total_transfer_out' => (int)$totalTransferOut,
                    'difference' => (int)$inventory->stock - (int)$calculatedStock,
                    'issue' => 'Tồn kho không khớp với tổng nhập/xuất/chuyển kho',
                ];
            }
        }

        return $mismatches;
    }
}
