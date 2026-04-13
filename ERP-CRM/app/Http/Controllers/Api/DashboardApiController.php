<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardApiController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Get summary statistics for the general dashboard.
     */
    public function summary(Request $request)
    {
        // Summary counts
        $totalCustomers = DB::table('customers')->count();
        $totalSuppliers = DB::table('suppliers')->count();
        $totalEmployees = DB::table('users')->whereNotNull('employee_code')->count();
        $totalProducts = DB::table('products')->count();

        // Recent activities (simplified for API)
        $recentActivities = DB::table('customers')
            ->select('name', 'created_at', DB::raw("'customer' as type"))
            ->union(
                DB::table('suppliers')->select('name', 'created_at', DB::raw("'supplier' as type"))
            )
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_customers' => $totalCustomers,
            'total_suppliers' => $totalSuppliers,
            'total_employees' => $totalEmployees,
            'total_products' => $totalProducts,
            'recent_activities' => $recentActivities,
            'status' => 'success'
        ]);
    }

    /**
     * Get detailed business activity data.
     */
    public function businessActivity(Request $request)
    {
        $periodType = $request->get('period_type', 'month');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Parse dates if not provided
        if (!$startDate || !$endDate) {
            $dates = $this->parsePredefinedPeriod($periodType);
            $startDate = $dates['start'];
            $endDate = $dates['end'];
        }

        $data = $this->dashboardService->getDashboardData([
            'period_type' => $periodType,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        // Map data to match the keys expected by the user's test
        return response()->json([
            'Metrics' => $data['metrics'],
            'Charts' => $data['charts'],
            'Analysis' => [
                'sales' => $data['sales_analysis'],
                'purchase' => $data['purchase_analysis'],
                'inventory' => $data['inventory_analysis'],
            ],
            'Filter Configuration' => $data['filters'],
            'status' => 'success'
        ]);
    }

    private function parsePredefinedPeriod(string $periodType): array
    {
        $now = Carbon::now();

        return match ($periodType) {
            'today' => [
                'start' => $now->copy()->startOfDay()->format('Y-m-d'),
                'end' => $now->copy()->endOfDay()->format('Y-m-d'),
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek()->format('Y-m-d'),
                'end' => $now->copy()->endOfWeek()->format('Y-m-d'),
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $now->copy()->endOfMonth()->format('Y-m-d'),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear()->format('Y-m-d'),
                'end' => $now->copy()->endOfYear()->format('Y-m-d'),
            ],
            default => [
                'start' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $now->copy()->endOfMonth()->format('Y-m-d'),
            ],
        };
    }
}
