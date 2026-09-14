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
            'margin_percent_min' => ['nullable', 'numeric'],
            'margin_percent_max' => ['nullable', 'numeric'],
            'revenue_min' => ['nullable', 'numeric', 'min:0'],
            'revenue_max' => ['nullable', 'numeric', 'min:0'],
            'total_min' => ['nullable', 'numeric', 'min:0'],
            'total_max' => ['nullable', 'numeric', 'min:0'],
            'cost_min' => ['nullable', 'numeric', 'min:0'],
            'cost_max' => ['nullable', 'numeric', 'min:0'],
            'margin_min' => ['nullable', 'numeric'],
            'margin_max' => ['nullable', 'numeric'],
        ]);
        
        $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $customerId = $request->input('customer_id');
        $productId = $request->input('product_id');
        $userId = $request->input('user_id');
        $vendorId = $request->input('vendor_id');
        $productType = $request->input('product_type');
        $search = $request->input('search');
        $paymentState = $request->input('payment_state');
        $paymentPercentMin = $request->input('payment_percent_min');
        $paymentPercentMax = $request->input('payment_percent_max');
        $marginPercentMin = $request->input('margin_percent_min');
        $marginPercentMax = $request->input('margin_percent_max');

        // Financial & Cost filters
        $revenueMin = $request->input('revenue_min');
        $revenueMax = $request->input('revenue_max');
        $totalMin = $request->input('total_min');
        $totalMax = $request->input('total_max');
        $costMin = $request->input('cost_min');
        $costMax = $request->input('cost_max');
        $hasVat = $request->input('has_vat');
        $hasImplementationCost = $request->input('has_implementation_cost');
        $hasContractorTax = $request->input('has_contractor_tax');
        $hasFinanceCost = $request->input('has_finance_cost');
        $hasManagementCost = $request->input('has_management_cost');
        $hasSupport247 = $request->input('has_support_247');
        $hasOtherSupport = $request->input('has_other_support');
        $marginMin = $request->input('margin_min');
        $marginMax = $request->input('margin_max');

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

        // Margin report (with full column filters)
        $marginReport = $this->getMarginReport(
            $dateFrom, $dateTo, $customerId, $productId, $userId, $vendorId,
            $productType, $search, $paymentState, $paymentPercentMin, $paymentPercentMax,
            $marginPercentMin, $marginPercentMax,
            $revenueMin, $revenueMax, $totalMin, $totalMax, $costMin, $costMax,
            $hasVat, $hasImplementationCost, $hasContractorTax, $hasFinanceCost,
            $hasManagementCost, $hasSupport247, $hasOtherSupport, $marginMin, $marginMax
        );

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
            'dateFrom', 'dateTo', 'customerId', 'productId', 'userId', 'vendorId',
            'productType', 'search', 'paymentState', 'paymentPercentMin', 'paymentPercentMax',
            'marginPercentMin', 'marginPercentMax', 'vendors',
            'revenueMin', 'revenueMax', 'totalMin', 'totalMax', 'costMin', 'costMax',
            'hasVat', 'hasImplementationCost', 'hasContractorTax', 'hasFinanceCost',
            'hasManagementCost', 'hasSupport247', 'hasOtherSupport', 'marginMin', 'marginMax'
        ));
    }

    /**
     * Get margin report data — matches the Excel template for Misa reconciliation.
     * Each row = one Sale order.
     */
    private function getMarginReport(
        $dateFrom,
        $dateTo,
        $customerId = null,
        $productId = null,
        $userId = null,
        $vendorId = null,
        $productType = null,
        $search = null,
        $paymentState = null,
        $paymentPercentMin = null,
        $paymentPercentMax = null,
        $marginPercentMin = null,
        $marginPercentMax = null,
        $revenueMin = null,
        $revenueMax = null,
        $totalMin = null,
        $totalMax = null,
        $costMin = null,
        $costMax = null,
        $hasVat = null,
        $hasImplementationCost = null,
        $hasContractorTax = null,
        $hasFinanceCost = null,
        $hasManagementCost = null,
        $hasSupport247 = null,
        $hasOtherSupport = null,
        $marginMin = null,
        $marginMax = null
    ): array
    {
        $query = Sale::with(['customer', 'user', 'items.product', 'items.supplier'])
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->whereIn('status', ['approved', 'shipping', 'completed']);

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
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($cQ) => $cQ->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('items', fn ($itQ) => $itQ->where('product_name', 'like', "%{$search}%")
                      ->orWhereHas('product', fn ($pQ) => $pQ->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                  );
            });
        }
        $this->applyVendorFilter($query, $vendorId);
        $this->applyPaymentState($query, $paymentState, '', $paymentPercentMin, $paymentPercentMax);

        $sales = $query->orderBy('date', 'asc')->get();

        $report = [];
        $stt = 1;
        foreach ($sales as $sale) {
            // Main product code
            $mainProductCode = '';
            if ($sale->items->isNotEmpty()) {
                $firstItem = $sale->items->first();
                $mainProductCode = $firstItem->product->code ?? ($firstItem->sku ?? '');
            }

            $brands = $sale->items->map(fn ($item) => $item->supplier?->name)
                ->filter()->unique()->values()->implode(', ');
            $isLicense = $sale->items->isNotEmpty() && $sale->items->every(function ($item) {
                $description = strtolower(($item->product_name ?? '') . ' ' . ($item->product?->name ?? ''));
                return str_contains($description, 'license') || str_contains($description, 'licence');
            });
            $actualProductType = $sale->items->pluck('is_service')->contains(true)
                ? 'Service'
                : ($isLicense ? 'License' : 'HW');

            // Filter by productType if specified
            if ($productType && strtolower($actualProductType) !== strtolower($productType)) {
                continue;
            }

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

            $contractImplementationCost = $implementationCost + $technicalPocCost;
            $totalCost = $goodsCost + $financeCost + $overdueInterestCost + $managementCost
                + $support247Cost + $otherSupportCost + $contractImplementationCost + $contractorTax;

            $margin = (float) $sale->margin;
            $revenueBeforeVat = (float) $sale->subtotal * (1 - ((float) $sale->discount / 100));
            $vatAmount = (float) ($sale->vat_amount ?? 0);
            $totalAmountInclVat = $revenueBeforeVat + $vatAmount;

            $netRevenue = (float) $sale->subtotal - ((float) $sale->subtotal * ((float) $sale->discount / 100));
            $marginPercent = $netRevenue > 0 ? ($margin / $netRevenue) * 100 : 0;
            $marginPercentRound = round($marginPercent, 1);

            // Filter by Margin % range
            if ($marginPercentMin !== null && $marginPercentMin !== '' && $marginPercentRound < (float)$marginPercentMin) {
                continue;
            }
            if ($marginPercentMax !== null && $marginPercentMax !== '' && $marginPercentRound > (float)$marginPercentMax) {
                continue;
            }

            // 1. Filter: Tiền hàng chưa VAT
            if ($revenueMin !== null && $revenueMin !== '' && $revenueBeforeVat < (float)$revenueMin) {
                continue;
            }
            if ($revenueMax !== null && $revenueMax !== '' && $revenueBeforeVat > (float)$revenueMax) {
                continue;
            }

            // 2. Filter: Tiền thuế VAT
            if ($hasVat === 'yes' && $vatAmount <= 0) {
                continue;
            }
            if ($hasVat === 'no' && $vatAmount > 0) {
                continue;
            }

            // 3. Filter: Tổng tiền gồm VAT
            if ($totalMin !== null && $totalMin !== '' && $totalAmountInclVat < (float)$totalMin) {
                continue;
            }
            if ($totalMax !== null && $totalMax !== '' && $totalAmountInclVat > (float)$totalMax) {
                continue;
            }

            // 4. Filter: Giá vốn hàng hóa
            if ($costMin !== null && $costMin !== '' && $goodsCost < (float)$costMin) {
                continue;
            }
            if ($costMax !== null && $costMax !== '' && $goodsCost > (float)$costMax) {
                continue;
            }

            // 5. Filter: Chi phí triển khai HĐ
            if ($hasImplementationCost === 'yes' && $contractImplementationCost <= 0) {
                continue;
            }
            if ($hasImplementationCost === 'no' && $contractImplementationCost > 0) {
                continue;
            }

            // 6. Filter: Thuế nhà thầu
            if ($hasContractorTax === 'yes' && $contractorTax <= 0) {
                continue;
            }
            if ($hasContractorTax === 'no' && $contractorTax > 0) {
                continue;
            }

            // 7. Filter: Chi phí tài chính 1%
            if ($hasFinanceCost === 'yes' && $financeCost <= 0) {
                continue;
            }
            if ($hasFinanceCost === 'no' && $financeCost > 0) {
                continue;
            }

            // 8. Filter: Chi phí Quản lý & Back Office
            if ($hasManagementCost === 'yes' && $managementCost <= 0) {
                continue;
            }
            if ($hasManagementCost === 'no' && $managementCost > 0) {
                continue;
            }

            // 9. Filter: 24x7 (0.5%)
            if ($hasSupport247 === 'yes' && $support247Cost <= 0) {
                continue;
            }
            if ($hasSupport247 === 'no' && $support247Cost > 0) {
                continue;
            }

            // 10. Filter: Other Support (Zyxel)
            if ($hasOtherSupport === 'yes' && $otherSupportCost <= 0) {
                continue;
            }
            if ($hasOtherSupport === 'no' && $otherSupportCost > 0) {
                continue;
            }

            // 11. Filter: Số tiền Margin (VNĐ)
            if ($marginMin !== null && $marginMin !== '' && $margin < (float)$marginMin) {
                continue;
            }
            if ($marginMax !== null && $marginMax !== '' && $margin > (float)$marginMax) {
                continue;
            }

            // Invoice number & date
            $invoiceNumber = $sale->code;
            $invoiceDate = $sale->invoice_date 
                ? $sale->invoice_date->format('d/m/Y') 
                : ($sale->date ? $sale->date->format('d/m/Y') : '');

            // Payment info
            $paidAmount = (float) $sale->paid_amount;
            $paymentPercent = $totalAmountInclVat > 0 ? ($paidAmount / $totalAmountInclVat) * 100 : 0;

            $report[] = [
                'sale_id' => $sale->id,
                'stt' => $stt++,
                'customer_name' => $sale->customer_name ?: ($sale->customer->name ?? ''),
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $invoiceDate,
                'brand' => $brands,
                'license' => $isLicense ? 'x' : '',
                'product_type' => $actualProductType,
                'main_product_code' => $mainProductCode,
                // Red box financial columns
                'revenue_before_vat' => round($revenueBeforeVat),
                'vat_amount' => round($vatAmount),
                'total_amount_incl_vat' => round($totalAmountInclVat),
                'goods_cost' => round($goodsCost),
                'implementation_cost' => round($contractImplementationCost),
                'contractor_tax' => round($contractorTax),
                'finance_cost' => round($financeCost),
                'management_cost' => round($managementCost),
                'support_247_cost' => round($support247Cost),
                'other_support_cost' => round($otherSupportCost),
                'overdue_interest_cost' => round($overdueInterestCost),
                'total_cost' => round($totalCost),
                // Margin & Salesperson
                'margin' => round($margin),
                'margin_percent' => $marginPercentRound,
                'salesperson' => $sale->user->name ?? '',
                // Payment
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
                SUM(discount) as discount_percent_sum,
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
            'revenue' => [
                'amount' => $revenue,
                'percent' => 100,
            ],
            'cogs' => [
                'amount' => $cogs,
                'percent' => round(($cogs / $base) * 100, 1),
            ],
            'expenses' => [
                'amount' => $expenses,
                'percent' => round(($expenses / $base) * 100, 1),
            ],
            'profit' => [
                'amount' => $profit,
                'percent' => round(($profit / $base) * 100, 1),
            ],
        ];
    }

    public function export(Request $request)
    {
        // The primary Export Excel button must export the same filtered report,
        // rather than returning a placeholder message.
        return $this->exportMargin($request);
    }

    /**
     * Export Margin Report to Excel.
     */
    public function exportMargin(Request $request)
    {
        $this->authorize('export', \App\Models\SaleReport::class);
        $request->validate([
            'payment_percent_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_percent_max' => ['nullable', 'numeric', 'min:0', 'max:100', 'gte:payment_percent_min'],
            'margin_percent_min' => ['nullable', 'numeric'],
            'margin_percent_max' => ['nullable', 'numeric'],
        ]);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $customerId = $request->input('customer_id');
        $productId = $request->input('product_id');
        $userId = $request->input('user_id');
        $vendorId = $request->input('vendor_id');
        $productType = $request->input('product_type');
        $search = $request->input('search');
        $paymentState = $request->input('payment_state');
        $paymentPercentMin = $request->input('payment_percent_min');
        $paymentPercentMax = $request->input('payment_percent_max');
        $marginPercentMin = $request->input('margin_percent_min');
        $marginPercentMax = $request->input('margin_percent_max');
        $revenueMin = $request->input('revenue_min');
        $revenueMax = $request->input('revenue_max');
        $totalMin = $request->input('total_min');
        $totalMax = $request->input('total_max');
        $costMin = $request->input('cost_min');
        $costMax = $request->input('cost_max');
        $hasVat = $request->input('has_vat');
        $hasImplementationCost = $request->input('has_implementation_cost');
        $hasContractorTax = $request->input('has_contractor_tax');
        $hasFinanceCost = $request->input('has_finance_cost');
        $hasManagementCost = $request->input('has_management_cost');
        $hasSupport247 = $request->input('has_support_247');
        $hasOtherSupport = $request->input('has_other_support');
        $marginMin = $request->input('margin_min');
        $marginMax = $request->input('margin_max');

        // Preserve the same data boundary as the on-screen report.
        if (!$request->user()->can('view_all_sales')) {
            $userId = $request->user()->id;
        }

        $marginReport = $this->getMarginReport(
            $dateFrom, $dateTo, $customerId, $productId, $userId, $vendorId,
            $productType, $search, $paymentState, $paymentPercentMin, $paymentPercentMax,
            $marginPercentMin, $marginPercentMax,
            $revenueMin, $revenueMax, $totalMin, $totalMax, $costMin, $costMax,
            $hasVat, $hasImplementationCost, $hasContractorTax, $hasFinanceCost,
            $hasManagementCost, $hasSupport247, $hasOtherSupport, $marginMin, $marginMax
        );

        $fromFormatted = date('d/m/Y', strtotime($dateFrom));
        $toFormatted = date('d/m/Y', strtotime($dateTo));

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Báo cáo Margin');

        // ── Styles ──
        $headerDarkFill = [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '1a3a5c'], // Navy Blue
        ];
        $headerLightFill = [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '79b5e5'], // Light Blue (as requested in template)
        ];
        $headerFontWhite = [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 10,
            'name' => 'Arial',
        ];
        $headerFontDark = [
            'bold' => true,
            'color' => ['rgb' => '0d2a4a'],
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
        $sheet->mergeCells('A1:W1');
        $sheet->setCellValue('A1', "Báo cáo Lãi/Lỗ (Margin) theo đơn hàng (Từ {$fromFormatted} đến {$toFormatted})");
        $sheet->getStyle('A1')->getFont()->applyFromArray($titleFont);

        // ── Row 3: Header ──
        $headers = [
            'A3' => 'STT',
            'B3' => 'Tên khách hàng',
            'C3' => "Số Hóa đơn tài chính\n(hoặc Số đơn hàng khởi tạo theo phần mềm)",
            'D3' => 'Ngày xuất hóa đơn',
            'E3' => 'HÃNG',
            'F3' => 'License',
            'G3' => 'Loại hàng',
            'H3' => 'Mã hàng hóa chính',
            // Red box financial columns (I to R)
            'I3' => "Tiền hàng\n(chưa gồm VAT)",
            'J3' => "Tiền thuế\n(VAT)",
            'K3' => "Tổng tiền\n(gồm VAT)",
            'L3' => "Giá vốn\nhàng hóa",
            'M3' => "Chi phí triển khai hợp đồng\n(Tiếp khách, cấu hình cài đặt, v..v)",
            'N3' => "Thuế nhà thầu",
            'O3' => "Chi phí\ntài chính 1%",
            'P3' => "Chi phí Quản lí,\nBack Office & kỹ thuật",
            'Q3' => "24x7\n(0.5%)",
            'R3' => "Other Support\n(Zyxel)",
            // S to W
            'S3' => 'Margin',
            'T3' => 'Margin %',
            'U3' => 'NV Kinh doanh',
            'V3' => "Tổng Tiền KH\nđã thanh toán",
            'W3' => "Tỷ lệ KH đã\nthanh toán (%)",
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        // Apply dark header styles for A3:H3 & S3:W3
        $sheet->getStyle('A3:H3')->applyFromArray([
            'fill' => $headerDarkFill,
            'font' => $headerFontWhite,
            'borders' => $borderAll,
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getStyle('S3:W3')->applyFromArray([
            'fill' => $headerDarkFill,
            'font' => $headerFontWhite,
            'borders' => $borderAll,
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Apply light blue header styles for I3:R3 (red box columns)
        $sheet->getStyle('I3:R3')->applyFromArray([
            'fill' => $headerLightFill,
            'font' => $headerFontDark,
            'borders' => $borderAll,
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(50);

        // ── Column widths ──
        $widths = [
            'A' => 6, 'B' => 28, 'C' => 22, 'D' => 14, 'E' => 14, 'F' => 10, 'G' => 16, 'H' => 18,
            'I' => 18, 'J' => 15, 'K' => 18, 'L' => 18, 'M' => 25, 'N' => 15, 'O' => 16, 'P' => 22,
            'Q' => 14, 'R' => 16, 'S' => 16, 'T' => 12, 'U' => 18, 'V' => 18, 'W' => 14
        ];
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
            // Red box columns I to R
            $sheet->setCellValue("I{$row}", $data['revenue_before_vat']);
            $sheet->setCellValue("J{$row}", $data['vat_amount']);
            $sheet->setCellValue("K{$row}", $data['total_amount_incl_vat']);
            $sheet->setCellValue("L{$row}", $data['goods_cost']);
            $sheet->setCellValue("M{$row}", $data['implementation_cost']);
            $sheet->setCellValue("N{$row}", $data['contractor_tax']);
            $sheet->setCellValue("O{$row}", $data['finance_cost']);
            $sheet->setCellValue("P{$row}", $data['management_cost']);
            $sheet->setCellValue("Q{$row}", $data['support_247_cost']);
            $sheet->setCellValue("R{$row}", $data['other_support_cost']);
            // S to W
            $sheet->setCellValue("S{$row}", $data['margin']);
            $sheet->setCellValue("T{$row}", $data['margin_percent'] / 100);
            $sheet->setCellValue("U{$row}", $data['salesperson']);
            $sheet->setCellValue("V{$row}", $data['paid_amount']);
            $sheet->setCellValue("W{$row}", $data['payment_percent'] / 100);

            // Format numbers
            $sheet->getStyle("I{$row}:S{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("T{$row}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("V{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("W{$row}")->getNumberFormat()->setFormatCode('0%');

            // Margin color: red if negative, green if positive
            if ($data['margin'] < 0) {
                $sheet->getStyle("S{$row}")->getFont()->getColor()->setRGB('CC0000');
            } else {
                $sheet->getStyle("S{$row}")->getFont()->getColor()->setRGB('006600');
            }

            // Alignment
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$row}:H{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$row}:S{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("T{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("V{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("W{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Alternate row colors
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:W{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle("A{$row}:W{$row}")->getFill()->getStartColor()->setRGB('F4F8FA');
            }

            $row++;
        }

        // Data borders
        $lastRow = $row - 1;
        if ($lastRow >= 4) {
            $sheet->getStyle("A4:W{$lastRow}")->applyFromArray([
                'borders' => $borderAll,
                'font' => ['size' => 10, 'name' => 'Arial'],
            ]);

            // Summary row in Excel
            $sumRow = $row;
            $sheet->setCellValue("A{$sumRow}", "TỔNG CỘNG");
            $sheet->mergeCells("A{$sumRow}:H{$sumRow}");
            $sheet->setCellValue("I{$sumRow}", "=SUM(I4:I{$lastRow})");
            $sheet->setCellValue("J{$sumRow}", "=SUM(J4:J{$lastRow})");
            $sheet->setCellValue("K{$sumRow}", "=SUM(K4:K{$lastRow})");
            $sheet->setCellValue("L{$sumRow}", "=SUM(L4:L{$lastRow})");
            $sheet->setCellValue("M{$sumRow}", "=SUM(M4:M{$lastRow})");
            $sheet->setCellValue("N{$sumRow}", "=SUM(N4:N{$lastRow})");
            $sheet->setCellValue("O{$sumRow}", "=SUM(O4:O{$lastRow})");
            $sheet->setCellValue("P{$sumRow}", "=SUM(P4:P{$lastRow})");
            $sheet->setCellValue("Q{$sumRow}", "=SUM(Q4:Q{$lastRow})");
            $sheet->setCellValue("R{$sumRow}", "=SUM(R4:R{$lastRow})");
            $sheet->setCellValue("S{$sumRow}", "=SUM(S4:S{$lastRow})");
            $sheet->setCellValue("T{$sumRow}", "=IF(I{$sumRow}>0, S{$sumRow}/I{$sumRow}, 0)");
            $sheet->setCellValue("V{$sumRow}", "=SUM(V4:V{$lastRow})");
            $sheet->setCellValue("W{$sumRow}", "=IF(K{$sumRow}>0, V{$sumRow}/K{$sumRow}, 0)");

            $sheet->getStyle("A{$sumRow}:W{$sumRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial'],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8EFF5']],
                'borders' => $borderAll,
            ]);
            $sheet->getStyle("A{$sumRow}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("I{$sumRow}:S{$sumRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("T{$sumRow}")->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle("V{$sumRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("W{$sumRow}")->getNumberFormat()->setFormatCode('0%');
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
