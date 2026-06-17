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
                ];
            }

            $grouped[$key]['requested'] += $poItem->quantity;
            $grouped[$key]['ordered'] += $poItem->quantity;
            $grouped[$key]['received'] += $poItem->received_quantity;

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

        foreach ($grouped as &$row) {
            $row['po_links'] = array_values($row['po_links']);
            $row['remaining'] = max(0, $row['requested'] - $row['received']);
            $row['total_usd'] = $row['requested'] * $row['unit_price_usd'];

            $row['cpq'] = implode(', ', array_filter($row['cpq_numbers']));
            $row['end_user'] = implode(', ', array_filter($row['end_users']));
            $row['si_partner'] = implode(', ', array_filter($row['partners']));
            $row['serial_number'] = implode(', ', array_filter($row['serial_numbers']));

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
