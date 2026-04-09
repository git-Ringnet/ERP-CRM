<?php

namespace App\Http\Controllers;

use App\Models\MarketingEvent;
use App\Models\Customer;
use App\Models\ApprovalHistory;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketingEventController extends Controller
{
    protected ApprovalService $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
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

        return view('marketing-events.index', compact('events', 'mktWorkflow'));
    }

    public function create()
    {
        $this->authorize('create', MarketingEvent::class);

        return view('marketing-events.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', MarketingEvent::class);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date'  => 'required|date',
            'location'    => 'nullable|string|max:255',
            'budget'      => 'required|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['status'] = 'draft';

        $event = MarketingEvent::create($validated);

        return redirect()->route('marketing-events.show', $event)
            ->with('success', 'Đã tạo sự kiện marketing thành công.');
    }

    public function show(MarketingEvent $marketingEvent)
    {
        $this->authorize('view', $marketingEvent);

        $marketingEvent->load(['creator', 'customers', 'approvalHistories']);
        $allCustomers = Customer::orderBy('name')->get();

        $approvalHistory = ApprovalHistory::where('document_type', 'marketing_budget')
            ->where('document_id', $marketingEvent->id)
            ->orderBy('level')
            ->orderBy('created_at')
            ->get();

        return view('marketing-events.show', compact('marketingEvent', 'allCustomers', 'approvalHistory'));
    }

    public function edit(MarketingEvent $marketingEvent)
    {
        $this->authorize('update', $marketingEvent);

        if (!$marketingEvent->isEditable()) {
            return redirect()->route('marketing-events.show', $marketingEvent)
                ->with('error', 'Chỉ có thể chỉnh sửa sự kiện ở trạng thái Nháp hoặc Từ chối.');
        }

        return view('marketing-events.edit', compact('marketingEvent'));
    }

    public function update(Request $request, MarketingEvent $marketingEvent)
    {
        $this->authorize('update', $marketingEvent);

        if (!$marketingEvent->isEditable()) {
            return back()->with('error', 'Không thể chỉnh sửa sự kiện này.');
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date'  => 'required|date',
            'location'    => 'nullable|string|max:255',
            'budget'      => 'required|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
        ]);

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
}
