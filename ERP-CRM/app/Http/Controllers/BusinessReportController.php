<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\PurchaseOrder;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\BalanceSheetExport;
use Maatwebsite\Excel\Facades\Excel;

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
     * Display the Balance Sheet according to Circular 200/2014/TT-BTC (Form B 01 - DN).
     */
    public function balanceSheet(Request $request): View
    {
        $date = $request->get('date', date('Y-m-d'));
        $reportData = $this->getBalanceSheetData($date);
        
        return view('reports.balance-sheet', compact('reportData', 'date'));
    }

    /**
     * Export the Balance Sheet to Excel.
     */
    public function exportBalanceSheet(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $reportData = $this->getBalanceSheetData($date);
        
        return Excel::download(new BalanceSheetExport($reportData, $date), 'Bao-cao-can-doi-ke-toan-' . $date . '.xlsx');
    }

    /**
     * Helper to get balance sheet data
     */
    private function getBalanceSheetData($date)
    {
        $beginningOfYear = Carbon::parse($date)->startOfYear()->toDateString();
        
        $getBalances = function($targetDate) {
            $cash = FinancialTransaction::where('date', '<=', $targetDate)
                ->whereHas('category', fn($q) => $q->where('type', 'income'))
                ->sum('amount') 
                - FinancialTransaction::where('date', '<=', $targetDate)
                ->whereHas('category', fn($q) => $q->where('type', 'expense'))
                ->sum('amount');

            // Simplified mapping for now
            $receivables_short = Sale::where('date', '<=', $targetDate)
                ->whereIn('status', ['approved', 'shipping', 'completed'])
                ->sum('debt_amount');

            $inventory = DB::table('inventories')
                ->selectRaw('SUM(stock * avg_cost) as total')
                ->value('total') ?? 0;

            $payables_short = PurchaseOrder::where('order_date', '<=', $targetDate)
                ->whereIn('status', ['sent', 'confirmed', 'shipping', 'received', 'partial_received'])
                ->get()
                ->sum(fn($po) => $po->total - ($po->paid_amount ?? 0));

            $undistributed_profit = ($cash + $receivables_short + $inventory) - $payables_short;

            return [
                'cash' => $cash,
                'cash_and_equiv' => $cash,
                'receivables_short' => $receivables_short,
                'receivables_cust_short' => $receivables_short,
                'inventory' => $inventory,
                'inventory_net' => $inventory,
                'payables_short' => $payables_short,
                'taxes_payable' => 0,
                'undistributed_profit' => $undistributed_profit,
                'equity' => $undistributed_profit,
                'total_assets' => $cash + $receivables_short + $inventory,
                'total_liabilities' => $payables_short,
                'total_equity' => $undistributed_profit,
                'total_resources' => $payables_short + $undistributed_profit,
            ];
        };

        $endBalances = $getBalances($date);
        $startBalances = $getBalances($beginningOfYear);

        $reportData = [
            'A' => [
                'name' => 'TÀI SẢN NGẮN HẠN', 'code' => '100', 'end' => $endBalances['total_assets'], 'start' => $startBalances['total_assets'],
                'sub' => [
                    'I' => [
                        'name' => 'Tiền và các khoản tương đương tiền', 'code' => '110', 'end' => $endBalances['cash_and_equiv'], 'start' => $startBalances['cash_and_equiv'],
                        'items' => [
                            ['name' => '1. Tiền', 'code' => '111', 'end' => $endBalances['cash'], 'start' => $startBalances['cash'], 'note' => 'V.01'],
                            ['name' => '2. Các khoản tương đương tiền', 'code' => '112', 'end' => 0, 'start' => 0],
                        ]
                    ],
                    'III' => [
                        'name' => 'Các khoản phải thu ngắn hạn', 'code' => '130', 'end' => $endBalances['receivables_short'], 'start' => $startBalances['receivables_short'],
                        'items' => [
                            ['name' => '1. Phải thu ngắn hạn của khách hàng', 'code' => '131', 'end' => $endBalances['receivables_cust_short'], 'start' => $startBalances['receivables_cust_short'], 'note' => 'V.03'],
                        ]
                    ],
                    'IV' => [
                        'name' => 'Hàng tồn kho', 'code' => '140', 'end' => $endBalances['inventory'], 'start' => $startBalances['inventory'],
                        'items' => [
                            ['name' => '1. Hàng tồn kho', 'code' => '141', 'end' => $endBalances['inventory_net'], 'start' => $startBalances['inventory_net'], 'note' => 'V.04'],
                        ]
                    ]
                ]
            ],
            'B' => [
                'name' => 'TÀI SẢN DÀI HẠN', 'code' => '200', 'end' => 0, 'start' => 0,
                'sub' => []
            ],
            'C' => [
                'name' => 'NỢ PHẢI TRẢ', 'code' => '300', 'end' => $endBalances['total_liabilities'], 'start' => $startBalances['total_liabilities'],
                'sub' => [
                    'I' => [
                        'name' => 'Nợ ngắn hạn', 'code' => '310', 'end' => $endBalances['payables_short'], 'start' => $startBalances['payables_short'],
                        'items' => [
                            ['name' => '1. Phải trả người bán ngắn hạn', 'code' => '311', 'end' => $endBalances['payables_short'], 'start' => $startBalances['payables_short'], 'note' => 'V.11'],
                            ['name' => '3. Thuế và các khoản phải nộp Nhà nước', 'code' => '313', 'end' => 0, 'start' => 0, 'note' => 'V.13'],
                        ]
                    ]
                ]
            ],
            'D' => [
                'name' => 'VỐN CHỦ SỞ HỮU', 'code' => '400', 'end' => $endBalances['total_equity'], 'start' => $startBalances['total_equity'],
                'sub' => [
                    'I' => [
                        'name' => 'Vốn chủ sở hữu', 'code' => '410', 'end' => $endBalances['equity'], 'start' => $startBalances['equity'],
                        'items' => [
                            ['name' => '11. Lợi nhuận sau thuế chưa phân phối', 'code' => '421', 'end' => $endBalances['undistributed_profit'], 'start' => $startBalances['undistributed_profit'], 'note' => 'V.21'],
                        ]
                    ]
                ]
            ],
            'TOTAL_ASSETS' => ['name' => 'TỔNG CỘNG TÀI SẢN', 'code' => '270', 'end' => $endBalances['total_assets'], 'start' => $startBalances['total_assets']],
            'TOTAL_RESOURCES' => ['name' => 'TỔNG CỘNG NGUỒN VỐN', 'code' => '440', 'end' => $endBalances['total_resources'], 'start' => $startBalances['total_resources']],
        ];

        // Filter out zero entries
        $hasData = ($endBalances['total_assets'] != 0 || $startBalances['total_assets'] != 0);
        
        foreach (['A', 'B', 'C', 'D'] as $sectionKey) {
            foreach ($reportData[$sectionKey]['sub'] as $subKey => $sub) {
                $reportData[$sectionKey]['sub'][$subKey]['items'] = array_filter($sub['items'], function($item) use ($hasData) {
                    return $item['end'] != 0 || $item['start'] != 0 || !$hasData;
                });
                
                if (empty($reportData[$sectionKey]['sub'][$subKey]['items']) && $sub['end'] == 0 && $sub['start'] == 0 && $hasData) {
                    unset($reportData[$sectionKey]['sub'][$subKey]);
                }
            }
            if (empty($reportData[$sectionKey]['sub']) && $reportData[$sectionKey]['end'] == 0 && $reportData[$sectionKey]['start'] == 0 && $hasData) {
                unset($reportData[$sectionKey]);
            }
        }

        return $reportData;
    }

    /**
     * Display the Detailed P&L report (Image 2).
     */
    public function detailedPnL(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfYear()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Detailed Sales Data with Expenses and Items
        $sales = Sale::with(['items.product.supplierPriceListItems.priceList.supplier'])
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->get();

        $rows = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                // Try to resolve supplier from product
                $supplierName = 'N/A';
                if ($item->product && $item->product->supplierPriceListItems->isNotEmpty()) {
                    $supplierName = $item->product->supplierPriceListItems->first()->priceList->supplier->name ?? 'N/A';
                }

                $rows[] = [
                    'supplier' => $supplierName,
                    'product_name' => $item->product_name,
                    'qty' => $item->quantity,
                    
                    // USD fields
                    'usd_price' => $item->usd_price,
                    'exchange_rate' => $item->exchange_rate,
                    'discount_rate' => $item->discount_rate,
                    'import_cost_rate' => $item->import_cost_rate,
                    'estimated_cost_usd' => $item->usd_price * (1 - ($item->discount_rate / 100)) * (1 + ($item->import_cost_rate / 100)),
                    
                    'unit_price' => $item->price,
                    'revenue' => $item->total,
                    'cost' => $item->cost_total,
                    'gross_profit' => $item->total - $item->cost_total,
                    
                    // Expenses from item fields
                    'expenses' => [
                        'finance' => $item->finance_cost,
                        'management' => $item->management_cost,
                        'support_247' => $item->support_247_cost,
                        'other_support' => $item->other_support_cost,
                        'technical_poc' => $item->technical_poc_cost,
                        'implementation' => $item->implementation_cost,
                        'contractor_tax' => $item->contractor_tax,
                    ],
                    'net_profit' => $item->net_profit,
                    'margin_percent' => $item->net_profit_percent,
                ];
            }
        }

        // Sort rows by supplier
        usort($rows, fn($a, $b) => strcmp($a['supplier'], $b['supplier']));

        return view('reports.detailed-pnl', compact('rows', 'dateFrom', 'dateTo'));
    }

    /**
     * Display the Misa Margin Report (Image 1).
     */
    public function misaMargin(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $sales = Sale::with(['user', 'items.product.supplierPriceListItems.priceList.supplier'])
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->get();

        $rows = $this->processPnLRows($sales);
        
        // Sort by date then customer
        usort($rows, function($a, $b) {
            $dateCmp = $a['sale_date']->timestamp <=> $b['sale_date']->timestamp;
            if ($dateCmp !== 0) return $dateCmp;
            return strcmp($a['customer_name'], $b['customer_name']);
        });

        return view('reports.misa-margin', compact('rows', 'dateFrom', 'dateTo'));
    }

    /**
     * Process sales into PnL rows (Used for Misa Margin Report)
     */
    private function processPnLRows($sales)
    {
        $rows = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                // Try to resolve supplier from product
                $supplierName = 'N/A';
                if ($item->product && $item->product->supplierPriceListItems->isNotEmpty()) {
                    $supplierName = $item->product->supplierPriceListItems->first()->priceList->supplier->name ?? 'N/A';
                }

                // Try to resolve category/license from product/extra_data
                $itemType = 'N/A';
                $isLicense = false;
                if ($item->product && $item->product->supplierPriceListItems->isNotEmpty()) {
                    $spli = $item->product->supplierPriceListItems->first();
                    $itemType = $spli->category ?? 'N/A';
                    
                    if (str_contains(strtolower($itemType), 'license') || 
                        str_contains(strtolower($item->product_name), 'license') ||
                        (isset($spli->extra_data['is_license']) && $spli->extra_data['is_license'])) {
                        $isLicense = true;
                    }
                }

                $rows[] = [
                    'sale_code' => $sale->code,
                    'sale_date' => $sale->date,
                    'customer_name' => $sale->customer_name,
                    'salesperson' => $sale->user->name ?? 'N/A',
                    'paid_amount' => $sale->paid_amount,
                    'total_amount' => $sale->total,
                    'payment_percent' => $sale->total > 0 ? ($sale->paid_amount / $sale->total) * 100 : 0,
                    
                    'supplier' => $supplierName,
                    'item_type' => $itemType,
                    'is_license' => $isLicense,
                    'product_code' => $item->product->code ?? 'N/A',
                    'product_name' => $item->product_name,
                    'qty' => $item->quantity,
                    
                    'revenue' => $item->total,
                    'cost' => $item->cost_total,
                    'net_profit' => $item->net_profit,
                    'margin_percent' => $item->net_profit_percent,
                ];
            }
        }
        return $rows;
    }

    /**
     * Export Misa Margin Report
     */
    public function exportMisaMargin(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $sales = Sale::with(['user', 'items.product.supplierPriceListItems.priceList.supplier'])
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'cancelled')
            ->get();

        $rows = $this->processPnLRows($sales);
        
        // Sort by date then customer
        usort($rows, function($a, $b) {
            $dateCmp = $a['sale_date']->timestamp <=> $b['sale_date']->timestamp;
            if ($dateCmp !== 0) return $dateCmp;
            return strcmp($a['customer_name'], $b['customer_name']);
        });

        return Excel::download(new \App\Exports\MisaMarginReportExport($rows, $dateFrom, $dateTo), 'Bao-cao-Margin-Misa-' . date('Ymd') . '.xlsx');
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
