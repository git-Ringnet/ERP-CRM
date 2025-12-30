<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with summary statistics and recent activities.
     * 
     * Requirements: 6.1, 6.5
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $filterType = $request->get('filter', 'month'); // week, month, quarter, year, custom
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        // If date_from or date_to is provided, use custom filter
        if ($dateFrom || $dateTo) {
            $filterType = 'custom';
        }
        
        // Calculate date range based on filter type
        $dateRange = $this->getDateRange($filterType, $dateFrom, $dateTo);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];
        
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
        
        // Transaction statistics (filtered by date range)
        $totalTransactions = DB::table('inventory_transactions')
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
        $pendingTransactions = DB::table('inventory_transactions')
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'pending')
            ->count();
        $todayTransactions = DB::table('inventory_transactions')
            ->whereDate('created_at', today())
            ->count();
        
        // Period statistics (filtered)
        $periodImports = DB::table('inventory_transactions')
            ->where('type', 'import')
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
        $periodExports = DB::table('inventory_transactions')
            ->where('type', 'export')
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
        $periodTransfers = DB::table('inventory_transactions')
            ->where('type', 'transfer')
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
        
        // Transaction by type for pie chart (filtered)
        $transactionsByType = [
            'import' => DB::table('inventory_transactions')
                ->where('type', 'import')
                ->whereBetween('date', [$startDate, $endDate])
                ->count(),
            'export' => DB::table('inventory_transactions')
                ->where('type', 'export')
                ->whereBetween('date', [$startDate, $endDate])
                ->count(),
            'transfer' => DB::table('inventory_transactions')
                ->where('type', 'transfer')
                ->whereBetween('date', [$startDate, $endDate])
                ->count(),
        ];
        
        // Transactions for line chart (based on date range)
        $transactionsChart = $this->getTransactionsChartData($startDate, $endDate);
        
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
            'periodImports',
            'periodExports',
            'periodTransfers',
            'transactionsByType',
            'transactionsChart',
            'stockByWarehouse',
            'totalActivities',
            'totalActivityPages',
            'page',
            'filterType',
            'startDate',
            'endDate'
        ));
    }
    
    /**
     * Get date range based on filter type
     */
    private function getDateRange($filterType, $dateFrom = null, $dateTo = null)
    {
        $now = Carbon::now();
        
        switch ($filterType) {
            case 'today':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay()
                ];
            case 'week':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek()
                ];
            case 'month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];
            case 'quarter':
                return [
                    'start' => $now->copy()->startOfQuarter(),
                    'end' => $now->copy()->endOfQuarter()
                ];
            case 'year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear()
                ];
            case 'custom':
                return [
                    'start' => $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : $now->copy()->startOfMonth(),
                    'end' => $dateTo ? Carbon::parse($dateTo)->endOfDay() : $now->copy()->endOfMonth()
                ];
            default:
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth()
                ];
        }
    }
    
    /**
     * Get transactions chart data based on date range
     */
    private function getTransactionsChartData($startDate, $endDate)
    {
        $diffDays = $startDate->diffInDays($endDate);
        $data = [];
        
        if ($diffDays <= 7) {
            // Show daily data
            for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                $data[$dateStr] = DB::table('inventory_transactions')
                    ->whereDate('date', $dateStr)
                    ->count();
            }
        } elseif ($diffDays <= 31) {
            // Show daily data for up to 31 days
            for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                $data[$dateStr] = DB::table('inventory_transactions')
                    ->whereDate('date', $dateStr)
                    ->count();
            }
        } elseif ($diffDays <= 90) {
            // Show weekly data
            for ($date = $startDate->copy(); $date <= $endDate; $date->addWeek()) {
                $weekEnd = $date->copy()->endOfWeek();
                if ($weekEnd > $endDate) $weekEnd = $endDate->copy();
                
                $label = 'T' . $date->weekOfYear;
                $data[$label] = DB::table('inventory_transactions')
                    ->whereBetween('date', [$date->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                    ->count();
            }
        } else {
            // Show monthly data
            for ($date = $startDate->copy()->startOfMonth(); $date <= $endDate; $date->addMonth()) {
                $monthEnd = $date->copy()->endOfMonth();
                if ($monthEnd > $endDate) $monthEnd = $endDate->copy();
                
                $label = 'T' . $date->month . '/' . $date->format('y');
                $data[$label] = DB::table('inventory_transactions')
                    ->whereBetween('date', [$date->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                    ->count();
            }
        }
        
        return $data;
    }
}
