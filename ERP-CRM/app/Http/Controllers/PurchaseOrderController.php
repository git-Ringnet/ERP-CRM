<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierQuotation;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Warehouse;
use App\Mail\PurchaseOrderMail;
use App\Exports\PurchaseOrdersExport;
use App\Services\PurchaseImportSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseOrderController extends Controller
{
    protected PurchaseImportSyncService $purchaseImportSyncService;

    public function __construct(PurchaseImportSyncService $purchaseImportSyncService)
    {
        $this->purchaseImportSyncService = $purchaseImportSyncService;
    }
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'items', 'supplierQuotation']);

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

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);
        $suppliers = Supplier::orderBy('name')->get();

        // Thống kê
        $stats = [
            'pending' => PurchaseOrder::where('status', 'pending_approval')->count(),
            'sent' => PurchaseOrder::whereIn('status', ['sent', 'confirmed', 'shipping'])->count(),
            'received' => PurchaseOrder::where('status', 'received')->count(),
            'total_value' => PurchaseOrder::whereIn('status', ['sent', 'confirmed', 'shipping', 'received'])->sum('total'),
        ];

        return view('purchase-orders.index', compact('orders', 'suppliers', 'stats'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $code = PurchaseOrder::generateCode();

        $quotation = null;
        if ($request->filled('quotation_id')) {
            $quotation = SupplierQuotation::with(['supplier', 'items'])->find($request->quotation_id);
        }

        return view('purchase-orders.create', compact('suppliers', 'products', 'code', 'quotation'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:purchase_orders,code',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $order = PurchaseOrder::create([
                'code' => $validated['code'],
                'supplier_id' => $validated['supplier_id'],
                'supplier_quotation_id' => $request->supplier_quotation_id,
                'order_date' => $validated['order_date'],
                'expected_delivery' => $request->expected_delivery,
                'delivery_address' => $request->delivery_address,
                'discount_percent' => $request->discount_percent ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'other_cost' => $request->other_cost ?? 0,
                'vat_percent' => $request->vat_percent ?? 10,
                'payment_terms' => $request->payment_terms ?? 'net30',
                'note' => $request->note,
                'status' => 'draft',
            ]);

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $total = $item['quantity'] * $item['unit_price'];
                $subtotal += $total;

                $order->items()->create([
                    'product_name' => $item['product_name'],
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Cái',
                    'unit_price' => $item['unit_price'],
                    'total' => $total,
                ]);
            }

            // Tính tổng
            $discountAmount = $subtotal * ($order->discount_percent / 100);
            $afterDiscount = $subtotal - $discountAmount;
            $beforeVat = $afterDiscount + $order->shipping_cost + $order->other_cost;
            $vatAmount = $beforeVat * ($order->vat_percent / 100);

            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total' => $beforeVat + $vatAmount,
            ]);

            // Cập nhật trạng thái báo giá NCC
            if ($request->supplier_quotation_id) {
                $quotation = SupplierQuotation::find($request->supplier_quotation_id);
                if ($quotation && $quotation->purchase_request_id) {
                    PurchaseRequest::where('id', $quotation->purchase_request_id)
                        ->update(['status' => 'converted']);
                }
            }

            DB::commit();
            return redirect()->route('purchase-orders.index')
                ->with('success', 'Đã tạo đơn mua hàng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }


    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.product', 'supplierQuotation', 'creator', 'approver']);
        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['draft', 'pending_approval'])) {
            return back()->with('error', 'Không thể sửa đơn hàng đã được duyệt!');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $purchaseOrder->load(['items']);

        return view('purchase-orders.edit', compact('purchaseOrder', 'suppliers', 'products'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['draft', 'pending_approval'])) {
            return back()->with('error', 'Không thể sửa đơn hàng đã được duyệt!');
        }

        $validated = $request->validate([
            'expected_delivery' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $purchaseOrder->update([
                'expected_delivery' => $request->expected_delivery,
                'delivery_address' => $request->delivery_address,
                'discount_percent' => $request->discount_percent ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'other_cost' => $request->other_cost ?? 0,
                'vat_percent' => $request->vat_percent ?? 10,
                'payment_terms' => $request->payment_terms ?? 'net30',
                'note' => $request->note,
            ]);

            $purchaseOrder->items()->delete();
            $subtotal = 0;

            foreach ($validated['items'] as $item) {
                $total = $item['quantity'] * $item['unit_price'];
                $subtotal += $total;

                $purchaseOrder->items()->create([
                    'product_name' => $item['product_name'],
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Cái',
                    'unit_price' => $item['unit_price'],
                    'total' => $total,
                ]);
            }

            $discountAmount = $subtotal * ($purchaseOrder->discount_percent / 100);
            $afterDiscount = $subtotal - $discountAmount;
            $beforeVat = $afterDiscount + $purchaseOrder->shipping_cost + $purchaseOrder->other_cost;
            $vatAmount = $beforeVat * ($purchaseOrder->vat_percent / 100);

            $purchaseOrder->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total' => $beforeVat + $vatAmount,
            ]);

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
        if (!in_array($purchaseOrder->status, ['draft', 'cancelled'])) {
            return back()->with('error', 'Không thể xóa đơn hàng đã xử lý!');
        }

        $purchaseOrder->delete();
        return redirect()->route('purchase-orders.index')
            ->with('success', 'Đã xóa đơn mua hàng!');
    }

    public function submitApproval(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Đơn hàng không ở trạng thái nháp!');
        }

        $purchaseOrder->update(['status' => 'pending_approval']);
        return back()->with('success', 'Đã gửi đơn hàng để duyệt!');
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending_approval') {
            return back()->with('error', 'Đơn hàng không ở trạng thái chờ duyệt!');
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Đã duyệt đơn mua hàng!');
    }

    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
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
        if ($purchaseOrder->status !== 'sent') {
            return back()->with('error', 'Đơn hàng chưa được gửi!');
        }

        $purchaseOrder->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Đã xác nhận NCC đã nhận đơn hàng!');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['confirmed', 'shipping', 'partial_received'])) {
            return back()->with('error', 'Đơn hàng chưa sẵn sàng để nhận!');
        }

        $request->validate([
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        DB::beginTransaction();
        try {
            $purchaseOrder->update([
                'status' => 'received',
                'actual_delivery' => now(),
            ]);

            // Cập nhật số lượng đã nhận
            foreach ($purchaseOrder->items as $item) {
                $item->update(['received_quantity' => $item->quantity]);
            }

            // Auto-create import when PO is received
            $purchaseOrder->load(['items', 'supplier']);
            $warehouseId = $request->warehouse_id ?? null;

            try {
                $import = $this->purchaseImportSyncService->createImportFromPO($purchaseOrder, $warehouseId, false);
                if ($import) {
                    DB::commit();
                    return back()->with('success', 'Đã nhận hàng và tạo phiếu nhập kho ' . $import->code . '. Vui lòng duyệt phiếu nhập kho để cập nhật tồn kho.');
                }
            } catch (\Exception $e) {
                Log::warning("Could not create import for PO #{$purchaseOrder->id}: " . $e->getMessage());
            }

            DB::commit();
            return back()->with('success', 'Đã xác nhận nhận hàng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
            return back()->with('error', 'Không thể hủy đơn hàng này!');
        }

        $purchaseOrder->update(['status' => 'cancelled']);
        return back()->with('success', 'Đã hủy đơn mua hàng!');
    }

    public function print(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items']);
        return view('purchase-orders.print', compact('purchaseOrder'));
    }

    /**
     * Export purchase orders to Excel
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search', 'status', 'supplier_id']);
        $filename = 'don-mua-hang-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new PurchaseOrdersExport($filters), $filename);
    }

    /**
     * Get linked import for a PO
     */
    public function getImport(PurchaseOrder $purchaseOrder)
    {
        $import = $this->purchaseImportSyncService->getImport($purchaseOrder);

        if (!$import) {
            return back()->with('error', 'Chưa có phiếu nhập kho liên kết với đơn mua hàng này.');
        }

        return redirect()->route('imports.show', $import);
    }
}
