<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PurchaseRequestMail;

class PurchaseRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseRequest::with(['suppliers', 'items', 'quotations']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchase-requests.index', compact('requests', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $code = PurchaseRequest::generateCode();

        return view('purchase-requests.create', compact('suppliers', 'products', 'code'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:purchase_requests,code',
            'title' => 'required|string|max:255',
            'deadline' => 'required|date|after:today',
            'priority' => 'required|in:normal,high,urgent',
            'suppliers' => 'required|array|min:1',
            'suppliers.*' => 'exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string',
            'items.*.specifications' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $status = $request->has('send') ? 'sent' : 'draft';
            $sentAt = $request->has('send') ? now() : null;

            $purchaseRequest = PurchaseRequest::create([
                'code' => $validated['code'],
                'title' => $validated['title'],
                'deadline' => $validated['deadline'],
                'priority' => $validated['priority'],
                'requirements' => $request->requirements,
                'note' => $request->note,
                'status' => $status,
                'sent_at' => $sentAt,
            ]);

            $purchaseRequest->suppliers()->attach($validated['suppliers']);

            foreach ($validated['items'] as $item) {
                $purchaseRequest->items()->create([
                    'product_name' => $item['product_name'],
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Cái',
                    'specifications' => $item['specifications'] ?? null,
                ]);
            }

            DB::commit();

            // Gửi email nếu trạng thái là sent
            if ($status === 'sent') {
                $this->sendEmailsToSuppliers($purchaseRequest);
            }

            $message = $status === 'sent'
                ? 'Đã tạo và gửi yêu cầu báo giá cho nhà cung cấp!'
                : 'Đã lưu nháp yêu cầu báo giá!';

            return redirect()->route('purchase-requests.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }


    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['suppliers', 'items.product', 'quotations.supplier', 'creator']);
        return view('purchase-requests.show', compact('purchaseRequest'));
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        if (!in_array($purchaseRequest->status, ['draft'])) {
            return back()->with('error', 'Chỉ có thể sửa yêu cầu ở trạng thái Nháp!');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $purchaseRequest->load(['suppliers', 'items']);

        return view('purchase-requests.edit', compact('purchaseRequest', 'suppliers', 'products'));
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        if (!in_array($purchaseRequest->status, ['draft'])) {
            return back()->with('error', 'Chỉ có thể sửa yêu cầu ở trạng thái Nháp!');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'deadline' => 'required|date',
            'priority' => 'required|in:normal,high,urgent',
            'suppliers' => 'required|array|min:1',
            'suppliers.*' => 'exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $purchaseRequest->update([
                'title' => $validated['title'],
                'deadline' => $validated['deadline'],
                'priority' => $validated['priority'],
                'requirements' => $request->requirements,
                'note' => $request->note,
            ]);

            $purchaseRequest->suppliers()->sync($validated['suppliers']);
            $purchaseRequest->items()->delete();

            foreach ($validated['items'] as $item) {
                $purchaseRequest->items()->create([
                    'product_name' => $item['product_name'],
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Cái',
                    'specifications' => $item['specifications'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('purchase-requests.index')
                ->with('success', 'Đã cập nhật yêu cầu báo giá!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        if (!in_array($purchaseRequest->status, ['draft', 'cancelled'])) {
            return back()->with('error', 'Không thể xóa yêu cầu đã gửi!');
        }

        $purchaseRequest->delete();
        return redirect()->route('purchase-requests.index')
            ->with('success', 'Đã xóa yêu cầu báo giá!');
    }

    public function send(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'draft') {
            return back()->with('error', 'Yêu cầu đã được gửi trước đó!');
        }

        $purchaseRequest->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // Gửi email cho các NCC
        $this->sendEmailsToSuppliers($purchaseRequest);

        $supplierNames = $purchaseRequest->suppliers->pluck('name')->join(', ');

        return back()->with('success', "Đã gửi yêu cầu báo giá đến: {$supplierNames}");
    }

    public function cancel(PurchaseRequest $purchaseRequest)
    {
        if (in_array($purchaseRequest->status, ['converted', 'cancelled'])) {
            return back()->with('error', 'Không thể hủy yêu cầu này!');
        }

        $purchaseRequest->update(['status' => 'cancelled']);
        return back()->with('success', 'Đã hủy yêu cầu báo giá!');
    }

    /**
     * Gửi email đến tất cả nhà cung cấp
     */
    private function sendEmailsToSuppliers(PurchaseRequest $purchaseRequest): void
    {
        $purchaseRequest->load(['items', 'suppliers']);

        $sentCount = 0;
        $failedSuppliers = [];

        foreach ($purchaseRequest->suppliers as $supplier) {
            if (empty($supplier->email)) {
                $failedSuppliers[] = $supplier->name . ' (không có email)';
                continue;
            }

            try {
                Mail::to($supplier->email)->send(new PurchaseRequestMail($purchaseRequest, $supplier));
                $sentCount++;
                Log::info("Đã gửi yêu cầu báo giá {$purchaseRequest->code} đến {$supplier->email}");
            } catch (\Exception $e) {
                $failedSuppliers[] = $supplier->name . ' (' . $e->getMessage() . ')';
                Log::error("Lỗi gửi email yêu cầu báo giá đến {$supplier->email}: " . $e->getMessage());
            }
        }

        if (!empty($failedSuppliers)) {
            session()->flash('warning', 'Không thể gửi email đến: ' . implode(', ', $failedSuppliers));
        }
    }
}
