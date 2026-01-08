<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Inventory;
use App\Models\Import;
use App\Models\Export;
use App\Models\Transfer;
use App\Models\DamagedGood;
use App\Models\Product;
use App\Exports\InventorySummaryExport;
use App\Exports\TransactionReportExport;
use App\Exports\DamagedGoodsReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
        // Build queries for each transaction type
        $importsQuery = Import::with(['warehouse', 'employee', 'items.product'])
            ->select('id', 'code', 'warehouse_id', 'date', 'employee_id', 'total_qty', 'status', 'note', 'created_at')
            ->selectRaw("'import' as type, NULL as to_warehouse_id");
        
        $exportsQuery = Export::with(['warehouse', 'employee', 'items.product'])
            ->select('id', 'code', 'warehouse_id', 'date', 'employee_id', 'total_qty', 'status', 'note', 'created_at')
            ->selectRaw("'export' as type, NULL as to_warehouse_id");
        
        $transfersQuery = Transfer::with(['fromWarehouse', 'toWarehouse', 'employee', 'items.product'])
            ->select('id', 'code', 'from_warehouse_id as warehouse_id', 'date', 'employee_id', 'total_qty', 'status', 'note', 'created_at', 'to_warehouse_id')
            ->selectRaw("'transfer' as type");

        // Filter by type
        if ($request->filled('type')) {
            if ($request->type === 'import') {
                $exportsQuery = null;
                $transfersQuery = null;
            } elseif ($request->type === 'export') {
                $importsQuery = null;
                $transfersQuery = null;
            } elseif ($request->type === 'transfer') {
                $importsQuery = null;
                $exportsQuery = null;
            }
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            if ($importsQuery) {
                $importsQuery->where('warehouse_id', $request->warehouse_id);
            }
            if ($exportsQuery) {
                $exportsQuery->where('warehouse_id', $request->warehouse_id);
            }
            if ($transfersQuery) {
                $transfersQuery->where(function ($q) use ($request) {
                    $q->where('from_warehouse_id', $request->warehouse_id)
                      ->orWhere('to_warehouse_id', $request->warehouse_id);
                });
            }
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            if ($importsQuery) {
                $importsQuery->whereBetween('date', [$request->start_date, $request->end_date]);
            }
            if ($exportsQuery) {
                $exportsQuery->whereBetween('date', [$request->start_date, $request->end_date]);
            }
            if ($transfersQuery) {
                $transfersQuery->whereBetween('date', [$request->start_date, $request->end_date]);
            }
        }

        // Get all transactions from each table
        $imports = $importsQuery ? $importsQuery->get()->map(function ($item) {
            $item->type = 'import';
            return $item;
        }) : collect();
        
        $exports = $exportsQuery ? $exportsQuery->get()->map(function ($item) {
            $item->type = 'export';
            return $item;
        }) : collect();
        
        $transfers = $transfersQuery ? $transfersQuery->get()->map(function ($item) {
            $item->type = 'transfer';
            $item->warehouse = $item->fromWarehouse;
            return $item;
        }) : collect();

        // Merge and sort all transactions
        $allTransactions = $imports->concat($exports)->concat($transfers)->sortByDesc('date');

        // Calculate summary statistics
        $totalTransactions = $allTransactions->count();
        $importCount = $imports->count();
        $exportCount = $exports->count();
        $transferCount = $transfers->count();
        $totalQuantity = $allTransactions->sum('total_qty');

        // Group by type
        $byType = collect([
            'import' => ['count' => $importCount, 'total_qty' => $imports->sum('total_qty')],
            'export' => ['count' => $exportCount, 'total_qty' => $exports->sum('total_qty')],
            'transfer' => ['count' => $transferCount, 'total_qty' => $transfers->sum('total_qty')],
        ]);

        // Group by date (daily)
        $byDate = $allTransactions->groupBy(function ($item) {
            return $item->date instanceof \Carbon\Carbon ? $item->date->format('Y-m-d') : $item->date;
        })->map(function ($items) {
            return [
                'count' => $items->count(),
                'total_qty' => $items->sum('total_qty'),
            ];
        })->sortKeys();

        // Paginate transactions for detail table
        $page = $request->get('page', 1);
        $perPage = 10;
        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $allTransactions->forPage($page, $perPage),
            $allTransactions->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

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

    /**
     * Export inventory summary to Excel
     */
    public function exportInventorySummary(Request $request)
    {
        $query = Inventory::with(['product', 'warehouse']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->whereRaw('stock <= min_stock');
            } elseif ($request->stock_status === 'normal') {
                $query->whereRaw('stock > min_stock');
            }
        }

        $inventories = $query->get();
        $filename = 'bao-cao-ton-kho-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new InventorySummaryExport($inventories), $filename);
    }

    /**
     * Export transaction report to Excel
     */
    public function exportTransactionReport(Request $request)
    {
        // Build queries for each transaction type
        $importsQuery = Import::with(['warehouse', 'employee']);
        $exportsQuery = Export::with(['warehouse', 'employee']);
        $transfersQuery = Transfer::with(['fromWarehouse', 'toWarehouse', 'employee']);

        // Filter by type
        if ($request->filled('type')) {
            if ($request->type === 'import') {
                $exportsQuery = null;
                $transfersQuery = null;
            } elseif ($request->type === 'export') {
                $importsQuery = null;
                $transfersQuery = null;
            } elseif ($request->type === 'transfer') {
                $importsQuery = null;
                $exportsQuery = null;
            }
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            if ($importsQuery) {
                $importsQuery->where('warehouse_id', $request->warehouse_id);
            }
            if ($exportsQuery) {
                $exportsQuery->where('warehouse_id', $request->warehouse_id);
            }
            if ($transfersQuery) {
                $transfersQuery->where(function ($q) use ($request) {
                    $q->where('from_warehouse_id', $request->warehouse_id)
                      ->orWhere('to_warehouse_id', $request->warehouse_id);
                });
            }
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            if ($importsQuery) {
                $importsQuery->whereBetween('date', [$request->start_date, $request->end_date]);
            }
            if ($exportsQuery) {
                $exportsQuery->whereBetween('date', [$request->start_date, $request->end_date]);
            }
            if ($transfersQuery) {
                $transfersQuery->whereBetween('date', [$request->start_date, $request->end_date]);
            }
        }

        // Get all transactions
        $imports = $importsQuery ? $importsQuery->get()->map(function ($item) {
            $item->type = 'import';
            return $item;
        }) : collect();
        
        $exports = $exportsQuery ? $exportsQuery->get()->map(function ($item) {
            $item->type = 'export';
            return $item;
        }) : collect();
        
        $transfers = $transfersQuery ? $transfersQuery->get()->map(function ($item) {
            $item->type = 'transfer';
            $item->warehouse = $item->fromWarehouse;
            return $item;
        }) : collect();

        $transactions = $imports->concat($exports)->concat($transfers)->sortByDesc('date');
        $filename = 'bao-cao-xuat-nhap-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new TransactionReportExport($transactions), $filename);
    }

    /**
     * Export damaged goods report to Excel
     */
    public function exportDamagedGoodsReport(Request $request)
    {
        $query = DamagedGood::with(['product', 'discoveredBy']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('discovery_date', [$request->start_date, $request->end_date]);
        }

        $damagedGoods = $query->latest('discovery_date')->get();
        $filename = 'bao-cao-hang-hong-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new DamagedGoodsReportExport($damagedGoods), $filename);
    }
}
