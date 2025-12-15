<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\DamagedGood;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function inventorySummary(Request $request)
    {
        $query = Inventory::with(['product', 'warehouse']);

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->whereRaw('stock <= min_stock');
            } elseif ($request->stock_status === 'normal') {
                $query->whereRaw('stock > min_stock');
            }
        }

        $inventories = $query->get();

        // Calculate summary statistics
        $totalProducts = $inventories->count();
        $totalStock = $inventories->sum('stock');
        $totalValue = $inventories->sum(function ($item) {
            return $item->stock * $item->avg_cost;
        });
        $lowStockCount = $inventories->filter(function ($item) {
            return $item->stock <= $item->min_stock;
        })->count();

        // Group by warehouse
        $byWarehouse = $inventories->groupBy('warehouse_id')->map(function ($items) {
            return [
                'warehouse' => $items->first()->warehouse,
                'product_count' => $items->count(),
                'total_stock' => $items->sum('stock'),
                'total_value' => $items->sum(function ($item) {
                    return $item->stock * $item->avg_cost;
                }),
            ];
        });

        $warehouses = Warehouse::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('reports.inventory-summary', compact(
            'inventories',
            'totalProducts',
            'totalStock',
            'totalValue',
            'lowStockCount',
            'byWarehouse',
            'warehouses',
            'products'
        ));
    }

    public function transactionReport(Request $request)
    {
        $query = InventoryTransaction::with(['warehouse', 'toWarehouse', 'employee', 'items.product']);

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id)
                  ->orWhere('to_warehouse_id', $request->warehouse_id);
            });
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        // Get all for statistics (without pagination)
        $allTransactions = (clone $query)->latest('date')->get();

        // Calculate summary statistics
        $totalTransactions = $allTransactions->count();
        $importCount = $allTransactions->where('type', 'import')->count();
        $exportCount = $allTransactions->where('type', 'export')->count();
        $transferCount = $allTransactions->where('type', 'transfer')->count();
        $totalQuantity = $allTransactions->sum('total_qty');

        // Group by type
        $byType = $allTransactions->groupBy('type')->map(function ($items, $type) {
            return [
                'count' => $items->count(),
                'total_qty' => $items->sum('total_qty'),
            ];
        });

        // Group by date (daily)
        $byDate = $allTransactions->groupBy(function ($item) {
            return $item->date->format('Y-m-d');
        })->map(function ($items) {
            return [
                'count' => $items->count(),
                'total_qty' => $items->sum('total_qty'),
            ];
        })->sortKeys();

        // Paginate transactions for detail table (10 per page)
        $transactions = $query->latest('date')->paginate(10)->withQueryString();

        $warehouses = Warehouse::orderBy('name')->get();

        return view('reports.transaction-report', compact(
            'transactions',
            'totalTransactions',
            'importCount',
            'exportCount',
            'transferCount',
            'totalQuantity',
            'byType',
            'byDate',
            'warehouses'
        ));
    }

    public function damagedGoodsReport(Request $request)
    {
        $query = DamagedGood::with(['product', 'discoveredBy']);

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('discovery_date', [$request->start_date, $request->end_date]);
        }

        $damagedGoods = $query->latest('discovery_date')->get();

        // Calculate summary statistics
        $totalRecords = $damagedGoods->count();
        $damagedCount = $damagedGoods->where('type', 'damaged')->count();
        $liquidationCount = $damagedGoods->where('type', 'liquidation')->count();
        $totalOriginalValue = $damagedGoods->sum('original_value');
        $totalRecoveryValue = $damagedGoods->sum('recovery_value');
        $totalLoss = $totalOriginalValue - $totalRecoveryValue;
        $recoveryRate = $totalOriginalValue > 0 ? ($totalRecoveryValue / $totalOriginalValue) * 100 : 0;

        // Group by type
        $byType = $damagedGoods->groupBy('type')->map(function ($items) {
            $originalValue = $items->sum('original_value');
            $recoveryValue = $items->sum('recovery_value');
            return [
                'count' => $items->count(),
                'original_value' => $originalValue,
                'recovery_value' => $recoveryValue,
                'loss' => $originalValue - $recoveryValue,
            ];
        });

        // Group by status
        $byStatus = $damagedGoods->groupBy('status')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total_loss' => $items->sum(function ($item) {
                    return $item->original_value - $item->recovery_value;
                }),
            ];
        });

        // Top damaged products
        $topProducts = $damagedGoods->groupBy('product_id')->map(function ($items) {
            return [
                'product' => $items->first()->product,
                'count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
                'total_loss' => $items->sum(function ($item) {
                    return $item->original_value - $item->recovery_value;
                }),
            ];
        })->sortByDesc('total_loss')->take(10);

        return view('reports.damaged-goods-report', compact(
            'damagedGoods',
            'totalRecords',
            'damagedCount',
            'liquidationCount',
            'totalOriginalValue',
            'totalRecoveryValue',
            'totalLoss',
            'recoveryRate',
            'byType',
            'byStatus',
            'topProducts'
        ));
    }
}
