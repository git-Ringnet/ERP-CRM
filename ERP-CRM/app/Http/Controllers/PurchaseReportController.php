<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\ImportItem;
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
        $this->authorize('viewAny', \App\Models\PurchaseReport::class);
        
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $supplierId = $request->input('supplier_id');
        $productId = $request->input('product_id');
        $productSearch = $request->input('product_search');

        // If product_id is provided but product_search doesn't match it, reset product_id
        if ($productId && !empty($productSearch)) {
            $selectedProduct = \App\Models\Product::find($productId);
            if ($selectedProduct && !str_contains($productSearch, $selectedProduct->code)) {
                $productId = null;
            }
        }

        // If product_id is empty but product_search is filled, try to resolve matching product
        if (empty($productId) && !empty($productSearch)) {
            $resolvedProduct = \App\Models\Product::where('code', $productSearch)
                ->orWhere('code', 'like', "%{$productSearch}%")
                ->orWhere('name', 'like', "%{$productSearch}%")
                ->first();
            if ($resolvedProduct) {
                $productId = $resolvedProduct->id;
            }
        }

        // Summary statistics
        $stats = $this->getSummaryStats($dateFrom, $dateTo, $supplierId, $productId);

        // Supplier report
        $supplierReport = $this->getSupplierReport($dateFrom, $dateTo, $supplierId, $productId);

        // Product report
        $productReport = $this->getProductReport($dateFrom, $dateTo, $productId, $supplierId);

        // Monthly report
        $monthlyReport = $this->getMonthlyReport($dateFrom, $dateTo, $supplierId, $productId);

        // Tracking report (Theo dõi hàng về)
        $trackingReport = $this->getTrackingReport($request);

        // Get suppliers for filter dropdown
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        
        // Optimize: Only load the selected product to avoid large dropdown lag
        $products = collect();
        if ($productId) {
            $selectedProduct = \App\Models\Product::find($productId);
            if ($selectedProduct) {
                $products->push($selectedProduct);
            }
        }
        
        return view('purchase-reports.index', compact(
            'stats', 'supplierReport', 'productReport', 'monthlyReport',
            'trackingReport', 'suppliers', 'products',
            'dateFrom', 'dateTo', 'supplierId', 'productId', 'productSearch'
        ));
    }

    private function getSummaryStats($dateFrom, $dateTo, $supplierId = null, $productId = null): array
    {
        $query = PurchaseOrder::whereBetween('order_date', [$dateFrom, $dateTo]);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($productId) {
            $query->whereHas('items', function($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_orders,
            SUM(total) as total_amount,
            SUM(CASE WHEN total_foreign > 0 THEN total_foreign ELSE total / NULLIF(exchange_rate, 0) END) as total_amount_usd,
            SUM(subtotal * exchange_rate) as total_subtotal,
            SUM(discount_amount * exchange_rate) as total_discount,
            SUM(shipping_cost * exchange_rate) as total_shipping,
            SUM(paid_amount) as total_paid
        ')->first();

        return [
            'total_orders' => $stats->total_orders ?? 0,
            'total_amount' => $stats->total_amount ?? 0,
            'total_amount_usd' => $stats->total_amount_usd ?? 0,
            'total_discount' => $stats->total_discount ?? 0,
            'total_shipping' => $stats->total_shipping ?? 0,
            'total_paid' => $stats->total_paid ?? 0,
        ];
    }

    private function getSupplierReport($dateFrom, $dateTo, $supplierId = null, $productId = null): array
    {
        $query = PurchaseOrder::select(
                'supplier_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('SUM(CASE WHEN total_foreign > 0 THEN total_foreign ELSE total / NULLIF(exchange_rate, 0) END) as total_amount_usd'),
                DB::raw('SUM(subtotal * exchange_rate) as total_subtotal'),
                DB::raw('SUM(CASE WHEN total_foreign > 0 THEN (subtotal * exchange_rate) / NULLIF(exchange_rate, 0) ELSE subtotal END) as total_subtotal_usd'),
                DB::raw('SUM(discount_amount * exchange_rate) as total_discount'),
                DB::raw('SUM(shipping_cost * exchange_rate) as total_shipping'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('SUM(CASE WHEN total_foreign > 0 THEN paid_amount / NULLIF(exchange_rate, 0) ELSE paid_amount / NULLIF(exchange_rate, 0) END) as total_paid_usd')
            )
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->groupBy('supplier_id')
            ->with('supplier');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($productId) {
            $query->whereHas('items', function($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }

        $results = $query->get();

        // Eager-load individual orders with relevant relationships to avoid N+1 queries
        $allOrdersQuery = PurchaseOrder::whereBetween('order_date', [$dateFrom, $dateTo])
            ->with(['currency', 'items.saleOrderRequestItem.saleOrderRequest.sale.user', 'sale.user'])
            ->orderBy('order_date', 'desc');

        if ($supplierId) {
            $allOrdersQuery->where('supplier_id', $supplierId);
        }

        if ($productId) {
            $allOrdersQuery->whereHas('items', function($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }

        $allOrders = $allOrdersQuery->get()->groupBy('supplier_id');

        return $results->map(function ($item) use ($allOrders) {
            $discountRate = $item->total_subtotal > 0 
                ? ($item->total_discount / $item->total_subtotal) * 100 
                : 0;

            $supplierOrders = $allOrders->get($item->supplier_id, collect())->map(function($po) {
                $val = $po->total_foreign ?? ($po->total / ($po->exchange_rate ?: 1));
                $decimals = (floor($val) == $val) ? 0 : ($po->currency->decimal_places ?? 2);

                return [
                    'id' => $po->id,
                    'code' => $po->code,
                    'order_date' => $po->order_date ? $po->order_date->format('d/m/Y') : 'N/A',
                    'linked_so_codes' => $po->linked_so_codes,
                    'linked_salesperson_names' => $po->linked_salesperson_names,
                    'total_usd' => number_format($val, $decimals),
                    'total_vnd' => number_format($po->total, 0, ',', '.'),
                    'discount_vnd' => number_format($po->discount_amount, 0, ',', '.'),
                    'shipping_cost_vnd' => number_format($po->shipping_cost, 0, ',', '.'),
                    'paid_vnd' => number_format($po->paid_amount, 0, ',', '.'),
                    'status_label' => $po->status_label,
                    'status_color' => $po->status_color,
                    'payment_status_label' => $po->payment_status_label,
                    'payment_status_color' => $po->payment_status_color,
                ];
            })->toArray();

            return [
                'supplier_id' => $item->supplier_id,
                'supplier' => $item->supplier->name ?? 'N/A',
                'order_count' => $item->order_count,
                'total_amount' => $item->total_amount,
                'total_amount_usd' => $item->total_amount_usd,
                'total_subtotal' => $item->total_subtotal,
                'total_discount' => $item->total_discount,
                'total_shipping' => $item->total_shipping,
                'total_paid' => $item->total_paid,
                'total_paid_usd' => $item->total_paid_usd,
                'discount_rate' => round($discountRate, 1),
                'orders' => $supplierOrders,
            ];
        })->toArray();
    }

    private function getProductReport($dateFrom, $dateTo, $productId = null, $supplierId = null): array
    {
        $query = ImportItem::query()
            ->join('imports', 'import_items.import_id', '=', 'imports.id')
            ->leftJoin('purchase_orders', function($join) {
                $join->on('imports.reference_id', '=', 'purchase_orders.id')
                     ->where('imports.reference_type', '=', 'purchase_order');
            })
            ->select(
                'import_items.product_id',
                DB::raw('SUM(import_items.quantity) as total_quantity'),
                DB::raw('AVG(import_items.cost) as avg_purchase_price'),
                DB::raw('AVG(import_items.cost / NULLIF(COALESCE(purchase_orders.exchange_rate, 25000), 0)) as avg_purchase_price_usd'),
                DB::raw('SUM(import_items.cost * import_items.quantity) as total_value'),
                DB::raw('SUM((import_items.cost / NULLIF(COALESCE(purchase_orders.exchange_rate, 25000), 0)) * import_items.quantity) as total_value_usd'),
                DB::raw('AVG(import_items.warehouse_price) as avg_warehouse_price'),
                DB::raw('SUM((import_items.warehouse_price - import_items.cost) * import_items.quantity) as total_service_cost'),
                DB::raw('COUNT(DISTINCT import_items.import_id) as import_count')
            )
            ->whereBetween('imports.date', [$dateFrom, $dateTo])
            ->where('imports.status', 'completed')
            ->groupBy('import_items.product_id')
            ->with('product');

        if ($productId) {
            $query->where('import_items.product_id', $productId);
        }

        if ($supplierId) {
            $query->where('imports.supplier_id', $supplierId);
        }

        $results = $query->get();

        return $results->map(function ($item) use ($supplierId) {
            // Get unique suppliers for this product
            $supplierCountQuery = ImportItem::where('product_id', $item->product_id)
                ->whereHas('import', function($q) use ($item, $supplierId) {
                    $q->where('status', 'completed');
                    if ($supplierId) {
                        $q->where('supplier_id', $supplierId);
                    }
                })
                ->join('imports', 'import_items.import_id', '=', 'imports.id')
                ->distinct('imports.supplier_id');

            $supplierCount = $supplierCountQuery->count('imports.supplier_id');

            $supplierNamesList = ImportItem::where('product_id', $item->product_id)
                ->whereHas('import', function($q) use ($supplierId) {
                    $q->where('status', 'completed');
                    if ($supplierId) {
                        $q->where('supplier_id', $supplierId);
                    }
                })
                ->join('imports', 'import_items.import_id', '=', 'imports.id')
                ->join('suppliers', 'imports.supplier_id', '=', 'suppliers.id')
                ->distinct('suppliers.name')
                ->pluck('suppliers.name')
                ->toArray();

            $supplierNames = implode(', ', $supplierNamesList) ?: 'N/A';

            return [
                'product_code' => $item->product->code ?? 'N/A',
                'product_name' => $item->product->name ?? 'N/A',
                'supplier_names' => $supplierNames,
                'product' => $item->product->code ?? 'N/A',
                'total_quantity' => $item->total_quantity,
                'avg_purchase_price' => $item->avg_purchase_price,
                'avg_purchase_price_usd' => $item->avg_purchase_price_usd,
                'total_value' => $item->total_value,
                'total_value_usd' => $item->total_value_usd,
                'avg_warehouse_price' => $item->avg_warehouse_price,
                'total_service_cost' => $item->total_service_cost ?? 0,
                'import_count' => $item->import_count,
                'supplier_count' => $supplierCount,
            ];
        })->toArray();
    }

    private function getMonthlyReport($dateFrom, $dateTo, $supplierId = null, $productId = null): array
    {
        $query = PurchaseOrder::query();

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($productId) {
            $query->whereHas('items', function($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }

        $results = $query->select(
                DB::raw("DATE_FORMAT(order_date, '%Y-%m') as period"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('SUM(CASE WHEN total_foreign > 0 THEN total_foreign ELSE total / NULLIF(exchange_rate, 0) END) as total_amount_usd'),
                DB::raw('SUM(subtotal * exchange_rate) as total_subtotal'),
                DB::raw('SUM(discount_amount * exchange_rate) as total_discount'),
                DB::raw('SUM(shipping_cost * exchange_rate) as total_shipping'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('SUM(paid_amount / NULLIF(exchange_rate, 0)) as total_paid_usd')
            )
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->get();

        $report = [];
        $previousTotal = null;

        foreach ($results as $item) {
            $change = null;
            if ($previousTotal !== null && $previousTotal > 0) {
                $change = (($item->total_paid - $previousTotal) / $previousTotal) * 100;
            }

            $report[] = [
                'month' => $item->period,
                'order_count' => $item->order_count,
                'total_amount' => $item->total_amount,
                'total_amount_usd' => $item->total_amount_usd,
                'total_subtotal' => $item->total_subtotal,
                'total_discount' => $item->total_discount,
                'total_shipping' => $item->total_shipping,
                'total_paid' => $item->total_paid,
                'total_paid_usd' => $item->total_paid_usd,
                'change' => $change !== null ? round($change, 1) : null,
            ];

            $previousTotal = $item->total_paid;
        }

        return $report;
    }



    private function getTrackingReport(Request $request): array
    {
        $query = \App\Models\SaleOrderRequestItem::with(['saleOrderRequest.sale', 'vendor', 'purchaseOrderItems.purchaseOrder', 'saleItem']);

        // Filter by Date
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $query->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        // Filter by Sales Order Code
        if ($request->filled('sale_code')) {
            $query->whereHas('saleOrderRequest.sale', function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->sale_code . '%');
            });
        }

        // Filter by Part Number (From Direct input or resolved product_id)
        if ($request->filled('product_id')) {
            $product = \App\Models\Product::find($request->product_id);
            if ($product) {
                $query->where('part_number', $product->code);
            }
        } elseif ($request->filled('product_search')) {
            $query->where('part_number', 'like', '%' . $request->product_search . '%');
        } elseif ($request->filled('part_number')) {
            $query->where('part_number', 'like', '%' . $request->part_number . '%');
        }

        // Filter by Vendor
        if ($request->filled('supplier_id')) {
            $query->where('vendor_id', $request->supplier_id);
        }

        $allItems = $query->latest()->get();

        $grouped = [];
        foreach ($allItems as $item) {
            $saleCode = $item->saleOrderRequest->sale->code ?? 'N/A';
            $saleId = $item->saleOrderRequest->sale_id ?? 0;
            $key = $saleId . '-' . ($item->part_number ?? 'no-pn');

            if (!isset($grouped[$key])) {
                // Lấy giá kho tạm tính (USD) từ SaleItem nếu chưa có PO
                $estimatedPrice = $item->saleItem ? ($item->saleItem->estimated_cost_usd ?? 0) : 0;
                
                $grouped[$key] = [
                    'sale_id' => $saleId,
                    'sale_code' => $saleCode,
                    'part_number' => $item->part_number,
                    'vendor_name' => $item->vendor->name ?? $item->vendor ?? 'N/A',
                    'requested' => 0,
                    'ordered' => 0,
                    'received' => 0,
                    'unit_price_usd' => $estimatedPrice, // Mặc định lấy từ Sale
                    'pr_codes' => [],
                    'po_links' => [], 
                    'created_at' => $item->created_at,
                ];
            }

            $ordered = $item->ordered_quantity_total;
            $received = $item->received_quantity_total;

            $grouped[$key]['requested'] += $item->quantity;
            $grouped[$key]['ordered'] += $ordered;
            $grouped[$key]['received'] += $received;

            // Nếu đã có PO, lấy giá từ PO (ưu tiên warehouse_unit_price)
            if ($item->purchaseOrderItems->count() > 0) {
                $lastPoItem = $item->purchaseOrderItems->last();
                $grouped[$key]['unit_price_usd'] = $lastPoItem->warehouse_unit_price ?: $lastPoItem->unit_price ?: $grouped[$key]['unit_price_usd'];
            }

            $prCode = $item->saleOrderRequest->code ?? '';
            if ($prCode && !in_array($prCode, $grouped[$key]['pr_codes'])) {
                $grouped[$key]['pr_codes'][] = $prCode;
            }

            foreach ($item->purchaseOrderItems as $poItem) {
                $poId = $poItem->purchase_order_id;
                if (!isset($grouped[$key]['po_links'][$poId])) {
                    $grouped[$key]['po_links'][$poId] = [
                        'id' => $poId,
                        'code' => $poItem->purchaseOrder->code ?? '',
                        'status_label' => $poItem->purchaseOrder->status_label ?? '',
                    ];
                }
            }
        }

        foreach ($grouped as &$row) {
            $row['po_links'] = array_values($row['po_links']);
            $row['remaining'] = max(0, $row['requested'] - $row['received']);
            $row['total_usd'] = $row['requested'] * $row['unit_price_usd'];

            if ($row['ordered'] <= 0) {
                $row['status'] = 'waiting';
                $row['status_label'] = 'Chờ đặt hàng';
                $row['status_color'] = 'bg-gray-100 text-gray-600';
                $row['status_icon'] = 'fas fa-clock';
            } elseif ($row['ordered'] < $row['requested']) {
                $row['status'] = 'ordering';
                $row['status_label'] = 'Đang đặt hàng';
                $row['status_color'] = 'bg-blue-100 text-blue-800';
                $row['status_icon'] = 'fas fa-shopping-cart';
            } elseif ($row['received'] < $row['requested']) {
                $row['status'] = 'in_transit';
                $row['status_label'] = 'Đang về hàng';
                $row['status_color'] = 'bg-orange-100 text-orange-800';
                $row['status_icon'] = 'fas fa-truck';
            } else {
                $row['status'] = 'completed';
                $row['status_label'] = 'Đã đủ hàng';
                $row['status_color'] = 'bg-green-100 text-green-800';
                $row['status_icon'] = 'fas fa-check-circle';
            }
        }

        return array_values($grouped);
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', \App\Models\PurchaseReport::class);
        
        $type = $request->input('report_type', 'tracking');
        
        if ($type === 'tracking') {
            $data = $this->getTrackingReport($request);
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PurchaseTrackingExport($data), 
                'bao-cao-theo-doi-hang-ve-' . date('Ymd') . '.xlsx'
            );
        }

        return redirect()->back()->with('error', 'Loại báo cáo không hợp lệ!');
    }
}
