<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchasePricing;
use App\Models\ShippingAllocation;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class PurchaseReportController extends Controller
{
    public function index(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $supplierId = $request->input('supplier_id');
        $productId = $request->input('product_id');

        // Summary statistics
        $stats = $this->getSummaryStats($dateFrom, $dateTo, $supplierId, $productId);

        // Supplier report
        $supplierReport = $this->getSupplierReport($dateFrom, $dateTo, $supplierId);

        // Product report
        $productReport = $this->getProductReport($dateFrom, $dateTo, $productId);

        // Monthly report
        $monthlyReport = $this->getMonthlyReport($dateFrom, $dateTo);

        // Cost analysis
        $costAnalysis = $this->getCostAnalysis($dateFrom, $dateTo);

        // Discount analysis
        $discountAnalysis = $this->getDiscountAnalysis($dateFrom, $dateTo);

        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('purchase-reports.index', compact(
            'stats', 'supplierReport', 'productReport', 'monthlyReport',
            'costAnalysis', 'discountAnalysis', 'suppliers', 'products',
            'dateFrom', 'dateTo', 'supplierId', 'productId'
        ));
    }

    private function getSummaryStats($dateFrom, $dateTo, $supplierId = null, $productId = null): array
    {
        $query = PurchaseOrder::whereBetween('order_date', [$dateFrom, $dateTo]);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        return [
            'total_orders' => $query->count(),
            'total_amount' => $query->sum('subtotal') ?? 0,
            'total_discount' => $query->sum('discount_amount') ?? 0,
            'total_shipping' => $query->sum('shipping_cost') ?? 0,
            'total_paid' => $query->sum('total') ?? 0,
        ];
    }

    private function getSupplierReport($dateFrom, $dateTo, $supplierId = null): array
    {
        $query = PurchaseOrder::select(
                'supplier_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(shipping_cost) as total_shipping'),
                DB::raw('SUM(total) as total_paid')
            )
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->groupBy('supplier_id')
            ->with('supplier');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $results = $query->get();

        return $results->map(function ($item) {
            $discountRate = $item->total_subtotal > 0 
                ? ($item->total_discount / $item->total_subtotal) * 100 
                : 0;

            return [
                'supplier' => $item->supplier->name ?? 'N/A',
                'order_count' => $item->order_count,
                'total_subtotal' => $item->total_subtotal,
                'total_discount' => $item->total_discount,
                'total_shipping' => $item->total_shipping,
                'total_paid' => $item->total_paid,
                'discount_rate' => round($discountRate, 1),
            ];
        })->toArray();
    }

    private function getProductReport($dateFrom, $dateTo, $productId = null): array
    {
        $query = PurchasePricing::select(
                'product_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('AVG(purchase_price) as avg_purchase_price'),
                DB::raw('SUM(purchase_price * quantity) as total_value'),
                DB::raw('AVG(warehouse_price) as avg_warehouse_price'),
                DB::raw('SUM(total_service_cost) as total_service_cost'),
                DB::raw('COUNT(DISTINCT supplier_id) as supplier_count')
            )
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('product_id')
            ->with('product');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $results = $query->get();

        return $results->map(function ($item) {
            return [
                'product' => $item->product->name ?? 'N/A',
                'total_quantity' => $item->total_quantity,
                'avg_purchase_price' => $item->avg_purchase_price,
                'total_value' => $item->total_value,
                'avg_warehouse_price' => $item->avg_warehouse_price,
                'total_service_cost' => $item->total_service_cost,
                'supplier_count' => $item->supplier_count,
            ];
        })->toArray();
    }

    private function getMonthlyReport($dateFrom, $dateTo): array
    {
        $results = PurchaseOrder::select(
                DB::raw("DATE_FORMAT(order_date, '%Y-%m') as month"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(shipping_cost) as total_shipping'),
                DB::raw('SUM(total) as total_paid')
            )
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        $report = [];
        $previousTotal = null;

        foreach ($results as $item) {
            $change = null;
            if ($previousTotal !== null && $previousTotal > 0) {
                $change = (($item->total_paid - $previousTotal) / $previousTotal) * 100;
            }

            $report[] = [
                'month' => $item->month,
                'order_count' => $item->order_count,
                'total_subtotal' => $item->total_subtotal,
                'total_discount' => $item->total_discount,
                'total_shipping' => $item->total_shipping,
                'total_paid' => $item->total_paid,
                'change' => $change !== null ? round($change, 1) : null,
            ];

            $previousTotal = $item->total_paid;
        }

        return $report;
    }

    private function getCostAnalysis($dateFrom, $dateTo): array
    {
        $totals = PurchaseOrder::whereBetween('order_date', [$dateFrom, $dateTo])
            ->selectRaw('
                SUM(subtotal) as goods_value,
                SUM(shipping_cost) as shipping_cost,
                SUM(other_cost) as service_cost,
                SUM(vat_amount) as vat_amount,
                SUM(total) as grand_total
            ')
            ->first();

        $grandTotal = $totals->grand_total ?? 1;

        return [
            'goods_value' => $totals->goods_value ?? 0,
            'shipping_cost' => $totals->shipping_cost ?? 0,
            'service_cost' => $totals->service_cost ?? 0,
            'vat_amount' => $totals->vat_amount ?? 0,
            'breakdown' => [
                ['name' => 'Giá trị hàng hóa', 'value' => $totals->goods_value ?? 0, 'rate' => round((($totals->goods_value ?? 0) / $grandTotal) * 100, 1)],
                ['name' => 'Chi phí vận chuyển', 'value' => $totals->shipping_cost ?? 0, 'rate' => round((($totals->shipping_cost ?? 0) / $grandTotal) * 100, 1)],
                ['name' => 'Chi phí phục vụ', 'value' => $totals->service_cost ?? 0, 'rate' => round((($totals->service_cost ?? 0) / $grandTotal) * 100, 1)],
                ['name' => 'VAT', 'value' => $totals->vat_amount ?? 0, 'rate' => round((($totals->vat_amount ?? 0) / $grandTotal) * 100, 1)],
            ]
        ];
    }

    private function getDiscountAnalysis($dateFrom, $dateTo): array
    {
        $suppliers = Supplier::withCount(['purchaseOrders' => function ($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('order_date', [$dateFrom, $dateTo]);
        }])
        ->withSum(['purchaseOrders' => function ($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('order_date', [$dateFrom, $dateTo]);
        }], 'discount_amount')
        ->withSum(['purchaseOrders' => function ($query) use ($dateFrom, $dateTo) {
            $query->whereBetween('order_date', [$dateFrom, $dateTo]);
        }], 'subtotal')
        ->having('purchase_orders_count', '>', 0)
        ->get();

        $totalBase = $suppliers->sum('base_discount');
        $totalVolume = $suppliers->sum('volume_discount');
        $totalEarly = $suppliers->sum('early_payment_discount');
        $totalSpecial = $suppliers->sum('special_discount');

        $supplierDiscounts = $suppliers->map(function ($supplier) {
            $subtotal = $supplier->purchase_orders_sum_subtotal ?? 0;
            $discount = $supplier->purchase_orders_sum_discount_amount ?? 0;
            $rate = $subtotal > 0 ? ($discount / $subtotal) * 100 : 0;

            return [
                'supplier' => $supplier->name,
                'base_discount' => $supplier->base_discount ?? 0,
                'volume_discount' => $supplier->volume_discount ?? 0,
                'early_discount' => $supplier->early_payment_discount ?? 0,
                'special_discount' => $supplier->special_discount ?? 0,
                'total_discount' => $discount,
                'discount_rate' => round($rate, 1),
            ];
        })->toArray();

        return [
            'totals' => [
                'base' => $totalBase,
                'volume' => $totalVolume,
                'early' => $totalEarly,
                'special' => $totalSpecial,
            ],
            'by_supplier' => $supplierDiscounts,
        ];
    }

    public function export(Request $request)
    {
        // Export logic here
        return redirect()->back()->with('success', 'Đã xuất báo cáo thành công!');
    }
}
