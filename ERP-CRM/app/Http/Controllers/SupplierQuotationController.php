<?php

namespace App\Http\Controllers;

use App\Models\SupplierQuotation;
use App\Models\SupplierQuotationItem;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierQuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierQuotation::with(['supplier', 'purchaseRequest', 'items']);

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

        $quotations = $query->orderBy('created_at', 'desc')->paginate(15);
        $suppliers = Supplier::orderBy('name')->get();
        $purchaseRequests = PurchaseRequest::whereIn('status', ['sent', 'received'])->get();

        return view('supplier-quotations.index', compact('quotations', 'suppliers', 'purchaseRequests'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $purchaseRequests = PurchaseRequest::whereIn('status', ['sent', 'received'])->with('items')->get();
        $code = SupplierQuotation::generateCode();

        $selectedRequest = null;
        if ($request->filled('purchase_request_id')) {
            $selectedRequest = PurchaseRequest::with('items')->find($request->purchase_request_id);
        }

        return view('supplier-quotations.create', compact('suppliers', 'products', 'purchaseRequests', 'code', 'selectedRequest'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:supplier_quotations,code',
            'supplier_id' => 'required|exists:suppliers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after:quotation_date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $quotation = SupplierQuotation::create([
                'code' => $validated['code'],
                'purchase_request_id' => $request->purchase_request_id,
                'supplier_id' => $validated['supplier_id'],
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'],
                'delivery_days' => $request->delivery_days,
                'payment_terms' => $request->payment_terms,
                'warranty' => $request->warranty,
                'discount_percent' => $request->discount_percent ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'vat_percent' => $request->vat_percent ?? 10,
                'note' => $request->note,
                'status' => 'pending',
            ]);

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $total = $item['quantity'] * $item['unit_price'];
                $subtotal += $total;
                
                $quotation->items()->create([
                    'product_name' => $item['product_name'],
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Cái',
                    'unit_price' => $item['unit_price'],
                    'total' => $total,
                    'note' => $item['note'] ?? null,
                ]);
            }

            // Tính tổng
            $discountAmount = $subtotal * ($quotation->discount_percent / 100);
            $afterDiscount = $subtotal - $discountAmount;
            $beforeVat = $afterDiscount + $quotation->shipping_cost;
            $vatAmount = $beforeVat * ($quotation->vat_percent / 100);

            $quotation->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total' => $beforeVat + $vatAmount,
            ]);

            // Cập nhật trạng thái yêu cầu báo giá
            if ($request->purchase_request_id) {
                $purchaseRequest = PurchaseRequest::find($request->purchase_request_id);
                if ($purchaseRequest && $purchaseRequest->status === 'sent') {
                    $purchaseRequest->update(['status' => 'received']);
                }
            }

            DB::commit();
            return redirect()->route('supplier-quotations.index')
                ->with('success', 'Đã nhập báo giá từ NCC thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }


    public function show(SupplierQuotation $supplierQuotation)
    {
        $supplierQuotation->load(['supplier', 'purchaseRequest', 'items.product']);
        
        // Lấy các báo giá khác cùng yêu cầu để so sánh
        $compareQuotations = [];
        if ($supplierQuotation->purchase_request_id) {
            $compareQuotations = SupplierQuotation::where('purchase_request_id', $supplierQuotation->purchase_request_id)
                ->where('id', '!=', $supplierQuotation->id)
                ->with('supplier')
                ->get();
        }

        return view('supplier-quotations.show', compact('supplierQuotation', 'compareQuotations'));
    }

    public function edit(SupplierQuotation $supplierQuotation)
    {
        if ($supplierQuotation->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể sửa báo giá đang chờ xử lý!');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $supplierQuotation->load(['items']);

        return view('supplier-quotations.edit', compact('supplierQuotation', 'suppliers', 'products'));
    }

    public function update(Request $request, SupplierQuotation $supplierQuotation)
    {
        if ($supplierQuotation->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể sửa báo giá đang chờ xử lý!');
        }

        $validated = $request->validate([
            'valid_until' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $supplierQuotation->update([
                'valid_until' => $validated['valid_until'],
                'delivery_days' => $request->delivery_days,
                'payment_terms' => $request->payment_terms,
                'warranty' => $request->warranty,
                'discount_percent' => $request->discount_percent ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'vat_percent' => $request->vat_percent ?? 10,
                'note' => $request->note,
            ]);

            $supplierQuotation->items()->delete();
            $subtotal = 0;

            foreach ($validated['items'] as $item) {
                $total = $item['quantity'] * $item['unit_price'];
                $subtotal += $total;
                
                $supplierQuotation->items()->create([
                    'product_name' => $item['product_name'],
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Cái',
                    'unit_price' => $item['unit_price'],
                    'total' => $total,
                ]);
            }

            $discountAmount = $subtotal * ($supplierQuotation->discount_percent / 100);
            $afterDiscount = $subtotal - $discountAmount;
            $beforeVat = $afterDiscount + $supplierQuotation->shipping_cost;
            $vatAmount = $beforeVat * ($supplierQuotation->vat_percent / 100);

            $supplierQuotation->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total' => $beforeVat + $vatAmount,
            ]);

            DB::commit();
            return redirect()->route('supplier-quotations.index')
                ->with('success', 'Đã cập nhật báo giá!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(SupplierQuotation $supplierQuotation)
    {
        if ($supplierQuotation->status === 'selected') {
            return back()->with('error', 'Không thể xóa báo giá đã được chọn!');
        }

        $supplierQuotation->delete();
        return redirect()->route('supplier-quotations.index')
            ->with('success', 'Đã xóa báo giá!');
    }

    public function select(SupplierQuotation $supplierQuotation)
    {
        if ($supplierQuotation->status !== 'pending') {
            return back()->with('error', 'Báo giá này không thể chọn!');
        }

        DB::beginTransaction();
        try {
            // Từ chối các báo giá khác cùng yêu cầu
            if ($supplierQuotation->purchase_request_id) {
                SupplierQuotation::where('purchase_request_id', $supplierQuotation->purchase_request_id)
                    ->where('id', '!=', $supplierQuotation->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'rejected']);
            }

            $supplierQuotation->update(['status' => 'selected']);

            DB::commit();
            return redirect()->route('purchase-orders.create', ['quotation_id' => $supplierQuotation->id])
                ->with('success', 'Đã chọn báo giá! Tiếp tục tạo đơn mua hàng.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function reject(SupplierQuotation $supplierQuotation)
    {
        if ($supplierQuotation->status !== 'pending') {
            return back()->with('error', 'Báo giá này không thể từ chối!');
        }

        $supplierQuotation->update(['status' => 'rejected']);
        return back()->with('success', 'Đã từ chối báo giá!');
    }

    public function compare(Request $request)
    {
        $ids = $request->input('ids', []);
        if (count($ids) < 2) {
            return back()->with('error', 'Vui lòng chọn ít nhất 2 báo giá để so sánh!');
        }

        $quotations = SupplierQuotation::whereIn('id', $ids)
            ->with(['supplier', 'items'])
            ->get();

        return view('supplier-quotations.compare', compact('quotations'));
    }
}
