<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\PurchaseOrder;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BusinessReportController extends Controller
{
    /**
     * Display the business overview report.
     */
    public function index(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // 1. Summary Stats
        $salesData = Sale::whereBetween('date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('SUM(total) as revenue, SUM(margin) as profit')
            ->first();

        $purchaseData = PurchaseOrder::whereBetween('order_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $revenue = $salesData->revenue ?? 0;
        $profit = $salesData->profit ?? 0;
        $purchaseCost = $purchaseData ?? 0;
        $marginPercent = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        $stats = [
            'total_revenue' => $revenue,
            'total_purchase' => $purchaseCost,
            'total_profit' => $profit,
            'margin_percent' => round($marginPercent, 1),
        ];

        // 2. Recent Orders for Tracking
        $recentSales = Sale::with('customer')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        $recentPurchases = PurchaseOrder::with('supplier')
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->orderBy('order_date', 'desc')
            ->limit(10)
            ->get();

        // 3. Monthly Comparison (Chart Data)
        $monthlyData = $this->getMonthlyComparison($dateFrom, $dateTo);

        return view('reports.business-overview', compact(
            'stats', 'monthlyData', 'recentSales', 'recentPurchases', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Display the Balance Sheet according to MISA standards.
     */
    public function balanceSheet(Request $request): View
    {
        // Default to end of today if no date provided
        $date = $request->input('date', now()->format('Y-m-d'));

        // Assets (Tài sản)
        $cash = FinancialTransaction::where('date', '<=', $date)
            ->where('payment_method', 'cash')
            ->selectRaw('SUM(CASE WHEN type = "income" THEN amount ELSE -amount END) as balance')
            ->value('balance') ?? 0;

        $bank = FinancialTransaction::where('date', '<=', $date)
            ->where('payment_method', 'bank_transfer')
            ->selectRaw('SUM(CASE WHEN type = "income" THEN amount ELSE -amount END) as balance')
            ->value('balance') ?? 0;

        $receivables = Sale::where('date', '<=', $date)
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->sum('debt_amount');

        $inventoryValue = DB::table('inventories')
            ->selectRaw('SUM(stock * avg_cost) as total')
            ->value('total') ?? 0;

        // Liabilities (Nguồn vốn)
        // 311. Phải trả người bán ngắn hạn
        $payables = PurchaseOrder::where('order_date', '<=', $date)
            ->whereIn('status', ['sent', 'confirmed', 'shipping', 'received', 'partial_received'])
            ->sum('total');
            
        // 313. Thuế và các khoản phải nộp Nhà nước (Ước tính doanh thu - đầu vào)
        $vatPayable = Sale::where('date', '<=', $date)
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->sum('vat');
        
        $vatDeductible = PurchaseOrder::where('order_date', '<=', $date)
            ->whereIn('status', ['received', 'confirmed'])
            ->sum('vat_amount');
            
        $netVat = max(0, $vatPayable - $vatDeductible);

        $totalAssetsValue = $cash + $bank + $receivables + $inventoryValue;
        $totalLiabilitiesValue = $payables + $netVat;
        $equityBalance = $totalAssetsValue - $totalLiabilitiesValue;

        $assets = [
            'short_term' => [
                'code' => '100',
                'items' => [
                    ['name' => 'I. Tiền và các khoản tương đương tiền', 'code' => '110', 'value' => $cash + $bank, 'sub' => [
                        ['name' => '1. Tiền mặt', 'code' => '111', 'value' => $cash],
                        ['name' => '2. Tiền gửi ngân hàng', 'code' => '112', 'value' => $bank],
                    ]],
                    ['name' => 'II. Đầu tư tài chính ngắn hạn', 'code' => '120', 'value' => 0],
                    ['name' => 'III. Các khoản phải thu ngắn hạn', 'code' => '130', 'value' => $receivables, 'sub' => [
                        ['name' => '1. Phải thu khách hàng', 'code' => '131', 'value' => $receivables],
                        ['name' => '2. Trả trước cho người bán', 'code' => '132', 'value' => 0],
                    ]],
                    ['name' => 'IV. Hàng tồn kho', 'code' => '140', 'value' => $inventoryValue, 'sub' => [
                        ['name' => '1. Hàng tồn kho', 'code' => '141', 'value' => $inventoryValue],
                    ]],
                    ['name' => 'V. Tài sản ngắn hạn khác', 'code' => '150', 'value' => 0],
                ]
            ],
            'long_term' => [
                'code' => '200',
                'items' => [
                    ['name' => 'I. Các khoản phải thu dài hạn', 'code' => '210', 'value' => 0],
                    ['name' => 'II. Tài sản cố định', 'code' => '220', 'value' => 0, 'sub' => [
                        ['name' => '1. Tài sản cố định hữu hình', 'code' => '221', 'value' => 0],
                        ['name' => '2. Tài sản cố định vô hình', 'code' => '227', 'value' => 0],
                    ]],
                    ['name' => 'III. Bất động sản đầu tư', 'code' => '230', 'value' => 0],
                    ['name' => 'IV. Tài sản dở dang dài hạn', 'code' => '240', 'value' => 0],
                    ['name' => 'V. Đầu tư tài chính dài hạn', 'code' => '250', 'value' => 0],
                    ['name' => 'VI. Tài sản dài hạn khác', 'code' => '260', 'value' => 0],
                ]
            ],
            'total' => ['name' => 'TỔNG CỘNG TÀI SẢN', 'code' => '270', 'value' => $cash + $bank + $receivables + $inventoryValue]
        ];

        $liabilities = [
            'liabilities' => [
                'code' => '300',
                'items' => [
                    ['name' => 'I. Nợ ngắn hạn', 'code' => '310', 'value' => $payables, 'sub' => [
                        ['name' => '1. Phải trả người bán ngắn hạn', 'code' => '311', 'value' => $payables],
                        ['name' => '2. Người mua trả tiền trước ngắn hạn', 'code' => '312', 'value' => 0],
                        ['name' => '3. Thuế và các khoản phải nộp Nhà nước', 'code' => '313', 'value' => $netVat],
                    ]],
                    ['name' => 'II. Nợ dài hạn', 'code' => '330', 'value' => 0],
                ]
            ],
            'equity' => [
                'code' => '400',
                'items' => [
                    ['name' => 'I. Vốn chủ sở hữu', 'code' => '410', 'value' => $equityBalance, 'sub' => [
                        ['name' => '1. Vốn góp của chủ sở hữu', 'code' => '411', 'value' => 0],
                        ['name' => '2. Lợi nhuận sau thuế chưa phân phối', 'code' => '421', 'value' => $equityBalance],
                    ]],
                ]
            ],
            'total' => ['name' => 'TỔNG CỘNG NGUỒN VỐN', 'code' => '440', 'value' => $totalAssetsValue]
        ];

        return view('reports.balance-sheet', compact('assets', 'liabilities', 'date'));
    }

    /**
     * Display the Detailed P&L report.
     */
    public function detailedPnL(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Detailed Sales Data with Expenses
        $sales = Sale::with(['customer', 'items', 'expenses'])
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->get();

        $rows = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $rows[] = [
                    'supplier' => 'N/A', // Need to link item to its purchase/supplier
                    'product_name' => $item->product_name,
                    'qty' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'revenue' => $item->total,
                    'cost' => $item->cost_total,
                    'gross_profit' => $item->total - $item->cost_total,
                    'expenses' => [
                        'shipping' => $sale->getExpensesByType('shipping'),
                        'marketing' => $sale->getExpensesByType('marketing'),
                        'commission' => $sale->getExpensesByType('commission'),
                        'other' => $sale->getExpensesByType('other'),
                    ],
                    'margin' => $sale->margin,
                ];
            }
        }

        return view('reports.detailed-pnl', compact('rows', 'dateFrom', 'dateTo'));
    }

    /**
     * Get monthly comparison data for sales and purchases.
     */
    private function getMonthlyComparison($dateFrom, $dateTo): array
    {
        $start = Carbon::parse($dateFrom)->startOfMonth();
        $end = Carbon::parse($dateTo)->endOfMonth();
        
        $months = [];
        $current = $start->copy();
        
        while ($current <= $end) {
            $monthKey = $current->format('Y-m');
            $months[$monthKey] = [
                'month' => $current->format('m/Y'),
                'revenue' => 0,
                'purchase' => 0,
                'profit' => 0
            ];
            $current->addMonth();
        }

        // Fetch Sales by Month
        $salesByMonth = Sale::select(
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month_key"),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('SUM(margin) as total_profit')
            )
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->groupBy('month_key')
            ->get();

        foreach ($salesByMonth as $sale) {
            if (isset($months[$sale->month_key])) {
                $months[$sale->month_key]['revenue'] = (float)$sale->total_revenue;
                $months[$sale->month_key]['profit'] = (float)$sale->total_profit;
            }
        }

        // Fetch Purchases by Month
        $purchasesByMonth = PurchaseOrder::select(
                DB::raw("DATE_FORMAT(order_date, '%Y-%m') as month_key"),
                DB::raw('SUM(total) as total_purchase')
            )
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->groupBy('month_key')
            ->get();

        foreach ($purchasesByMonth as $purchase) {
            if (isset($months[$purchase->month_key])) {
                $months[$purchase->month_key]['purchase'] = (float)$purchase->total_purchase;
            }
        }

        return array_values($months);
    }
}
