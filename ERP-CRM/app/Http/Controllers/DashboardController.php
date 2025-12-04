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

        // Get product distribution by management type
        $productsByType = DB::table('products')
            ->select('management_type', DB::raw('count(*) as total'))
            ->groupBy('management_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->management_type => (int) $item->total];
            })
            ->toArray();

        // Ensure all types exist
        $productsByType = array_merge(['normal' => 0, 'serial' => 0, 'lot' => 0], $productsByType);
        
        // Additional statistics
        $vipCustomers = $customersByType['vip'] ?? 0;
        $normalCustomers = $customersByType['normal'] ?? 0;
        $vipPercentage = $totalCustomers > 0 ? round(($vipCustomers / $totalCustomers) * 100, 1) : 0;
        
        $activeEmployees = DB::table('users')
            ->whereNotNull('employee_code')
            ->where('status', 'active')
            ->count();
            
        $lowStockProducts = DB::table('products')
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        // Get recent activities (last 10 records from each entity)
        $recentCustomers = DB::table('customers')
            ->select('name', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
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
            ->limit(5)
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
            ->limit(5)
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
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => 'product',
                    'name' => $item->name,
                    'created_at' => $item->created_at
                ];
            });

        // Merge and sort all recent activities
        $recentActivities = collect()
            ->merge($recentCustomers)
            ->merge($recentSuppliers)
            ->merge($recentEmployees)
            ->merge($recentProducts)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();

        return view('dashboard.index', compact(
            'totalCustomers',
            'totalSuppliers',
            'totalEmployees',
            'totalProducts',
            'customersByType',
            'employeesByDepartment',
            'productsByType',
            'recentActivities',
            'vipCustomers',
            'normalCustomers',
            'vipPercentage',
            'activeEmployees',
            'lowStockProducts'
        ));
    }
}
