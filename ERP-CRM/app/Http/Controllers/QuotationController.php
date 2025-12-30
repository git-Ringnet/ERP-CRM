<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalHistory;
use App\Exports\QuotationsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::with('customer');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->filterByStatus($request->status);
        }

        $quotations = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('quotations.index', compact('quotations'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $code = $this->generateCode();

        return view('quotations.create', compact('customers', 'products', 'code'));
    }

    private function generateCode(): string
    {
        $date = date('Ymd');
        $prefix = 'QT-' . $date . '-';

        $last = Quotation::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        $number = $last ? intval(substr($last->code, -4)) + 1 : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:quotations,code'],
            'customer_id' => ['required', 'exists:customers,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:date'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'delivery_time' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::find($validated['customer_id']);

            $subtotal = 0;
            foreach ($validated['products'] as $item) {
                $subtotal += $item['quantity'] * $item['price'];
            }

            $discountAmount = $subtotal * ($validated['discount'] ?? 0) / 100;
            $afterDiscount = $subtotal - $discountAmount;
            $vatAmount = $afterDiscount * ($validated['vat'] ?? 10) / 100;
            $total = $afterDiscount + $vatAmount;

            $quotation = Quotation::create([
                'code' => $validated['code'],
                'customer_id' => $validated['customer_id'],
                'customer_name' => $customer->name,
                'title' => $validated['title'],
                'date' => $validated['date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'vat' => $validated['vat'] ?? 10,
                'total' => $total,
                'payment_terms' => $validated['payment_terms'],
                'delivery_time' => $validated['delivery_time'],
                'note' => $validated['note'],
                'status' => 'draft',
                'current_approval_level' => 0,
                'created_by' => null, // TODO: auth()->id()
            ]);

            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
                ]);
            }

            DB::commit();

            return redirect()->route('quotations.index')
                ->with('success', 'Báo giá đã được tạo thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Quotation $quotation)
    {
        $quotation->load('items', 'customer');
        $approvalHistories = $quotation->approvalHistories();
        $workflow = $quotation->getApprovalWorkflow();

        return view('quotations.show', compact('quotation', 'approvalHistories', 'workflow'));
    }

    public function edit(Quotation $quotation)
    {
        if (!in_array($quotation->status, ['draft', 'rejected'])) {
            return back()->with('error', 'Chỉ có thể sửa báo giá ở trạng thái Nháp hoặc Từ chối.');
        }

        $quotation->load('items');
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('quotations.edit', compact('quotation', 'customers', 'products'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        if (!in_array($quotation->status, ['draft', 'rejected'])) {
            return back()->with('error', 'Chỉ có thể sửa báo giá ở trạng thái Nháp hoặc Từ chối.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('quotations')->ignore($quotation->id)],
            'customer_id' => ['required', 'exists:customers,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:date'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'delivery_time' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::find($validated['customer_id']);

            $subtotal = 0;
            foreach ($validated['products'] as $item) {
                $subtotal += $item['quantity'] * $item['price'];
            }

            $discountAmount = $subtotal * ($validated['discount'] ?? 0) / 100;
            $afterDiscount = $subtotal - $discountAmount;
            $vatAmount = $afterDiscount * ($validated['vat'] ?? 10) / 100;
            $total = $afterDiscount + $vatAmount;

            $quotation->update([
                'code' => $validated['code'],
                'customer_id' => $validated['customer_id'],
                'customer_name' => $customer->name,
                'title' => $validated['title'],
                'date' => $validated['date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'vat' => $validated['vat'] ?? 10,
                'total' => $total,
                'payment_terms' => $validated['payment_terms'],
                'delivery_time' => $validated['delivery_time'],
                'note' => $validated['note'],
                'status' => 'draft',
                'current_approval_level' => 0,
            ]);

            $quotation->items()->delete();

            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
                ]);
            }

            // Clear old approval histories
            ApprovalHistory::where('document_type', 'quotation')
                ->where('document_id', $quotation->id)
                ->delete();

            DB::commit();

            return redirect()->route('quotations.index')
                ->with('success', 'Báo giá đã được cập nhật.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Quotation $quotation)
    {
        if (!in_array($quotation->status, ['draft', 'rejected', 'expired'])) {
            return back()->with('error', 'Không thể xóa báo giá ở trạng thái này.');
        }

        DB::beginTransaction();
        try {
            ApprovalHistory::where('document_type', 'quotation')
                ->where('document_id', $quotation->id)
                ->delete();
            $quotation->items()->delete();
            $quotation->delete();
            DB::commit();

            return redirect()->route('quotations.index')
                ->with('success', 'Báo giá đã được xóa.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Submit quotation for approval
     */
    public function submitForApproval(Quotation $quotation)
    {
        if ($quotation->status !== 'draft') {
            return back()->with('error', 'Chỉ có thể gửi duyệt báo giá ở trạng thái Nháp.');
        }

        $workflow = $quotation->getApprovalWorkflow();
        if (!$workflow || $workflow->levels->isEmpty()) {
            return back()->with('error', 'Chưa cấu hình quy trình duyệt cho báo giá.');
        }

        DB::beginTransaction();
        try {
            $quotation->update([
                'status' => 'pending',
                'current_approval_level' => 0,
            ]);

            // Create pending approval for first level
            $firstLevel = $workflow->levels->first();
            ApprovalHistory::create([
                'document_type' => 'quotation',
                'document_id' => $quotation->id,
                'level' => $firstLevel->level,
                'level_name' => $firstLevel->name,
                'approver_id' => null,
                'approver_name' => $firstLevel->approver_label,
                'action' => 'pending',
            ]);

            DB::commit();

            return back()->with('success', 'Báo giá đã được gửi duyệt.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Approve quotation
     */
    public function approve(Request $request, Quotation $quotation)
    {
        $request->validate([
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        if ($quotation->status !== 'pending') {
            return back()->with('error', 'Báo giá không ở trạng thái chờ duyệt.');
        }

        $workflow = $quotation->getApprovalWorkflow();
        if (!$workflow) {
            return back()->with('error', 'Không tìm thấy quy trình duyệt.');
        }

        $nextLevel = $quotation->getNextApprovalLevel();
        if (!$nextLevel) {
            return back()->with('error', 'Không tìm thấy cấp duyệt tiếp theo.');
        }

        // TODO: Check if current user can approve
        // if (!$nextLevel->canApprove(auth()->user(), $quotation->total)) {
        //     return back()->with('error', 'Bạn không có quyền duyệt báo giá này.');
        // }

        DB::beginTransaction();
        try {
            // Update pending approval to approved
            ApprovalHistory::where('document_type', 'quotation')
                ->where('document_id', $quotation->id)
                ->where('level', $nextLevel->level)
                ->where('action', 'pending')
                ->update([
                    'approver_id' => null, // TODO: auth()->id()
                    'approver_name' => 'Admin', // TODO: auth()->user()->name
                    'action' => 'approved',
                    'comment' => $request->comment,
                    'action_at' => now(),
                ]);

            $quotation->current_approval_level = $nextLevel->level;

            // Check if this is the last level
            $maxLevel = $workflow->max_level;
            if ($nextLevel->level >= $maxLevel) {
                $quotation->status = 'approved';
            } else {
                // Create pending for next level
                $nextNextLevel = $workflow->levels()
                    ->where('level', $nextLevel->level + 1)
                    ->first();

                if ($nextNextLevel) {
                    ApprovalHistory::create([
                        'document_type' => 'quotation',
                        'document_id' => $quotation->id,
                        'level' => $nextNextLevel->level,
                        'level_name' => $nextNextLevel->name,
                        'approver_id' => null,
                        'approver_name' => $nextNextLevel->approver_label,
                        'action' => 'pending',
                    ]);
                }
            }

            $quotation->save();
            DB::commit();

            $message = $quotation->status === 'approved' 
                ? 'Báo giá đã được duyệt hoàn tất.' 
                : 'Đã duyệt cấp ' . $nextLevel->level . '. Chờ duyệt cấp tiếp theo.';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Reject quotation
     */
    public function reject(Request $request, Quotation $quotation)
    {
        $request->validate([
            'comment' => ['required', 'string', 'max:500'],
        ]);

        if ($quotation->status !== 'pending') {
            return back()->with('error', 'Báo giá không ở trạng thái chờ duyệt.');
        }

        $nextLevel = $quotation->getNextApprovalLevel();
        if (!$nextLevel) {
            return back()->with('error', 'Không tìm thấy cấp duyệt.');
        }

        DB::beginTransaction();
        try {
            ApprovalHistory::where('document_type', 'quotation')
                ->where('document_id', $quotation->id)
                ->where('level', $nextLevel->level)
                ->where('action', 'pending')
                ->update([
                    'approver_id' => null, // TODO: auth()->id()
                    'approver_name' => 'Admin',
                    'action' => 'rejected',
                    'comment' => $request->comment,
                    'action_at' => now(),
                ]);

            $quotation->update(['status' => 'rejected']);

            DB::commit();

            return back()->with('success', 'Báo giá đã bị từ chối.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Mark as sent to customer
     */
    public function markAsSent(Quotation $quotation)
    {
        if ($quotation->status !== 'approved') {
            return back()->with('error', 'Chỉ có thể gửi báo giá đã được duyệt.');
        }

        $quotation->update(['status' => 'sent']);

        return back()->with('success', 'Đã đánh dấu báo giá đã gửi khách.');
    }

    /**
     * Customer response
     */
    public function customerResponse(Request $request, Quotation $quotation)
    {
        $request->validate([
            'response' => ['required', 'in:accepted,declined'],
        ]);

        if (!in_array($quotation->status, ['approved', 'sent'])) {
            return back()->with('error', 'Báo giá chưa được duyệt hoặc gửi.');
        }

        $quotation->update(['status' => $request->response]);

        $message = $request->response === 'accepted' 
            ? 'Khách hàng đã chấp nhận báo giá.' 
            : 'Khách hàng đã từ chối báo giá.';

        return back()->with('success', $message);
    }

    /**
     * Convert quotation to sale order
     */
    public function convertToSale(Quotation $quotation)
    {
        if (!$quotation->canConvertToSale()) {
            return back()->with('error', 'Không thể chuyển báo giá này thành đơn hàng.');
        }

        DB::beginTransaction();
        try {
            // Generate sale code
            $date = date('Ymd');
            $prefix = 'SO-' . $date . '-';
            $lastSale = Sale::where('code', 'like', $prefix . '%')
                ->orderBy('code', 'desc')
                ->first();
            $number = $lastSale ? intval(substr($lastSale->code, -4)) + 1 : 1;
            $saleCode = $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);

            // Create sale
            $sale = Sale::create([
                'code' => $saleCode,
                'type' => 'retail',
                'customer_id' => $quotation->customer_id,
                'customer_name' => $quotation->customer_name,
                'date' => now(),
                'subtotal' => $quotation->subtotal,
                'discount' => $quotation->discount,
                'vat' => $quotation->vat,
                'total' => $quotation->total,
                'cost' => 0,
                'margin' => 0,
                'margin_percent' => 0,
                'paid_amount' => 0,
                'debt_amount' => $quotation->total,
                'payment_status' => 'unpaid',
                'status' => 'pending',
                'note' => 'Chuyển từ báo giá: ' . $quotation->code,
            ]);

            // Create sale items
            foreach ($quotation->items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                ]);
            }

            // Update quotation
            $quotation->update([
                'status' => 'converted',
                'converted_to_sale_id' => $sale->id,
            ]);

            DB::commit();

            return redirect()->route('sales.show', $sale)
                ->with('success', 'Đã chuyển báo giá thành đơn hàng ' . $sale->code);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Print quotation
     */
    public function print(Quotation $quotation)
    {
        $quotation->load('items', 'customer');
        return view('quotations.print', compact('quotation'));
    }

    /**
     * Export quotations to Excel
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search', 'status']);
        $filename = 'bao-gia-' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(new QuotationsExport($filters), $filename);
    }
}
