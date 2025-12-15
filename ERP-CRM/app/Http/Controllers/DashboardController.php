<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with summary statistics and recent activities.
     * 
     * Requirements: 6.1, 6.5
     */
    public function index()
    {
        // Calculate summary counts
        $totalCustomers = DB::table('customers')->count();
        $totalSuppliers = DB::table('suppliers')->count();
        $totalEmployees = DB::table('users')->whereNotNull('employee_code')->count();
        $totalProducts = DB::table('products')->count();

        // Get customer distribution by type
        $customersByType = DB::table('customers')
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->type => (int) $item->total];
            })
            ->toArray();

        // Ensure both types exist
        $customersByType = array_merge(['normal' => 0, 'vip' => 0], $customersByType);

        // Get employee distribution by department
        $employeesByDepartment = DB::table('users')
            ->whereNotNull('employee_code')
            ->select('department', DB::raw('count(*) as total'))
            ->groupBy('department')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->department => (int) $item->total];
            })
            ->toArray();

        // Get product distribution by category (replaced management_type)
        $productsByType = DB::table('products')
            ->select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->category => (int) $item->total];
            })
            ->toArray();
        
        // Additional statistics
        $vipCustomers = $customersByType['vip'] ?? 0;
        $normalCustomers = $customersByType['normal'] ?? 0;
        $vipPercentage = $totalCustomers > 0 ? round(($vipCustomers / $totalCustomers) * 100, 1) : 0;
        
        $activeEmployees = DB::table('users')
            ->whereNotNull('employee_code')
            ->where('status', 'active')
            ->count();
            
        // Low stock products - count products with low total quantity
        // Since stock is now tracked in product_items, we need to calculate differently
        $lowStockProducts = 0; // TODO: Implement low stock logic with new product_items structure

        // Additional inventory statistics
        $totalWarehouses = DB::table('warehouses')->count();
        $totalInventoryValue = DB::table('inventories')->sum(DB::raw('stock * avg_cost'));
        $totalProductItems = DB::table('product_items')->where('status', 'in_stock')->count();
        
        // Transaction statistics
        $totalTransactions = DB::table('inventory_transactions')->count();
        $pendingTransactions = DB::table('inventory_transactions')->where('status', 'pending')->count();
        $todayTransactions = DB::table('inventory_transactions')
            ->whereDate('created_at', today())
            ->count();
        
        // This month statistics
        $thisMonthImports = DB::table('inventory_transactions')
            ->where('type', 'import')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();
        $thisMonthExports = DB::table('inventory_transactions')
            ->where('type', 'export')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();
        $thisMonthTransfers = DB::table('inventory_transactions')
            ->where('type', 'transfer')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();
        
        // Transaction by type for pie chart
        $transactionsByType = [
            'import' => DB::table('inventory_transactions')->where('type', 'import')->count(),
            'export' => DB::table('inventory_transactions')->where('type', 'export')->count(),
            'transfer' => DB::table('inventory_transactions')->where('type', 'transfer')->count(),
        ];
        
        // Transactions last 7 days for line chart
        $transactionsLast7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $transactionsLast7Days[$date] = DB::table('inventory_transactions')
                ->whereDate('date', $date)
                ->count();
        }
        
        // Stock by warehouse for bar chart
        $stockByWarehouse = DB::table('inventories')
            ->join('warehouses', 'inventories.warehouse_id', '=', 'warehouses.id')
            ->select('warehouses.name', DB::raw('SUM(inventories.stock) as total_stock'))
            ->groupBy('warehouses.id', 'warehouses.name')
            ->orderBy('total_stock', 'desc')
            ->limit(5)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->name => (int) $item->total_stock];
            })
            ->toArray();

        // Get recent activities with pagination (5 per page)
        $page = request()->get('activity_page', 1);
        $perPage = 5;
        
        $recentCustomers = DB::table('customers')
            ->select('name', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'customer',
                    'name' => $item->name,
                    'created_at' => $item->created_at
                ];
            });

        $recentSuppliers = DB::table('suppliers')
            ->select('name', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'supplier',
                    'name' => $item->name,
                    'created_at' => $item->created_at
                ];
            });

        $recentEmployees = DB::table('users')
            ->whereNotNull('employee_code')
            ->select('name', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'employee',
                    'name' => $item->name,
                    'created_at' => $item->created_at
                ];
            });

        $recentProducts = DB::table('products')
            ->select('name', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'product',
                    'name' => $item->name,
                    'created_at' => $item->created_at
                ];
            });
        
        $recentTransactions = DB::table('inventory_transactions')
            ->select('code as name', 'type', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'transaction_' . $item->type,
                    'name' => $item->name,
                    'created_at' => $item->created_at
                ];
            });

        // Merge and sort all recent activities
        $allActivities = collect()
            ->merge($recentCustomers)
            ->merge($recentSuppliers)
            ->merge($recentEmployees)
            ->merge($recentProducts)
            ->merge($recentTransactions)
            ->sortByDesc('created_at')
            ->values();
        
        $totalActivities = $allActivities->count();
        $totalActivityPages = ceil($totalActivities / $perPage);
        $recentActivities = $allActivities->forPage($page, $perPage);

        return view('dashboard.index', compact(
            'totalCustomers',
            'totalSuppliers',
            'totalEmployees',
            'totalProducts',
            'customersByType',
            'employeesByDepartment',
            'recentActivities',
            'vipCustomers',
            'normalCustomers',
            'vipPercentage',
            'activeEmployees',
            'totalWarehouses',
            'totalInventoryValue',
            'totalProductItems',
            'totalTransactions',
            'pendingTransactions',
            'todayTransactions',
            'thisMonthImports',
            'thisMonthExports',
            'thisMonthTransfers',
            'transactionsByType',
            'transactionsLast7Days',
            'stockByWarehouse',
            'totalActivities',
            'totalActivityPages',
            'page'
        ));
    }
}
