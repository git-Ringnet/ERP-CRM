<?php

namespace App\Http\Controllers;

use App\Models\MarketingEvent;
use App\Models\Customer;
use App\Models\ApprovalHistory;
use App\Services\ApprovalService;
use App\Models\MarketingSupplierFund;
use App\Models\MarketingSupplierTransaction;
use App\Models\MarketingRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MarketingSupplierFundTemplateExport;
use App\Imports\MarketingSupplierFundsImport;

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

        $query = MarketingEvent::with(['creator', 'approvalHistories', 'vendor', 'suppliers'])->latest();

        $user = $request->user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'director', 'marketing', 'marketing_manager', 'sales_manager'])) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere('is_public_to_sales', 1)
                    ->orWhereHas('requests', fn ($requests) => $requests->where('assigned_to', $user->id))
                    ->orWhereHas('customers', fn ($customers) => $customers->where('am', $user->id)->orWhere('am', 'like', '%' . $user->name . '%'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $events = $query->paginate(15)->withQueryString();
        $directRequests = collect();
        $marketingAssignees = collect();
        $availableMarketingItems = collect();
        $paymentRequests = collect();

        if ($request->query('tab') === 'requests') {
            $directRequests = MarketingRequest::with([
                'ticket.creator',
                'opportunity.customer',
                'opportunity.marketingItemTransactions.marketingItem',
                'event.marketingItemTransactions.marketingItem',
                'assignee',
                'comments.user'
            ])
                ->where(function ($q) {
                    $q->whereNotNull('opportunity_id')
                      ->orWhere('support_team', 'marketing')
                      ->orWhere('support_content', 'giveaway');
                })
                ->when($request->filled('opportunity_id'), fn ($q) => $q->where('opportunity_id', $request->integer('opportunity_id')))
                ->latest()
                ->get();
            $marketingAssignees = User::where(function ($query) {
                $query->where('department', 'like', '%Marketing%')
                    ->orWhereHas('roles', fn ($roles) => $roles->whereIn('slug', ['marketing', 'marketing_manager']));
            })->orderBy('name')->get(['id', 'name']);
            $availableMarketingItems = \App\Models\MarketingItem::where('status', 'active')
                ->where('stock_quantity', '>', 0)
                ->orderBy('name')
                ->get();
        } elseif ($request->query('tab') === 'payments') {
            $paymentQuery = MarketingRequest::with([
                'ticket.creator',
                'ticket.event.suppliers',
                'event.suppliers',
                'fund.supplier',
                'comments.user'
            ])->whereHas('ticket', fn($q) => $q->where('type', 'payment'));

            if ($request->filled('payment_status')) {
                $paymentQuery->where('status', $request->payment_status);
            }
            if ($request->filled('event_id')) {
                $paymentQuery->where('marketing_event_id', $request->event_id);
            }
            if ($request->filled('search')) {
                $s = $request->search;
                $paymentQuery->where(function($q) use ($s) {
                    $q->where('code', 'like', "%{$s}%")
                      ->orWhere('description', 'like', "%{$s}%")
                      ->orWhere('funding_source', 'like', "%{$s}%");
                });
            }
            $paymentRequests = $paymentQuery->latest()->paginate(20)->withQueryString();
        }

        // Stats for payment requests
        $pendingPaymentApprovalCount = MarketingRequest::whereHas('ticket', fn($q) => $q->where('type', 'payment'))->where('status', 'pending_approval')->count();
        $pendingPaymentCount = MarketingRequest::whereHas('ticket', fn($q) => $q->where('type', 'payment'))->where('status', 'pending_payment')->count();
        $paidTotalAmount = MarketingRequest::whereHas('ticket', fn($q) => $q->where('type', 'payment'))->where('status', 'completed')->sum('amount');
        
        // Load workflow to check permissions on index
        $mktWorkflow = \App\Models\ApprovalWorkflow::getForDocumentType('marketing_budget');

        // Load Supplier Funds and Transactions if tab is funds
        $supplierFunds = [];
        $suppliers = [];
        $transactions = [];
        if ($request->query('tab') === 'funds' || $request->query('tab') === 'payments') {
            $supplierFunds = MarketingSupplierFund::with([
                'supplier',
                'creator',
                'transactions' => fn($q) => $q->with(['supplier', 'event', 'request', 'creator'])->latest()
            ])->latest()->get();
            $suppliers = \App\Models\Supplier::all(['id', 'name']);
            $transactions = MarketingSupplierTransaction::with(['supplier', 'fund', 'event', 'request', 'creator'])->latest()->get();
        }

        $allEventsForSelect = MarketingEvent::latest('id')->get(['id', 'code', 'title']);

        return view('marketing-events.index', compact(
            'events', 
            'mktWorkflow', 
            'supplierFunds', 
            'suppliers', 
            'transactions', 
            'directRequests', 
            'marketingAssignees',
            'availableMarketingItems',
            'paymentRequests',
            'pendingPaymentApprovalCount',
            'pendingPaymentCount',
            'paidTotalAmount',
            'allEventsForSelect'
        ));
    }

    public function create()
    {
        $this->authorize('create', MarketingEvent::class);
        $suppliers = \App\Models\Supplier::all(['id', 'name']);
        $supplierFunds = MarketingSupplierFund::with('supplier')->latest()->get();

        return view('marketing-events.create', compact('suppliers', 'supplierFunds'));
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
            'is_public_to_sales'    => 'nullable|boolean',
            'vendor_id'             => 'nullable|exists:suppliers,id',
            'vendor_ids'            => 'nullable|array',
            'vendor_ids.*'          => 'exists:suppliers,id',
            'vendor_other_note'     => 'nullable|string',
            'partner_cooperation'   => 'nullable|in:yes,no,other',
            'partner_info'          => 'nullable|string',
            'organize_type'         => 'required|in:workshop,networking_dinner,exhibition,other',
            'organize_type_other'   => 'nullable|string|max:255',
            'start_time'            => 'nullable',
            'end_time'              => 'nullable',
            'target_audience_count' => 'required|integer|min:0',
            'target_audience_note'  => 'nullable|string',
            'budget_external_note'  => 'nullable|string',
            'funding_source'        => 'nullable|string|max:255',
            'funding_sources'       => 'nullable',
            'special_notes'         => 'nullable|string',
            'internal_department'   => 'nullable|string|max:255',
            'internal_purpose'      => 'nullable|string|max:255',
            'support_marketing'     => 'nullable|boolean',
            'support_technical'     => 'nullable|boolean',
            'support_request_note'  => 'nullable|string|max:2000',
        ]);

        $validated['is_public_to_sales'] = $request->boolean('is_public_to_sales');
        $validated['partner_cooperation'] = $validated['partner_cooperation'] ?? 'no';

        // Multi-vendor handling
        $vendorIds = (array) $request->input('vendor_ids', []);
        if (empty($vendorIds) && !empty($validated['vendor_id'])) {
            $vendorIds = [$validated['vendor_id']];
        }
        $validated['vendor_id'] = !empty($vendorIds) ? (int)$vendorIds[0] : null;

        // Structured funding sources handling (Multi-brand + Union + Company)
        $rawSources = $request->input('funding_sources', []);
        if (is_string($rawSources)) {
            $rawSources = json_decode($rawSources, true) ?: [];
        }
        $cleanedSources = [];
        $sourceNames = [];
        $totalPlannedFromSources = 0;
        foreach ($rawSources as $src) {
            if (empty($src)) continue;
            $name = trim($src['name'] ?? '');
            $planned = (float) preg_replace('/[^\d.]/', '', str_replace(',', '', (string)($src['planned_amount'] ?? 0)));
            $fundId = !empty($src['fund_id']) ? (int)$src['fund_id'] : null;
            $supplierId = !empty($src['supplier_id']) ? (int)$src['supplier_id'] : null;
            if ($fundId) {
                $foundFund = MarketingSupplierFund::find($fundId);
                if ($foundFund) {
                    if (!$supplierId) $supplierId = $foundFund->supplier_id;
                    if (!$name) $name = ($foundFund->supplier->name ?? 'Hãng') . ' - ' . $foundFund->name;
                }
            }
            if ($name || $planned > 0 || $fundId) {
                $cleanedSources[] = [
                    'source_type'      => $src['source_type'] ?? 'brand',
                    'fund_id'          => $fundId,
                    'supplier_id'      => $supplierId,
                    'name'             => $name,
                    'planned_amount'   => $planned,
                    'actual_amount'    => (float)($src['actual_amount'] ?? 0),
                    'remaining_amount' => (float)($src['remaining_amount'] ?? 0),
                    'note'             => $src['note'] ?? '',
                ];
                if ($name) $sourceNames[] = $name;
                $totalPlannedFromSources += $planned;
            }
        }
        $validated['funding_sources'] = $cleanedSources;
        if (!empty($sourceNames)) {
            $validated['funding_source'] = implode(', ', array_unique($sourceNames));
        }
        if ($totalPlannedFromSources > 0 && ($validated['budget'] <= 0 || empty($request->input('budget')))) {
            $validated['budget'] = $totalPlannedFromSources;
        }

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

        if (!empty($vendorIds)) {
            $event->suppliers()->sync($vendorIds);
        }

        $requestedTeams = array_filter([
            $request->boolean('support_marketing') ? 'marketing' : null,
            $request->boolean('support_technical') ? 'technical' : null,
        ]);
        if ($requestedTeams) {
            $ticket = \App\Models\MarketingTicket::create([
                'marketing_event_id' => $event->id,
                'type' => 'internal_collaboration',
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            foreach ($requestedTeams as $team) {
                MarketingRequest::create([
                    'marketing_ticket_id' => $ticket->id,
                    'marketing_event_id' => $event->id,
                    'support_team' => $team,
                    'pic_type' => 'lead',
                    'support_content' => $team === 'technical' ? 'technical_support' : 'others',
                    'support_content_other' => $team === 'marketing' ? 'Chuẩn bị/điều phối Marketing' : null,
                    'description' => $request->input('support_request_note'),
                    'deadline' => $event->event_date,
                    'status' => 'pending_approval',
                ]);
            }
        }

        return redirect()->route('marketing-events.show', $event)
            ->with('success', 'Đã tạo chương trình marketing thành công.');
    }

    public function show(MarketingEvent $marketingEvent)
    {
        $this->authorize('view', $marketingEvent);

        $marketingEvent->load(['creator', 'customers', 'approvalHistories', 'tickets.requests.assignee', 'tickets.requests.comments.user', 'vendor', 'suppliers', 'completer']);
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

        $marketingEvent->load('suppliers');
        $suppliers = \App\Models\Supplier::all(['id', 'name']);
        $supplierFunds = MarketingSupplierFund::with('supplier')->latest()->get();

        return view('marketing-events.edit', compact('marketingEvent', 'suppliers', 'supplierFunds'));
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
            'is_public_to_sales'    => 'nullable|boolean',
            'vendor_id'             => 'nullable|exists:suppliers,id',
            'vendor_ids'            => 'nullable|array',
            'vendor_ids.*'          => 'exists:suppliers,id',
            'vendor_other_note'     => 'nullable|string',
            'partner_cooperation'   => 'nullable|in:yes,no,other',
            'partner_info'          => 'nullable|string',
            'organize_type'         => 'required|in:workshop,networking_dinner,exhibition,other',
            'organize_type_other'   => 'nullable|string|max:255',
            'start_time'            => 'nullable',
            'end_time'              => 'nullable',
            'target_audience_count' => 'required|integer|min:0',
            'target_audience_note'  => 'nullable|string',
            'budget_external_note'  => 'nullable|string',
            'funding_source'        => 'nullable|string|max:255',
            'funding_sources'       => 'nullable',
            'special_notes'         => 'nullable|string',
            'internal_department'   => 'nullable|string|max:255',
            'internal_purpose'      => 'nullable|string|max:255',
        ]);

        $validated['is_public_to_sales'] = $request->boolean('is_public_to_sales');
        $validated['partner_cooperation'] = $validated['partner_cooperation'] ?? 'no';

        // Multi-vendor handling
        $vendorIds = (array) $request->input('vendor_ids', []);
        if (empty($vendorIds) && !empty($validated['vendor_id'])) {
            $vendorIds = [$validated['vendor_id']];
        }
        $validated['vendor_id'] = !empty($vendorIds) ? (int)$vendorIds[0] : null;

        // Structured funding sources handling
        $rawSources = $request->input('funding_sources', []);
        if (is_string($rawSources)) {
            $rawSources = json_decode($rawSources, true) ?: [];
        }
        $cleanedSources = [];
        $sourceNames = [];
        $totalPlannedFromSources = 0;
        foreach ($rawSources as $src) {
            if (empty($src)) continue;
            $name = trim($src['name'] ?? '');
            $planned = (float) preg_replace('/[^\d.]/', '', str_replace(',', '', (string)($src['planned_amount'] ?? 0)));
            $fundId = !empty($src['fund_id']) ? (int)$src['fund_id'] : null;
            $supplierId = !empty($src['supplier_id']) ? (int)$src['supplier_id'] : null;
            if ($fundId) {
                $foundFund = MarketingSupplierFund::find($fundId);
                if ($foundFund) {
                    if (!$supplierId) $supplierId = $foundFund->supplier_id;
                    if (!$name) $name = ($foundFund->supplier->name ?? 'Hãng') . ' - ' . $foundFund->name;
                }
            }
            if ($name || $planned > 0 || $fundId) {
                $cleanedSources[] = [
                    'source_type'      => $src['source_type'] ?? 'brand',
                    'fund_id'          => $fundId,
                    'supplier_id'      => $supplierId,
                    'name'             => $name,
                    'planned_amount'   => $planned,
                    'actual_amount'    => (float)($src['actual_amount'] ?? 0),
                    'remaining_amount' => (float)($src['remaining_amount'] ?? 0),
                    'note'             => $src['note'] ?? '',
                ];
                if ($name) $sourceNames[] = $name;
                $totalPlannedFromSources += $planned;
            }
        }
        $validated['funding_sources'] = $cleanedSources;
        if (!empty($sourceNames)) {
            $validated['funding_source'] = implode(', ', array_unique($sourceNames));
        }
        if ($totalPlannedFromSources > 0 && ($validated['budget'] <= 0 || empty($request->input('budget')))) {
            $validated['budget'] = $totalPlannedFromSources;
        }

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
        $marketingEvent->suppliers()->sync($vendorIds);

        return redirect()->route('marketing-events.show', $marketingEvent)
            ->with('success', 'Đã cập nhật sự kiện thành công.');
    }

    /**
     * Hoàn thành sự kiện & Nghiệm thu quyết toán chi phí thực tế
     */
    public function complete(Request $request, MarketingEvent $marketingEvent)
    {
        $this->authorize('update', $marketingEvent);

        if (!in_array($marketingEvent->status, ['approved', 'completed'])) {
            return back()->with('error', 'Chỉ có thể hoàn thành sự kiện đã được phê duyệt.');
        }

        $this->normalizeMoneyFields($request, ['actual_cost']);

        $request->validate([
            'actual_cost'             => 'required|numeric|min:0',
            'variance_funding_source' => 'nullable|string|max:255',
            'completion_note'         => 'nullable|string',
        ]);

        $actualSourcesInput = $request->input('actual_funding_sources', []);
        $existingSources = $marketingEvent->funding_sources ?? [];
        $updatedSources = [];
        $totalActualFunding = 0;

        foreach ($existingSources as $idx => $src) {
            $rawVal = $actualSourcesInput[$idx] ?? ($actualSourcesInput[$src['name']] ?? $src['planned_amount'] ?? 0);
            $actualAmt = (float) preg_replace('/[^\d.]/', '', str_replace(',', '', (string)$rawVal));
            $src['actual_amount'] = $actualAmt;
            $totalActualFunding += $actualAmt;
            $updatedSources[] = $src;
        }

        // If there were no structured funding sources previously, create default sources from inputs
        if (empty($updatedSources) && !empty($actualSourcesInput)) {
            foreach ($actualSourcesInput as $name => $amt) {
                $actualAmt = (float) preg_replace('/[^\d.]/', '', str_replace(',', '', (string)$amt));
                $updatedSources[] = [
                    'source_type'    => 'brand',
                    'name'           => is_string($name) ? $name : 'Nguồn tài trợ ' . ($name + 1),
                    'planned_amount' => $actualAmt,
                    'actual_amount'  => $actualAmt,
                    'note'           => '',
                ];
                $totalActualFunding += $actualAmt;
            }
        }

        $actualCost = (float) $request->input('actual_cost', 0);
        $varianceAmount = $actualCost - $totalActualFunding; // > 0: vượt chi (thiếu hụt), < 0: dư tiền tài trợ

        $marketingEvent->update([
            'status'                  => 'completed',
            'actual_cost'             => $actualCost,
            'actual_funding_sources'  => $updatedSources,
            'variance_amount'         => $varianceAmount,
            'variance_funding_source' => $request->input('variance_funding_source'),
            'completion_note'         => $request->input('completion_note'),
            'completed_at'            => now(),
            'completed_by'            => auth()->id(),
        ]);

        return redirect()->route('marketing-events.show', $marketingEvent)
            ->with('success', 'Đã ghi nhận hoàn thành sự kiện và cập nhật quyết toán chi phí thực tế thành công.');
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
            $this->activateEventCollaborationRequests($marketingEvent);
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
            $this->activateEventCollaborationRequests($marketingEvent);
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

    /**
     * Cập nhật Quỹ Hãng (Điều chỉnh thông tin, nạp thêm/bổ sung quỹ & lưu lịch sử giao dịch)
     */
    public function updateFund(Request $request, MarketingSupplierFund $fund)
    {
        $this->normalizeMoneyFields($request, ['amount', 'top_up_amount']);

        $validated = $request->validate([
            'supplier_id'        => 'required|exists:suppliers,id',
            'name'               => 'required|string|max:255',
            'quarter'            => 'required|in:Q1,Q2,Q3,Q4',
            'year'               => 'required|integer|min:2020|max:2100',
            'update_mode'        => 'required|in:set_total,top_up',
            'amount'             => 'nullable|numeric',
            'top_up_amount'      => 'nullable|numeric|min:0',
            'adjustment_reason'  => 'nullable|string|max:1000',
            'note'               => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $oldAmount = (float)$fund->amount;
            $diff = 0;

            if ($validated['update_mode'] === 'top_up') {
                $topUp = (float)($validated['top_up_amount'] ?? 0);
                if ($topUp > 0) {
                    $newAmount = $oldAmount + $topUp;
                    $diff = $topUp;
                } else {
                    $newAmount = $oldAmount;
                }
            } else {
                $setAmount = (float)($validated['amount'] ?? $oldAmount);
                $diff = $setAmount - $oldAmount;
                $newAmount = $setAmount;
            }

            $fund->supplier_id = $validated['supplier_id'];
            $fund->name = $validated['name'];
            $fund->quarter = $validated['quarter'];
            $fund->year = $validated['year'];
            $fund->amount = $newAmount;
            $fund->remaining_amount = $newAmount - (float)$fund->used_amount;
            if (isset($validated['note'])) {
                $fund->note = $validated['note'];
            }
            $fund->save();

            // Nếu có thay đổi số tiền quỹ thì lưu lịch sử giao dịch
            if ($diff != 0) {
                $reason = trim($validated['adjustment_reason'] ?? '');
                $type = $diff > 0 ? 'incoming' : 'adjustment';
                $sign = $diff > 0 ? '+' : '-';
                $defaultNote = ($diff > 0 ? 'Hãng cấp bổ sung / tăng ngân sách quỹ' : 'Điều chỉnh giảm ngân sách quỹ') 
                    . " ({$sign}" . number_format(abs($diff)) . " đ)";
                $txNote = $reason ? ($defaultNote . ": " . $reason) : $defaultNote;

                MarketingSupplierTransaction::create([
                    'supplier_id'                => $fund->supplier_id,
                    'marketing_supplier_fund_id' => $fund->id,
                    'type'                       => $type,
                    'amount'                     => abs($diff),
                    'note'                       => $txNote,
                    'created_by'                 => auth()->id(),
                ]);
            }

            DB::commit();
            return redirect()->route('marketing-events.index', ['tab' => 'funds'])
                ->with('success', 'Đã cập nhật quỹ hãng và ghi nhận lịch sử giao dịch thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi cập nhật quỹ hãng: ' . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách lịch sử giao dịch của 1 quỹ hãng cụ thể (JSON)
     */
    public function fundTransactions(MarketingSupplierFund $fund)
    {
        $transactions = $fund->transactions()
            ->with(['supplier', 'event', 'request', 'creator'])
            ->latest()
            ->get()
            ->map(function ($tx) {
                return [
                    'id'            => $tx->id,
                    'created_at'    => $tx->created_at->format('d/m/Y H:i'),
                    'type'          => $tx->type,
                    'type_label'    => $tx->type_label,
                    'amount'        => (float)$tx->amount,
                    'amount_format' => number_format($tx->amount) . ' đ',
                    'note'          => $tx->note,
                    'status'        => $tx->status,
                    'event_title'   => $tx->event->title ?? null,
                    'creator_name'  => $tx->creator->name ?? 'Hệ thống',
                ];
            });

        return response()->json([
            'fund' => [
                'id'               => $fund->id,
                'name'             => $fund->name,
                'supplier_name'    => $fund->supplier->name ?? '—',
                'quarter'          => $fund->quarter,
                'year'             => $fund->year,
                'amount'           => (float)$fund->amount,
                'amount_format'    => number_format($fund->amount) . ' đ',
                'used_amount'      => (float)$fund->used_amount,
                'used_format'      => number_format($fund->used_amount) . ' đ',
                'remaining_amount' => (float)$fund->remaining_amount,
                'remaining_format' => number_format($fund->remaining_amount) . ' đ',
                'is_negative'      => $fund->remaining_amount < 0,
                'note'             => $fund->note,
            ],
            'transactions' => $transactions,
        ]);
    }

    /**
     * Tải file mẫu Excel (.xlsx) để import Quỹ Hãng MDF
     */
    public function downloadFundTemplate()
    {
        return Excel::download(
            new MarketingSupplierFundTemplateExport(),
            'Mau_Import_Quy_Hang_MDF.xlsx'
        );
    }

    /**
     * Import Quỹ Hãng từ file Excel / CSV
     */
    public function importFunds(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new MarketingSupplierFundsImport();
            Excel::import($import, $request->file('file'));

            $imported = $import->getImportedCount();
            $updated = $import->getUpdatedCount();
            $errors = $import->getErrors();
            $warnings = $import->getWarnings();

            $msg = "Đã import thành công {$imported} nguồn quỹ mới";
            if ($updated > 0) {
                $msg .= ", cập nhật {$updated} quỹ hiện có";
            }
            $msg .= ".";

            if (!empty($errors)) {
                $errorMsg = $msg . " Có " . count($errors) . " dòng lỗi: " . implode('; ', array_slice($errors, 0, 3));
                return redirect()->route('marketing-events.index', ['tab' => 'funds'])->with('warning', $errorMsg);
            }

            return redirect()->route('marketing-events.index', ['tab' => 'funds'])->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('marketing-events.index', ['tab' => 'funds'])->with('error', 'Lỗi khi đọc file import: ' . $e->getMessage());
        }
    }

    /** Activate assistance requests only after the event budget is approved. */
    private function activateEventCollaborationRequests(MarketingEvent $event): void
    {
        $event->tickets()->where('status', 'pending')->update(['status' => 'in_progress']);

        $requests = $event->requests()
            ->where('status', 'pending_approval')
            ->get();

        foreach ($requests as $request) {
            $request->update(['status' => 'received']);

            $recipients = User::where('status', 'active')
                ->where(function ($query) use ($request) {
                    if ($request->support_team === 'technical') {
                        $query->whereIn('department', ['Technical', 'Tech', 'IT']);
                    } elseif ($request->support_team === 'marketing') {
                        $query->where(function ($marketing) {
                            $marketing->where('department', 'like', '%Marketing%')
                                ->orWhereHas('roles', fn ($roles) => $roles->whereIn('slug', ['marketing', 'marketing_manager']));
                        });
                    }
                })
                ->get();

            foreach ($recipients as $recipient) {
                \App\Models\Notification::create([
                    'user_id' => $recipient->id,
                    'type' => 'marketing_event_support',
                    'title' => 'Yêu cầu phối hợp sự kiện đã được duyệt',
                    'message' => "Sự kiện {$event->code} cần {$request->support_team} phối hợp.",
                    'link' => route('marketing-events.show', $event),
                    'icon' => 'fas fa-calendar-check',
                    'color' => 'purple',
                ]);
            }
        }
    }
}
