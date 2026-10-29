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
use App\Exports\SinglePurchaseOrderExport;
use App\Exports\FortinetPurchaseOrderExport;
use App\Exports\SaleContractPurchaseOrderExport;
use App\Services\PurchaseImportSyncService;
use App\Services\PurchaseOrderApprovalNotificationService;
use App\Services\CurrencyService;
use App\Services\PurchaseOrderDeletionService;
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
    protected PurchaseOrderApprovalNotificationService $purchaseOrderApprovalNotificationService;

    public function __construct(PurchaseImportSyncService $purchaseImportSyncService, CurrencyService $currencyService, PurchaseOrderApprovalNotificationService $purchaseOrderApprovalNotificationService)
    {
        $this->purchaseImportSyncService = $purchaseImportSyncService;
        $this->currencyService = $currencyService;
        $this->purchaseOrderApprovalNotificationService = $purchaseOrderApprovalNotificationService;
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $query = PurchaseOrder::with(['supplier', 'items', 'supplierQuotation', 'creator']);

        // Apply data filtering based on permissions
        $user = auth()->user();
        if (!$user->can('view_all_purchase_orders') && !$user->can('view_all_sales') && !$user->can('approve_purchase_orders')) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('sale', fn($sQ) => $sQ->where('user_id', $user->id))
                  ->orWhereHas('items.saleOrderRequestItem.saleOrderRequest.sale', fn($sQ) => $sQ->where('user_id', $user->id));
            });
        }

        // Tìm theo mã PO, NCC, S/N (dòng PO / thiết bị đã nhập kho), EU, SI, mã SO, Part Number.
        // Nhiều từ khoá ("PO - EU") thì mỗi từ khoá phải khớp ít nhất một trường.
        if ($request->filled('search')) {
            $terms = array_filter(preg_split('/\s+-\s+|\s*[,;]\s*/u', trim($request->search)), fn ($t) => trim($t) !== '');
            foreach ($terms as $term) {
                $like = '%' . trim($term) . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('code', 'like', $like)
                        ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $like))
                        ->orWhereHas('sale', fn ($s) => $s->where('code', 'like', $like)->orWhere('eu_name', 'like', $like))
                        ->orWhereHas('items', fn ($i) => $i->where('serial_number', 'like', $like)
                            ->orWhere('product_name', 'like', $like)
                            ->orWhereHas('saleOrderRequestItem', fn ($sori) => $sori->where('serial_number', 'like', $like)
                                ->orWhere('si_name', 'like', $like)
                                ->orWhere('eu_name_mst', 'like', $like)
                                ->orWhere('part_number', 'like', $like)
                                ->orWhereHas('saleOrderRequest.sale', fn ($s) => $s->where('code', 'like', $like)->orWhere('eu_name', 'like', $like))))
                        ->orWhereIn('id', \App\Models\Import::where('reference_type', 'purchase_order')
                            ->whereIn('id', \App\Models\ProductItem::where('sku', 'like', $like)->select('import_id'))
                            ->select('reference_id'));
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Lọc / sắp xếp theo từng cột trên toàn bộ dữ liệu (giữ khi chuyển trang)
        $query->orderBy('created_at', 'desc');
        \App\Services\TableColumnFilterService::apply($query, $request, \App\Services\PurchaseOrderColumnFilter::columnMap());

        $orders = $query->with([
            'supplier', 'creator', 'currency', 'sale.user',
            'items.saleOrderRequestItem.saleOrderRequest.sale.user',
            'items.saleOrderRequestItem.saleItem:id,deal_item_type', // BS 13: cột Phân loại SP
        ])->paginate(10)->withQueryString(); // giữ bộ lọc khi chuyển trang
        $suppliers = Supplier::orderBy('name')->get();

        // Thống kê - apply same filtering
        $statsQuery = PurchaseOrder::query();
        if (!$user->can('view_all_purchase_orders') && !$user->can('view_all_sales') && !$user->can('approve_purchase_orders')) {
            $statsQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('sale', fn($sQ) => $sQ->where('user_id', $user->id))
                  ->orWhereHas('items.saleOrderRequestItem.saleOrderRequest.sale', fn($sQ) => $sQ->where('user_id', $user->id));
            });
        }

        $totalValue = (clone $statsQuery)
            ->whereIn('purchase_orders.status', ['draft', 'pending_approval', 'approved', 'shipping', 'partial_received', 'received'])
            ->selectRaw('SUM(COALESCE(purchase_orders.total_foreign, purchase_orders.total / COALESCE(NULLIF(purchase_orders.exchange_rate, 0), 1))) as total_val_usd')
            ->value('total_val_usd') ?? 0;

        $stats = [
            'pending' => (clone $statsQuery)->whereIn('status', ['draft', 'pending_approval'])->count(),
            'sent' => (clone $statsQuery)->whereIn('status', ['approved', 'sent', 'confirmed', 'shipping', 'partial_received'])->count(),
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
            if ($quotation && $quotation->isExpired()) {
                return redirect()->back()->with('error', "Báo giá nhà cung cấp {$quotation->code} đã hết hạn ({$quotation->valid_until->format('d/m/Y')}), không thể dùng để tạo PO.");
            }
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

        if ($request->filled('supplier_quotation_id')) {
            $supplierQuotation = SupplierQuotation::find($request->supplier_quotation_id);
            if ($supplierQuotation && $supplierQuotation->isExpired()) {
                return back()->withInput()->with('error', 'Báo giá nhà cung cấp ' . $supplierQuotation->code . ' đã hết hạn, không thể dùng để tạo PO.');
            }
        }

        // Mã PO cho phép nhập tự do; bỏ khoảng trắng thừa để kiểm tra trùng chính xác
        $request->merge(['code' => trim((string) $request->input('code'))]);

        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:purchase_orders,code',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'cpq_number' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.sale_order_request_item_id' => 'nullable|exists:sale_order_request_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:255',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.warehouse_unit_price' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ], [
            'code.unique' => 'Mã PO ":input" đã tồn tại, vui lòng nhập mã khác.',
        ], [
            'code' => 'Mã PO',
            'cpq_number' => 'CPQ đơn hàng',
        ]);

        DB::beginTransaction();
        try {
            $order = PurchaseOrder::create([
                'code' => $validated['code'],
                'supplier_id' => $validated['supplier_id'],
                'supplier_quotation_id' => $request->supplier_quotation_id,
                'sale_id' => $request->sale_id,
                // Đặt trước cho đơn hàng khi chưa có SO/P&L -> chặn xuất hàng tới khi liên kết
                'awaiting_so_link' => !$request->filled('sale_id') && $request->boolean('awaiting_so_link'),
                'cpq_number' => $validated['cpq_number'] ?? null,
                'order_date' => $this->parseDate($validated['order_date']),
                'expected_delivery' => $this->parseDate($request->expected_delivery),
                'expected_arrival_date' => $this->parseDate($request->expected_arrival_date),
                'manufacturer_release_date' => $this->parseDate($request->manufacturer_release_date),
                'delivery_address' => $request->delivery_address,
                'discount_percent' => $request->discount_percent ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'other_cost' => $request->other_cost ?? 0,
                'vat_percent' => 0,
                'payment_terms' => $request->payment_terms ?? 'net30',
                'note' => $request->note,
                'currency_id' => $request->currency_id ?? Currency::getBaseCurrencyId(),
                'exchange_rate' => $request->exchange_rate ?? 1,
                // The create form has a "Lưu và gửi duyệt" action.  Previously
                // it was silently saved as draft, so PO appeared to vanish from
                // the approval queue.  Persist the workflow state requested.
                'status' => $request->boolean('submit_approval') ? 'pending_approval' : 'draft',
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
                    'discount_percent' => $item['discount_percent'] ?? 0,
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

            if ($order->status === 'pending_approval') {
                $this->purchaseOrderApprovalNotificationService->notifyApprovers($order);
            }

            return redirect()->route('purchase-orders.index', $order->status === 'pending_approval' ? ['status' => 'pending_approval'] : [])
                ->with('success', $order->status === 'pending_approval'
                    ? 'Đã tạo PO và gửi vào danh sách chờ duyệt.'
                    : 'Đã tạo đơn mua hàng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }


    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Không thể sửa đơn hàng đã hoàn thành hoặc đã hủy!');
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

        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Không thể sửa đơn hàng đã hoàn thành hoặc đã hủy!');
        }

        $request->merge(['code' => trim((string) $request->input('code'))]);

        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:purchase_orders,code,' . $purchaseOrder->id,
            'expected_delivery' => 'nullable|date',
            'cpq_number' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.sale_order_request_item_id' => 'nullable|exists:sale_order_request_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:255',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.warehouse_unit_price' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ], [
            'code.unique' => 'Mã PO ":input" đã tồn tại, vui lòng nhập mã khác.',
        ], [
            'code' => 'Mã PO',
            'cpq_number' => 'CPQ đơn hàng',
        ]);

        DB::beginTransaction();
        try {
            $purchaseOrder->update([
                'code' => $validated['code'],
                'expected_delivery' => $this->parseDate($request->expected_delivery),
                'cpq_number' => $validated['cpq_number'] ?? null,
                'expected_arrival_date' => $this->parseDate($request->expected_arrival_date),
                'manufacturer_release_date' => $this->parseDate($request->manufacturer_release_date),
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

            // Yêu cầu đặt hàng (SOR) liên quan trước khi sửa, để tính lại trạng thái sau khi đổi số lượng
            $affectedPrItemIds = $purchaseOrder->items()->whereNotNull('sale_order_request_item_id')
                ->pluck('sale_order_request_item_id')
                ->merge(collect($validated['items'])->pluck('sale_order_request_item_id')->filter());

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
                    'sale_order_request_item_id' => $item['sale_order_request_item_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'ordered_quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Cái',
                    'unit_price' => $item['unit_price'],
                    'warehouse_unit_price' => $item['warehouse_unit_price'] ?? 0,
                    'discount_percent' => $item['discount_percent'] ?? 0,
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

            // Giảm số lượng trên PO -> SOR quay lại "Đang xử lý" để đặt tiếp phần còn thiếu (và ngược lại)
            \App\Models\SaleOrderRequest::whereIn('id', \App\Models\SaleOrderRequestItem::whereIn('id', $affectedPrItemIds->unique())
                    ->pluck('sale_order_request_id'))
                ->get()
                ->each->checkAndUpdateStatus();

            DB::commit();
            return redirect()->route('purchase-orders.index')
                ->with('success', 'Đã cập nhật đơn mua hàng!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified purchase order from storage.
     * Chỉ cho phép quyền quản trị viên (admin, super_admin) xóa ở tất cả các trạng thái.
     * Khi xóa sẽ tự động cascade xóa các dữ liệu liên quan: Nhập kho, Tồn kho, Xuất hóa đơn, Xuất kho.
     */
    public function destroy(PurchaseOrder $purchaseOrder, PurchaseOrderDeletionService $deletionService)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasAnyRole(['super_admin', 'admin']);

        if (!$isAdmin) {
            return back()->with('error', 'Chỉ quản trị viên mới có quyền xóa đơn đặt hàng với hãng.');
        }

        $this->authorize('delete', $purchaseOrder);

        try {
            $poCode = $purchaseOrder->code;
            $deletionService->deletePurchaseOrderCascade($purchaseOrder);

            return redirect()->route('purchase-orders.index')
                ->with('success', "Đã xóa thành công đơn đặt hàng {$poCode} và toàn bộ dữ liệu liên quan (Nhập kho, Tồn kho, Xuất kho, Hóa đơn).");
        } catch (\Exception $e) {
            Log::error("Lỗi khi xóa đơn đặt hàng #{$purchaseOrder->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Có lỗi xảy ra khi xóa đơn đặt hàng: ' . $e->getMessage());
        }
    }

    public function submitApproval(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Đơn hàng không ở trạng thái nháp!');
        }

        $purchaseOrder->update(['status' => 'pending_approval']);
        $this->purchaseOrderApprovalNotificationService->notifyApprovers($purchaseOrder);

        return redirect()->route('purchase-orders.index', ['status' => 'pending_approval'])
            ->with('success', 'Đã gửi đơn hàng vào danh sách chờ duyệt. Người có quyền duyệt đã nhận được thông báo.');
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

        // Tự động cập nhật tiến độ đơn hàng bán liên kết sang "Chờ hàng về"
        $this->syncLinkedSaleOrdersStatus($purchaseOrder);

        // Thông báo cho sales khi PO được duyệt (đã đặt)
        if ($purchaseOrder->sale_id) {
            $this->notifySalesUser($purchaseOrder, 'Đơn mua hàng đã được duyệt', "Đơn mua hàng {$purchaseOrder->code} đã được duyệt và chuyển sang trạng thái 'Đã đặt' - Chờ hàng về.");
        }

        $this->purchaseOrderApprovalNotificationService->notifyCreator(
            $purchaseOrder,
            'PO đã được duyệt',
            "PO {$purchaseOrder->code} đã được duyệt và sẵn sàng để gửi hãng/theo dõi hàng về.",
            'green'
        );

        return back()->with('success', 'Đã duyệt đơn mua hàng!');
    }

    private function syncLinkedSaleOrdersStatus(PurchaseOrder $purchaseOrder): void
    {
        $saleIds = collect([$purchaseOrder->sale_id]);
        
        $purchaseOrder->loadMissing('items.saleOrderRequestItem.saleOrderRequest');
        foreach ($purchaseOrder->items as $item) {
            if ($item->saleOrderRequestItem && $item->saleOrderRequestItem->saleOrderRequest) {
                $saleIds->push($item->saleOrderRequestItem->saleOrderRequest->sale_id);
            }
        }

        $uniqueSaleIds = $saleIds->filter()->unique();
        foreach ($uniqueSaleIds as $saleId) {
            $sale = \App\Models\Sale::find($saleId);
            if ($sale) {
                $poCode = $purchaseOrder->code;
                $logMsg = "[PO Team]: PO {$poCode} đã được đặt hàng - Chờ hàng về.";
                if (!str_contains($sale->note ?? '', $poCode)) {
                    $sale->update([
                        'note' => trim(($sale->note ?? '') . "\n" . $logMsg)
                    ]);
                }
            }
        }
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

        $this->purchaseOrderApprovalNotificationService->notifyCreator(
            $purchaseOrder,
            'PO bị từ chối',
            "PO {$purchaseOrder->code} đã bị từ chối. Vui lòng chỉnh sửa và gửi duyệt lại." . ($request->reason ? " Lý do: {$request->reason}" : ''),
            'red'
        );

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

        $purchaseOrder->load([
            'supplier', 
            'sale.orderRequests.attachments',
            'sale.orderRequests.creator',
            'items.product', 
            'items.saleOrderRequestItem.saleItem', 
            'items.saleOrderRequestItem.saleOrderRequest.sale', 
            'items.saleOrderRequestItem.saleOrderRequest.attachments', 
            'items.saleOrderRequestItem.saleOrderRequest.creator', 
            'creator', 
            'approver', 
            'currency', 
            'imports.warehouse'
        ]);
        $warehouses = \App\Models\Warehouse::active()->get();

        return view('purchase-orders.show', compact('purchaseOrder', 'warehouses'));
    }

    public function ship(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['approved', 'shipping', 'partial_received'])) {
            return back()->with('error', 'Đơn hàng chưa ở trạng thái có thể chuyển!');
        }

        // Batch update: chỉ chuyển items đang 'ordered' → 'shipping'
        $updated = $purchaseOrder->items()
            ->where('status', 'ordered')
            ->update(['status' => 'shipping']);

        if ($updated === 0) {
            return back()->with('warning', 'Không có sản phẩm nào ở trạng thái "Chờ hàng" để chuyển.');
        }

        // Update PO status to shipping if not already
        if ($purchaseOrder->status === 'approved') {
            $purchaseOrder->update(['status' => 'shipping']);
        }

        // Thông báo cho sales
        if ($purchaseOrder->sale_id) {
            $this->notifySalesUser($purchaseOrder, 'Đơn mua hàng đang về', "Đơn mua hàng {$purchaseOrder->code} đã được xuất đi và đang trên đường về.");
        }

        return back()->with('success', "Đã chuyển {$updated} sản phẩm sang trạng thái: Đang về!");
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
                if ($item->received_quantity >= $item->quantity) {
                    $item->status = 'received';
                }
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

    /**
     * Quickly receive all remaining items in a PO
     */
    public function receiveAll(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['approved', 'shipping', 'partial_received'])) {
            return back()->with('error', 'Đơn hàng chưa sẵn sàng để nhận!');
        }

        DB::beginTransaction();
        try {
            $receivedItemData = [];
            foreach ($purchaseOrder->items as $item) {
                if ($item->remaining_quantity > 0) {
                    $receivedItemData[$item->id] = $item->remaining_quantity;
                    $item->received_quantity += $item->remaining_quantity;
                    $item->status = 'received';
                    $item->save();
                }
            }

            if (empty($receivedItemData)) {
                return back()->with('warning', 'Đơn hàng đã được nhận đủ trước đó!');
            }

            $purchaseOrder->update([
                'status' => 'received',
                'actual_delivery' => now(),
            ]);

            // Sync với Module Nhập kho
            try {
                $warehouse = \App\Models\Warehouse::active()->first();
                $this->purchaseImportSyncService->createPartialImportFromPO(
                    $purchaseOrder, 
                    $receivedItemData, 
                    $warehouse->id ?? null, 
                    false
                );
            } catch (\Exception $e) {
                Log::warning("Could not create import for PO #{$purchaseOrder->id}: " . $e->getMessage());
            }

            DB::commit();

            if ($purchaseOrder->sale_id) {
                $this->notifySalesUser($purchaseOrder, 'Hàng đã về - đủ hàng', "Đơn mua hàng {$purchaseOrder->code} đã được nhận đủ hàng.");
            }

            $this->checkAndNotifyOrderComplete($purchaseOrder);

            return back()->with('success', 'Đã nhận đủ hàng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Liên kết PO (đặt trước khi chưa có SO/P&L) với Đơn hàng bán để mở chặn xuất hàng.
     */
    public function linkSale(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if ($purchaseOrder->status === 'cancelled') {
            return back()->with('error', 'PO đã hủy, không thể liên kết.');
        }

        $validated = $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
        ], [], ['sale_id' => 'Đơn hàng bán']);

        $purchaseOrder->update(['sale_id' => $validated['sale_id']]);
        $purchaseOrder->load('sale');

        $message = "Đã liên kết PO {$purchaseOrder->code} với đơn {$purchaseOrder->sale->code}.";
        $message .= $purchaseOrder->isExportBlocked()
            ? ' Hàng của PO vẫn bị chặn xuất cho tới khi P&L của đơn được duyệt.'
            : ' Hàng của PO đã được phép xuất kho.';

        return back()->with('success', $message);
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Không thể hủy đơn hàng này!');
        }

        $reason = trim((string)($request->input('reason') ?? $request->input('cancel_reason') ?? ''));
        $cancelTarget = $request->input('cancel_target', 'order_request');
        if (empty($reason)) {
            $reason = 'Hủy đặt hàng theo yêu cầu quản lý.';
        }

        DB::beginTransaction();
        try {
            // Thu thập các PR liên quan trước khi hủy items
            $affectedPrItemIds = $purchaseOrder->items()
                ->whereNotNull('sale_order_request_item_id')
                ->pluck('sale_order_request_item_id')
                ->unique();

            $affectedSorIds = [];
            if ($affectedPrItemIds->isNotEmpty()) {
                $affectedSorIds = \App\Models\SaleOrderRequestItem::whereIn('id', $affectedPrItemIds)
                    ->pluck('sale_order_request_id')
                    ->unique()
                    ->filter()
                    ->toArray();
            }

            // Nếu không tìm thấy PR từ items nhưng PO có gắn sale_id, kiểm tra PR của sale đó
            if (empty($affectedSorIds) && $purchaseOrder->sale_id) {
                $affectedSorIds = \App\Models\SaleOrderRequest::where('sale_id', $purchaseOrder->sale_id)
                    ->whereIn('status', [
                        \App\Models\SaleOrderRequest::STATUS_PROCESSING,
                        \App\Models\SaleOrderRequest::STATUS_COMPLETED,
                        \App\Models\SaleOrderRequest::STATUS_SUBMITTED
                    ])
                    ->pluck('id')
                    ->toArray();
            }

            $previousStatus = $purchaseOrder->status;

            // Cập nhật trạng thái các PO items sang 'cancelled' (thay vì xóa để giữ lại lịch sử hiển thị)
            $purchaseOrder->items()->update(['status' => 'cancelled']);

            // Hủy PO và lưu lý do hủy vào ghi chú PO
            $cancelLogNote = "[" . now()->format('d/m/Y H:i') . " - " . (auth()->user()->name ?? 'System') . "] Đã hủy đơn: " . $reason;
            $newPoNote = trim(($purchaseOrder->note ? $purchaseOrder->note . "\n" : '') . $cancelLogNote);
            $purchaseOrder->update([
                'status' => 'cancelled',
                'note' => $newPoNote,
            ]);

            // Giữ PO lại chờ Sales chỉnh sửa: khi yêu cầu đặt hàng được duyệt
            // lại, sản phẩm quay về PO này thay vì qua Gom đơn tạo PO mới.
            app(\App\Services\PurchaseOrderRevisionService::class)
                ->markAwaitingRevision($purchaseOrder, $previousStatus, $affectedSorIds);

            $revertPrCodes = [];
            $formattedReason = "Hủy đặt hàng (PO #{$purchaseOrder->code}): {$reason}";
            if ($cancelTarget === 'pnl') {
                $formattedReason = "Hủy đặt hàng (PO #{$purchaseOrder->code}) & Yêu cầu chỉnh lại P&L: {$reason}";
            }

            // Revert trực tiếp các PR liên quan về trạng thái "Thiếu thông tin" (need_info)
            // để Sales có thể vào chỉnh sửa ngay lập tức kèm lý do!
            $affectedSaleIds = [];
            if ($purchaseOrder->sale_id) {
                $affectedSaleIds[] = $purchaseOrder->sale_id;
            }

            foreach ($affectedSorIds as $sorId) {
                $sor = \App\Models\SaleOrderRequest::find($sorId);
                if ($sor) {
                    $sor->status = \App\Models\SaleOrderRequest::STATUS_NEED_INFO;
                    $sor->rejection_note = $formattedReason;
                    $sor->save();
                    $revertPrCodes[] = $sor->code;
                    if ($sor->sale_id) {
                        $affectedSaleIds[] = $sor->sale_id;
                    }

                    // Gỡ bỏ bất kỳ draft PO item nào khác đang giữ sản phẩm của PR này (nếu có)
                    $prItemIds = $sor->items()->pluck('id');
                    if ($prItemIds->isNotEmpty()) {
                        \App\Models\PurchaseOrderItem::whereIn('sale_order_request_item_id', $prItemIds)
                            ->whereHas('purchaseOrder', fn($q) => $q->where('status', 'draft'))
                            ->delete();
                    }

                    // Ghi log hoạt động
                    try {
                        \App\Models\ActivityLog::create([
                            'log_name' => 'purchase_order',
                            'description' => "Đã hủy PO #{$purchaseOrder->code} và hoàn trả Yêu cầu đặt hàng #{$sor->code} về trạng thái Thiếu thông tin. Lý do: {$reason}" . ($cancelTarget === 'pnl' ? ' (Yêu cầu chỉnh lại P&L)' : ''),
                            'subject_type' => \App\Models\SaleOrderRequest::class,
                            'subject_id' => $sor->id,
                            'causer_type' => \App\Models\User::class,
                            'causer_id' => auth()->id(),
                            'properties' => [
                                'purchase_order_id' => $purchaseOrder->id,
                                'purchase_order_code' => $purchaseOrder->code,
                                'reason' => $reason,
                                'cancel_target' => $cancelTarget,
                                'status' => \App\Models\SaleOrderRequest::STATUS_NEED_INFO,
                            ],
                        ]);
                    } catch (\Exception $e) {
                        Log::warning("Could not log activity for PR revert #{$sor->id}: " . $e->getMessage());
                    }

                    // Gửi thông báo cho Sales
                    $this->notifySalesRevertNeedInfo($sor, $purchaseOrder, $reason);
                }
            }

            // Nếu chọn Chỉnh lại P&L, mở lại bảng P&L của tất cả đơn hàng liên quan
            if ($cancelTarget === 'pnl') {
                $affectedSaleIds = array_unique($affectedSaleIds);
                foreach ($affectedSaleIds as $sId) {
                    $sale = \App\Models\Sale::find($sId);
                    if ($sale) {
                        $sale->update([
                            'pl_status' => 'need_revision',
                            'status'    => 'pending',
                        ]);

                        \App\Models\ApprovalHistory::recordPnlRevision(
                            $sale,
                            auth()->user(),
                            "Hủy đơn đặt hàng hãng (PO #{$purchaseOrder->code}) và yêu cầu chỉnh lại P&L: {$reason}"
                        );

                        if ($sale->user_id) {
                            Notification::create([
                                'user_id' => $sale->user_id,
                                'type'    => 'pnl_need_revision',
                                'title'   => 'P&L yêu cầu chỉnh sửa lại (do hủy đơn đặt hãng)',
                                'message' => (auth()->user()->name ?? 'PO Team') . " đã hủy đơn đặt hàng hãng ({$purchaseOrder->code}) và yêu cầu chỉnh sửa lại P&L cho đơn {$sale->code}. Lý do: \"{$reason}\". Vui lòng chỉnh sửa P&L và gửi duyệt lại.",
                                'link'    => route('sales.show', $sale->id) . '#pnl',
                                'icon'    => 'fas fa-chart-line',
                                'color'   => 'orange',
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            $successMsg = "Đã hủy đơn mua hàng #{$purchaseOrder->code}!";
            if (!empty($revertPrCodes)) {
                $prListStr = implode(', ', $revertPrCodes);
                $successMsg .= " Yêu cầu đặt hàng ({$prListStr}) đã được hoàn trả về cho Sales ở trạng thái \"Thiếu thông tin\" để chỉnh sửa.";
            }
            if ($cancelTarget === 'pnl') {
                $successMsg .= " Bảng P&L của đơn hàng đã được mở lại để Sales chỉnh sửa chi phí và gửi duyệt lại.";
            }
            if (!empty($revertPrCodes)) {
                $successMsg .= " Sau khi yêu cầu đặt hàng được duyệt lại, sản phẩm sẽ tự quay về đơn #{$purchaseOrder->code} để xử lý tiếp.";
            }

            return back()->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error cancelling PO #{$purchaseOrder->id}: " . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Trả PO về Gom đơn (dùng khi PO team tick nhầm SO / tạo nhầm PO).
     * Khác với Hủy/Từ chối: KHÔNG trả yêu cầu đặt hàng về Sales, chỉ hủy PO này
     * để các sản phẩm quay lại danh sách "Cần đặt hàng" cho PO team gom lại.
     */
    public function returnToNeedsOrdering(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['draft', 'pending_approval', 'approved', 'sent', 'confirmed'], true)) {
            return back()->with('error', 'Chỉ có thể trả về Gom đơn khi PO chưa chuyển sang Đang về / Nhận hàng / Hủy.');
        }

        $purchaseOrder->load('items');

        if ($purchaseOrder->items->contains(fn ($i) => (float) $i->received_quantity > 0 || in_array($i->status, ['shipping', 'received'], true))) {
            return back()->with('error', 'PO đã có sản phẩm Đang về / Đã nhận hàng, không thể trả về Gom đơn.');
        }

        if ($purchaseOrder->imports()->exists()) {
            return back()->with('error', 'PO đã phát sinh phiếu nhập kho, không thể trả về Gom đơn.');
        }

        if ((float) $purchaseOrder->paid_amount > 0) {
            return back()->with('error', 'PO đã phát sinh thanh toán cho hãng, không thể trả về Gom đơn.');
        }

        $reason = trim((string) $request->input('reason', '')) ?: 'PO team tạo nhầm PO, trả về Gom đơn để làm lại.';

        DB::beginTransaction();
        try {
            $prItemIds = $purchaseOrder->items->pluck('sale_order_request_item_id')->filter()->unique();
            $affectedSorIds = $prItemIds->isEmpty() ? collect() : \App\Models\SaleOrderRequestItem::whereIn('id', $prItemIds)
                ->pluck('sale_order_request_id')
                ->filter()
                ->unique();

            $previousStatus = $purchaseOrder->status;

            // Hủy PO (giữ lại lịch sử) → số lượng đã đặt được giải phóng vì
            // ordered_quantity_total bỏ qua PO đã hủy.
            $purchaseOrder->items()->update(['status' => 'cancelled']);

            $logNote = "[" . now()->format('d/m/Y H:i') . " - " . (auth()->user()->name ?? 'System') . "] Trả về Gom đơn: " . $reason;
            $purchaseOrder->update([
                'status' => 'cancelled',
                'note' => trim(($purchaseOrder->note ? $purchaseOrder->note . "\n" : '') . $logNote),
                // Không để PO này tự nhận lại sản phẩm theo luồng chỉnh sửa của Sales
                'awaiting_revision' => false,
                'revision_previous_status' => null,
                'revision_sale_order_request_ids' => null,
            ]);

            // Yêu cầu đặt hàng quay lại "Đang xử lý" → hiện lại ở Gom đơn
            $prCodes = [];
            foreach ($affectedSorIds as $sorId) {
                $sor = \App\Models\SaleOrderRequest::find($sorId);
                if (!$sor) {
                    continue;
                }
                $sor->checkAndUpdateStatus();
                $prCodes[] = $sor->code;

                // PO đã duyệt từng ghi "Chờ hàng về" vào ghi chú đơn bán → ghi đè lại thông tin
                if ($sor->sale && !in_array($previousStatus, ['draft', 'pending_approval'], true)) {
                    $sor->sale->update([
                        'note' => trim(($sor->sale->note ?? '') . "\n[PO Team]: PO {$purchaseOrder->code} đã được trả về Gom đơn để đặt lại."),
                    ]);
                }
            }

            try {
                \App\Models\ActivityLog::create([
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? null,
                    'action' => 'return_to_needs_ordering',
                    'description' => "Trả PO #{$purchaseOrder->code} về Gom đơn (trạng thái trước: {$previousStatus}). Lý do: {$reason}",
                    'subject_type' => PurchaseOrder::class,
                    'subject_id' => $purchaseOrder->id,
                    'properties' => [
                        'previous_status' => $previousStatus,
                        'reason' => $reason,
                        'sale_order_request_codes' => $prCodes,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::warning("Could not log return-to-needs-ordering for PO #{$purchaseOrder->id}: " . $e->getMessage());
            }

            DB::commit();

            return redirect()->route('purchasing.needs-ordering')
                ->with('success', "Đã trả PO #{$purchaseOrder->code} về Gom đơn. Các sản phẩm đã quay lại danh sách Cần đặt hàng" . (!empty($prCodes) ? ' (' . implode(', ', $prCodes) . ')' : '') . '.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error returning PO #{$purchaseOrder->id} to needs-ordering: " . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Gửi thông báo cho Sales khi PO bị hủy và PR bị revert về "Thiếu thông tin"
     */
    private function notifySalesRevertNeedInfo(\App\Models\SaleOrderRequest $pr, PurchaseOrder $po, string $reason): void
    {
        try {
            $pr->load('sale');
            $actorName = auth()->user()->name ?? 'PO Team';
            $saleCode = $pr->sale?->code ?? 'N/A';

            $targetUserIds = array_filter(array_unique([
                $pr->created_by,
                $pr->sale?->user_id,
            ]));

            foreach ($targetUserIds as $userId) {
                \App\Models\Notification::create([
                    'user_id' => $userId,
                    'type' => 'order_request_need_info',
                    'title' => 'Đơn mua hàng bị hủy - Yêu cầu đặt hàng cần chỉnh sửa',
                    'message' => "{$actorName} đã hủy đơn mua hàng ({$po->code}) cho đơn bán {$saleCode} và hoàn trả yêu cầu đặt hàng ({$pr->code}) về trạng thái Thiếu thông tin. Lý do: \"{$reason}\". Vui lòng kiểm tra và chỉnh sửa lại yêu cầu.",
                    'link' => $pr->sale ? route('sales.show', $pr->sale_id) : route('purchase-requests.index'),
                    'icon' => 'fas fa-undo-alt',
                    'color' => 'orange',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("Could not send notification for PR revert #{$pr->id}: " . $e->getMessage());
        }
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

        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Không thể cập nhật theo dõi cho đơn hàng đã hoàn thành hoặc đã hủy!');
        }

        $validated = $request->validate([
            'expected_arrival_date' => 'nullable|date',
            'manufacturer_release_date' => 'nullable|date',
            'expected_delivery' => 'nullable|date',
        ]);

        $purchaseOrder->update([
            'expected_arrival_date' => $this->parseDate($request->expected_arrival_date),
            'manufacturer_release_date' => $this->parseDate($request->manufacturer_release_date),
            'expected_delivery' => $this->parseDate($request->expected_delivery),
        ]);

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
                    'pos_id' => $item->pos_id,
                    'estimated_cost_usd' => (float) ($item->saleItem->estimated_cost_usd ?? 0),
                    'cost_price_vnd' => (float) ($item->saleItem ? $item->saleItem->calculateVndCost() : 0),
                    'list_price_usd' => (float) ($item->saleItem->usd_price ?? 0),
                    'list_price_vnd' => (float) (($item->saleItem->usd_price ?? 0) * ($item->saleItem->exchange_rate ?? 1)),
                    'discount_percent' => (float) ($item->saleItem->discount_rate ?? 0),
                ];
            })
            ->values();

        return response()->json($items);
    }

    public function updateExpectedDelivery(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'Không thể cập nhật cho đơn hàng đã hoàn thành hoặc đã hủy.'], 422);
        }

        $request->validate([
            'expected_delivery' => 'nullable|date',
        ]);

        $purchaseOrder->update([
            'expected_delivery' => $this->parseDate($request->expected_delivery),
        ]);

        return response()->json(['success' => true]);
    }

    public function updateManufacturerReleaseDate(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'Không thể cập nhật cho đơn hàng đã hoàn thành hoặc đã hủy.'], 422);
        }

        $request->validate([
            'manufacturer_release_date' => 'nullable|date',
        ]);

        $purchaseOrder->update([
            'manufacturer_release_date' => $this->parseDate($request->manufacturer_release_date),
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
        // Nếu sản phẩm đã ở trạng thái 'received', không cho phép thay đổi nữa
        if ($item->status === 'received') {
            return response()->json([
                'success' => false, 
                'message' => 'Sản phẩm đã về hàng không thể thay đổi trạng thái.'
            ], 422);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:ordered,shipping,received,cancelled'
        ]);

        // Nếu cancel item → xóa PO item và revert PR
        if ($validated['status'] === 'cancelled') {
            DB::beginTransaction();
            try {
                $po = $item->purchaseOrder;
                $prItemId = $item->sale_order_request_item_id;

                // Xóa PO item (giải phóng ordered_quantity)
                $item->delete();

                // Recalculate PO totals
                $po->load('items');
                $po->calculateTotals();
                $po->updateDebt();
                $po->save();

                // Nếu PO không còn items → hủy PO luôn
                if ($po->items->isEmpty()) {
                    $po->update(['status' => 'cancelled']);
                }

                // Revert PR status
                if ($prItemId) {
                    $prItem = \App\Models\SaleOrderRequestItem::find($prItemId);
                    if ($prItem) {
                        $prItem->saleOrderRequest->checkAndUpdateStatus();
                    }
                }

                DB::commit();
                return response()->json([
                    'success' => true,
                    'po_status_updated' => $po->items->isEmpty() ? 'cancelled' : null,
                    'message' => 'Đã hủy sản phẩm và trả về Gom đơn.'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        $oldReceivedQty = (float) $item->received_quantity;
        // Cập nhật số lượng đã nhận nếu trạng thái là received
        if ($validated['status'] === 'received') {
            $item->received_quantity = $item->quantity;
        } elseif ($validated['status'] === 'ordered' || $validated['status'] === 'shipping') {
            $item->received_quantity = 0;
        }

        $item->update(['status' => $validated['status']]);

        // Logic tạo phiếu nhập kho (Import) khi hàng về
        if ($validated['status'] === 'received') {
            $batchQty = $item->quantity - $oldReceivedQty;
            if ($batchQty > 0) {
                $this->syncPoItemToImport($item, $batchQty);
            }
        }

        // Auto-check: nếu tất cả items đều 'received' → cập nhật PO status
        $po = $item->purchaseOrder;
        $po->load('items');
        $allReceived = $po->items->every(fn($i) => $i->status === 'received' || $i->status === 'cancelled');
        $hasReceived = $po->items->contains(fn($i) => $i->status === 'received');
        
        if ($allReceived && $hasReceived && $po->status !== 'received') {
            $po->update([
                'status' => 'received',
                'actual_delivery' => now(),
            ]);
            
            // Notify sales
            if ($po->sale_id) {
                $this->notifySalesUser($po, 'Hàng đã về - đủ hàng', "Tất cả sản phẩm trong đơn mua hàng {$po->code} đã về đủ.");
            }
            $this->checkAndNotifyOrderComplete($po);
            
            return response()->json(['success' => true, 'po_status_updated' => 'received']);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Tải lên file license cho từng món hàng (hỗ trợ nhiều file)
     */
    public function uploadItemLicense(Request $request, PurchaseOrderItem $item)
    {
        $request->validate([
            'license_files' => 'required|array',
            'license_files.*' => 'file|max:10240', // 10MB per file
        ]);

        if ($request->hasFile('license_files')) {
            $existingFiles = [];
            if ($item->license_file) {
                $decoded = json_decode($item->license_file, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $existingFiles = $decoded;
                } else {
                    $existingFiles = [$item->license_file];
                }
            }

            foreach ($request->file('license_files') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('po-licenses', $filename, 'public');
                $existingFiles[] = $path;
            }
            
            $item->update(['license_file' => json_encode($existingFiles)]);

            // Notify Sales
            if ($item->saleOrderRequestItem && $item->saleOrderRequestItem->saleOrderRequest && $item->saleOrderRequestItem->saleOrderRequest->sale) {
                $sale = $item->saleOrderRequestItem->saleOrderRequest->sale;
                if ($sale->user_id) {
                    \App\Models\Notification::create([
                        'user_id' => $sale->user_id,
                        'type' => 'license_uploaded',
                        'title' => 'Đã có License cho sản phẩm ' . $item->product_name,
                        'message' => "PO Team đã tải lên license cho sản phẩm trong đơn hàng {$sale->code}. Bạn có thể xem và tải về ngay.",
                        'link' => route('sales.show', $sale->id),
                        'icon' => 'fas fa-certificate',
                        'color' => 'teal',
                        'is_read' => false,
                    ]);
                }
            }
        }

        return back()->with('success', 'Đã tải lên license cho sản phẩm ' . $item->product_name);
    }

    /**
     * Xóa một file license của món hàng theo index
     */
    public function deleteItemLicense(Request $request, PurchaseOrderItem $item)
    {
        $request->validate([
            'file_index' => 'required|integer',
        ]);

        $index = (int) $request->input('file_index');

        if ($item->license_file) {
            $decoded = json_decode($item->license_file, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $files = $decoded;
            } else {
                $files = [$item->license_file];
            }

            if (isset($files[$index])) {
                $filePath = $files[$index];
                // Xóa file khỏi storage
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }

                // Xóa khỏi mảng và reindex
                unset($files[$index]);
                $files = array_values($files);

                // Cập nhật database
                if (empty($files)) {
                    $item->update(['license_file' => null]);
                } else {
                    $item->update(['license_file' => json_encode($files)]);
                }

                return back()->with('success', 'Đã xóa file license thành công.');
            }
        }

        return back()->with('error', 'Không tìm thấy file để xóa.');
    }

    /**
     * Preview license file for a purchase order item
     */
    /**
     * Quyền xem/tải file license của một dòng PO. PO gom nhiều SO: Sales chỉ xem được license
     * của dòng thuộc SO mình xem được, không xem license của Sales khác chung PO.
     */
    private function authorizeItemLicense(PurchaseOrderItem $item): void
    {
        $user = auth()->user();
        $item->loadMissing('purchaseOrder.sale', 'saleOrderRequestItem.saleOrderRequest.sale');

        // PO Team / người tạo PO / xem toàn bộ PO. Không dùng policy 'view' của PO: policy đó cho Sales
        // xem cả PO chỉ vì có 1 dòng của mình, sẽ lộ license dòng của SO khác.
        $permissions = app(\App\Services\PermissionService::class);
        if ($user->hasAnyRole(Sale::PO_TEAM_ROLES)
            || (int) $item->purchaseOrder?->created_by === (int) $user->id
            || $permissions->checkPermission($user->id, 'view_all_purchase_orders')
            || $permissions->checkPermission($user->id, 'approve_purchase_orders')) {
            return;
        }

        // Sales / kế toán xem được SO sở hữu dòng PO này
        $sale = $item->saleOrderRequestItem?->saleOrderRequest?->sale ?: $item->purchaseOrder?->sale;
        if ($sale && $user->can('view', $sale)) {
            return;
        }

        // Kho (tab Hàng license) và kỹ thuật tra cứu S/N / license
        $isWarehouse = $user->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff'])
            || $user->department === 'Warehouse';
        if ($isWarehouse || \App\Models\TechnicalTicket::canUserLookupSerials($user)) {
            return;
        }

        abort(403, 'Bạn không có quyền xem license của dòng PO này.');
    }

    public function downloadItemLicense(PurchaseOrderItem $item, $index)
    {
        $this->authorizeItemLicense($item);

        $decoded = json_decode((string) $item->license_file, true);
        $files = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? array_values($decoded) : array_filter([$item->license_file]);
        $filePath = $files[(int) $index] ?? null;

        if ($filePath && Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->download($filePath, basename($filePath));
        }

        abort(404, 'File không tồn tại.');
    }

    public function previewItemLicense(PurchaseOrderItem $item, $index)
    {
        $this->authorizeItemLicense($item);

        $index = (int) $index;

        if ($item->license_file) {
            $decoded = json_decode($item->license_file, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $files = $decoded;
            } else {
                $files = [$item->license_file];
            }

            if (isset($files[$index])) {
                $filePath = $files[$index];
                $path = Storage::disk('public')->path($filePath);

                if (file_exists($path)) {
                    return response()->file($path);
                }
            }
        }

        abort(404, 'File không tồn tại.');
    }

    /**
     * Batch xác nhận nhận hàng: chuyển tất cả items 'shipping' → 'received'
     */
    public function confirmReceived(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['approved', 'shipping', 'partial_received'])) {
            return back()->with('error', 'Đơn hàng chưa sẵn sàng để nhận!');
        }

        DB::beginTransaction();
        try {
            // Lưu lại danh sách item IDs đang ở trạng thái 'shipping' TRƯỚC KHI update
            $shippingItemIds = $purchaseOrder->items()
                ->where('status', 'shipping')
                ->pluck('id')
                ->toArray();

            if (empty($shippingItemIds)) {
                return back()->with('warning', 'Không có sản phẩm nào ở trạng thái "Đang về" để xác nhận.');
            }

            // Batch update: chỉ chuyển items đang 'shipping' → 'received'
            $updated = $purchaseOrder->items()
                ->whereIn('id', $shippingItemIds)
                ->update(['status' => 'received']);

            // Cập nhật received_quantity CHỈ cho các items vừa chuyển (không động vào items đã received trước đó)
            $purchaseOrder->load('items');
            $itemBatchQtys = [];
            foreach ($purchaseOrder->items as $item) {
                if (in_array($item->id, $shippingItemIds) && $item->received_quantity < $item->quantity) {
                    $itemBatchQtys[$item->id] = $item->quantity - $item->received_quantity;
                    $item->received_quantity = $item->quantity;
                    $item->save();
                }
            }

            // Check if all items are received
            $allReceived = $purchaseOrder->items->every(fn($i) => $i->status === 'received' || $i->status === 'cancelled');
            $hasReceived = $purchaseOrder->items->contains(fn($i) => $i->status === 'received');

            if ($allReceived && $hasReceived) {
                $purchaseOrder->update([
                    'status' => 'received',
                    'actual_delivery' => now(),
                ]);
            } elseif ($hasReceived) {
                $purchaseOrder->update(['status' => 'partial_received']);
            }

            // Sync CHỈ các items vừa mới chuyển sang 'received' (không sync lại items đã nhập kho trước đó)
            foreach ($purchaseOrder->items as $item) {
                if (in_array($item->id, $shippingItemIds)) {
                    $batchQty = $itemBatchQtys[$item->id] ?? $item->quantity;
                    $this->syncPoItemToImport($item, $batchQty);
                }
            }

            DB::commit();

            // Notify sales
            if ($purchaseOrder->sale_id) {
                $statusText = ($allReceived && $hasReceived) ? 'Hàng đã về - đủ hàng' : 'Hàng đã về - một phần';
                $this->notifySalesUser($purchaseOrder, $statusText, "Đơn mua hàng {$purchaseOrder->code}: {$updated} sản phẩm đã được xác nhận nhận hàng.");
            }

            if ($allReceived && $hasReceived) {
                $this->checkAndNotifyOrderComplete($purchaseOrder);
            }

            $msg = ($allReceived && $hasReceived) 
                ? "Đã xác nhận nhận {$updated} sản phẩm. Đơn hàng đã đủ hàng!" 
                : "Đã xác nhận nhận {$updated} sản phẩm.";

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Đồng bộ một sản phẩm từ PO sang Phiếu nhập kho (Import)
     */
    private function syncPoItemToImport(PurchaseOrderItem $item, ?float $batchQty = null): void
    {
        try {
            $po = $item->purchaseOrder;

            // Dòng PO chưa liên kết sản phẩm danh mục (mã mới) → tìm/tạo sản phẩm trước khi nhập kho
            if (!$item->product_id && !$this->purchaseImportSyncService->resolveProductIdForPoItem($item)) {
                Log::warning("PO item #{$item->id} '{$item->product_name}' chưa liên kết sản phẩm danh mục - bỏ qua nhập kho");
                return;
            }

            // 1. Tìm phiếu nhập kho duy nhất cho PO này (bất kể trạng thái)
            $import = \App\Models\Import::where('reference_type', 'purchase_order')
                ->where('reference_id', $po->id)
                ->first();

            // 2. Nếu chưa có, tạo mới. Nếu có rồi mà đã completed, mở lại thành pending
            if (!$import) {
                // Resolve kho cho item đầu tiên để đặt kho chính của phiếu nhập
                $initialWarehouseId = $this->purchaseImportSyncService->resolveWarehouseForPoItem($item);
                
                $import = \App\Models\Import::create([
                    'code' => $po->code,
                    'warehouse_id' => $initialWarehouseId ?? \App\Models\Warehouse::where('code', 'WH_RUNRATE')->value('id') ?? \App\Models\Warehouse::first()->id ?? 1,
                    'supplier_id' => $po->supplier_id,
                    'date' => now(),
                    'employee_id' => auth()->id(),
                    'reference_type' => 'purchase_order',
                    'reference_id' => $po->id,
                    'status' => 'pending',
                    'note' => "Tự động tạo từ đơn mua hàng " . $po->code,
                    'total_qty' => 0,
                ]);
            }

            // 3. Thêm/Cập nhật item vào phiếu nhập kho
            // Dùng purchase_order_item_id (comments chứa PO item ID) để phân biệt
            // từng dòng sản phẩm, kể cả sản phẩm trùng product_id
            $poItemTag = "[POItem:{$item->id}]";
            $existing = \App\Models\ImportItem::where('import_id', $import->id)
                ->where('comments', 'like', "%{$poItemTag}%")
                ->first();

            // Fallback: nếu không tìm thấy bằng tag (dữ liệu cũ), thử tìm bằng product_id + cost
            // nhưng chỉ khi KHÔNG có tag nào khác (tức là dòng chưa được tag)
            if (!$existing) {
                $existing = \App\Models\ImportItem::where('import_id', $import->id)
                    ->where('product_id', $item->product_id)
                    ->where('cost', $item->unit_price)
                    ->whereNull('processed_at')
                    ->where('comments', 'not like', '%[POItem:%')
                    ->first();
            }

            $saleInfo = ($item->saleOrderRequestItem && $item->saleOrderRequestItem->saleOrderRequest && $item->saleOrderRequestItem->saleOrderRequest->sale)
                ? " (Đơn " . $item->saleOrderRequestItem->saleOrderRequest->sale->code . ")"
                : "";
            $comment = "{$poItemTag} Hàng về từ PO " . $po->code . $saleInfo;

            $itemWarehouseId = $this->purchaseImportSyncService->resolveWarehouseForPoItem($item, $import->warehouse_id);

            // Parse and filter out already imported serials
            $poSerials = [];
            if (!empty($item->serial_number)) {
                $decoded = is_array($item->serial_number) ? $item->serial_number : json_decode($item->serial_number, true);
                if (is_array($decoded)) {
                    $poSerials = array_values(array_filter($decoded, fn($s) => !empty(trim((string)$s))));
                }
            }
            if (!empty($poSerials)) {
                $existingRealSerials = \App\Models\ProductItem::where('product_id', $item->product_id)
                    ->whereIn('sku', $poSerials)
                    ->pluck('sku')
                    ->toArray();
                $poSerials = array_values(array_diff($poSerials, $existingRealSerials));
            }
            $qtyToImport = $batchQty !== null ? $batchQty : $item->quantity;

            // Đợt nhận này chỉ gắn tối đa đúng số lượng nhận; serial còn lại dành cho đợt sau
            $poSerials = array_slice($poSerials, 0, (int) $qtyToImport);
            $serialJson = !empty($poSerials) ? json_encode($poSerials) : null;

            if (!$existing || $existing->processed_at !== null) {
                \App\Models\ImportItem::create([
                    'import_id' => $import->id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $itemWarehouseId,
                    'quantity' => $qtyToImport,
                    'cost' => $item->unit_price, 
                    'warehouse_price' => $item->unit_price,
                    'comments' => $comment,
                    'serial_number' => $serialJson,
                ]);
            } else {
                $existing->update([
                    'quantity' => $qtyToImport,
                    'warehouse_id' => $itemWarehouseId,
                    'comments' => $comment,
                    'serial_number' => $serialJson,
                ]);
            }

            // 4. Cập nhật lại tổng số lượng, kho nhập chính và trạng thái của phiếu nhập
            $importItemsWarehouseIds = $import->items()->pluck('warehouse_id')->filter()->unique();
            $mainImportWarehouseId = $importItemsWarehouseIds->count() === 1 ? $importItemsWarehouseIds->first() : null;
            $hasUnprocessed = $import->items()->whereNull('processed_at')->exists();

            $import->update([
                'warehouse_id' => $mainImportWarehouseId ?? $import->warehouse_id,
                'total_qty' => $import->items()->sum('quantity'),
                'status' => $hasUnprocessed ? 'pending' : 'completed',
            ]);

        } catch (\Exception $e) {
            \Log::error("Lỗi tự động đồng bộ sang phiếu nhập kho: " . $e->getMessage());
        }
    }

    public function exportSingle(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier.poConfig']);
        $supplierName = $purchaseOrder->supplier->name ?? '';
        $poConfig = $purchaseOrder->supplier->poConfig ?? null;

        $safeCode = str_replace(['/', '\\'], '-', $purchaseOrder->code);
        $filename = $safeCode . '-' . date('Y-m-d') . '.xlsx';

        if ($poConfig) {
            if ($poConfig->template_type === 'fortinet') {
                return Excel::download(new FortinetPurchaseOrderExport($purchaseOrder), $filename);
            } elseif ($poConfig->template_type === 'sale_contract') {
                return Excel::download(new SaleContractPurchaseOrderExport($purchaseOrder), $filename);
            }
        }

        if (stripos($supplierName, 'fortinet') !== false) {
            return Excel::download(new FortinetPurchaseOrderExport($purchaseOrder), $filename);
        }

        return Excel::download(new SaleContractPurchaseOrderExport($purchaseOrder), $filename);
    }

    public function previewHtml(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier.poConfig', 'items.product', 'items.saleOrderRequestItem.saleItem', 'currency']);
        $company = \App\Models\PoCompanyConfig::getConfig();
        $poConfig = $purchaseOrder->supplier->poConfig ?? null;

        // Determine template type
        $templateType = $poConfig->template_type ?? 'sale_contract';
        
        // Fallback for Fortinet supplier names
        $supplierName = $purchaseOrder->supplier->name ?? '';
        if (!$poConfig && stripos($supplierName, 'fortinet') !== false) {
            $templateType = 'fortinet';
        }

        if ($templateType === 'fortinet') {
            $config = $poConfig ?: new \App\Models\SupplierPoConfig([
                'template_type' => 'fortinet',
                'seller_name' => 'FORTINET INC',
                'seller_address_line1' => 'US Headquarters, 909 Kifer Road, Sunnyvale, CA 94086 US',
                'seller_address_line2' => '',
                'seller_tel' => '(408) 486-4816',
                'seller_fax' => '(408) 235-7737',
                'seller_contact' => "ANSON HA - Order Coordinator",
                'seller_beneficiary' => 'Fortinet Inc., 909 Kifer Road',
                'seller_beneficiary_address' => 'Sunnyvale, CA 94086 United States',
                'seller_bank_name' => 'WELLS FARGO BANK, N.A',
                'seller_bank_account' => '4040006199',
                'seller_bank_address_line1' => 'Santa Clara Valley RCBO, 420 Montgomery St,',
                'seller_bank_address_line2' => 'San Francisco, CA 94104',
                'seller_bank_aba' => '121000248',
                'seller_swift_code' => 'WFBIUS6SSFO',
                'port_loading' => 'TAIWAN/ USA',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ]);

            return view('reports.vouchers.po-fortinet', [
                'po' => $purchaseOrder,
                'company' => $company,
                'config' => $config,
                'isPreview' => true,
            ]);
        } else {
            $config = $poConfig ?: new \App\Models\SupplierPoConfig([
                'template_type' => 'sale_contract',
                'seller_name' => $purchaseOrder->supplier->name,
                'port_loading' => 'TAIWAN/ USA',
                'port_discharge' => 'HOCHIMINH CITY, VIETNAM',
            ]);

            return view('reports.vouchers.po-sale-contract', [
                'po' => $purchaseOrder,
                'company' => $company,
                'config' => $config,
                'isPreview' => true,
            ]);
        }
    }

    public function generateCodeApi(Request $request)
    {
        $supplierId = $request->get('supplier_id');
        $supplierName = null;
        if ($supplierId) {
            $supplier = Supplier::find($supplierId);
            $supplierName = $supplier ? $supplier->name : null;
        }
        $code = PurchaseOrder::generateCode($supplierName);
        return response()->json(['code' => $code]);
    }

    /**
     * Kiểm tra mã PO đã tồn tại hay chưa (dùng cho trang tạo / cập nhật PO).
     */
    public function checkCodeApi(Request $request)
    {
        $code = trim((string) $request->get('code'));
        if ($code === '') {
            return response()->json(['exists' => false]);
        }

        $existing = PurchaseOrder::where('code', $code)
            ->when($request->filled('ignore_id'), fn ($q) => $q->where('id', '!=', (int) $request->get('ignore_id')))
            ->first(['id', 'code', 'status']);

        return response()->json([
            'exists' => (bool) $existing,
            'purchase_order' => $existing ? [
                'id' => $existing->id,
                'code' => $existing->code,
                'status_label' => $existing->status_label,
                'url' => route('purchase-orders.show', $existing->id),
            ] : null,
        ]);
    }

    public function importSerials(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $request->validate([
            'serial_file' => 'required|file|mimes:xlsx,xls,csv,txt'
        ]);

        try {
            $data = \Maatwebsite\Excel\Facades\Excel::toArray(new class {}, $request->file('serial_file'));

            if (empty($data) || empty($data[0])) {
                return back()->with('error', 'File Excel không có dữ liệu.');
            }

            $rows = $data[0];
            if (count($rows) < 2) {
                return back()->with('error', 'File Excel chỉ có tiêu đề hoặc trống.');
            }

            [$partNumberIdx, $serialNumberIdx, $poNumberIdx] = $this->findExcelHeaderIndexes($rows[0]);

            $serialsByPart = [];
            $poCodeUpper = strtoupper(trim($purchaseOrder->code));
            $poCodeNorm = preg_replace('/[^A-Z0-9]/i', '', $poCodeUpper);
            
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty($row) || !isset($row[$partNumberIdx])) continue;

                $partNumber = strtoupper(trim((string)$row[$partNumberIdx]));
                $serialNumber = isset($row[$serialNumberIdx]) ? trim((string)$row[$serialNumberIdx]) : '';
                $poNumber = isset($row[$poNumberIdx]) ? strtoupper(trim((string)$row[$poNumberIdx])) : '';

                if (!$partNumber || !$serialNumber) continue;

                if ($poNumber) {
                    $rowPoNorm = preg_replace('/[^A-Z0-9]/i', '', $poNumber);
                    if ($rowPoNorm !== '' && $poCodeNorm !== '' && $rowPoNorm !== $poCodeNorm && !str_contains($rowPoNorm, $poCodeNorm) && !str_contains($poCodeNorm, $rowPoNorm)) {
                        continue;
                    }
                }

                if (!isset($serialsByPart[$partNumber])) {
                    $serialsByPart[$partNumber] = [];
                }
                $serialsByPart[$partNumber][] = $serialNumber;
            }

            if (empty($serialsByPart)) {
                return back()->with('error', 'Không tìm thấy dòng dữ liệu nào khớp với mã PO hiện tại (' . $purchaseOrder->code . ').');
            }

            $purchaseOrder->load(['items.product', 'items.saleOrderRequestItem']);

            DB::beginTransaction();
            $updatedCount = 0;

            $partsWithExactLine = $this->partsWithExactPoLine($purchaseOrder->items, $serialsByPart);

            foreach ($purchaseOrder->items as $item) {
                $newExcelSerials = $this->getMatchingSerialsForPoItem($item, $serialsByPart, $matchedParts, false, $partsWithExactLine);

                if (!empty($newExcelSerials)) {
                    // Keep any existing serial numbers that are already received/transferred/sold in product_items table
                    $existingSerials = json_decode($item->serial_number, true) ?: [];
                    $alreadyImportedSerials = [];
                    if (!empty($existingSerials)) {
                        $alreadyImportedSerials = \App\Models\ProductItem::whereIn('sku', $existingSerials)
                            ->pluck('sku')
                            ->toArray();
                    }

                    $mergedSerials = array_values(array_unique(array_merge($alreadyImportedSerials, $newExcelSerials)));

                    // Find all imports created from this PO
                    $importIds = \App\Models\Import::where('reference_type', 'purchase_order')
                        ->where('reference_id', $purchaseOrder->id)
                        ->pluck('id')
                        ->toArray();

                    // Update existing ProductItem records in the warehouse if they have placeholder SKUs
                    if (!empty($importIds)) {
                        $productItems = \App\Models\ProductItem::whereIn('import_id', $importIds)
                            ->where('product_id', $item->product_id)
                            ->get();

                        // Get list of existing real SKUs
                        $existingRealSkus = $productItems->filter(function($pi) {
                            return !str_starts_with($pi->sku, \App\Models\ProductItem::NO_SKU_PREFIX)
                                && !str_starts_with($pi->sku, \App\Models\ProductItem::OLD_NO_SKU_PREFIX)
                                && !empty($pi->sku);
                        })->pluck('sku')->toArray();

                        // Find which new serials are not already assigned in the warehouse
                        $serialsToAssign = array_diff($newExcelSerials, $existingRealSkus);

                        // Find placeholder product items
                        $placeholderItems = $productItems->filter(function($pi) {
                            return str_starts_with($pi->sku, \App\Models\ProductItem::NO_SKU_PREFIX)
                                || str_starts_with($pi->sku, \App\Models\ProductItem::OLD_NO_SKU_PREFIX)
                                || empty($pi->sku);
                        });

                        // Assign new serials to placeholders
                        foreach ($placeholderItems as $pi) {
                            if (empty($serialsToAssign)) break;
                            $nextSerial = array_shift($serialsToAssign);
                            $pi->update(['sku' => $nextSerial]);
                        }
                    }

                    $item->update([
                        'serial_number' => json_encode($mergedSerials)
                    ]);
                    $updatedCount += count($newExcelSerials);
                }
            }

            if ($updatedCount === 0) {
                DB::rollBack();
                return back()->with('error', 'File Excel có dữ liệu khớp mã PO nhưng không tìm thấy mặt hàng trùng PART NUMBER trong đơn này.');
            }

            $this->purchaseImportSyncService->syncImportSerialsFromPO($purchaseOrder);

            DB::commit();

            return back()->with('success', "Đã import thành công {$updatedCount} số Serial cho đơn hàng.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Import thất bại: ' . $e->getMessage());
        }
    }

    public function downloadImportSerialsTemplate()
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        try {
            $excelImportService = app(\App\Services\ExcelImportService::class);
            $tempFile = $excelImportService->generatePoSerialTemplate();
            
            $filename = 'mau_import_serial_po_' . date('Y-m-d') . '.xlsx';
            
            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi tạo file mẫu: ' . $e->getMessage());
        }
    }

    public function importSerialsBulk(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $request->validate([
            'serial_file' => 'required|file|mimes:xlsx,xls,csv,txt'
        ]);

        try {
            $data = \Maatwebsite\Excel\Facades\Excel::toArray(new class {}, $request->file('serial_file'));

            if (empty($data) || empty($data[0])) {
                return back()->with('error', 'File Excel không có dữ liệu.');
            }

            $rows = $data[0];
            if (count($rows) < 2) {
                return back()->with('error', 'File Excel chỉ có tiêu đề hoặc trống.');
            }

            [$partNumberIdx, $serialNumberIdx, $poNumberIdx] = $this->findExcelHeaderIndexes($rows[0]);

            // Group by [PO NUMBER][PART NUMBER]
            $serialsByPoAndPart = [];
            
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty($row) || !isset($row[$partNumberIdx]) || !isset($row[$poNumberIdx])) continue;

                $partNumber = strtoupper(trim((string)$row[$partNumberIdx]));
                $serialNumber = isset($row[$serialNumberIdx]) ? trim((string)$row[$serialNumberIdx]) : '';
                $poNumber = strtoupper(trim((string)$row[$poNumberIdx]));

                if (!$partNumber || !$serialNumber || !$poNumber) continue;

                if (!isset($serialsByPoAndPart[$poNumber])) {
                    $serialsByPoAndPart[$poNumber] = [];
                }
                if (!isset($serialsByPoAndPart[$poNumber][$partNumber])) {
                    $serialsByPoAndPart[$poNumber][$partNumber] = [];
                }
                $serialsByPoAndPart[$poNumber][$partNumber][] = $serialNumber;
            }

            if (empty($serialsByPoAndPart)) {
                return back()->with('error', 'Không tìm thấy dòng dữ liệu hợp lệ nào trong file Excel.');
            }

            DB::beginTransaction();
            $updatedCount = 0;
            $posUpdated = [];
            $foundExcelPos = [];   // mã PO trong file tìm được PO trên hệ thống
            $matchedPairs = [];    // [mã PO trong file][part] đã khớp với dòng PO
            $notInWarehouse = [];  // PO có serial nhưng dòng PO chưa liên kết sản phẩm danh mục
            $overflow = [];        // serial vượt số lượng dòng PO (dòng đã đủ serial trong kho)

            $poCodes = array_keys($serialsByPoAndPart);
            $purchaseOrders = PurchaseOrder::whereNotIn('status', ['cancelled'])
                ->with(['items.product', 'items.saleOrderRequestItem'])
                ->get()
                ->filter(function($po) use ($serialsByPoAndPart, $poCodes) {
                    $poNorm = preg_replace('/[^A-Z0-9]/i', '', strtoupper($po->code));
                    foreach ($poCodes as $excelPo) {
                        $excelNorm = preg_replace('/[^A-Z0-9]/i', '', strtoupper($excelPo));
                        if ($poNorm === $excelNorm || str_contains($poNorm, $excelNorm) || str_contains($excelNorm, $poNorm)) {
                            return true;
                        }
                    }
                    return false;
                });

            foreach ($purchaseOrders as $po) {
                $poCodeUpper = strtoupper($po->code);
                $poCodeNorm = preg_replace('/[^A-Z0-9]/i', '', $poCodeUpper);
                
                // Find matching serials by part for this PO
                $partsForPo = [];
                $partSources = []; // part => [mã PO trong file] để biết dòng nào đã khớp
                foreach ($serialsByPoAndPart as $excelPo => $parts) {
                    $excelPoNorm = preg_replace('/[^A-Z0-9]/i', '', strtoupper($excelPo));
                    if ($poCodeNorm === $excelPoNorm || str_contains($poCodeNorm, $excelPoNorm) || str_contains($excelPoNorm, $poCodeNorm)) {
                        $foundExcelPos[$excelPo] = true;
                        foreach ($parts as $pNum => $sList) {
                            if (!isset($partsForPo[$pNum])) {
                                $partsForPo[$pNum] = [];
                            }
                            $partsForPo[$pNum] = array_merge($partsForPo[$pNum], $sList);
                            $partSources[$pNum][] = $excelPo;
                        }
                    }
                }

                if (empty($partsForPo)) continue;

                $partsWithExactLine = $this->partsWithExactPoLine($po->items, $partsForPo);

                foreach ($po->items as $item) {
                    $newExcelSerials = $this->getMatchingSerialsForPoItem($item, $partsForPo, $matchedParts, false, $partsWithExactLine);
                    foreach ($matchedParts as $pNum) {
                        foreach ($partSources[$pNum] ?? [] as $excelPo) {
                            $matchedPairs[$excelPo][$pNum] = true;
                        }
                    }
                    if (!empty($newExcelSerials) && !$item->product_id) {
                        $notInWarehouse[$po->code] = true;
                    }

                    if (!empty($newExcelSerials)) {
                        // Keep any existing serial numbers that are already received/transferred/sold in product_items table
                        $existingSerials = json_decode($item->serial_number, true) ?: [];
                        $alreadyImportedSerials = [];
                        if (!empty($existingSerials)) {
                            $alreadyImportedSerials = \App\Models\ProductItem::whereIn('sku', $existingSerials)
                                ->pluck('sku')
                                ->toArray();
                        }

                        $mergedSerials = array_values(array_unique(array_merge($alreadyImportedSerials, $newExcelSerials)));

                        // Serial đã nhập kho không bị thay thế: chỉ nhận thêm serial mới trong phạm vi số
                        // lượng của dòng PO, phần vượt báo lại để không ghi thừa serial vào PO.
                        $capacity = max(0, (int) $item->quantity - count($alreadyImportedSerials));
                        $freshSerials = array_values(array_diff($mergedSerials, $alreadyImportedSerials));
                        $rejectedSerials = array_slice($freshSerials, $capacity);
                        if (!empty($rejectedSerials)) {
                            $overflow[] = "{$po->code} / " . ($item->product->code ?? $item->product_name) . ': đã có ' . count($alreadyImportedSerials)
                                . '/' . (int) $item->quantity . ' serial trong kho, không nhận ' . implode(', ', $rejectedSerials);
                            $mergedSerials = array_values(array_merge($alreadyImportedSerials, array_slice($freshSerials, 0, $capacity)));
                            $newExcelSerials = array_values(array_diff($newExcelSerials, $rejectedSerials));
                        }

                        // Find all imports created from this PO
                        $importIds = \App\Models\Import::where('reference_type', 'purchase_order')
                            ->where('reference_id', $po->id)
                            ->pluck('id')
                            ->toArray();

                        // Update existing ProductItem records in the warehouse if they have placeholder SKUs
                        if (!empty($importIds)) {
                            $productItems = \App\Models\ProductItem::whereIn('import_id', $importIds)
                                ->where('product_id', $item->product_id)
                                ->get();

                            // Get list of existing real SKUs
                            $existingRealSkus = $productItems->filter(function($pi) {
                                return !str_starts_with($pi->sku, \App\Models\ProductItem::NO_SKU_PREFIX)
                                    && !str_starts_with($pi->sku, \App\Models\ProductItem::OLD_NO_SKU_PREFIX)
                                    && !empty($pi->sku);
                            })->pluck('sku')->toArray();

                            // Find which new serials are not already assigned in the warehouse
                            $serialsToAssign = array_diff($newExcelSerials, $existingRealSkus);

                            // Find placeholder product items
                            $placeholderItems = $productItems->filter(function($pi) {
                                return str_starts_with($pi->sku, \App\Models\ProductItem::NO_SKU_PREFIX)
                                    || str_starts_with($pi->sku, \App\Models\ProductItem::OLD_NO_SKU_PREFIX)
                                    || empty($pi->sku);
                            });

                            // Assign new serials to placeholders
                            foreach ($placeholderItems as $pi) {
                                if (empty($serialsToAssign)) break;
                                $nextSerial = array_shift($serialsToAssign);
                                $pi->update(['sku' => $nextSerial]);
                            }
                        }

                        $item->update([
                            'serial_number' => json_encode($mergedSerials)
                        ]);
                        $updatedCount += count($newExcelSerials);
                        if (!empty($newExcelSerials)) {
                            $posUpdated[$po->code] = true;
                        }
                    }
                }
            }

            foreach ($purchaseOrders as $po) {
                $this->purchaseImportSyncService->syncImportSerialsFromPO($po);
            }

            DB::commit();

            // Liệt kê các dòng trong file không import được để người dùng biết và sửa
            $skipped = [];
            foreach ($serialsByPoAndPart as $excelPo => $parts) {
                if (!isset($foundExcelPos[$excelPo])) {
                    $skipped[] = "{$excelPo}: không tìm thấy PO (sai mã PO hoặc PO đã hủy)";
                    continue;
                }
                foreach ($parts as $pNum => $sList) {
                    if (!isset($matchedPairs[$excelPo][$pNum])) {
                        $skipped[] = "{$excelPo} / {$pNum}: PO không có mặt hàng mã này (" . count($sList) . " serial)";
                    }
                }
            }
            $skippedMsg = '';
            if (!empty($skipped)) {
                $skippedMsg = ' Không import được ' . count($skipped) . ' dòng: ' . e(implode('; ', array_slice($skipped, 0, 15)))
                    . (count($skipped) > 15 ? '; ... và ' . (count($skipped) - 15) . ' dòng khác' : '') . '.';
            }
            if (!empty($overflow)) {
                $skipped = array_merge($skipped, $overflow);
                $skippedMsg .= ' Serial đã nhập kho không thay thế được bằng import: ' . e(implode('; ', array_slice($overflow, 0, 10)))
                    . (count($overflow) > 10 ? '; ...' : '') . '.';
            }
            if (!empty($notInWarehouse)) {
                $skippedMsg .= ' Lưu ý: PO ' . e(implode(', ', array_keys($notInWarehouse)))
                    . ' đã lưu serial nhưng dòng PO chưa liên kết sản phẩm trong danh mục nên chưa gắn được vào kho.';
            }

            $poCount = count($posUpdated);
            if ($updatedCount === 0) {
                return back()->with('error', 'Không tìm thấy đơn hàng PO hoặc mặt hàng phù hợp với file Excel.' . $skippedMsg);
            }

            return back()->with(empty($skipped) ? 'success' : 'warning', "Đã import thành công {$updatedCount} số Serial cho {$poCount} đơn hàng PO." . $skippedMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Import thất bại: ' . $e->getMessage());
        }
    }

    /**
     * Helper to find column indexes from Excel header row flexibly
     */
    protected function findExcelHeaderIndexes(array $headers): array
    {
        $partIdx = false;
        $serialIdx = false;
        $poIdx = false;

        foreach ($headers as $idx => $h) {
            $hClean = strtoupper(trim(preg_replace('/[\x00-\x1F\x7F\xEF\xBB\xBF\xC2\xA0]/u', '', (string)$h)));

            if ($partIdx === false && (str_contains($hClean, 'PART') || str_contains($hClean, 'MODEL') || str_contains($hClean, 'MÃ SP') || str_contains($hClean, 'PRODUCT'))) {
                $partIdx = $idx;
            }
            if ($serialIdx === false && (str_contains($hClean, 'SERIAL') || str_contains($hClean, 'S/N') || str_contains($hClean, 'SN'))) {
                $serialIdx = $idx;
            }
            if ($poIdx === false && (str_contains($hClean, 'PO') || str_contains($hClean, 'PURCHASE') || str_contains($hClean, 'ĐƠN HÀNG'))) {
                $poIdx = $idx;
            }
        }

        return [
            $partIdx !== false ? $partIdx : 0,
            $serialIdx !== false ? $serialIdx : 1,
            $poIdx !== false ? $poIdx : 2,
        ];
    }

    /**
     * Helper to match serials for a PO Item using multiple candidate product identifiers
     */
    protected function getMatchingSerialsForPoItem($item, array $serialsByPart, ?array &$matchedParts = null, bool $exactOnly = false, array $partsWithExactLine = []): array
    {
        $matchedParts = [];
        $candidates = [];
        if ($item->product && !empty($item->product->code)) {
            $candidates[] = (string)$item->product->code;
        }
        if (!empty($item->product_name)) {
            $candidates[] = (string)$item->product_name;
        }
        if ($item->saleOrderRequestItem && !empty($item->saleOrderRequestItem->part_number)) {
            $candidates[] = (string)$item->saleOrderRequestItem->part_number;
        }

        $allMatchingSerials = [];

        foreach ($serialsByPart as $excelPart => $serials) {
            $excelUpper = strtoupper(trim((string)$excelPart));
            $excelNorm = preg_replace('/[^A-Z0-9]/i', '', $excelUpper);
            if (empty($excelNorm)) continue;

            $matched = false;
            foreach ($candidates as $cand) {
                $candUpper = strtoupper(trim($cand));
                $candNorm = preg_replace('/[^A-Z0-9]/i', '', $candUpper);
                if (empty($candNorm)) continue;

                if ($candUpper === $excelUpper || $candNorm === $excelNorm) {
                    $matched = true;
                    break;
                }

                // Mã bảng giá Fortinet dạng "...-DD" (DD = số tháng) khớp với mã thực tế -12/-24/-36/-48/-60
                if (str_ends_with($candNorm, 'DD') && strlen($candNorm) > 4
                    && preg_match('/^' . preg_quote(substr($candNorm, 0, -2), '/') . '(12|24|36|48|60)$/', $excelNorm)) {
                    $matched = true;
                    break;
                }

                // Khớp gần đúng (mã này chứa mã kia) chỉ khi PO không có dòng nào khớp chính xác mã trong
                // file — tránh gán cùng serial cho cả dòng "FG-60F" lẫn "FG-60F-BDL-950-12" của một PO.
                if (!$exactOnly && !in_array($excelPart, $partsWithExactLine, true)
                    && strlen($candNorm) >= 4 && strlen($excelNorm) >= 4) {
                    if (str_contains($candNorm, $excelNorm) || str_contains($excelNorm, $candNorm)) {
                        $matched = true;
                        break;
                    }
                }
            }

            if ($matched) {
                $allMatchingSerials = array_merge($allMatchingSerials, $serials);
                $matchedParts[] = $excelPart;
            }
        }

        return array_values(array_unique($allMatchingSerials));
    }

    /**
     * Các mã hàng trong file có ít nhất một dòng PO khớp chính xác (kể cả mã -DD).
     */
    protected function partsWithExactPoLine($items, array $serialsByPart): array
    {
        $parts = [];
        foreach ($items as $item) {
            $this->getMatchingSerialsForPoItem($item, $serialsByPart, $exactMatched, true);
            $parts = array_merge($parts, $exactMatched);
        }

        return array_values(array_unique($parts));
    }

    /**
     * Update serial numbers manually for a PO item.
     */
    public function updateItemSerialsManual(Request $request, PurchaseOrderItem $item)
    {
        $purchaseOrder = $item->purchaseOrder;
        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Không thể chỉnh sửa đơn hàng đã hoàn thành hoặc đã hủy.');
        }

        $request->validate([
            'serials' => 'nullable|string',
        ]);

        // Parse serials (comma separated or newline separated)
        $rawSerials = $request->input('serials', '');
        $lines = preg_split('/[\n,\r]+/', $rawSerials);
        $newSerials = [];
        foreach ($lines as $line) {
            $serial = trim($line);
            if (!empty($serial)) {
                $newSerials[] = $serial;
            }
        }

        // Keep any existing serial numbers that are already received/transferred/sold in product_items table
        $existingSerials = json_decode($item->serial_number, true) ?: [];
        $alreadyImportedSerials = [];
        if (!empty($existingSerials)) {
            $alreadyImportedSerials = \App\Models\ProductItem::whereIn('sku', $existingSerials)
                ->pluck('sku')
                ->toArray();
        }

        $mergedSerials = array_values(array_unique(array_merge($alreadyImportedSerials, $newSerials)));

        DB::beginTransaction();
        try {
            // Find all imports created from this PO
            $importIds = \App\Models\Import::where('reference_type', 'purchase_order')
                ->where('reference_id', $purchaseOrder->id)
                ->pluck('id')
                ->toArray();

            // Update existing ProductItem records in the warehouse if they have placeholder SKUs
            if (!empty($importIds)) {
                $productItems = \App\Models\ProductItem::whereIn('import_id', $importIds)
                    ->where('product_id', $item->product_id)
                    ->get();

                // Get list of existing real SKUs
                $existingRealSkus = $productItems->filter(function($pi) {
                    return !str_starts_with($pi->sku, \App\Models\ProductItem::NO_SKU_PREFIX)
                        && !str_starts_with($pi->sku, \App\Models\ProductItem::OLD_NO_SKU_PREFIX)
                        && !empty($pi->sku);
                })->pluck('sku')->toArray();

                // Find which new serials are not already assigned in the warehouse
                $serialsToAssign = array_diff($mergedSerials, $existingRealSkus);

                // Find placeholder product items
                $placeholderItems = $productItems->filter(function($pi) {
                    return str_starts_with($pi->sku, \App\Models\ProductItem::NO_SKU_PREFIX)
                        || str_starts_with($pi->sku, \App\Models\ProductItem::OLD_NO_SKU_PREFIX)
                        || empty($pi->sku);
                });

                // Assign new serials to placeholders
                foreach ($placeholderItems as $pi) {
                    if (empty($serialsToAssign)) break;
                    $nextSerial = array_shift($serialsToAssign);
                    $pi->update(['sku' => $nextSerial]);
                }
            }

            $item->update([
                'serial_number' => json_encode($mergedSerials)
            ]);

            $this->purchaseImportSyncService->syncImportSerialsFromPO($purchaseOrder);

            DB::commit();
            return back()->with('success', 'Cập nhật danh sách Serial thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Đồng nhất múi giờ và format ngày tháng trước khi lưu vào Database
     */
    private function parseDate(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            // Parse chuỗi ngày và chuyển đổi sang múi giờ 'Asia/Ho_Chi_Minh'
            return \Carbon\Carbon::parse($dateString)
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('Y-m-d');
        } catch (\Exception $e) {
            \Log::warning('Không thể parse ngày: ' . $dateString . '. Lỗi: ' . $e->getMessage());
            return null;
        }
    }
}
