<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
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
        $this->authorize('viewAny', \App\Models\SaleReport::class);
        $request->validate([
            'payment_percent_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_percent_max' => ['nullable', 'numeric', 'min:0', 'max:100', 'gte:payment_percent_min'],
        ]);
        
        $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $customerId = $request->input('customer_id');
        $productId = $request->input('product_id');
        $userId = $request->input('user_id');
        $vendorId = $request->input('vendor_id');
        $paymentState = $request->input('payment_state');
        $paymentPercentMin = $request->input('payment_percent_min');
        $paymentPercentMax = $request->input('payment_percent_max');

        // A Sales user must never obtain another salesperson's figures merely
        // by changing the user_id query string.
        $viewer = $request->user();
        $canViewAllSales = $viewer->can('view_all_sales');
        if (!$canViewAllSales) {
            $userId = $viewer->id;
        }

        // Summary statistics
        $stats = $this->getSummaryStats($dateFrom, $dateTo, $customerId, $productId, $userId, $vendorId, $paymentState, $paymentPercentMin, $paymentPercentMax);

        // Customer report
        $customerReport = $this->getCustomerReport($dateFrom, $dateTo, $customerId, $productId, $userId, $vendorId, $paymentState, $paymentPercentMin, $paymentPercentMax);

        // Product report
        $productReport = $this->getProductReport($dateFrom, $dateTo, $customerId, $productId, $userId, $vendorId, $paymentState, $paymentPercentMin, $paymentPercentMax);

        // Margin report (new)
        $marginReport = $this->getMarginReport($dateFrom, $dateTo, $customerId, $userId, $vendorId, $paymentState, $paymentPercentMin, $paymentPercentMax);

        $customers = Customer::orderBy('name')->get();
        // Don't load all products to prevent slow page load
        $selectedProduct = $productId ? Product::find($productId) : null;
        $users = $canViewAllSales
            ? User::where(function ($query) {
                $query->where('department', 'like', '%Sales%')->orWhereHas('sales');
            })->orderBy('name')->get()
            : User::whereKey($viewer->id)->get();
        $vendors = \App\Models\Supplier::orderBy('name')->get(['id', 'name']);

        return view('sale-reports.index', compact(
            'stats', 'customerReport', 'productReport',
            'marginReport', 'customers', 'selectedProduct', 'users',
            'dateFrom', 'dateTo', 'customerId', 'productId', 'userId', 'vendorId', 'paymentState', 'paymentPercentMin', 'paymentPercentMax', 'vendors'
        ));
    }

    /**
     * Get margin report data — matches the Excel template for Misa reconciliation.
     * Each row = one Sale order.
     */
    private function getMarginReport($dateFrom, $dateTo, $customerId = null, $userId = null, $vendorId = null, $paymentState = null, $paymentPercentMin = null, $paymentPercentMax = null): array
    {
        $query = Sale::with(['customer', 'user', 'items.product', 'items.supplier'])
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'shipping', 'completed']);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        $this->applyVendorFilter($query, $vendorId);
        $this->applyPaymentState($query, $paymentState, '', $paymentPercentMin, $paymentPercentMax);

        $sales = $query->orderBy('date', 'asc')->get();

        $report = [];
        foreach ($sales as $index => $sale) {
            // Get main product code (first item's product code)
            $mainProductCode = '';
            if ($sale->items->isNotEmpty()) {
                $firstItem = $sale->items->first();
                $mainProductCode = $firstItem->product->code ?? '';
            }

            $brands = $sale->items->map(fn ($item) => $item->supplier?->name)
                ->filter()->unique()->values()->implode(', ');
            $isLicense = $sale->items->isNotEmpty() && $sale->items->every(function ($item) {
                $description = strtolower(($item->product_name ?? '') . ' ' . ($item->product?->name ?? ''));
                return str_contains($description, 'license') || str_contains($description, 'licence');
            });
            $productType = $sale->items->pluck('is_service')->contains(true)
                ? 'Service'
                : ($isLicense ? 'License' : 'HW');
            $goodsCost = $sale->items->sum(fn ($item) => (float) $item->cost_total);
            $financeCost = $sale->items->sum(fn ($item) => (float) $item->finance_cost);
            $overdueInterestCost = $sale->items->sum(fn ($item) => (float) $item->overdue_interest_cost);
            $managementCost = $sale->items->sum(fn ($item) => (float) $item->management_cost);
            $support247Cost = $sale->items->sum(fn ($item) => (float) $item->support_247_cost);
            $otherSupportCost = $sale->items->sum(fn ($item) => (float) $item->other_support_cost_vnd);
            $technicalPocCost = $sale->items->sum(fn ($item) => $item->technical_poc_percent !== null
                ? (float) $item->cost_total * ((float) $item->technical_poc_percent / 100)
                : (float) $item->technical_poc_cost);
            $implementationCost = $sale->items->sum(fn ($item) => $item->implementation_cost_percent !== null
                ? (float) $item->cost_total * ((float) $item->implementation_cost_percent / 100)
                : (float) $item->implementation_cost);
            $contractorTax = $sale->items->sum(fn ($item) => $item->contractor_tax_percent !== null
                ? (float) $item->cost_total * ((float) $item->contractor_tax_percent / 100)
                : (float) $item->contractor_tax);
            $totalCost = $goodsCost + $financeCost + $overdueInterestCost + $managementCost
                + $support247Cost + $otherSupportCost + $technicalPocCost + $implementationCost + $contractorTax;

            // Use margin from Sale table for consistency
            $margin = (float) $sale->margin;
            // VAT is collected on behalf of the tax authority, not revenue
            // used for the gross-margin denominator.
            $revenueTotal = (float) $sale->subtotal * (1 - ((float) $sale->discount / 100));
            
            // Calculate margin % based on revenue before tax (matches list view)
            $netRevenue = (float) $sale->subtotal - ((float) $sale->subtotal * ((float) $sale->discount / 100));
            $marginPercent = $netRevenue > 0 ? ($margin / $netRevenue) * 100 : 0;

            // Payment info
            $paidAmount = (float) $sale->paid_amount;
            $paymentPercent = $revenueTotal > 0 ? ($paidAmount / $revenueTotal) * 100 : 0;

            $report[] = [
                'sale_id' => $sale->id,
                'stt' => $index + 1,
                'customer_name' => $sale->customer_name ?: ($sale->customer->name ?? ''),
                'invoice_number' => $sale->code,
                'invoice_date' => $sale->date ? $sale->date->format('d/m/Y') : '',
                'brand' => '', // Manual field — not in DB yet
                'license' => '', // Manual field — not in DB yet
                'product_type' => '', // Manual field — not in DB yet
                'brand' => $brands,
                'license' => $isLicense ? 'X' : '',
                'product_type' => $productType,
                'revenue_before_vat' => round($revenueTotal),
                'vat_amount' => round((float) $sale->vat_amount),
                'goods_cost' => round($goodsCost),
                'finance_cost' => round($financeCost),
                'overdue_interest_cost' => round($overdueInterestCost),
                'management_cost' => round($managementCost),
                'support_247_cost' => round($support247Cost),
                'other_support_cost' => round($otherSupportCost),
                'technical_poc_cost' => round($technicalPocCost),
                'implementation_cost' => round($implementationCost),
                'contractor_tax' => round($contractorTax),
                'total_cost' => round($totalCost),
                'main_product_code' => $mainProductCode,
                'margin' => round($margin),
                'margin_percent' => round($marginPercent, 1),
                'salesperson' => $sale->user->name ?? '',
                'paid_amount' => round($paidAmount),
                'payment_percent' => round($paymentPercent, 1),
                'payment_status_text' => $paymentPercent >= 100 ? 'Đã thanh toán' : ($paidAmount > 0 ? 'Thanh toán một phần' : 'Chưa thanh toán'),
            ];
        }

        return $report;
    }

    private function getSummaryStats($dateFrom, $dateTo, $customerId = null, $productId = null, $userId = null, $vendorId = null, $paymentState = null, $paymentPercentMin = null, $paymentPercentMax = null): array
    {
        $query = Sale::whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'shipping', 'completed']); // Only include confirmed orders

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($productId) {
            $query->whereHas('items', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        $this->applyVendorFilter($query, $vendorId);
        $this->applyPaymentState($query, $paymentState, '', $paymentPercentMin, $paymentPercentMax);

        // Clone query for sums to avoid issues if we needed to group (not needed here but good practice)
        
        $totalOrders = $query->count();
        $totalRevenue = (float) (clone $query)
            ->sum(DB::raw('subtotal * (1 - COALESCE(discount, 0) / 100)'));
        $totalMargin = $query->sum('margin');
        
        // Calculate total net revenue for percent calculation
        $totalNetRevenue = Sale::whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->when($customerId, fn($q) => $q->where('customer_id', $customerId))
            ->when($productId, fn($q) => $q->whereHas('items', fn($items) => $items->where('product_id', $productId)))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->tap(fn($q) => $this->applyVendorFilter($q, $vendorId))
            ->tap(fn($q) => $this->applyPaymentState($q, $paymentState, '', $paymentPercentMin, $paymentPercentMax))
            ->get()
            ->sum(function($s) {
                return (float)$s->subtotal * (1 - (float)$s->discount / 100);
            });

        // Cost = Revenue - Margin (margin already net of COGS + expenses)
        $totalCalculatedCost = $totalRevenue - $totalMargin;

        $marginPercent = $totalNetRevenue > 0 ? ($totalMargin / $totalNetRevenue) * 100 : 0;

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue ?? 0,
            'total_cost' => $totalCalculatedCost ?? 0,
            'total_profit' => $totalMargin ?? 0,
            'margin_percent' => round($marginPercent, 1),
        ];
    }

    private function getCustomerReport($dateFrom, $dateTo, $customerId = null, $productId = null, $userId = null, $vendorId = null, $paymentState = null, $paymentPercentMin = null, $paymentPercentMax = null): array
    {
        $query = Sale::select(
                'customer_id',
                'customer_name',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(subtotal * (1 - COALESCE(discount, 0) / 100)) as total_revenue'),
                DB::raw('SUM(margin) as total_profit')
            )
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->groupBy('customer_id', 'customer_name');

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        if ($productId) {
            $query->whereHas('items', fn($items) => $items->where('product_id', $productId));
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        $this->applyVendorFilter($query, $vendorId);
        $this->applyPaymentState($query, $paymentState, '', $paymentPercentMin, $paymentPercentMax);

        $results = $query->orderByDesc('total_revenue')->get();

        return $results->map(function ($item) use ($dateFrom, $dateTo, $productId, $userId, $vendorId, $paymentState, $paymentPercentMin, $paymentPercentMax) {
            // Get net revenue sum for this customer in this range
            $netRevenueSum = Sale::where('customer_id', $item->customer_id)
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->whereIn('status', ['approved', 'shipping', 'completed'])
                ->when($productId, fn($q) => $q->whereHas('items', fn($items) => $items->where('product_id', $productId)))
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->tap(fn($q) => $this->applyVendorFilter($q, $vendorId))
                ->tap(fn($q) => $this->applyPaymentState($q, $paymentState, '', $paymentPercentMin, $paymentPercentMax))
                ->get()
                ->sum(fn($s) => (float)$s->subtotal * (1 - (float)$s->discount / 100));

            $marginPercent = $netRevenueSum > 0 
                ? ($item->total_profit / $netRevenueSum) * 100 
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

    private function getProductReport($dateFrom, $dateTo, $customerId = null, $productId = null, $userId = null, $vendorId = null, $paymentState = null, $paymentPercentMin = null, $paymentPercentMax = null): array
    {
        $query = SaleItem::select(
                'sale_items.product_id',
                'products.code as product_code',
                'sale_items.product_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total * sales.exchange_rate) as total_revenue'),
                DB::raw('SUM((sale_items.total * sales.exchange_rate) - sale_items.cost_total) as total_profit')
            )
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->whereIn('sales.status', ['approved', 'shipping', 'completed'])
            ->whereBetween('sales.date', [$dateFrom, $dateTo])
            ->groupBy('sale_items.product_id', 'products.code', 'sale_items.product_name');

        if ($productId) {
            $query->where('sale_items.product_id', $productId);
        }
        if ($customerId) {
            $query->where('sales.customer_id', $customerId);
        }
        if ($userId) {
            $query->where('sales.user_id', $userId);
        }
        if ($vendorId) {
            $query->where(function ($itemsQuery) use ($vendorId) {
                $itemsQuery->whereHas('sale.project', fn($project) => $project->where('vendor_id', $vendorId))
                    ->orWhereHas('sale.orderRequests.items', fn($items) => $items->where('vendor_id', $vendorId));
            });
        }
        $this->applyPaymentState($query, $paymentState, 'sales.', $paymentPercentMin, $paymentPercentMax);

        $results = $query->orderByDesc('total_revenue')->get();

        return $results->map(function ($item) {
            // Product report is already based on unit_price (excl VAT) * qty
            $marginPercent = $item->total_revenue > 0 
                ? ($item->total_profit / $item->total_revenue) * 100 
                : 0;

            return [
                'product_id' => $item->product_id,
                'product_code' => $item->product_code,
                'product_name' => $item->product_name,
                'total_quantity' => $item->total_quantity,
                'total_revenue' => $item->total_revenue,
                'total_profit' => $item->total_profit,
                'margin_percent' => round($marginPercent, 1),
            ];
        })->toArray();
    }

    /** Apply the brand/vendor filter consistently to sale-based reports. */
    private function applyVendorFilter($query, $vendorId): void
    {
        if (!$vendorId) {
            return;
        }

        $query->where(function ($sales) use ($vendorId) {
            $sales->whereHas('project', fn ($project) => $project->where('vendor_id', $vendorId))
                ->orWhereHas('orderRequests.items', fn ($items) => $items->where('vendor_id', $vendorId));
        });
    }

    /** Filter consistently by payment completion, using revenue before VAT. */
    private function applyPaymentState($query, ?string $paymentState, string $prefix = '', $paymentPercentMin = null, $paymentPercentMax = null): void
    {
        $paid = "COALESCE({$prefix}paid_amount, 0)";
        $netRevenue = "({$prefix}subtotal * (1 - COALESCE({$prefix}discount, 0) / 100))";

        if (in_array($paymentState, ['unpaid', 'partial', 'paid'], true)) {
            match ($paymentState) {
                'unpaid' => $query->whereRaw("{$paid} <= 0"),
                'partial' => $query->whereRaw("{$paid} > 0 AND {$paid} < {$netRevenue}"),
                'paid' => $query->whereRaw("{$netRevenue} > 0 AND {$paid} >= {$netRevenue}"),
            };
        }

        if (is_numeric($paymentPercentMin)) {
            $query->whereRaw("{$netRevenue} > 0 AND ({$paid} / {$netRevenue} * 100) >= ?", [(float) $paymentPercentMin]);
        }
        if (is_numeric($paymentPercentMax)) {
            $query->whereRaw("{$netRevenue} > 0 AND ({$paid} / {$netRevenue} * 100) <= ?", [(float) $paymentPercentMax]);
        }
    }

    private function getMonthlyReport($dateFrom, $dateTo): array
    {
        $results = Sale::select(
                DB::raw("DATE_FORMAT(date, '%Y-%m') as month"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(subtotal * (1 - COALESCE(discount, 0) / 100)) as total_revenue'),
                DB::raw('SUM(margin) as total_profit')
            )
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'shipping', 'completed'])
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

            // Calculate monthly net revenue for percent
            $monthlyNetRevenue = Sale::whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$item->month])
                ->whereIn('status', ['approved', 'shipping', 'completed'])
                ->get()
                ->sum(fn($s) => (float)$s->subtotal * (1 - (float)$s->discount / 100));

            $marginPercent = $monthlyNetRevenue > 0 
                ? ($item->total_profit / $monthlyNetRevenue) * 100 
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
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->selectRaw('
                SUM(subtotal) as subtotal,
                SUM(discount) as discount_percent_sum, -- This is meaningless
                SUM(subtotal * (1 - COALESCE(discount, 0) / 100)) as total_revenue,
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
        // The primary Export Excel button must export the same filtered report,
        // rather than returning a placeholder message.
        return $this->exportMargin($request);
    }

    /**
     * Export Margin Report to Excel (CSV with BOM for Vietnamese chars).
     */
    public function exportMargin(Request $request)
    {
        $this->authorize('export', \App\Models\SaleReport::class);
        $request->validate([
            'payment_percent_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_percent_max' => ['nullable', 'numeric', 'min:0', 'max:100', 'gte:payment_percent_min'],
        ]);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $customerId = $request->input('customer_id');
        $userId = $request->input('user_id');
        $vendorId = $request->input('vendor_id');
        $paymentState = $request->input('payment_state');
        $paymentPercentMin = $request->input('payment_percent_min');
        $paymentPercentMax = $request->input('payment_percent_max');

        // Preserve the same data boundary as the on-screen report.
        if (!$request->user()->can('view_all_sales')) {
            $userId = $request->user()->id;
        }

        $marginReport = $this->getMarginReport($dateFrom, $dateTo, $customerId, $userId, $vendorId, $paymentState, $paymentPercentMin, $paymentPercentMax);

        $fromFormatted = date('d/m/Y', strtotime($dateFrom));
        $toFormatted = date('d/m/Y', strtotime($dateTo));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Báo cáo Margin');

        // ── Styles ──
        $headerFill = [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '1a3a5c'],
        ];
        $headerFont = [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 10,
            'name' => 'Arial',
        ];
        $titleFont = [
            'bold' => true,
            'size' => 13,
            'name' => 'Arial',
        ];
        $borderAll = [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ];

        // ── Row 1: Title ──
        $sheet->mergeCells('A1:Y1');
        $sheet->setCellValue('A1', "Báo cáo Lãi/Lỗ (Margin) theo đơn hàng tháng .../(Từ {$fromFormatted} đến {$toFormatted})");
        $sheet->getStyle('A1')->getFont()->applyFromArray($titleFont);

        // ── Row 3: Header ──
        $headers = [
            'A3' => 'STT',
            'B3' => 'Tên khách hàng',
            'C3' => "Số Hóa đơn tài chính\n(hoặc đối với hàng khởi tạo theo phần mềm)",
            'D3' => 'Ngày xuất hóa đơn',
            'E3' => 'HÃNG',
            'F3' => 'License',
            'G3' => 'Loại hàng',
            'H3' => 'Mã Hàng hóa chính',
            'I3' => 'Margin',
            'J3' => 'Margin %',
            'K3' => 'NV Kinh doanh',
            'L3' => "Tổng Tiền khách hàng\nđã thanh toán",
            'M3' => "Tỷ lệ khách hàng\nđã thanh toán (%)",
        ];

        $headers += [
            'N3' => 'Tiền hàng (chưa VAT)', 'O3' => 'Thuế VAT', 'P3' => 'Giá vốn hàng hóa',
            'Q3' => 'Chi phí tài chính', 'R3' => 'Lãi vay quá hạn', 'S3' => 'QL/Back Office/Kỹ thuật',
            'T3' => '24x7 Support', 'U3' => 'Other Support', 'V3' => 'Technical POC',
            'W3' => 'Chi phí triển khai', 'X3' => 'Thuế nhà thầu', 'Y3' => 'Tổng chi phí',
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        // Apply header style
        $headerRange = 'A3:Y3';
        $sheet->getStyle($headerRange)->applyFromArray([
            'fill' => $headerFill,
            'font' => $headerFont,
            'borders' => $borderAll,
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(45);

        // ── Column widths ──
        $widths = ['A' => 5, 'B' => 28, 'C' => 20, 'D' => 14, 'E' => 14, 'F' => 8, 'G' => 22, 'H' => 14, 'I' => 16, 'J' => 10, 'K' => 18, 'L' => 20, 'M' => 16, 'N' => 18, 'O' => 14, 'P' => 18, 'Q' => 16, 'R' => 16, 'S' => 20, 'T' => 14, 'U' => 14, 'V' => 16, 'W' => 18, 'X' => 16, 'Y' => 18];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // ── Data rows ──
        $row = 4;
        foreach ($marginReport as $data) {
            $sheet->setCellValue("A{$row}", $data['stt']);
            $sheet->setCellValue("B{$row}", $data['customer_name']);
            $sheet->setCellValue("C{$row}", $data['invoice_number']);
            $sheet->setCellValue("D{$row}", $data['invoice_date']);
            $sheet->setCellValue("E{$row}", $data['brand']);
            $sheet->setCellValue("F{$row}", $data['license']);
            $sheet->setCellValue("G{$row}", $data['product_type']);
            $sheet->setCellValue("H{$row}", $data['main_product_code']);
            $sheet->setCellValue("I{$row}", $data['margin']);
            $sheet->setCellValue("J{$row}", $data['margin_percent'] / 100);
            $sheet->setCellValue("K{$row}", $data['salesperson']);

            if ($data['paid_amount'] > 0) {
                $sheet->setCellValue("L{$row}", $data['paid_amount']);
            } else {
                $sheet->setCellValue("L{$row}", 'Chưa thanh toán');
            }

            $sheet->setCellValue("M{$row}", $data['payment_percent'] / 100);
            $sheet->setCellValue("N{$row}", $data['revenue_before_vat']);
            $sheet->setCellValue("O{$row}", $data['vat_amount']);
            $sheet->setCellValue("P{$row}", $data['goods_cost']);
            $sheet->setCellValue("Q{$row}", $data['finance_cost']);
            $sheet->setCellValue("R{$row}", $data['overdue_interest_cost']);
            $sheet->setCellValue("S{$row}", $data['management_cost']);
            $sheet->setCellValue("T{$row}", $data['support_247_cost']);
            $sheet->setCellValue("U{$row}", $data['other_support_cost']);
            $sheet->setCellValue("V{$row}", $data['technical_poc_cost']);
            $sheet->setCellValue("W{$row}", $data['implementation_cost']);
            $sheet->setCellValue("X{$row}", $data['contractor_tax']);
            $sheet->setCellValue("Y{$row}", $data['total_cost']);

            // Format numbers
            $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J{$row}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("L{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("M{$row}")->getNumberFormat()->setFormatCode('0%');
            $sheet->getStyle("N{$row}:Y{$row}")->getNumberFormat()->setFormatCode('#,##0');

            // Margin color: red if negative, green if positive
            if ($data['margin'] < 0) {
                $sheet->getStyle("I{$row}")->getFont()->getColor()->setRGB('CC0000');
            } else {
                $sheet->getStyle("I{$row}")->getFont()->getColor()->setRGB('006600');
            }

            // Alignment
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Alternate row colors
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:Y{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle("A{$row}:Y{$row}")->getFill()->getStartColor()->setRGB('F2F7FB');
            }

            $row++;
        }

        // Data borders
        $lastRow = $row - 1;
        if ($lastRow >= 4) {
            $sheet->getStyle("A4:Y{$lastRow}")->applyFromArray([
                'borders' => $borderAll,
                'font' => ['size' => 10, 'name' => 'Arial'],
            ]);
        }

        // ── Download ──
        $filename = 'Bao_cao_Margin_' . date('Ymd', strtotime($dateFrom)) . '_' . date('Ymd', strtotime($dateTo)) . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
    /**
     * Get conversion efficiency report.
     * Counts: Customers mapped to User, Opportunities assigned to User, Sales created by User.
     */
    private function getConversionReport($dateFrom, $dateTo, $customerId = null, $productId = null, $userId = null, $search = null): array
    {
        $usersQuery = User::select('id', 'name');

        if ($userId) {
            $usersQuery->where('id', $userId);
        }

        if ($search) {
            $usersQuery->where('name', 'like', '%' . $search . '%');
        }

        $users = $usersQuery->withCount([
                'customers as customers_count' => function ($q) use ($dateFrom, $dateTo, $customerId) {
                    $q->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                    if ($customerId) {
                        $q->where('id', $customerId);
                    }
                },
                'opportunities as opportunities_count' => function ($q) use ($dateFrom, $dateTo, $customerId) {
                    $q->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                    if ($customerId) {
                        $q->where('customer_id', $customerId);
                    }
                },
                'sales as sales_count' => function ($q) use ($dateFrom, $dateTo, $customerId, $productId) {
                    $q->whereBetween('date', [$dateFrom, $dateTo])
                      ->whereIn('status', ['approved', 'shipping', 'completed']);
                    if ($customerId) {
                        $q->where('customer_id', $customerId);
                    }
                    if ($productId) {
                        $q->whereHas('items', function ($sq) use ($productId) {
                            $sq->where('product_id', $productId);
                        });
                    }
                }
            ])
            ->get();

        $report = [];
        foreach ($users as $user) {
            if ($user->customers_count == 0 && $user->opportunities_count == 0 && $user->sales_count == 0 && !$userId && !$search) {
                continue;
            }

            // Lead to Opp rate
            $leadToOpp = $user->customers_count > 0 
                ? ($user->opportunities_count / $user->customers_count) * 100 
                : 0;
            
            // Opp to Sale rate
            $oppToSale = $user->opportunities_count > 0 
                ? ($user->sales_count / $user->opportunities_count) * 100 
                : 0;

            $report[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'customers_count' => $user->customers_count,
                'opportunities_count' => $user->opportunities_count,
                'sales_count' => $user->sales_count,
                'lead_to_opp_rate' => round($leadToOpp, 1),
                'opp_to_sale_rate' => round($oppToSale, 1),
            ];
        }

        // Sort by sales count desc
        usort($report, fn($a, $b) => $b['sales_count'] <=> $a['sales_count']);

        return $report;
    }
}
