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

        $productIds = [];
        if ($productId) {
            $productIds = [$productId];
        } elseif (!empty($productSearch)) {
            $productIds = \App\Models\Product::search($productSearch)->pluck('id')->toArray();
            if (empty($productIds)) {
                $productIds = [-1]; // Ensure empty results when search fails to match
            }
        }

        // Summary statistics
        $stats = $this->getSummaryStats($dateFrom, $dateTo, $supplierId, $productIds);

        // Supplier report
        $supplierReport = $this->getSupplierReport($dateFrom, $dateTo, $supplierId, $productIds);

        // Product report
        $productReport = $this->getProductReport($dateFrom, $dateTo, $productIds, $supplierId);

        // Monthly report
        $monthlyReport = $this->getMonthlyReport($dateFrom, $dateTo, $supplierId, $productIds);

        // Tracking report (Theo dõi hàng về)
        $trackingReport = $this->getTrackingReport($request, $productIds);

        // Cancelled orders report
        $cancelledReport = $this->getCancelledOrders($dateFrom, $dateTo, $supplierId, $productIds);

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
            'trackingReport', 'cancelledReport', 'suppliers', 'products',
            'dateFrom', 'dateTo', 'supplierId', 'productId', 'productSearch'
        ));
    }

    private function getSummaryStats($dateFrom, $dateTo, $supplierId = null, $productIds = []): array
    {
        $query = PurchaseOrder::where('status', '!=', 'cancelled')
            ->whereBetween('order_date', [$dateFrom, $dateTo]);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if (!empty($productIds)) {
            $query->whereHas('items', function($q) use ($productIds) {
                $q->whereIn('product_id', $productIds);
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

    private function getSupplierReport($dateFrom, $dateTo, $supplierId = null, $productIds = []): array
    {
        $query = PurchaseOrder::select(
                'supplier_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('SUM(CASE WHEN total_foreign > 0 THEN total_foreign ELSE total / NULLIF(exchange_rate, 0) END) as total_amount_usd'),
                DB::raw('SUM(subtotal * exchange_rate) as total_subtotal'),
                DB::raw('SUM(CASE WHEN total_foreign > 0 THEN (subtotal * exchange_rate) / NULLIF(exchange_rate, 0) ELSE subtotal END) as total_subtotal_usd'),
                DB::raw('SUM(discount_amount * exchange_rate) as total_discount'),
                DB::raw('SUM(discount_amount) as total_discount_usd'),
                DB::raw('SUM(shipping_cost * exchange_rate) as total_shipping'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('SUM(CASE WHEN total_foreign > 0 THEN paid_amount / NULLIF(exchange_rate, 0) ELSE paid_amount / NULLIF(exchange_rate, 0) END) as total_paid_usd')
            )
            ->where('status', '!=', 'cancelled')
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->groupBy('supplier_id')
            ->with('supplier');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if (!empty($productIds)) {
            $query->whereHas('items', function($q) use ($productIds) {
                $q->whereIn('product_id', $productIds);
            });
        }

        $results = $query->get();

        // Eager-load individual orders with relevant relationships to avoid N+1 queries
        $allOrdersQuery = PurchaseOrder::where('status', '!=', 'cancelled')
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->with(['currency', 'items.saleOrderRequestItem.saleOrderRequest.sale.user', 'items.saleOrderRequestItem.saleOrderRequest.sale.project', 'sale.user', 'sale.project'])
            ->orderBy('order_date', 'desc');

        if ($supplierId) {
            $allOrdersQuery->where('supplier_id', $supplierId);
        }

        if (!empty($productIds)) {
            $allOrdersQuery->whereHas('items', function($q) use ($productIds) {
                $q->whereIn('product_id', $productIds);
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
                    'linked_partner_names' => $po->linked_partner_names,
                    'linked_end_user_names' => $po->linked_end_user_names,
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
                'total_discount_usd' => $item->total_discount_usd,
                'total_shipping' => $item->total_shipping,
                'total_paid' => $item->total_paid,
                'total_paid_usd' => $item->total_paid_usd,
                'discount_rate' => round($discountRate, 1),
                'orders' => $supplierOrders,
            ];
        })->toArray();
    }

    private function getProductReport($dateFrom, $dateTo, $productIds = [], $supplierId = null): array
    {
        // Query từ purchase_order_items JOIN purchase_orders (nhất quán với Supplier & Monthly Report)
        $query = \App\Models\PurchaseOrderItem::query()
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->select(
                'purchase_order_items.product_id',
                DB::raw('SUM(purchase_order_items.quantity) as total_quantity'),
                DB::raw('AVG(purchase_order_items.unit_price) as avg_purchase_price_usd'),
                DB::raw('AVG(purchase_order_items.unit_price * purchase_orders.exchange_rate) as avg_purchase_price'),
                DB::raw('SUM(purchase_order_items.total) as total_value_usd'),
                DB::raw('SUM(purchase_order_items.total * purchase_orders.exchange_rate) as total_value'),
                DB::raw('AVG(COALESCE(purchase_order_items.warehouse_unit_price, purchase_order_items.unit_price) * purchase_orders.exchange_rate) as avg_warehouse_price'),
                DB::raw('SUM((COALESCE(purchase_order_items.warehouse_unit_price, purchase_order_items.unit_price) - purchase_order_items.unit_price) * purchase_order_items.quantity * purchase_orders.exchange_rate) as total_service_cost'),
                DB::raw('COUNT(DISTINCT purchase_order_items.purchase_order_id) as import_count')
            )
            ->where('purchase_orders.status', '!=', 'cancelled')
            ->whereBetween('purchase_orders.order_date', [$dateFrom, $dateTo])
            ->groupBy('purchase_order_items.product_id');

        if (!empty($productIds)) {
            $query->whereIn('purchase_order_items.product_id', $productIds);
        }

        if ($supplierId) {
            $query->where('purchase_orders.supplier_id', $supplierId);
        }

        $results = $query->get();

        // Load product info separately
        $productIdsInResult = $results->pluck('product_id')->filter()->unique()->toArray();
        $productsMap = Product::whereIn('id', $productIdsInResult)->get()->keyBy('id');

        return $results->map(function ($item) use ($supplierId, $dateFrom, $dateTo, $productsMap) {
            $product = $productsMap->get($item->product_id);

            // Đếm số NCC unique cho sản phẩm này trong date range
            $supplierCountQuery = \App\Models\PurchaseOrderItem::where('product_id', $item->product_id)
                ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                ->where('purchase_orders.status', '!=', 'cancelled')
                ->whereBetween('purchase_orders.order_date', [$dateFrom, $dateTo]);

            if ($supplierId) {
                $supplierCountQuery->where('purchase_orders.supplier_id', $supplierId);
            }

            $supplierCount = (clone $supplierCountQuery)->distinct('purchase_orders.supplier_id')->count('purchase_orders.supplier_id');

            $supplierNamesList = (clone $supplierCountQuery)
                ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                ->distinct('suppliers.name')
                ->pluck('suppliers.name')
                ->toArray();

            $supplierNames = implode(', ', $supplierNamesList) ?: 'N/A';

            return [
                'product_code' => $product->code ?? 'N/A',
                'product_name' => $product->name ?? 'N/A',
                'supplier_names' => $supplierNames,
                'product' => $product->code ?? 'N/A',
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

    private function getMonthlyReport($dateFrom, $dateTo, $supplierId = null, $productIds = []): array
    {
        $query = PurchaseOrder::where('status', '!=', 'cancelled');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if (!empty($productIds)) {
            $query->whereHas('items', function($q) use ($productIds) {
                $q->whereIn('product_id', $productIds);
            });
        }

        // Sort ASC để tính change đúng chiều (tháng cũ → tháng mới)
        $results = $query->select(
                DB::raw("DATE_FORMAT(order_date, '%Y-%m') as period"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('SUM(CASE WHEN total_foreign > 0 THEN total_foreign ELSE total / NULLIF(exchange_rate, 0) END) as total_amount_usd'),
                DB::raw('SUM(subtotal * exchange_rate) as total_subtotal'),
                DB::raw('SUM(discount_amount * exchange_rate) as total_discount'),
                DB::raw('SUM(discount_amount) as total_discount_usd'),
                DB::raw('SUM(shipping_cost * exchange_rate) as total_shipping'),
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('SUM(paid_amount / NULLIF(exchange_rate, 0)) as total_paid_usd')
            )
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        // Tính change theo chiều ASC (so sánh tháng hiện tại vs tháng trước đó)
        $report = [];
        $previousTotal = null;

        foreach ($results as $item) {
            $change = null;
            if ($previousTotal !== null && $previousTotal > 0) {
                $change = (($item->total_amount_usd - $previousTotal) / $previousTotal) * 100;
            }

            $report[] = [
                'month' => $item->period,
                'order_count' => $item->order_count,
                'total_amount' => $item->total_amount,
                'total_amount_usd' => $item->total_amount_usd,
                'total_subtotal' => $item->total_subtotal,
                'total_discount' => $item->total_discount,
                'total_discount_usd' => $item->total_discount_usd,
                'total_shipping' => $item->total_shipping,
                'total_paid' => $item->total_paid,
                'total_paid_usd' => $item->total_paid_usd,
                'change' => $change !== null ? round($change, 1) : null,
            ];

            $previousTotal = $item->total_amount_usd;
        }

        // Reverse để hiển thị DESC (tháng mới nhất ở trên), change đã tính đúng
        return array_reverse($report);
    }

    private function getCancelledOrders($dateFrom, $dateTo, $supplierId = null, $productIds = []): array
    {
        $query = PurchaseOrder::where('status', 'cancelled')
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->with(['supplier', 'currency']);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if (!empty($productIds)) {
            $query->whereHas('items', function($q) use ($productIds) {
                $q->whereIn('product_id', $productIds);
            });
        }

        $results = $query->orderBy('order_date', 'desc')->get();

        return $results->map(function ($po) {
            $val = $po->total_foreign ?? ($po->total / ($po->exchange_rate ?: 1));
            $decimals = (floor($val) == $val) ? 0 : ($po->currency->decimal_places ?? 2);

            return [
                'id' => $po->id,
                'code' => $po->code,
                'order_date' => $po->order_date ? $po->order_date->format('d/m/Y') : 'N/A',
                'supplier_name' => $po->supplier->name ?? 'N/A',
                'linked_so_codes' => $po->linked_so_codes,
                'linked_salesperson_names' => $po->linked_salesperson_names,
                'linked_partner_names' => $po->linked_partner_names,
                'linked_end_user_names' => $po->linked_end_user_names,
                'total_usd' => number_format($val, $decimals),
                'total_vnd' => number_format($po->total, 0, ',', '.'),
                'note' => $po->note,
            ];
        })->toArray();
    }

    private function calculateTimeline($requested, $ordered, $received, $poItems, $sale = null, $prCreatedAt = null)
    {
        $statusWeights = [
            'draft' => 0,
            'pending_approval' => 1,
            'approved' => 2,
            'sent' => 3,
            'confirmed' => 4,
            'shipping' => 5,
            'partial_received' => 6,
            'received' => 7,
        ];

        $representativePo = null;
        $maxStatusWeight = -1;

        foreach ($poItems as $poItem) {
            $po = $poItem instanceof \App\Models\PurchaseOrder ? $poItem : ($poItem->purchaseOrder ?? null);
            if ($po) {
                $weight = $statusWeights[$po->status] ?? 0;
                if ($weight > $maxStatusWeight) {
                    $maxStatusWeight = $weight;
                    $representativePo = $po;
                }
            }
        }

        $timeline = [];

        // 1. PR
        $timeline['pr'] = [
            'label' => 'Yêu cầu (PR)',
            'status' => 'completed',
            'date' => $prCreatedAt ? \Carbon\Carbon::parse($prCreatedAt)->format('d/m/Y') : null,
            'details' => $prCreatedAt ? 'Yêu cầu mua hàng (PR) đã được tạo.' : 'Đơn hàng mua trực tiếp (không qua PR).'
        ];

        // 2. PO
        $poStatus = 'pending';
        $poDate = null;
        $poDetails = 'Chưa đặt hàng (PO).';
        if ($ordered > 0) {
            $poDate = $representativePo ? ($representativePo->order_date ? $representativePo->order_date->format('d/m/Y') : null) : null;
            if ($ordered >= $requested) {
                $poStatus = 'completed';
                $poDetails = "Đã đặt đủ số lượng ({$ordered}/{$requested}).";
            } else {
                $poStatus = 'active';
                $poDetails = "Đang đặt hàng ({$ordered}/{$requested}).";
            }
        }
        $timeline['po'] = [
            'label' => 'Đặt hàng (PO)',
            'status' => $poStatus,
            'date' => $poDate,
            'details' => $poDetails
        ];

        // 3. Vendor Confirm
        $vcStatus = 'pending';
        $vcDate = null;
        $vcDetails = 'Nhà cung cấp chưa xác nhận.';
        if ($representativePo) {
            $confirmedAt = $representativePo->confirmed_at;
            $poStatusVal = $representativePo->status;
            if (in_array($poStatusVal, ['confirmed', 'shipping', 'partial_received', 'received']) || $confirmedAt) {
                $vcStatus = 'completed';
                $vcDate = $confirmedAt ? $confirmedAt->format('d/m/Y') : null;
                $vcDetails = 'Nhà cung cấp đã xác nhận đơn hàng.';
            } elseif (in_array($poStatusVal, ['approved', 'sent'])) {
                $vcStatus = 'active';
                $vcDetails = 'Chờ nhà cung cấp xác nhận.';
            }
        }
        $timeline['vendor_confirm'] = [
            'label' => 'Vendor Confirm',
            'status' => $vcStatus,
            'date' => $vcDate,
            'details' => $vcDetails
        ];

        // 4. Sản xuất
        $prodStatus = 'pending';
        $prodDate = null;
        $prodDetails = 'Chưa bắt đầu sản xuất.';
        if ($vcStatus === 'completed' && $representativePo) {
            $releaseDate = $representativePo->manufacturer_release_date;
            $poStatusVal = $representativePo->status;
            if (in_array($poStatusVal, ['shipping', 'partial_received', 'received'])) {
                $prodStatus = 'completed';
                $prodDate = $releaseDate ? $releaseDate->format('d/m/Y') : null;
                $prodDetails = 'Đã sản xuất xong.';
            } else {
                $prodStatus = 'active';
                $prodDate = $releaseDate ? $releaseDate->format('d/m/Y') : null;
                $prodDetails = $releaseDate ? 'Dự kiến sản xuất xong ngày ' . $releaseDate->format('d/m/Y') . '.' : 'Đang sản xuất.';
            }
        }
        $timeline['production'] = [
            'label' => 'Sản xuất',
            'status' => $prodStatus,
            'date' => $prodDate,
            'details' => $prodDetails
        ];

        // 5. Xuất kho hãng
        $mfgExportStatus = 'pending';
        $mfgExportDate = null;
        $mfgExportDetails = 'Hãng chưa xuất kho.';
        if ($prodStatus === 'completed' && $representativePo) {
            $releaseDate = $representativePo->manufacturer_release_date;
            $poStatusVal = $representativePo->status;
            if (in_array($poStatusVal, ['shipping', 'partial_received', 'received']) || ($releaseDate && $releaseDate->isPast())) {
                $mfgExportStatus = 'completed';
                $mfgExportDate = $releaseDate ? $releaseDate->format('d/m/Y') : null;
                $mfgExportDetails = 'Hãng đã xuất kho.';
            } else {
                $mfgExportStatus = 'active';
                $mfgExportDetails = 'Chờ hãng xuất kho.';
            }
        }
        $timeline['mfg_export'] = [
            'label' => 'Xuất kho hãng',
            'status' => $mfgExportStatus,
            'date' => $mfgExportDate,
            'details' => $mfgExportDetails
        ];

        // 6. Đang vận chuyển
        $transStatus = 'pending';
        $transDate = null;
        $transDetails = 'Chưa vận chuyển.';
        if ($mfgExportStatus === 'completed' && $representativePo) {
            $arrivalDate = $representativePo->expected_arrival_date;
            $poStatusVal = $representativePo->status;
            if (in_array($poStatusVal, ['shipping', 'partial_received'])) {
                $transStatus = 'active';
                $transDate = $arrivalDate ? $arrivalDate->format('d/m/Y') : null;
                $transDetails = $arrivalDate ? 'Dự kiến về VN ngày ' . $arrivalDate->format('d/m/Y') . '.' : 'Đang trên đường vận chuyển.';
            } elseif ($poStatusVal === 'received') {
                $transStatus = 'completed';
                $transDetails = 'Vận chuyển đã hoàn tất.';
            } else {
                $transStatus = 'active';
                $transDetails = 'Đang xử lý vận chuyển.';
            }
        }
        $timeline['transit'] = [
            'label' => 'Đang vận chuyển',
            'status' => $transStatus,
            'date' => $transDate,
            'details' => $transDetails
        ];

        // 7. Đã về VN
        $vnStatus = 'pending';
        $vnDate = null;
        $vnDetails = 'Chưa về VN.';
        if ($representativePo) {
            $arrivalDate = $representativePo->expected_arrival_date;
            $actualDelivery = $representativePo->actual_delivery;
            $poStatusVal = $representativePo->status;

            if ($actualDelivery || in_array($poStatusVal, ['partial_received', 'received'])) {
                $vnStatus = 'completed';
                $vnDate = $actualDelivery ? $actualDelivery->format('d/m/Y') : ($arrivalDate ? $arrivalDate->format('d/m/Y') : null);
                $vnDetails = 'Hàng đã về Việt Nam.';
            } elseif ($transStatus === 'active') {
                if ($arrivalDate && $arrivalDate->isPast()) {
                    $vnStatus = 'active';
                    $vnDetails = 'Đang làm thủ tục thông quan tại VN.';
                }
            }
        }
        $timeline['arrived_vn'] = [
            'label' => 'Đã về VN',
            'status' => $vnStatus,
            'date' => $vnDate,
            'details' => $vnDetails
        ];

        // 8. Đã nhập kho
        $whStatus = 'pending';
        $whDate = null;
        $whDetails = 'Chưa nhập kho.';
        if ($received > 0) {
            $whDate = $representativePo ? ($representativePo->actual_delivery ? $representativePo->actual_delivery->format('d/m/Y') : null) : null;
            if ($received >= $requested) {
                $whStatus = 'completed';
                $whDetails = "Đã nhập kho đủ ({$received}/{$requested}).";
            } else {
                $whStatus = 'active';
                $whDetails = "Đã nhập kho một phần ({$received}/{$requested}).";
            }
        } elseif ($vnStatus === 'completed') {
            $whStatus = 'active';
            $whDetails = 'Đang chờ thủ tục nhập kho.';
        }
        $timeline['warehouse_received'] = [
            'label' => 'Đã nhập kho',
            'status' => $whStatus,
            'date' => $whDate,
            'details' => $whDetails
        ];

        // 9. Đã giao Sales
        $saleStatus = 'pending';
        $saleDate = null;
        $saleDetails = 'Chưa bàn giao.';
        if ($sale) {
            $saleStatusVal = $sale->status;
            $deliveryDate = $sale->delivery_date;
            if ($saleStatusVal === 'completed' || ($deliveryDate && $deliveryDate->isPast())) {
                $saleStatus = 'completed';
                $saleDate = $deliveryDate ? $deliveryDate->format('d/m/Y') : null;
                $saleDetails = 'Đã bàn giao cho Sales/Khách hàng.';
            } elseif ($whStatus === 'completed' || $saleStatusVal === 'shipping') {
                $saleStatus = 'active';
                $saleDetails = 'Đang chuẩn bị bàn giao.';
            }
        }
        $timeline['delivered_sales'] = [
            'label' => 'Đã giao Sales',
            'status' => $saleStatus,
            'date' => $saleDate,
            'details' => $saleDetails
        ];

        return $timeline;
    }

    private function getTrackingReport(Request $request, $productIds = []): array
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $query = \App\Models\SaleOrderRequestItem::where('is_cancelled', false)
            ->whereHas('saleOrderRequest', function($q) {
                $q->whereIn('status', [
                    \App\Models\SaleOrderRequest::STATUS_SUBMITTED,
                    \App\Models\SaleOrderRequest::STATUS_PROCESSING,
                    \App\Models\SaleOrderRequest::STATUS_COMPLETED
                ]);
            })
            ->with(['saleOrderRequest.sale.project', 'vendor', 'purchaseOrderItems' => function($q) {
                // Chỉ load PO items từ PO không bị cancelled
                $q->whereHas('purchaseOrder', function($pq) {
                    $pq->where('status', '!=', 'cancelled');
                });
            }, 'purchaseOrderItems.purchaseOrder', 'saleItem']);

        // Filter by Date - Logic chặt hơn:
        // Lấy PR items có PO trong date range, HOẶC PR tạo trong date range nhưng chưa có PO nào
        $query->where(function($q) use ($dateFrom, $dateTo) {
            // Case 1: Có PO trong date range
            $q->whereHas('purchaseOrderItems.purchaseOrder', function($pq) use ($dateFrom, $dateTo) {
                $pq->where('status', '!=', 'cancelled')
                   ->whereBetween('order_date', [$dateFrom, $dateTo]);
            })
            // Case 2: PR tạo trong date range nhưng chưa có PO nào (chờ đặt hàng)
            ->orWhere(function($q2) use ($dateFrom, $dateTo) {
                $q2->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                   ->whereDoesntHave('purchaseOrderItems');
            });
        });

        // Filter by Sales Order Code
        if ($request->filled('sale_code')) {
            $query->whereHas('saleOrderRequest.sale', function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->sale_code . '%');
            });
        }

        // Filter by Part Number (From Direct input or resolved product_ids)
        if (!empty($productIds)) {
            if (in_array(-1, $productIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function($q) use ($productIds) {
                    $productCodes = \App\Models\Product::whereIn('id', $productIds)->pluck('code')->toArray();
                    $q->whereIn('product_id', $productIds)
                      ->orWhereIn('part_number', $productCodes);
                });
            }
        } elseif ($request->filled('product_search')) {
            $query->where('part_number', 'like', '%' . $request->product_search . '%');
        } elseif ($request->filled('part_number')) {
            $query->where('part_number', 'like', '%' . $request->part_number . '%');
        }

        // Filter by Vendor
        if ($request->filled('supplier_id')) {
            $query->where(function($q) use ($request) {
                $q->where('vendor_id', $request->supplier_id)
                  ->orWhereHas('purchaseOrderItems.purchaseOrder', function($pq) use ($request) {
                      $pq->where('supplier_id', $request->supplier_id);
                  });
            });
        }

        $allItems = $query->latest()->get();

        // Query direct/manual PurchaseOrderItems (no linked PR item)
        $poQuery = \App\Models\PurchaseOrderItem::whereNull('sale_order_request_item_id')
            ->whereHas('purchaseOrder', function($pq) {
                $pq->where('status', '!=', 'cancelled');
            })
            ->with(['purchaseOrder.supplier', 'purchaseOrder.currency', 'product']);

        // Filter direct PO items by Date
        $poQuery->whereHas('purchaseOrder', function($pq) use ($dateFrom, $dateTo) {
            $pq->whereBetween('order_date', [$dateFrom, $dateTo]);
        });

        // Filter direct PO items by Vendor
        if ($request->filled('supplier_id')) {
            $poQuery->whereHas('purchaseOrder', function($pq) use ($request) {
                $pq->where('supplier_id', $request->supplier_id);
            });
        }

        // Filter direct PO items by Product
        if (!empty($productIds)) {
            if (in_array(-1, $productIds)) {
                $poQuery->whereRaw('1 = 0');
            } else {
                $poQuery->whereIn('product_id', $productIds);
            }
        } elseif ($request->filled('product_search')) {
            $poQuery->whereHas('product', function($pq) use ($request) {
                $pq->where('code', 'like', '%' . $request->product_search . '%');
            });
        }

        $directPoItems = $poQuery->get();

        // Track PO item IDs đã được xử lý qua PR items để tránh double-count
        $processedPoItemIds = [];

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
                    'cpq_numbers' => [],
                    'end_users' => [],
                    'partners' => [],
                    'serial_numbers' => [],
                    'created_at' => $item->created_at,
                    'po_items_raw' => [],
                    'sale_raw' => $item->saleOrderRequest->sale ?? null,
                    'pr_created_at' => $item->created_at,
                ];
            }

            // Chỉ tính ordered/received từ PO items KHÔNG bị cancelled (đã filter ở eager load)
            $ordered = 0;
            $received = 0;
            foreach ($item->purchaseOrderItems as $poItem) {
                $ordered += (float) ($poItem->ordered_quantity ?? $poItem->quantity ?? 0);
                $received += (float) ($poItem->received_quantity ?? 0);
                // Track PO item ID để tránh double-count
                $processedPoItemIds[] = $poItem->id;
                
                // Track raw PO items
                $grouped[$key]['po_items_raw'][] = $poItem;
            }

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

                $poCpq = $poItem->purchaseOrder->cpq_number ?? '';
                if ($poCpq && !in_array($poCpq, $grouped[$key]['cpq_numbers'])) {
                    $grouped[$key]['cpq_numbers'][] = $poCpq;
                }
            }

            $eu = $item->eu_name_mst ?? ($item->saleOrderRequest->sale->project->eu_name_vi ?? '');
            if ($eu && !in_array($eu, $grouped[$key]['end_users'])) {
                $grouped[$key]['end_users'][] = $eu;
            }

            $partner = $item->saleOrderRequest->sale->customer_name ?? ($item->si_name ?? '');
            if ($partner && !in_array($partner, $grouped[$key]['partners'])) {
                $grouped[$key]['partners'][] = $partner;
            }

            $sn = $item->serial_number ?? '';
            if ($sn && !in_array($sn, $grouped[$key]['serial_numbers'])) {
                $grouped[$key]['serial_numbers'][] = $sn;
            }
        }

        // Chỉ thêm direct PO items chưa được xử lý qua PR
        foreach ($directPoItems as $poItem) {
            // Skip nếu PO item này đã được xử lý qua PR items
            if (in_array($poItem->id, $processedPoItemIds)) {
                continue;
            }

            $saleCode = 'N/A';
            $saleId = 0;
            $partNumber = $poItem->product->code ?? $poItem->product_name ?? 'N/A';
            $key = $saleId . '-' . $partNumber;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'sale_id' => $saleId,
                    'sale_code' => $saleCode,
                    'part_number' => $partNumber,
                    'vendor_name' => $poItem->purchaseOrder->supplier->name ?? 'N/A',
                    'requested' => 0,
                    'ordered' => 0,
                    'received' => 0,
                    'unit_price_usd' => $poItem->warehouse_unit_price ?: $poItem->unit_price ?: 0,
                    'pr_codes' => [],
                    'po_links' => [],
                    'cpq_numbers' => [],
                    'end_users' => [],
                    'partners' => [],
                    'serial_numbers' => [],
                    'created_at' => $poItem->purchaseOrder->order_date ?? $poItem->created_at,
                    'po_items_raw' => [],
                    'sale_raw' => null,
                    'pr_created_at' => null,
                ];
            }

            $grouped[$key]['requested'] += $poItem->quantity;
            $grouped[$key]['ordered'] += $poItem->quantity;
            $grouped[$key]['received'] += $poItem->received_quantity;
            $grouped[$key]['po_items_raw'][] = $poItem;

            $poId = $poItem->purchase_order_id;
            $po = $poItem->purchaseOrder;
            if ($po && !isset($grouped[$key]['po_links'][$poId])) {
                $grouped[$key]['po_links'][$poId] = [
                    'id' => $poId,
                    'code' => $po->code ?? '',
                    'status' => $po->status ?? '',
                    'status_label' => $po->status_label ?? '',
                    'order_date' => $po->order_date ? $po->order_date->format('d/m/Y') : '--',
                    'supplier_name' => $po->supplier->name ?? 'N/A',
                    'cpq_number' => $po->cpq_number ?? '--',
                    'etd' => $po->manufacturer_release_date ? $po->manufacturer_release_date->format('d/m/Y') : '--',
                    'eta' => $po->expected_arrival_date ? $po->expected_arrival_date->format('d/m/Y') : '--',
                    'actual_delivery' => $po->actual_delivery ? $po->actual_delivery->format('d/m/Y') : '--',
                    'total_usd' => number_format($po->total_foreign ?: ($po->total / max(1, $po->exchange_rate ?: 1)), 2),
                    'payment_terms' => $po->payment_terms_label ?? $po->payment_terms ?? 'Net 30',
                    'note' => $po->note ?? '',
                    'hold_reason' => $po->hold_reason ?? '',
                ];
            }

            $poCpq = $poItem->purchaseOrder->cpq_number ?? '';
            if ($poCpq && !in_array($poCpq, $grouped[$key]['cpq_numbers'])) {
                $grouped[$key]['cpq_numbers'][] = $poCpq;
            }
        }

        foreach ($grouped as &$row) {
            $row['po_links'] = array_values($row['po_links']);
            $row['remaining'] = max(0, $row['requested'] - $row['received']);
            $row['total_usd'] = $row['requested'] * $row['unit_price_usd'];

            $row['completion_percent'] = $row['requested'] > 0 ? min(100, round(($row['received'] / $row['requested']) * 100)) : 0;
            $row['ordered_percent'] = $row['requested'] > 0 ? min(100, round(($row['ordered'] / $row['requested']) * 100)) : 0;

            $row['cpq'] = implode(', ', array_filter($row['cpq_numbers']));
            $row['end_user'] = implode(', ', array_filter($row['end_users']));
            $row['si_partner'] = implode(', ', array_filter($row['partners']));
            $row['serial_number'] = implode(', ', array_filter($row['serial_numbers']));

            // Compute 9-step timeline details
            $row['timeline'] = $this->calculateTimeline(
                $row['requested'],
                $row['ordered'],
                $row['received'],
                $row['po_items_raw'],
                $row['sale_raw'],
                $row['pr_created_at']
            );

            // Find current active step in the timeline for fallback/label rendering
            $currentStatusStep = 'pr';
            $stepOrder = ['pr', 'po', 'vendor_confirm', 'production', 'mfg_export', 'transit', 'arrived_vn', 'warehouse_received', 'delivered_sales'];
            foreach ($stepOrder as $stepKey) {
                if ($row['timeline'][$stepKey]['status'] !== 'pending') {
                    $currentStatusStep = $stepKey;
                }
            }

            // Determine the status label based on the active step and PO status
            $statusLabel = 'N/A';
            $statusColor = 'bg-gray-100 text-gray-600';
            $statusIcon = 'fas fa-info-circle';

            $representativePo = null;
            if (!empty($row['po_items_raw'])) {
                // Find representative PO (highest weight)
                $statusWeights = [
                    'draft' => 0,
                    'pending_approval' => 1,
                    'approved' => 2,
                    'sent' => 3,
                    'confirmed' => 4,
                    'shipping' => 5,
                    'partial_received' => 6,
                    'received' => 7,
                ];
                $maxStatusWeight = -1;
                foreach ($row['po_items_raw'] as $poItem) {
                    $po = $poItem instanceof \App\Models\PurchaseOrder ? $poItem : ($poItem->purchaseOrder ?? null);
                    if ($po) {
                        $weight = $statusWeights[$po->status] ?? 0;
                        if ($weight > $maxStatusWeight) {
                            $maxStatusWeight = $weight;
                            $representativePo = $po;
                        }
                    }
                }
            }

            // Timeline mốc thời gian ETA/ETD/Actual Arrival
            $row['etd'] = $representativePo && $representativePo->manufacturer_release_date ? $representativePo->manufacturer_release_date->format('d/m/Y') : null;
            $row['eta'] = $representativePo && $representativePo->expected_arrival_date ? $representativePo->expected_arrival_date->format('d/m/Y') : null;
            $row['actual_arrival'] = $representativePo && $representativePo->actual_delivery ? $representativePo->actual_delivery->format('d/m/Y') : null;

            // Phân tích nguyên nhân thiếu hàng (nếu còn thiếu)
            $shortageReason = null;
            if ($row['remaining'] > 0) {
                if ($row['ordered'] <= 0) {
                    $shortageReason = 'Chưa tạo đơn mua hàng (PO) với nhà cung cấp';
                } elseif ($representativePo) {
                    if ($representativePo->is_hold) {
                        $shortageReason = 'PO đang tạm hoãn: ' . ($representativePo->hold_reason ?: 'Cần kiểm tra lại');
                    } elseif ($representativePo->status === 'draft') {
                        $shortageReason = 'PO mới ở dạng nháp, chưa gửi duyệt';
                    } elseif ($representativePo->status === 'pending_approval') {
                        $shortageReason = 'PO đang chờ cấp quản lý phê duyệt';
                    } elseif (in_array($representativePo->status, ['approved', 'sent'])) {
                        $shortageReason = 'Đã gửi PO, chờ nhà cung cấp xác nhận đơn';
                    } elseif ($representativePo->status === 'confirmed') {
                        $releaseStr = $representativePo->manufacturer_release_date ? $representativePo->manufacturer_release_date->format('d/m/Y') : 'chưa rõ';
                        $shortageReason = "Hãng đang sản xuất (Dự kiến xong ETD: {$releaseStr})";
                    } elseif (in_array($representativePo->status, ['shipping', 'partial_received'])) {
                        $arrivalStr = $representativePo->expected_arrival_date ? $representativePo->expected_arrival_date->format('d/m/Y') : 'chưa rõ';
                        $shortageReason = "Hàng đang trên đường vận chuyển về VN (Dự kiến ETA: {$arrivalStr})";
                    }
                }
            }
            $row['shortage_reason'] = $shortageReason;

            if ($currentStatusStep === 'pr') {
                $statusLabel = 'Chờ đặt hàng';
                $statusColor = 'bg-gray-100 text-gray-600';
                $statusIcon = 'fas fa-clock';
            } elseif ($currentStatusStep === 'po') {
                $poStatusVal = $representativePo ? $representativePo->status : '';
                if ($poStatusVal === 'draft') {
                    $statusLabel = 'PO Nháp';
                    $statusColor = 'bg-yellow-100 text-yellow-800';
                    $statusIcon = 'fas fa-file-signature';
                } elseif ($poStatusVal === 'pending_approval') {
                    $statusLabel = 'Chờ duyệt PO';
                    $statusColor = 'bg-orange-100 text-orange-800';
                    $statusIcon = 'fas fa-user-clock';
                } elseif ($poStatusVal === 'approved') {
                    $statusLabel = 'Đã duyệt PO';
                    $statusColor = 'bg-indigo-100 text-indigo-800';
                    $statusIcon = 'fas fa-check-double';
                } elseif ($poStatusVal === 'sent') {
                    $statusLabel = 'Đã gửi NCC';
                    $statusColor = 'bg-blue-100 text-blue-800';
                    $statusIcon = 'fas fa-paper-plane';
                } else {
                    $statusLabel = 'Đang đặt hàng';
                    $statusColor = 'bg-blue-100 text-blue-800';
                    $statusIcon = 'fas fa-shopping-cart';
                }
            } elseif ($currentStatusStep === 'vendor_confirm') {
                $statusLabel = 'Hãng xác nhận';
                $statusColor = 'bg-cyan-100 text-cyan-800';
                $statusIcon = 'fas fa-user-check';
            } elseif ($currentStatusStep === 'production') {
                $statusLabel = 'Đang sản xuất';
                $statusColor = 'bg-purple-100 text-purple-800';
                $statusIcon = 'fas fa-industry';
            } elseif ($currentStatusStep === 'mfg_export') {
                $statusLabel = 'Xuất kho hãng';
                $statusColor = 'bg-pink-100 text-pink-800';
                $statusIcon = 'fas fa-sign-out-alt';
            } elseif ($currentStatusStep === 'transit') {
                $statusLabel = 'Đang vận chuyển';
                $statusColor = 'bg-amber-100 text-amber-800';
                $statusIcon = 'fas fa-shipping-fast';
            } elseif ($currentStatusStep === 'arrived_vn') {
                $statusLabel = 'Đã về VN';
                $statusColor = 'bg-teal-100 text-teal-800';
                $statusIcon = 'fas fa-plane-arrival';
            } elseif ($currentStatusStep === 'warehouse_received') {
                if ($row['received'] < $row['requested']) {
                    $statusLabel = 'Nhập kho 1 phần';
                    $statusColor = 'bg-emerald-100 text-emerald-800';
                    $statusIcon = 'fas fa-warehouse';
                } else {
                    $statusLabel = 'Đã nhập kho';
                    $statusColor = 'bg-emerald-200 text-emerald-950';
                    $statusIcon = 'fas fa-warehouse';
                }
            } elseif ($currentStatusStep === 'delivered_sales') {
                $statusLabel = 'Đã giao Sales';
                $statusColor = 'bg-green-100 text-green-800';
                $statusIcon = 'fas fa-check-circle';
            }

            $row['status'] = $currentStatusStep;
            $row['status_label'] = $statusLabel;
            $row['status_color'] = $statusColor;
            $row['status_icon'] = $statusIcon;
        }

        return array_values($grouped);
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', \App\Models\PurchaseReport::class);
        
        $type = $request->input('report_type', 'tracking');
        
        $productId = $request->input('product_id');
        $productSearch = $request->input('product_search');

        if ($productId && !empty($productSearch)) {
            $selectedProduct = \App\Models\Product::find($productId);
            if ($selectedProduct && !str_contains($productSearch, $selectedProduct->code)) {
                $productId = null;
            }
        }

        $productIds = [];
        if ($productId) {
            $productIds = [$productId];
        } elseif (!empty($productSearch)) {
            $productIds = \App\Models\Product::search($productSearch)->pluck('id')->toArray();
            if (empty($productIds)) {
                $productIds = [-1];
            }
        }

        if ($type === 'tracking') {
            $data = $this->getTrackingReport($request, $productIds);
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PurchaseTrackingExport($data), 
                'bao-cao-theo-doi-hang-ve-' . date('Ymd') . '.xlsx'
            );
        } elseif ($type === 'supplier') {
            $data = $this->getSupplierReport(
                $request->input('date_from', now()->startOfMonth()->format('Y-m-d')),
                $request->input('date_to', now()->format('Y-m-d')),
                $request->input('supplier_id'),
                $productIds
            );
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PurchaseSupplierExport($data), 
                'bao-cao-mua-hang-theo-ncc-' . date('Ymd') . '.xlsx'
            );
        } elseif ($type === 'product') {
            $data = $this->getProductReport(
                $request->input('date_from', now()->startOfMonth()->format('Y-m-d')),
                $request->input('date_to', now()->format('Y-m-d')),
                $productIds,
                $request->input('supplier_id')
            );
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PurchaseProductExport($data), 
                'bao-cao-mua-hang-theo-san-pham-' . date('Ymd') . '.xlsx'
            );
        } elseif ($type === 'monthly') {
            $data = $this->getMonthlyReport(
                $request->input('date_from', now()->startOfMonth()->format('Y-m-d')),
                $request->input('date_to', now()->format('Y-m-d')),
                $request->input('supplier_id'),
                $productIds
            );
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PurchaseMonthlyExport($data), 
                'bao-cao-mua-hang-theo-thang-' . date('Ymd') . '.xlsx'
            );
        } elseif ($type === 'cancelled') {
            $data = $this->getCancelledOrders(
                $request->input('date_from', now()->startOfMonth()->format('Y-m-d')),
                $request->input('date_to', now()->format('Y-m-d')),
                $request->input('supplier_id'),
                $productIds
            );
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PurchaseCancelledExport($data), 
                'bao-cao-don-hang-da-huy-' . date('Ymd') . '.xlsx'
            );
        }

        return redirect()->back()->with('error', 'Loại báo cáo không hợp lệ!');
    }
}
