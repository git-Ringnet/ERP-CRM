<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class SaleReportController extends Controller
{
    /**
     * Display the sales report dashboard.
     */
    public function index(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $customerId = $request->input('customer_id');
        $productId = $request->input('product_id');

        // Summary statistics
        $stats = $this->getSummaryStats($dateFrom, $dateTo, $customerId, $productId);

        // Customer report
        $customerReport = $this->getCustomerReport($dateFrom, $dateTo, $customerId);

        // Product report
        $productReport = $this->getProductReport($dateFrom, $dateTo, $productId);

        // Monthly report
        $monthlyReport = $this->getMonthlyReport($dateFrom, $dateTo);

        // Profit analysis
        $profitAnalysis = $this->getProfitAnalysis($dateFrom, $dateTo);

        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('sale-reports.index', compact(
            'stats', 'customerReport', 'productReport', 'monthlyReport',
            'profitAnalysis', 'customers', 'products',
            'dateFrom', 'dateTo', 'customerId', 'productId'
        ));
    }

    private function getSummaryStats($dateFrom, $dateTo, $customerId = null, $productId = null): array
    {
        $query = Sale::whereBetween('date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled'); // Exclude cancelled orders

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($productId) {
            $query->whereHas('items', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }

        // Clone query for sums to avoid issues if we needed to group (not needed here but good practice)
        
        $totalOrders = $query->count();
        $totalRevenue = $query->sum('total');
        $totalCost = $query->sum(DB::raw('cost + (SELECT COALESCE(SUM(cost_total), 0) FROM sale_items WHERE sale_items.sale_id = sales.id)'));
        // Note: The above subquery for cost might be heavy. 
        // Alternatively, since Sale model has 'margin', we can use that if it's reliable.
        // Let's rely on the 'margin' column which is calculated and saved on sale update.
        
        $totalMargin = $query->sum('margin');
        
        // Calculate total cost as Revenue - Margin
        $totalCalculatedCost = $totalRevenue - $totalMargin;

        $marginPercent = $totalRevenue > 0 ? ($totalMargin / $totalRevenue) * 100 : 0;

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue ?? 0,
            'total_cost' => $totalCalculatedCost ?? 0,
            'total_profit' => $totalMargin ?? 0,
            'margin_percent' => round($marginPercent, 1),
        ];
    }

    private function getCustomerReport($dateFrom, $dateTo, $customerId = null): array
    {
        $query = Sale::select(
                'customer_id',
                'customer_name',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('SUM(margin) as total_profit')
            )
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->groupBy('customer_id', 'customer_name');

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $results = $query->orderByDesc('total_revenue')->get();

        return $results->map(function ($item) {
            $marginPercent = $item->total_revenue > 0 
                ? ($item->total_profit / $item->total_revenue) * 100 
                : 0;

            return [
                'customer' => $item->customer_name,
                'order_count' => $item->order_count,
                'total_revenue' => $item->total_revenue,
                'total_profit' => $item->total_profit,
                'margin_percent' => round($marginPercent, 1),
            ];
        })->toArray();
    }

    private function getProductReport($dateFrom, $dateTo, $productId = null): array
    {
        $query = SaleItem::select(
                'product_id',
                'product_name',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('SUM(total - cost_total) as total_profit') // Approximate profit per item (ignoring shared sale expenses)
            )
            ->whereHas('sale', function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'cancelled');
            })
            ->groupBy('product_id', 'product_name');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $results = $query->orderByDesc('total_revenue')->get();

        return $results->map(function ($item) {
            $marginPercent = $item->total_revenue > 0 
                ? ($item->total_profit / $item->total_revenue) * 100 
                : 0;

            return [
                'product' => $item->product_name,
                'total_quantity' => $item->total_quantity,
                'total_revenue' => $item->total_revenue,
                'total_profit' => $item->total_profit, // Note: This is Gross Profit per item, excludes sale-level expenses like shipping
                'margin_percent' => round($marginPercent, 1),
            ];
        })->toArray();
    }

    private function getMonthlyReport($dateFrom, $dateTo): array
    {
        $results = Sale::select(
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('SUM(margin) as total_profit')
            )
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        $report = [];
        $previousRevenue = null;

        foreach ($results as $item) {
            $growth = null;
            if ($previousRevenue !== null && $previousRevenue > 0) {
                // Determine growth compared to NEXT row (which is previous month due to desc order)
                // Actually to do this correctly in a loop, we usually process asc or look ahead.
                // Let's just keep it simple or fix logic if needed. 
                // Since it is ordered DESC, previous loop iteration was the NEXT month.
                // So comparison is tricky here without reordering.
                // Let's simpler: just calculate margin %
            }

            $marginPercent = $item->total_revenue > 0 
                ? ($item->total_profit / $item->total_revenue) * 100 
                : 0;

            $report[] = [
                'month' => $item->month,
                'order_count' => $item->order_count,
                'total_revenue' => $item->total_revenue,
                'total_profit' => $item->total_profit,
                'margin_percent' => round($marginPercent, 1),
            ];
        }

        return $report;
    }

    private function getProfitAnalysis($dateFrom, $dateTo): array
    {
        $totals = Sale::whereBetween('date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('
                SUM(subtotal) as subtotal,
                SUM(discount) as discount_percent_sum, -- This is meaningless
                SUM(total) as total_revenue,
                SUM(cost) as total_expenses,
                SUM(margin) as total_profit
            ')
            ->first();

        // Calculate COGS (Cost of Goods Sold)
        // Profit = Revenue - COGS - Expenses
        // => COGS = Revenue - Profit - Expenses
        
        $revenue = $totals->total_revenue ?? 0;
        $profit = $totals->total_profit ?? 0;
        $expenses = $totals->total_expenses ?? 0;
        $cogs = $revenue - $profit - $expenses;
        
        $base = $revenue > 0 ? $revenue : 1;

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'expenses' => $expenses,
            'profit' => $profit,
            'breakdown' => [
                ['name' => 'Giá vốn hàng bán (COGS)', 'value' => $cogs, 'rate' => round(($cogs / $base) * 100, 1), 'color' => 'text-blue-600'],
                ['name' => 'Chi phí bán hàng', 'value' => $expenses, 'rate' => round(($expenses / $base) * 100, 1), 'color' => 'text-yellow-600'],
                ['name' => 'Lợi nhuận ròng', 'value' => $profit, 'rate' => round(($profit / $base) * 100, 1), 'color' => 'text-green-600'],
            ]
        ];
    }

    public function export(Request $request)
    {
        // Export logic here (Reuse existing export or create new one)
        return redirect()->back()->with('warning', 'Chức năng xuất báo cáo đang được phát triển.');
    }
}
