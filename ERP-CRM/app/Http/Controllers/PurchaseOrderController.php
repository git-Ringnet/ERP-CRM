<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierQuotation;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Sale;
use App\Models\PurchaseRequest;
use App\Models\Currency;
use App\Models\Warehouse;
use App\Models\Notification;
use App\Mail\PurchaseOrderMail;
use App\Exports\PurchaseOrdersExport;
use App\Services\PurchaseImportSyncService;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseOrderController extends Controller
{
    protected PurchaseImportSyncService $purchaseImportSyncService;
    protected CurrencyService $currencyService;

    public function __construct(PurchaseImportSyncService $purchaseImportSyncService, CurrencyService $currencyService)
    {
        $this->purchaseImportSyncService = $purchaseImportSyncService;
        $this->currencyService = $currencyService;
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $query = PurchaseOrder::with(['supplier', 'items', 'supplierQuotation', 'creator']);

        // Apply data filtering based on permissions
        $user = auth()->user();
        if (!$user->can('view_all_purchase_orders') && !$user->can('view_purchase_orders')) {
            // User only has view_own_purchase_orders permission
            if ($user->can('view_own_purchase_orders')) {
                $query->where('created_by', $user->id);
            } else {
                // User has no permission to view purchase orders
                abort(403, 'Unauthorized action.');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $orders = $query->with([
            'supplier', 'creator', 'currency', 'sale.user',
            'items.saleOrderRequestItem.saleOrderRequest.sale.user'
        ])->orderBy('created_at', 'desc')->paginate(15);
        $suppliers = Supplier::orderBy('name')->get();

        // Thống kê - apply same filtering
        $statsQuery = PurchaseOrder::query();
        if (!$user->can('view_all_purchase_orders') && !$user->can('view_purchase_orders')) {
            if ($user->can('view_own_purchase_orders')) {
                $statsQuery->where('created_by', $user->id);
            }
        }

        $totalValue = (clone $statsQuery)
            ->join('purchase_order_items', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->leftJoin('sale_order_request_items', 'purchase_order_items.sale_order_request_item_id', '=', 'sale_order_request_items.id')
            ->leftJoin('sale_items', 'sale_order_request_items.sale_item_id', '=', 'sale_items.id')
            ->whereIn('purchase_orders.status', ['approved', 'shipping', 'received'])
            ->selectRaw('SUM(
                CASE 
                    WHEN sale_items.id IS NOT NULL THEN (
                        sale_items.usd_price * (1 - COALESCE(sale_items.discount_rate, 0)/100) * (1 + COALESCE(sale_items.import_cost_rate, 0)/100) * purchase_order_items.quantity
                    )
                    ELSE (purchase_order_items.unit_price * purchase_order_items.quantity / COALESCE(purchase_orders.exchange_rate, 1))
                END
            ) as total_cost_usd')
            ->value('total_cost_usd') ?? 0;

        $stats = [
            'pending' => (clone $statsQuery)->whereIn('status', ['draft', 'pending_approval'])->count(),
            'sent' => (clone $statsQuery)->whereIn('status', ['approved', 'shipping'])->count(),
            'received' => (clone $statsQuery)->where('status', 'received')->count(),
            'total_value' => $totalValue,
        ];

        return view('purchase-orders.index', compact('orders', 'suppliers', 'stats'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $code = PurchaseOrder::generateCode();

        $quotation = null;
        if ($request->filled('quotation_id')) {
            $quotation = SupplierQuotation::with(['supplier', 'items'])->find($request->quotation_id);
        }

        $currencies = $this->currencyService->getActiveCurrencies();
        $baseCurrencyId = Currency::getBaseCurrencyId();

        // Get approved sales that can be linked to PO
        $availableSales = Sale::where('status', 'approved')
            ->orderBy('date', 'desc')
            ->get(['id', 'code', 'customer_name', 'total']);

        // Pre-select sale if passed from sale detail page
        $selectedSaleId = $request->get('sale_id');

        return view('purchase-orders.create', compact('suppliers', 'products', 'code', 'quotation', 'currencies', 'baseCurrencyId', 'availableSales', 'selectedSaleId'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $validated = $request->validate([
            'code' => 'required|unique:purchase_orders,code',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.warehouse_unit_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $order = PurchaseOrder::create([
                'code' => $validated['code'],
                'supplier_id' => $validated['supplier_id'],
                'supplier_quotation_id' => $request->supplier_quotation_id,
                'sale_id' => $request->sale_id,
                'order_date' => $validated['order_date'],
                'expected_delivery' => $request->expected_delivery,
                'expected_arrival_date' => $request->expected_arrival_date,
                'manufacturer_release_date' => $request->manufacturer_release_date,
                'delivery_address' => $request->delivery_address,
                'discount_percent' => $request->discount_percent ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'other_cost' => $request->other_cost ?? 0,
                'vat_percent' => 0,
                'payment_terms' => $request->payment_terms ?? 'net30',
                'note' => $request->note,
                'currency_id' => $request->currency_id ?? Currency::getBaseCurrencyId(),
                'exchange_rate' => $request->exchange_rate ?? 1,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $total = round($item['quantity'] * $item['unit_price'], 2);
                $subtotal += $total;

                $itemVatPercent = 0;
                $itemVatAmount = 0;

                $order->items()->create([
                    'product_name' => $item['product_name'],
                    'product_id' => $item['product_id'] ?? null,
                    'sale_order_request_item_id' => $item['sale_order_request_item_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'ordered_quantity' => $item['quantity'], // Set ordered_quantity initially
                    'unit' => $item['unit'] ?? 'Cái',
                    'unit_price' => $item['unit_price'],
                    'warehouse_unit_price' => $item['warehouse_unit_price'] ?? 0,
                    'total' => $total,
                    'vat_percent' => $itemVatPercent,
                    'vat_amount' => $itemVatAmount,
                ]);
            }

            // Tính tổng (Sử dụng calculateTotals của Model để đảm bảo tính nhất quán)
            $order->load('items');
            $order->calculateTotals();
            $order->updateDebt();
            $order->save();

            // Cập nhật trạng thái báo giá NCC
            if ($request->supplier_quotation_id) {
                $quotation = SupplierQuotation::find($request->supplier_quotation_id);
                if ($quotation && $quotation->purchase_request_id) {
                    PurchaseRequest::where('id', $quotation->purchase_request_id)
                        ->update(['status' => 'converted']);
                }
            }

            DB::commit();

            // Notify sales when PO is created for their sale
            if ($order->sale_id) {
                $this->notifySalesUser($order, 'Đơn mua hàng đã được tạo', "Đơn mua hàng {$order->code} đã được tạo cho đơn bán hàng của bạn.");
            }

            return redirect()->route('purchase-orders.index')
                ->with('success', 'Đã tạo đơn mua hàng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }


    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['draft', 'pending_approval'])) {
            return back()->with('error', 'Không thể sửa đơn hàng đã được duyệt!');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $purchaseOrder->load(['items']);
        $currencies = $this->currencyService->getActiveCurrencies();
        $baseCurrencyId = Currency::getBaseCurrencyId();

        return view('purchase-orders.edit', compact('purchaseOrder', 'suppliers', 'products', 'currencies', 'baseCurrencyId'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['draft', 'pending_approval'])) {
            return back()->with('error', 'Không thể sửa đơn hàng đã được duyệt!');
        }

        $validated = $request->validate([
            'expected_delivery' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.warehouse_unit_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $purchaseOrder->update([
                'expected_delivery' => $request->expected_delivery,
                'expected_arrival_date' => $request->expected_arrival_date,
                'manufacturer_release_date' => $request->manufacturer_release_date,
                'delivery_address' => $request->delivery_address,
                'discount_percent' => $request->discount_percent ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'other_cost' => $request->other_cost ?? 0,
                'vat_percent' => 0,
                'payment_terms' => $request->payment_terms ?? 'net30',
                'note' => $request->note,
                'currency_id' => $request->currency_id ?? $purchaseOrder->currency_id,
                'exchange_rate' => $request->exchange_rate ?? $purchaseOrder->exchange_rate,
            ]);

            $purchaseOrder->items()->delete();
            $subtotal = 0;

            foreach ($validated['items'] as $item) {
                $total = round($item['quantity'] * $item['unit_price'], 2);
                $subtotal += $total;

                $itemVatPercent = 0;
                $itemVatAmount = 0;

                $purchaseOrder->items()->create([
                    'product_name' => $item['product_name'],
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Cái',
                    'unit_price' => $item['unit_price'],
                    'warehouse_unit_price' => $item['warehouse_unit_price'] ?? 0,
                    'total' => $total,
                    'vat_percent' => $itemVatPercent,
                    'vat_amount' => $itemVatAmount,
                ]);
            }

            // Tính tổng (Sử dụng calculateTotals của Model để đảm bảo tính nhất quán)
            $purchaseOrder->load('items');
            $purchaseOrder->calculateTotals();
            $purchaseOrder->updateDebt();
            $purchaseOrder->save();

            DB::commit();
            return redirect()->route('purchase-orders.index')
                ->with('success', 'Đã cập nhật đơn mua hàng!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('delete', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['draft', 'cancelled'])) {
            return back()->with('error', 'Không thể xóa đơn hàng đã xử lý!');
        }

        $purchaseOrder->delete();
        return redirect()->route('purchase-orders.index')
            ->with('success', 'Đã xóa đơn mua hàng!');
    }

    public function submitApproval(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Đơn hàng không ở trạng thái nháp!');
        }

        $purchaseOrder->update(['status' => 'pending_approval']);
        return back()->with('success', 'Đã gửi đơn hàng để duyệt!');
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('approve', $purchaseOrder);

        if ($purchaseOrder->status !== 'pending_approval') {
            return back()->with('error', 'Đơn hàng không ở trạng thái chờ duyệt!');
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // Cập nhật công nợ khi đơn hàng được duyệt
        $purchaseOrder->updateDebt();
        $purchaseOrder->save();

        // Thông báo cho sales khi PO được duyệt (đã đặt)
        if ($purchaseOrder->sale_id) {
            $this->notifySalesUser($purchaseOrder, 'Đơn mua hàng đã được duyệt', "Đơn mua hàng {$purchaseOrder->code} đã được duyệt và chuyển sang trạng thái 'Đã đặt'.");
        }

        return back()->with('success', 'Đã duyệt đơn mua hàng!');
    }

    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('approve', $purchaseOrder);

        if ($purchaseOrder->status !== 'pending_approval') {
            return back()->with('error', 'Đơn hàng không ở trạng thái chờ duyệt!');
        }

        $purchaseOrder->update([
            'status' => 'draft',
            'note' => $purchaseOrder->note . "\n[Từ chối]: " . $request->reason,
        ]);

        return back()->with('success', 'Đã từ chối đơn mua hàng!');
    }

    public function send(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['approved'])) {
            return back()->with('error', 'Đơn hàng chưa được duyệt!');
        }

        $purchaseOrder->load(['supplier', 'items']);
        $supplierEmail = $purchaseOrder->supplier->email;

        if (empty($supplierEmail)) {
            return back()->with('error', 'Nhà cung cấp chưa có email! Vui lòng cập nhật thông tin NCC.');
        }

        try {
            Mail::to($supplierEmail)->send(new PurchaseOrderMail($purchaseOrder));

            $purchaseOrder->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            Log::info("PO {$purchaseOrder->code} sent to {$supplierEmail}");

            return back()->with('success', "Đã gửi đơn mua hàng đến {$purchaseOrder->supplier->name} ({$supplierEmail})!");
        } catch (\Exception $e) {
            Log::error("Failed to send PO {$purchaseOrder->code}: " . $e->getMessage());
            return back()->with('error', 'Gửi email thất bại: ' . $e->getMessage());
        }
    }

    public function confirmBySupplier(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if ($purchaseOrder->status !== 'sent') {
            return back()->with('error', 'Đơn hàng chưa được gửi!');
        }

        $purchaseOrder->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Đã xác nhận NCC đã nhận đơn hàng!');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'sale', 'items.product', 'creator', 'approver', 'currency', 'imports.warehouse']);
        $warehouses = \App\Models\Warehouse::active()->get();

        return view('purchase-orders.show', compact('purchaseOrder', 'warehouses'));
    }

    public function ship(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if ($purchaseOrder->status !== 'approved') {
            return back()->with('error', 'Đơn hàng chưa ở trạng thái Đã đặt!');
        }

        $purchaseOrder->update([
            'status' => 'shipping',
        ]);

        // Thông báo cho sales
        if ($purchaseOrder->sale_id) {
            $this->notifySalesUser($purchaseOrder, 'Đơn mua hàng đang về', "Đơn mua hàng {$purchaseOrder->code} đã được xuất đi và đang trên đường về.");
        }

        return back()->with('success', 'Đã cập nhật trạng thái: Đang về!');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['approved', 'shipping', 'partial_received'])) {
            return back()->with('error', 'Đơn hàng chưa sẵn sàng để nhận!');
        }

        $request->validate([
            'items' => 'required|array',
            'items.*' => 'numeric|min:0',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        DB::beginTransaction();
        try {
            $receivedItemData = $request->input('items', []);
            $anyReceived = false;

            foreach ($purchaseOrder->items as $item) {
                $receivingQty = (float) ($receivedItemData[$item->id] ?? 0);
                if ($receivingQty <= 0) continue;

                $remainingToReceive = $item->quantity - $item->received_quantity;
                if ($receivingQty > ($remainingToReceive + 0.001)) { // Allow small float delta
                    throw new \Exception("Số lượng nhận cho sản phẩm {$item->product_name} vượt quá số lượng còn lại.");
                }

                $item->received_quantity += $receivingQty;
                $item->save();
                
                // Không cập nhật trực tiếp received_quantity trên PR Item nữa
                // → Dùng accessor received_quantity_total (SUM từ PO items) để tránh data lệch
                
                $anyReceived = true;
            }

            if (!$anyReceived) {
                return back()->with('warning', 'Vui lòng nhập số lượng nhận cho ít nhất một sản phẩm!');
            }

            // Kiểm tra trạng thái tổng thể của PO
            $allReceived = true;
            foreach ($purchaseOrder->items as $item) {
                if ($item->received_quantity < $item->quantity) {
                    $allReceived = false;
                    break;
                }
            }

            $newStatus = $allReceived ? 'received' : 'partial_received';
            $purchaseOrder->update([
                'status' => $newStatus,
                'actual_delivery' => $allReceived ? now() : $purchaseOrder->actual_delivery,
            ]);

            $msg = $allReceived ? 'Đã nhận đủ hàng thành công!' : 'Đã xác nhận nhận một phần hàng!';

            // Sync với Module Nhập kho (Tạo phiếu nhập kho cho số lượng vừa nhận)
            try {
                $import = $this->purchaseImportSyncService->createPartialImportFromPO(
                    $purchaseOrder, 
                    $receivedItemData, 
                    $request->warehouse_id, 
                    false // Cần duyệt thủ công để tăng tồn kho
                );
                
                if ($import) {
                    $msg .= " Đã tạo phiếu nhập kho: {$import->code}.";
                }
            } catch (\Exception $e) {
                Log::warning("Could not create partial import for PO #{$purchaseOrder->id}: " . $e->getMessage());
            }

            DB::commit();
            
            // Notify sales
            if ($purchaseOrder->sale_id) {
                $statusText = $allReceived ? 'Hàng đã về - đủ hàng' : 'Hàng đã về - một phần';
                $this->notifySalesUser($purchaseOrder, $statusText, "Đơn mua hàng {$purchaseOrder->code} đã được cập nhật số lượng hàng về thực tế.");
            }

            // 🔥 Kiểm tra xem toàn bộ SO đã đủ hàng chưa → gửi notification tổng hợp
            $this->checkAndNotifyOrderComplete($purchaseOrder);

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Không thể hủy đơn hàng này!');
        }

        $purchaseOrder->update(['status' => 'cancelled']);
        return back()->with('success', 'Đã hủy đơn mua hàng!');
    }

    public function print(PurchaseOrder $purchaseOrder)
    {
        // Return 404 instead of 403 if user lacks permission to prevent information disclosure
        if (!auth()->user()->can('view', $purchaseOrder)) {
            abort(404);
        }

        $purchaseOrder->load(['supplier', 'items']);
        return view('purchase-orders.print', compact('purchaseOrder'));
    }

    /**
     * Export purchase orders to Excel
     */
    public function export(Request $request)
    {
        $this->authorize('export', PurchaseOrder::class);

        $filters = $request->only(['search', 'status', 'supplier_id']);
        $filename = 'don-mua-hang-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new PurchaseOrdersExport($filters), $filename);
    }

    /**
     * Get linked import for a PO
     */
    public function getImport(PurchaseOrder $purchaseOrder)
    {
        // Return 404 instead of 403 if user lacks permission to prevent information disclosure
        if (!auth()->user()->can('view', $purchaseOrder)) {
            abort(404);
        }

        $import = $this->purchaseImportSyncService->getImport($purchaseOrder);

        if (!$import) {
            return back()->with('error', 'Chưa có phiếu nhập kho liên kết với đơn mua hàng này.');
        }

        return redirect()->route('imports.show', $import);
    }

    /**
     * Toggle hold status for a PO
     */
    public function toggleHold(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Không thể hold/unhold đơn hàng này!');
        }

        $isHold = !$purchaseOrder->is_hold;

        $purchaseOrder->update([
            'is_hold' => $isHold,
            'hold_reason' => $isHold ? $request->input('hold_reason', '') : null,
        ]);

        // Notify sales about hold status change
        if ($purchaseOrder->sale_id) {
            $title = $isHold ? 'Đơn mua hàng đang HOLD' : 'Đơn mua hàng đã gỡ HOLD';
            $msg = $isHold
                ? "Đơn mua hàng {$purchaseOrder->code} đang bị hold. Lý do: {$purchaseOrder->hold_reason}"
                : "Đơn mua hàng {$purchaseOrder->code} đã được gỡ hold, tiếp tục xử lý.";
            $this->notifySalesUser($purchaseOrder, $title, $msg);
        }

        $statusMsg = $isHold ? 'Đã đặt trạng thái Hold cho đơn mua hàng.' : 'Đã gỡ trạng thái Hold.';
        return back()->with('success', $statusMsg);
    }

    /**
     * Notify the sales user who created the linked sale
     */
    private function notifySalesUser(PurchaseOrder $po, string $title, string $message): void
    {
        try {
            // 1. Direct Sale
            $saleIds = [];
            if ($po->sale_id) {
                $saleIds[] = $po->sale_id;
            }

            // 2. Sales from Aggregated items
            $aggregatedSaleIds = \App\Models\SaleOrderRequestItem::whereHas('purchaseOrderItems', function($q) use ($po) {
                    $q->where('purchase_order_id', $po->id);
                })
                ->whereHas('saleOrderRequest', function($q) {
                    $q->whereNotNull('sale_id');
                })
                ->get()
                ->pluck('saleOrderRequest.sale_id')
                ->unique()
                ->toArray();
            
            $allSaleIds = array_unique(array_merge($saleIds, $aggregatedSaleIds));

            foreach ($allSaleIds as $saleId) {
                $sale = Sale::find($saleId);
                if (!$sale || !$sale->user_id) continue;

                Notification::create([
                    'user_id' => $sale->user_id,
                    'type' => 'po_update',
                    'title' => $title,
                    'message' => $message,
                    'link' => route('purchase-orders.show', $po->id),
                    'icon' => 'fas fa-truck',
                    'color' => 'purple',
                    'data' => ['po_id' => $po->id, 'po_code' => $po->code, 'sale_id' => $sale->id],
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to notify sales user: ' . $e->getMessage());
        }
    }

    /**
     * 🔥 Kiểm tra xem toàn bộ SO đã đủ hàng chưa → gửi notification
     * Khi received >= requested cho TẤT CẢ các PR items trong SO
     */
    private function checkAndNotifyOrderComplete(PurchaseOrder $po): void
    {
        try {
            // Lấy tất cả PR items liên quan qua PO items
            $prItemIds = $po->items()
                ->whereNotNull('sale_order_request_item_id')
                ->pluck('sale_order_request_item_id')
                ->unique();

            if ($prItemIds->isEmpty()) return;

            // Lấy sale_id từ PR items
            $prItems = \App\Models\SaleOrderRequestItem::with('saleOrderRequest')
                ->whereIn('id', $prItemIds)
                ->get();

            // Group theo sale_id
            $saleIds = $prItems->pluck('saleOrderRequest.sale_id')->unique()->filter();

            foreach ($saleIds as $saleId) {
                $sale = Sale::find($saleId);
                if (!$sale || !$sale->user_id) continue;

                // Lấy TẤT CẢ PR items của SO này
                $allPrItems = \App\Models\SaleOrderRequestItem::whereHas('saleOrderRequest', function($q) use ($saleId) {
                    $q->where('sale_id', $saleId);
                })->get();

                $allComplete = true;
                foreach ($allPrItems as $prItem) {
                    $received = $prItem->received_quantity_total; // Accessor: SUM từ PO items
                    if ($received < $prItem->quantity) {
                        $allComplete = false;
                        break;
                    }
                }

                if ($allComplete) {
                    // Kiểm tra đã gửi notification chưa (tránh gửi trùng)
                    $alreadyNotified = Notification::where('user_id', $sale->user_id)
                        ->where('type', 'order_complete')
                        ->where('data->sale_id', $saleId)
                        ->exists();

                    if (!$alreadyNotified) {
                        Notification::create([
                            'user_id' => $sale->user_id,
                            'type' => 'order_complete',
                            'title' => '🎉 Đơn hàng đã đủ hàng!',
                            'message' => "Tất cả sản phẩm trong đơn {$sale->code} đã nhận đủ hàng.",
                            'link' => route('sales.order-tracking') . '?sale_code=' . $sale->code,
                            'icon' => 'fas fa-check-double',
                            'color' => 'green',
                            'data' => ['sale_id' => $saleId, 'sale_code' => $sale->code],
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to check order complete: ' . $e->getMessage());
        }
    }

    /**
     * Cập nhật thông tin theo dõi (ngày dự kiến hàng về, ngày hãng xuất...)
     */
    public function updateTracking(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $validated = $request->validate([
            'expected_arrival_date' => 'nullable|date',
            'manufacturer_release_date' => 'nullable|date',
            'expected_delivery' => 'nullable|date',
        ]);

        $oldArrival = $purchaseOrder->expected_arrival_date;
        $purchaseOrder->update($validated);

        // Gửi thông báo cho sales khi cập nhật thông tin theo dõi
        if ($request->filled('expected_arrival_date') || $request->filled('manufacturer_release_date') || $request->filled('expected_delivery')) {
            $this->notifySalesUser(
                $purchaseOrder,
                'Cập nhật thông tin theo dõi PO',
                "Đơn mua hàng {$purchaseOrder->code} đã cập nhật thông tin theo dõi (Ngày về dự kiến, ngày xuất hãng...)."
            );
        }

        return back()->with('success', 'Đã cập nhật thông tin theo dõi đơn hàng.');
    }
    /**
     * Get PR items for a specific vendor that haven't been fully ordered
     */
    public function getPrItems(Request $request)
    {
        $supplierId = $request->query('supplier_id');
        if (!$supplierId) return response()->json([]);

        // Get PR items for this supplier where ordered_quantity < quantity
        $items = \App\Models\SaleOrderRequestItem::with(['orderRequest.sale', 'orderRequest.creator', 'saleItem'])
            ->where('vendor_id', $supplierId)
            ->get()
            ->filter(function ($item) {
                return $item->remaining_to_order > 0;
            })
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'pr_code' => $item->orderRequest->code,
                    'sale_code' => $item->orderRequest->sale->code ?? 'N/A',
                    'part_number' => $item->part_number,
                    'quantity' => $item->quantity,
                    'remaining' => $item->remaining_to_order,
                    'unit' => $item->unit,
                    'type' => $item->type,
                    'si_name' => $item->si_name,
                    'estimated_cost_usd' => (float) ($item->saleItem->estimated_cost_usd ?? 0),
                    'cost_price_vnd' => (float) ($item->saleItem ? $item->saleItem->calculateVndCost() : 0),
                ];
            })
            ->values();

        return response()->json($items);
    }

    public function updateExpectedDelivery(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'expected_delivery' => 'nullable|date',
        ]);

        $purchaseOrder->update([
            'expected_delivery' => $request->expected_delivery,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Cập nhật giá mua thực tế của từng món hàng
     */
    public function updateItemPrice(Request $request, PurchaseOrderItem $item)
    {
        $validated = $request->validate([
            'unit_price' => 'required|numeric|min:0'
        ]);

        $item->update([
            'unit_price' => $validated['unit_price'],
            'total' => round($item->quantity * $validated['unit_price'], 2)
        ]);

        // Cập nhật lại tổng tiền PO
        $item->purchaseOrder->calculateTotals();
        $item->purchaseOrder->updateDebt();
        $item->purchaseOrder->save();

        return response()->json(['success' => true]);
    }

    /**
     * Cập nhật trạng thái từng món hàng trong PO
     */
    public function updateItemStatus(Request $request, PurchaseOrderItem $item)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:ordered,shipping,received,cancelled'
        ]);

        $item->update(['status' => $validated['status']]);

        return response()->json(['success' => true]);
    }

    /**
     * Tải lên file license cho từng món hàng
     */
    public function uploadItemLicense(Request $request, PurchaseOrderItem $item)
    {
        $request->validate([
            'license_file' => 'required|file|max:10240', // 10MB
        ]);

        if ($request->hasFile('license_file')) {
            $file = $request->file('license_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('po-licenses', $filename, 'public');
            
            $item->update(['license_file' => $path]);

            // Notify Sales
            if ($item->saleOrderRequestItem && $item->saleOrderRequestItem->saleOrderRequest && $item->saleOrderRequestItem->saleOrderRequest->sale) {
                $sale = $item->saleOrderRequestItem->saleOrderRequest->sale;
                if ($sale->user_id) {
                    \App\Models\Notification::create([
                        'user_id' => $sale->user_id,
                        'type' => 'license_uploaded',
                        'title' => 'Đã có License cho sản phẩm ' . $item->product_name,
                        'content' => "PO Team đã tải lên license cho sản phẩm trong đơn hàng {$sale->code}. Bạn có thể xem và tải về ngay.",
                        'link' => route('sales.show', $sale->id),
                        'is_read' => false,
                    ]);
                }
            }
        }

        return back()->with('success', 'Đã tải lên license cho sản phẩm ' . $item->product_name);
    }
}
