<?php

namespace App\Http\Controllers;

use App\Models\MarketingEvent;
use App\Models\Customer;
use App\Models\ApprovalHistory;
use App\Services\ApprovalService;
use App\Models\MarketingSupplierFund;
use App\Models\MarketingSupplierTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketingEventController extends Controller
{
    protected ApprovalService $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    private function normalizeMoneyFields(Request $request, array $fields): void
    {
        $normalized = [];
        foreach ($fields as $field) {
            if (!$request->has($field)) {
                continue;
            }

            $raw = $request->input($field);
            if ($raw === null) {
                $normalized[$field] = null;
                continue;
            }

            $raw = trim((string) $raw);
            if ($raw === '') {
                $normalized[$field] = null;
                continue;
            }

            // Accept "100,000,000" format: strip thousands separators and spaces
            $clean = preg_replace('/[,\s]/', '', $raw);
            $normalized[$field] = $clean;
        }

        if (!empty($normalized)) {
            $request->merge($normalized);
        }
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', MarketingEvent::class);

        $query = MarketingEvent::with(['creator', 'approvalHistories'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $events = $query->paginate(15)->withQueryString();
        
        // Load workflow to check permissions on index
        $mktWorkflow = \App\Models\ApprovalWorkflow::getForDocumentType('marketing_budget');

        // Load Supplier Funds and Transactions if tab is funds
        $supplierFunds = [];
        $suppliers = [];
        $transactions = [];
        if ($request->query('tab') === 'funds') {
            $supplierFunds = MarketingSupplierFund::with(['supplier', 'creator'])->latest()->get();
            $suppliers = \App\Models\Supplier::all(['id', 'name']);
            $transactions = MarketingSupplierTransaction::with(['supplier', 'fund', 'event', 'request', 'creator'])->latest()->get();
        }

        return view('marketing-events.index', compact('events', 'mktWorkflow', 'supplierFunds', 'suppliers', 'transactions'));
    }

    public function create()
    {
        $this->authorize('create', MarketingEvent::class);
        $suppliers = \App\Models\Supplier::all(['id', 'name']);

        return view('marketing-events.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', MarketingEvent::class);

        $this->normalizeMoneyFields($request, ['budget', 'actual_cost']);

        $validated = $request->validate([
            'title'                 => 'nullable|string|max:255',
            'description'           => 'nullable|string',
            'event_date'            => 'required|date',
            'location'              => 'required|string|max:255',
            'budget'                => 'required|numeric|min:0',
            'actual_cost'           => 'nullable|numeric|min:0',
            'scope'                 => 'required|in:internal,external',
            'vendor_id'             => 'nullable|exists:suppliers,id',
            'vendor_other_note'     => 'nullable|string',
            'partner_cooperation'   => 'required|in:yes,no,other',
            'partner_info'          => 'nullable|string',
            'organize_type'         => 'required|in:workshop,networking_dinner,exhibition,other',
            'organize_type_other'   => 'nullable|string|max:255',
            'start_time'            => 'nullable',
            'end_time'              => 'nullable',
            'target_audience_count' => 'required|integer|min:0',
            'target_audience_note'  => 'nullable|string',
            'budget_external_note'  => 'nullable|string',
            'funding_source'        => 'nullable|string|max:255',
            'special_notes'         => 'nullable|string',
        ]);

        if (empty($validated['title'])) {
            $validated['title'] = 'Chương trình Marketing ' . MarketingEvent::generateCode();
        }

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'draft';

        // Handle file uploads
        $attachments = [];
        $fileKeys = ['cost_estimation_file', 'event_plan_file', 'quotation_file', 'agenda_file', 'guest_list_file'];
        foreach ($fileKeys as $key) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $path = $file->store('marketing_attachments', 'public');
                $attachments[$key] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url'  => asset('storage/' . $path)
                ];
            }
        }
        $validated['attachments'] = $attachments;

        $event = MarketingEvent::create($validated);

        return redirect()->route('marketing-events.show', $event)
            ->with('success', 'Đã tạo chương trình marketing thành công.');
    }

    public function show(MarketingEvent $marketingEvent)
    {
        $this->authorize('view', $marketingEvent);

        $marketingEvent->load(['creator', 'customers', 'approvalHistories', 'tickets.requests.assignee', 'tickets.requests.comments.user', 'vendor']);
        $existingCustomerIds = $marketingEvent->customers()->pluck('customers.id')->all();
        $suggestCustomers = Customer::query()
            ->when(!empty($existingCustomerIds), fn ($q) => $q->whereNotIn('id', $existingCustomerIds))
            ->latest()
            ->limit(10)
            ->get(['id', 'name']);

        $approvalHistory = ApprovalHistory::where('document_type', 'marketing_budget')
            ->where('document_id', $marketingEvent->id)
            ->orderBy('level')
            ->orderBy('created_at')
            ->get();

        $users = \App\Models\User::where('status', 'active')->get(['id', 'name', 'department', 'position']);
        $suppliers = \App\Models\Supplier::all(['id', 'name']);
        $supplierFunds = MarketingSupplierFund::with('supplier')->latest()->get();

        return view('marketing-events.show', compact('marketingEvent', 'suggestCustomers', 'approvalHistory', 'users', 'suppliers', 'supplierFunds'));
    }

    public function edit(MarketingEvent $marketingEvent)
    {
        $this->authorize('update', $marketingEvent);

        if (!$marketingEvent->isEditable()) {
            return redirect()->route('marketing-events.show', $marketingEvent)
                ->with('error', 'Chỉ có thể chỉnh sửa sự kiện ở trạng thái Nháp hoặc Từ chối.');
        }

        $suppliers = \App\Models\Supplier::all(['id', 'name']);

        return view('marketing-events.edit', compact('marketingEvent', 'suppliers'));
    }

    public function update(Request $request, MarketingEvent $marketingEvent)
    {
        $this->authorize('update', $marketingEvent);

        if (!$marketingEvent->isEditable()) {
            return back()->with('error', 'Không thể chỉnh sửa sự kiện này.');
        }

        $this->normalizeMoneyFields($request, ['budget', 'actual_cost']);

        $validated = $request->validate([
            'title'                 => 'nullable|string|max:255',
            'description'           => 'nullable|string',
            'event_date'            => 'required|date',
            'location'              => 'required|string|max:255',
            'budget'                => 'required|numeric|min:0',
            'actual_cost'           => 'nullable|numeric|min:0',
            'scope'                 => 'required|in:internal,external',
            'vendor_id'             => 'nullable|exists:suppliers,id',
            'vendor_other_note'     => 'nullable|string',
            'partner_cooperation'   => 'required|in:yes,no,other',
            'partner_info'          => 'nullable|string',
            'organize_type'         => 'required|in:workshop,networking_dinner,exhibition,other',
            'organize_type_other'   => 'nullable|string|max:255',
            'start_time'            => 'nullable',
            'end_time'              => 'nullable',
            'target_audience_count' => 'required|integer|min:0',
            'target_audience_note'  => 'nullable|string',
            'budget_external_note'  => 'nullable|string',
            'funding_source'        => 'nullable|string|max:255',
            'special_notes'         => 'nullable|string',
        ]);

        if (empty($validated['title'])) {
            $validated['title'] = 'Chương trình Marketing ' . ($marketingEvent->code ?: MarketingEvent::generateCode());
        }

        // Handle file uploads
        $attachments = $marketingEvent->attachments ?? [];
        $fileKeys = ['cost_estimation_file', 'event_plan_file', 'quotation_file', 'agenda_file', 'guest_list_file'];
        foreach ($fileKeys as $key) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $path = $file->store('marketing_attachments', 'public');
                $attachments[$key] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url'  => asset('storage/' . $path)
                ];
            }
        }
        $validated['attachments'] = $attachments;
        $validated['status'] = 'draft'; // Reset về draft khi chỉnh sửa

        $marketingEvent->update($validated);

        return redirect()->route('marketing-events.show', $marketingEvent)
            ->with('success', 'Đã cập nhật sự kiện thành công.');
    }

    public function destroy(MarketingEvent $marketingEvent)
    {
        $this->authorize('delete', $marketingEvent);

        if (!in_array($marketingEvent->status, ['draft', 'rejected', 'cancelled'])) {
            return back()->with('error', 'Không thể xóa sự kiện đã duyệt hoặc đang chờ duyệt.');
        }

        $marketingEvent->customers()->detach();
        $marketingEvent->delete();

        return redirect()->route('marketing-events.index')
            ->with('success', 'Đã xóa sự kiện thành công.');
    }

    /**
     * Gửi duyệt ngân sách marketing
     */
    public function submitApproval(MarketingEvent $marketingEvent)
    {
        $this->authorize('update', $marketingEvent);

        if (!$marketingEvent->isEditable()) {
            return back()->with('error', 'Sự kiện không ở trạng thái có thể gửi duyệt.');
        }

        // Xóa lịch sử duyệt cũ
        ApprovalHistory::where('document_type', 'marketing_budget')
            ->where('document_id', $marketingEvent->id)
            ->delete();

        $result = $this->approvalService->submit($marketingEvent, 'marketing_budget');

        if (!$result['success']) {
            // Hiển thị lỗi thực tế từ service thay vì thông báo cứng
            return back()->with('warning', $result['message'] ?? 'Chưa cấu hình quy trình duyệt marketing.');
        }

        $marketingEvent->refresh();
        if (isset($result['auto_approved']) && $result['auto_approved']) {
            $marketingEvent->update([
                'status'           => 'approved',
                'approved_at'      => now(),
                'approved_by'      => auth()->id(),
                'rejection_reason' => null,
            ]);
        } else {
            $marketingEvent->update([
                'status'           => 'pending',
                'rejection_reason' => null,
            ]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Duyệt ngân sách
     */
    public function approve(Request $request, MarketingEvent $marketingEvent)
    {
        $this->authorize('approve', $marketingEvent);

        $request->validate(['comment' => 'nullable|string|max:500']);

        $result = $this->approvalService->approve($marketingEvent, 'marketing_budget', $request->comment);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        $marketingEvent->refresh();
        if ($marketingEvent->status === 'approved') {
            $marketingEvent->update([
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Từ chối ngân sách
     */
    public function reject(Request $request, MarketingEvent $marketingEvent)
    {
        $this->authorize('approve', $marketingEvent);

        $request->validate(['comment' => 'required|string|min:3|max:500']);

        $result = $this->approvalService->reject($marketingEvent, 'marketing_budget', $request->comment);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        $marketingEvent->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->comment,
        ]);

        return back()->with('success', 'Đã từ chối ngân sách sự kiện.');
    }

    /**
     * Thêm khách hàng vào danh sách mời
     */
    public function addCustomers(Request $request, MarketingEvent $marketingEvent)
    {
        $this->authorize('update', $marketingEvent);

        $request->validate([
            'customer_ids'   => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
        ]);

        foreach ($request->customer_ids as $customerId) {
            $marketingEvent->customers()->syncWithoutDetaching([
                $customerId => ['status' => 'invited']
            ]);
        }

        return back()->with('success', 'Đã thêm ' . count($request->customer_ids) . ' khách hàng vào danh sách mời.');
    }

    /**
     * Xóa khách hàng khỏi danh sách
     */
    public function removeCustomer(MarketingEvent $marketingEvent, Customer $customer)
    {
        $this->authorize('update', $marketingEvent);

        $marketingEvent->customers()->detach($customer->id);

        return back()->with('success', 'Đã xóa khách hàng khỏi danh sách.');
    }

    /**
     * Cập nhật trạng thái tham dự
     */
    public function updateCustomerStatus(Request $request, MarketingEvent $marketingEvent, Customer $customer)
    {
        $this->authorize('update', $marketingEvent);

        $request->validate(['status' => 'required|in:invited,attended,cancelled']);

        $marketingEvent->customers()->updateExistingPivot($customer->id, [
            'status' => $request->status,
            'notes'  => $request->notes,
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái khách hàng.');
    }

    /**
     * Cập nhật trạng thái hàng loạt cho khách mời
     */
    public function bulkUpdateCustomerStatus(Request $request, MarketingEvent $marketingEvent)
    {
        $this->authorize('update', $marketingEvent);

        $validated = $request->validate([
            'customer_ids'   => 'required|array|min:1',
            'customer_ids.*' => 'integer|exists:customers,id',
            'status'         => 'required|in:invited,attended,cancelled',
        ]);

        $customerIds = collect($validated['customer_ids'])->unique()->values();

        // Chỉ cập nhật các khách đang thuộc event này
        $existingIds = $marketingEvent->customers()
            ->whereIn('customers.id', $customerIds)
            ->pluck('customers.id');

        if ($existingIds->isEmpty()) {
            return back()->with('warning', 'Không tìm thấy khách hàng hợp lệ để cập nhật.');
        }

        DB::table('marketing_event_customers')
            ->where('marketing_event_id', $marketingEvent->id)
            ->whereIn('customer_id', $existingIds)
            ->update([
                'status'     => $validated['status'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Đã cập nhật trạng thái cho ' . $existingIds->count() . ' khách hàng.');
    }

    /**
     * Khai báo Quỹ Hãng mới
     */
    public function storeFund(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'name'        => 'required|string|max:255',
            'quarter'     => 'required|in:Q1,Q2,Q3,Q4',
            'year'        => 'required|integer|min:2020|max:2100',
            'amount'      => 'required|numeric|min:0',
            'note'        => 'nullable|string',
        ]);

        $this->normalizeMoneyFields($request, ['amount']);
        $validated['amount'] = $request->amount;

        DB::beginTransaction();
        try {
            $fund = MarketingSupplierFund::create([
                'supplier_id'      => $validated['supplier_id'],
                'name'             => $validated['name'],
                'quarter'          => $validated['quarter'],
                'year'             => $validated['year'],
                'amount'           => $validated['amount'],
                'used_amount'      => 0,
                'remaining_amount' => $validated['amount'],
                'note'             => $validated['note'],
                'created_by'       => auth()->id(),
            ]);

            // Ghi giao dịch incoming
            MarketingSupplierTransaction::create([
                'supplier_id'                => $fund->supplier_id,
                'marketing_supplier_fund_id' => $fund->id,
                'type'                       => 'incoming',
                'amount'                     => $fund->amount,
                'note'                       => "Khởi tạo quỹ hãng: " . $fund->name,
                'created_by'                 => auth()->id(),
            ]);

            DB::commit();
            return redirect()->route('marketing-events.index', ['tab' => 'funds'])
                ->with('success', 'Đã khai báo quỹ hãng thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi khai báo quỹ hãng: ' . $e->getMessage());
        }
    }

    /**
     * Xác nhận hãng đã trả tiền nợ (Thu hồi công nợ)
     */
    public function collectDebt(MarketingSupplierTransaction $transaction)
    {
        if ($transaction->type !== 'receivable' || $transaction->status !== 'pending') {
            return back()->with('error', 'Giao dịch không hợp lệ hoặc đã được tất toán.');
        }

        DB::beginTransaction();
        try {
            // Cập nhật trạng thái giao dịch nợ
            $transaction->update(['status' => 'collected']);

            // Tạo giao dịch collected
            MarketingSupplierTransaction::create([
                'supplier_id'                => $transaction->supplier_id,
                'marketing_supplier_fund_id' => $transaction->marketing_supplier_fund_id,
                'marketing_event_id'         => $transaction->marketing_event_id,
                'marketing_request_id'       => $transaction->marketing_request_id,
                'type'                       => 'collected',
                'amount'                     => $transaction->amount,
                'note'                       => "Hãng tất toán thanh toán công nợ: " . ($transaction->note ?? ''),
                'created_by'                 => auth()->id(),
            ]);

            DB::commit();
            return redirect()->route('marketing-events.index', ['tab' => 'funds'])
                ->with('success', 'Đã xác nhận hãng thanh toán công nợ thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi tất toán công nợ: ' . $e->getMessage());
        }
    }
}
