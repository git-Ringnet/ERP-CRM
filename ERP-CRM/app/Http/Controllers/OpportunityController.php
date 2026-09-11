<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\OpportunityAttachment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Reminder;
use App\Models\Notification;
use App\Models\TechnicalTicket;
use App\Models\MarketingTicket;
use App\Models\MarketingRequest;
use App\Models\MarketingItem;
use App\Models\MarketingItemTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OpportunityController extends Controller
{
    /**
     * Display a listing of the resource (Calendar & List view).
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Opportunity::class);

        $query = Opportunity::with(['customer', 'contact', 'assignedTo', 'technicalUser']);

        // Check permissions: Sales only see their assigned or created opportunities, Managers see all
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id)
                  ->orWhere('technical_user_id', $user->id);
            });
        }

        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('activity_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('activity_date', '<=', $request->end_date);
        }
        if ($request->filled('customer_name')) {
            $customerName = $request->customer_name;
            $query->where(function ($q) use ($customerName) {
                $q->where('eu_company_name', 'like', '%' . $customerName . '%')
                  ->orWhereHas('customer', function ($cq) use ($customerName) {
                      $cq->where('name', 'like', '%' . $customerName . '%');
                  });
            });
        }
        if ($request->filled('activity_type')) {
            $query->where('activity_type', $request->activity_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->filled('technical_user')) {
            $query->where('technical_user_id', $request->technical_user);
        }

        $viewType = $request->get('view', 'calendar'); // Default view is calendar

        $users = User::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $activityTypes = Opportunity::ACTIVITY_TYPES;
        $statuses = Opportunity::STATUSES;

        if ($viewType === 'list') {
            $opportunities = $query->latest('activity_date')->paginate(20);
            return view('opportunities.index_list', compact('opportunities', 'users', 'customers', 'activityTypes', 'statuses'));
        }

        return view('opportunities.index', compact('users', 'customers', 'activityTypes', 'statuses'));
    }

    /**
     * API returning JSON events for FullCalendar.
     */
    public function calendarEvents(Request $request)
    {
        $this->authorize('viewAny', Opportunity::class);

        $query = Opportunity::with(['customer', 'assignedTo', 'technicalUser']);

        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id)
                  ->orWhere('technical_user_id', $user->id);
            });
        }

        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('activity_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('activity_date', '<=', $request->end_date);
        }
        if ($request->filled('customer_name')) {
            $customerName = $request->customer_name;
            $query->where(function ($q) use ($customerName) {
                $q->where('eu_company_name', 'like', '%' . $customerName . '%')
                  ->orWhereHas('customer', function ($cq) use ($customerName) {
                      $cq->where('name', 'like', '%' . $customerName . '%');
                  });
            });
        }
        if ($request->filled('activity_type')) {
            $query->where('activity_type', $request->activity_type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        if ($request->filled('technical_user')) {
            $query->where('technical_user_id', $request->technical_user);
        }

        // FullCalendar request parameters
        if ($request->filled('start')) {
            $query->whereDate('activity_date', '>=', Carbon::parse($request->start)->toDateString());
        }
        if ($request->filled('end')) {
            $query->whereDate('activity_date', '<=', Carbon::parse($request->end)->toDateString());
        }

        $opportunities = $query->get();
        $events = [];

        foreach ($opportunities as $opp) {
            // Legacy opportunities may not have an activity date. They can be
            // listed and corrected, but cannot be rendered as calendar events.
            if (!$opp->activity_date) {
                continue;
            }

            $startTime = $opp->start_time ?: '09:00:00';
            $endTime = $opp->end_time ?: '10:00:00';
            $start = $opp->activity_date->format('Y-m-d') . 'T' . $startTime;
            $end = $opp->activity_date->format('Y-m-d') . 'T' . $endTime;

            $color = match ($opp->status) {
                'planned'    => '#3B82F6', // Blue
                'in_progress' => '#F59E0B', // Amber
                'completed'  => '#10B981', // Green
                'cancelled'  => '#EF4444', // Red
                default      => '#6B7280',
            };

            $events[] = [
                'id' => $opp->id,
                'title' => '[' . $opp->activity_type_label . '] ' . $opp->customer_display_name,
                'start' => $start,
                'end' => $end,
                'color' => $color,
                'url' => route('opportunities.show', $opp->id),
                'extendedProps' => [
                    'status' => $opp->status,
                    'statusLabel' => $opp->status_label,
                    'customer' => $opp->customer_display_name,
                    'assignedTo' => $opp->assignedTo?->name,
                ]
            ];
        }

        return response()->json($events);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Opportunity::class);

        $customers = Customer::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $technicalUsers = $this->technicalAssignees()->get();
        $activityTypes = Opportunity::ACTIVITY_TYPES;

        $prefill = [];
        if ($request->has('customer_id')) {
            $prefill['customer_id'] = $request->get('customer_id');
        }

        $technicalManagerId = (clone $this->technicalAssignees())
            ->whereHas('roles', fn ($roles) => $roles->where('slug', 'technical_lead'))
            ->value('id') ?? $technicalUsers->first()?->id;

        return view('opportunities.create', compact('customers', 'users', 'technicalUsers', 'activityTypes', 'prefill', 'technicalManagerId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Opportunity::class);

        $rules = [
            'customer_type' => 'required|in:si,eu',
            'customer_id' => 'required_if:customer_type,si|nullable|exists:customers,id',
            'contact_id' => 'required_if:customer_type,si|nullable|exists:contacts,id',
            'contact_name' => 'required_if:customer_type,si|nullable|string|max:255',
            'contact_position' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'eu_company_name' => 'required_if:customer_type,eu|nullable|string|max:255',
            'eu_contact_name' => 'nullable|string|max:255',
            'eu_phone' => 'nullable|string|max:50',
            'eu_email' => 'nullable|email|max:255',
            'eu_position' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'activity_type' => 'required|string',
            'activity_type_other' => 'required_if:activity_type,other|nullable|string|max:255',
            'activity_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'materials_required' => 'nullable|string',
            'giveaway' => 'nullable|string',
            'needs_technical' => 'nullable|boolean',
            'technical_user_id' => 'required_if:needs_technical,1|nullable|exists:users,id',
            'status' => 'required|in:draft,planned,confirmed,in_progress,completed,cancelled,postponed',
            'cancel_reason' => 'required_if:status,cancelled|nullable|string',
            'assigned_to' => 'required|exists:users,id',
        ];

        $validated = $request->validate($rules);
        $validated['needs_technical'] = $request->has('needs_technical') ? true : false;

        if ($validated['needs_technical'] && !$this->technicalAssignees()->whereKey($validated['technical_user_id'])->exists()) {
            return back()->withInput()->withErrors(['technical_user_id' => 'Người phối hợp phải thuộc nhóm Kỹ thuật.']);
        }

        if (in_array($validated['status'], ['confirmed', 'in_progress', 'completed'], true) &&
            !auth()->user()->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return back()->withInput()->withErrors([
                'status' => 'Hoạt động cần được BOD/Manager xác nhận trước khi triển khai hoặc hoàn thành.'
            ]);
        }

        // Backend validation: meeting must have attachments
        if (in_array($validated['activity_type'], ['meeting', 'project_meeting'])) {
            if (!$request->hasFile('files') || count($request->file('files')) === 0) {
                return back()->withInput()->withErrors(['files' => 'Đối với hoạt động "Meeting liên quan đến dự án", bạn bắt buộc phải đính kèm ít nhất một hình ảnh, biên bản meeting hoặc proposal ở phần Tài liệu đính kèm.']);
            }
        }

        // Auto-calculate duration_minutes
        if (!empty($request->start_time) && !empty($request->end_time)) {
            try {
                $start = Carbon::createFromFormat('H:i', $request->start_time);
                $end = Carbon::createFromFormat('H:i', $request->end_time);
                if ($end->greaterThan($start)) {
                    $validated['duration_minutes'] = $start->diffInMinutes($end);
                } else {
                    $validated['duration_minutes'] = 0;
                }
            } catch (\Exception $e) {
                $validated['duration_minutes'] = 0;
            }
        } else {
            $validated['duration_minutes'] = 0;
        }

        $validated['created_by'] = auth()->id();

        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        } else {
            $validated['completed_at'] = null;
        }

        if ($validated['status'] !== 'cancelled') {
            $validated['cancel_reason'] = null;
        }

        // Marketing preparation is a coordinated workstream only for a
        // solution-presentation activity. A generic opportunity must not
        // silently create a Marketing ticket just because a note was entered.
        if (!$this->isSolutionPresentationActivity($validated['activity_type'])) {
            $validated['giveaway'] = null;
        }
        $validated['giveaway_status'] = !empty($validated['giveaway']) ? 'pending' : 'none';

        $opportunity = Opportunity::create($validated);

        // Update contact inline if SI mode and contact_id is selected
        if ($validated['customer_type'] === 'si' && !empty($validated['contact_id'])) {
            $contact = \App\Models\Contact::find($validated['contact_id']);
            if ($contact) {
                $contact->update([
                    'name' => $request->input('contact_name'),
                    'position' => $request->input('contact_position'),
                    'phone' => $request->input('contact_phone'),
                    'email' => $request->input('contact_email'),
                ]);
            }
        }

        // Upload files
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('opportunity-attachments', 'public');
                OpportunityAttachment::create([
                    'opportunity_id' => $opportunity->id,
                    'uploaded_by' => auth()->id(),
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'note' => $request->get('file_note'),
                ]);
            }
        }

        // Reminders creation
        if (in_array($opportunity->status, ['planned', 'in_progress'])) {
            $activityDateTime = Carbon::parse($opportunity->activity_date->format('Y-m-d') . ' ' . ($opportunity->start_time ?: '09:00:00'));
            
            // 1 day reminder
            $remindAt1Day = $activityDateTime->copy()->subDay();
            if ($remindAt1Day->isFuture()) {
                Reminder::create([
                    'remindable_type' => Opportunity::class,
                    'remindable_id' => $opportunity->id,
                    'user_id' => $opportunity->assigned_to,
                    'remind_at' => $remindAt1Day,
                    'message' => 'Bạn có hoạt động "' . $opportunity->name . '" diễn ra vào ngày mai lúc ' . ($opportunity->start_time ?: '09:00') . '.',
                    'is_sent' => false,
                ]);
            }

            // 1 hour reminder
            $remindAt1Hour = $activityDateTime->copy()->subHour();
            if ($remindAt1Hour->isFuture()) {
                Reminder::create([
                    'remindable_type' => Opportunity::class,
                    'remindable_id' => $opportunity->id,
                    'user_id' => $opportunity->assigned_to,
                    'remind_at' => $remindAt1Hour,
                    'message' => 'Bạn có hoạt động "' . $opportunity->name . '" sẽ bắt đầu sau 1 giờ nữa.',
                    'is_sent' => false,
                ]);
            }
        }

        // A Technical-support request must be an actual Technical ticket, not
        // only a notification that disappears from the team's work queue.
        if ($opportunity->status === 'confirmed') {
            $this->createTechnicalTicketForConfirmedOpportunity($opportunity);
        }

        // Notifications
        if ($opportunity->status === 'confirmed' && $opportunity->needs_technical && $opportunity->technical_user_id) {
            Notification::create([
                'user_id' => $opportunity->technical_user_id,
                'type' => 'opportunity_technical_assigned',
                'title' => 'Yêu cầu phối hợp kỹ thuật',
                'message' => auth()->user()->name . ' đã yêu cầu bạn phối hợp kỹ thuật cho hoạt động: "' . $opportunity->name . '" vào ngày ' . $opportunity->activity_date->format('d/m/Y') . '.',
                'link' => route('opportunities.show', $opportunity->id),
                'icon' => 'fas fa-cogs',
                'color' => 'blue',
            ]);
        }

        // Notify sales managers/admins
        $managers = User::whereHas('roles', function ($q) {
            $q->whereIn('slug', ['sales_manager', 'super_admin', 'admin', 'director']);
        })->where('id', '!=', auth()->id())->get();

        foreach ($managers as $manager) {
            Notification::create([
                'user_id' => $manager->id,
                'type' => 'opportunity_created',
                'title' => 'Hoạt động cơ hội mới',
                'message' => auth()->user()->name . ' đã lên lịch hoạt động mới: "' . $opportunity->name . '" cho khách hàng ' . $opportunity->customer_display_name . '.',
                'link' => route('opportunities.show', $opportunity->id),
                'icon' => 'fas fa-calendar-plus',
                'color' => 'green',
            ]);

            if ($opportunity->giveaway_status === 'pending') {
                Notification::create([
                    'user_id' => $manager->id,
                    'type' => 'giveaway_request',
                    'title' => 'Yêu cầu quà tặng/budget mới',
                    'message' => auth()->user()->name . ' đã yêu cầu quà tặng/budget cho hoạt động: "' . $opportunity->name . '" của khách hàng ' . $opportunity->customer_display_name . '.',
                    'link' => route('opportunities.show', $opportunity->id),
                    'icon' => 'fas fa-gift',
                    'color' => 'amber',
                ]);
            }
        }

        return redirect()->route('opportunities.index')->with('success_swal', 'Đã tạo hoạt động cơ hội thành công.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Opportunity $opportunity)
    {
        $this->authorize('view', $opportunity);

        $opportunity->load(['customer', 'contact', 'assignedTo', 'technicalUser', 'attachments.uploader', 'createdBy']);

        $giveawayMarketingRequest = MarketingRequest::with('ticket')
            ->where('opportunity_id', $opportunity->id)
            ->where('support_content', 'giveaway')
            ->latest('id')
            ->first();
        $technicalTicket = TechnicalTicket::where('opportunity_id', $opportunity->id)
            ->latest('id')
            ->first();
        
        $users = User::orderBy('name')->get();
        $technicalEngineers = $this->technicalAssignees()->get();
        $marketingItems = MarketingItem::where('status', 'active')->orderBy('name')->get();
        $marketingTransactions = MarketingItemTransaction::with(['marketingItem', 'creator'])
            ->where('opportunity_id', $opportunity->id)
            ->latest('id')
            ->get();
        $statuses = Opportunity::STATUSES;
        $ratings = Opportunity::POTENTIAL_RATINGS;

        return view('opportunities.show', compact(
            'opportunity',
            'users',
            'technicalEngineers',
            'marketingItems',
            'marketingTransactions',
            'statuses',
            'ratings',
            'giveawayMarketingRequest',
            'technicalTicket'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        $customers = Customer::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $technicalUsers = $this->technicalAssignees()->get();
        $activityTypes = Opportunity::ACTIVITY_TYPES;

        $technicalManagerId = (clone $this->technicalAssignees())
            ->whereHas('roles', fn ($roles) => $roles->where('slug', 'technical_lead'))
            ->value('id') ?? $technicalUsers->first()?->id;

        return view('opportunities.edit', compact('opportunity', 'customers', 'users', 'technicalUsers', 'activityTypes', 'technicalManagerId'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        $rules = [
            'customer_type' => 'required|in:si,eu',
            'customer_id' => 'required_if:customer_type,si|nullable|exists:customers,id',
            'contact_id' => 'required_if:customer_type,si|nullable|exists:contacts,id',
            'eu_company_name' => 'required_if:customer_type,eu|nullable|string|max:255',
            'eu_contact_name' => 'nullable|string|max:255',
            'eu_phone' => 'nullable|string|max:50',
            'eu_email' => 'nullable|email|max:255',
            'eu_position' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'activity_type' => 'required|string',
            'activity_type_other' => 'required_if:activity_type,other|nullable|string|max:255',
            'activity_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'materials_required' => 'nullable|string',
            'giveaway' => 'nullable|string',
            'needs_technical' => 'nullable|boolean',
            'technical_user_id' => 'required_if:needs_technical,1|nullable|exists:users,id',
            'status' => 'required|in:draft,planned,confirmed,in_progress,completed,cancelled,postponed',
            'cancel_reason' => 'required_if:status,cancelled|nullable|string',
            'assigned_to' => 'required|exists:users,id',
            
            // SI Contact fields
            'contact_name' => 'required_if:customer_type,si|nullable|string|max:255',
            'contact_position' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            
            // Phase 2 reports
            'customer_feedback' => 'nullable|string',
            'meeting_result' => 'nullable|string',
            'pain_points' => 'nullable|string',
            'next_action' => 'nullable|string',
            'potential_rating' => 'nullable|string|in:25,50,75,90',
        ];

        $validated = $request->validate($rules);
        $validated['needs_technical'] = $request->has('needs_technical') ? true : false;

        if ($validated['needs_technical'] && !$this->technicalAssignees()->whereKey($validated['technical_user_id'])->exists()) {
            return back()->withInput()->withErrors(['technical_user_id' => 'Người phối hợp phải thuộc nhóm Kỹ thuật.']);
        }

        // Backend validation: meeting must have attachments
        if (in_array($validated['activity_type'], ['meeting', 'project_meeting'])) {
            if (!$request->hasFile('files') && $opportunity->attachments()->count() === 0) {
                return back()->withInput()->withErrors(['files' => 'Đối với hoạt động "Meeting liên quan đến dự án", bạn bắt buộc phải đính kèm ít nhất một hình ảnh, biên bản meeting hoặc proposal ở phần Tài liệu đính kèm.']);
            }
        }

        // Auto-calculate duration_minutes
        if (!empty($request->start_time) && !empty($request->end_time)) {
            try {
                $start = Carbon::createFromFormat('H:i', $request->start_time);
                $end = Carbon::createFromFormat('H:i', $request->end_time);
                if ($end->greaterThan($start)) {
                    $validated['duration_minutes'] = $start->diffInMinutes($end);
                } else {
                    $validated['duration_minutes'] = 0;
                }
            } catch (\Exception $e) {
                $validated['duration_minutes'] = 0;
            }
        } else {
            $validated['duration_minutes'] = 0;
        }

        $oldStatus = $opportunity->status;
        if ($validated['status'] === 'completed' && $oldStatus !== 'completed') {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }

        if ($validated['status'] !== 'cancelled') {
            $validated['cancel_reason'] = null;
        }

        // A Marketing giveaway request is only valid for a solution
        // presentation. Clearing/changing the activity type also clears the
        // pending Marketing request instead of leaving it orphaned.
        if (!$this->isSolutionPresentationActivity($validated['activity_type'])) {
            $validated['giveaway'] = null;
            $validated['giveaway_status'] = 'none';
        } elseif ($opportunity->giveaway !== ($validated['giveaway'] ?? null)) {
            $validated['giveaway_status'] = !empty($validated['giveaway']) ? 'pending' : 'none';
        } else {
            $validated['giveaway_status'] = $opportunity->giveaway_status ?: 'none';
        }

        $giveawayStatusChangedToPending = ($validated['giveaway_status'] === 'pending' && $opportunity->giveaway_status !== 'pending');

        $opportunity->update($validated);

        // Update contact inline if SI mode and contact_id is selected
        if ($validated['customer_type'] === 'si' && !empty($validated['contact_id'])) {
            $contact = \App\Models\Contact::find($validated['contact_id']);
            if ($contact) {
                $contact->update([
                    'name' => $request->input('contact_name'),
                    'position' => $request->input('contact_position'),
                    'phone' => $request->input('contact_phone'),
                    'email' => $request->input('contact_email'),
                ]);
            }
        }

        // Notify managers if giveaway status changed to pending
        if ($giveawayStatusChangedToPending) {
            $managers = User::whereHas('roles', function ($q) {
                $q->whereIn('slug', ['sales_manager', 'super_admin', 'admin', 'director']);
            })->where('id', '!=', auth()->id())->get();

            foreach ($managers as $manager) {
                Notification::create([
                    'user_id' => $manager->id,
                    'type' => 'giveaway_request',
                    'title' => 'Yêu cầu quà tặng/budget thay đổi',
                    'message' => auth()->user()->name . ' đã cập nhật yêu cầu quà tặng/budget cho hoạt động: "' . $opportunity->name . '" của khách hàng ' . $opportunity->customer_display_name . '.',
                    'link' => route('opportunities.show', $opportunity->id),
                    'icon' => 'fas fa-gift',
                    'color' => 'amber',
                ]);
            }
        }

        // Upload files
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('opportunity-attachments', 'public');
                OpportunityAttachment::create([
                    'opportunity_id' => $opportunity->id,
                    'uploaded_by' => auth()->id(),
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'note' => $request->get('file_note'),
                ]);
            }
        }

        // Notification for completion
        if ($opportunity->status === 'completed' && $oldStatus !== 'completed') {
            $managers = User::whereHas('roles', function ($q) {
                $q->whereIn('slug', ['sales_manager', 'super_admin', 'admin']);
            })->where('id', '!=', auth()->id())->get();

            foreach ($managers as $manager) {
                Notification::create([
                    'user_id' => $manager->id,
                    'type' => 'opportunity_completed',
                    'title' => 'Hoạt động cơ hội hoàn thành',
                    'message' => auth()->user()->name . ' đã hoàn thành báo cáo hoạt động: "' . $opportunity->name . '" của khách hàng ' . $opportunity->customer_display_name . '.',
                    'link' => route('opportunities.show', $opportunity->id),
                    'icon' => 'fas fa-check-circle',
                    'color' => 'blue',
                ]);
            }
        }

        return redirect()->route('opportunities.show', $opportunity->id)->with('success_swal', 'Đã cập nhật hoạt động cơ hội thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Opportunity $opportunity)
    {
        $this->authorize('delete', $opportunity);

        foreach ($opportunity->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
        }

        $opportunity->delete();

        return redirect()->route('opportunities.index')->with('success', 'Đã xóa hoạt động cơ hội.');
    }

    /**
     * API to update status dynamically
     */
    public function updateStatus(Request $request, Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        $validated = $request->validate([
            'status' => 'required|in:draft,planned,confirmed,in_progress,completed,cancelled,postponed',
            'cancel_reason' => 'required_if:status,cancelled|nullable|string',
        ]);

        $oldStatus = $opportunity->status;
        $requestedStatus = $validated['status'];
        $isApprover = auth()->user()->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'director']);
        $isCoordinationRequired = $this->isSolutionPresentationActivity($opportunity->activity_type) || $opportunity->needs_technical || !empty($opportunity->giveaway);

        // 1. Chặn nếu chưa có BOD xác nhận / duyệt đợt trình bày
        if ($isCoordinationRequired && in_array($oldStatus, ['draft', 'planned'], true)) {
            if (in_array($requestedStatus, ['in_progress', 'completed'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Đợt trình bày/hoạt động phối hợp cần được BOD/Manager phê duyệt và điều phối nhân sự/quà tặng trước khi thực hiện.'
                ], 422);
            }
        }

        if ($requestedStatus === 'confirmed' && !$isApprover) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ BOD/Manager mới có quyền phê duyệt & xác nhận hoạt động cơ hội này.'
            ], 403);
        }

        if ($requestedStatus === 'completed' && !in_array($oldStatus, ['confirmed', 'in_progress'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Hoạt động cần được BOD/Manager xác nhận trước khi đánh dấu hoàn thành.'
            ], 422);
        }

        if ($requestedStatus === 'completed' && $opportunity->needs_technical) {
            $technicalTicket = TechnicalTicket::where('opportunity_id', $opportunity->id)->latest('id')->first();
            if (!$technicalTicket || !in_array($technicalTicket->status, ['completed', 'closed'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa thể hoàn thành cơ hội vì ticket Kỹ thuật chưa hoàn thành. Vui lòng chờ Kỹ thuật cập nhật và hoàn tất ticket.'
                ], 422);
            }
        }

        // A solution presentation can have an approved Marketing giveaway
        // request in addition to Technical support. Both workstreams belong to
        // the same Opportunity and must be completed before Sales closes it.
        if ($requestedStatus === 'completed' && $this->isSolutionPresentationActivity($opportunity->activity_type)) {
            if ($opportunity->giveaway_status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa thể hoàn thành Cơ hội vì yêu cầu quà tặng/Marketing đang chờ BOD duyệt.'
                ], 422);
            }

            if ($opportunity->giveaway_status === 'approved') {
                $marketingRequest = MarketingRequest::where('opportunity_id', $opportunity->id)
                    ->where('support_content', 'giveaway')
                    ->latest('id')
                    ->first();
                if ($marketingRequest && $marketingRequest->status !== 'completed') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Chưa thể hoàn thành Cơ hội vì ticket Marketing chưa hoàn thành. Vui lòng chờ Marketing cập nhật kết quả chuẩn bị quà tặng.'
                    ], 422);
                }
            }
        }

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'cancelled') {
            $updateData['cancel_reason'] = $validated['cancel_reason'];
        } else {
            $updateData['cancel_reason'] = null;
        }

        if ($validated['status'] === 'completed') {
            $updateData['completed_at'] = now();
        } else {
            $updateData['completed_at'] = null;
        }

        $opportunity->update($updateData);

        if ($requestedStatus === 'confirmed' && $oldStatus !== 'confirmed' && $opportunity->needs_technical && $opportunity->technical_user_id) {
            $this->createTechnicalTicketForConfirmedOpportunity($opportunity);

            Notification::create([
                'user_id' => $opportunity->technical_user_id,
                'type' => 'opportunity_technical_assigned',
                'title' => 'Yêu cầu phối hợp kỹ thuật đã được duyệt',
                'message' => 'Hoạt động "' . $opportunity->name . '" đã được xác nhận. Vui lòng phối hợp vào ngày ' . $opportunity->activity_date->format('d/m/Y') . '.',
                'link' => route('opportunities.show', $opportunity->id),
                'icon' => 'fas fa-cogs',
                'color' => 'blue',
            ]);
        }

        if ($validated['status'] === 'completed' && $oldStatus !== 'completed') {
            $managers = User::whereHas('roles', function ($q) {
                $q->whereIn('slug', ['sales_manager', 'super_admin', 'admin', 'director']);
            })->where('id', '!=', auth()->id())->get();

            foreach ($managers as $manager) {
                Notification::create([
                    'user_id' => $manager->id,
                    'type' => 'opportunity_completed',
                    'title' => 'Hoạt động cơ hội hoàn thành',
                    'message' => auth()->user()->name . ' đã hoàn thành hoạt động: "' . $opportunity->name . '" của khách hàng ' . $opportunity->customer_display_name . '.',
                    'link' => route('opportunities.show', $opportunity->id),
                    'icon' => 'fas fa-check-circle',
                    'color' => 'blue',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'status_label' => $opportunity->status_label,
            'status_color' => $opportunity->status_color
        ]);
    }

    /**
     * BOD Phê duyệt đợt trình bày & Điều phối nhân sự / vật phẩm Marketing
     */
    public function approvePresentation(Request $request, Opportunity $opportunity)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'director'])) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'technical_user_id' => 'nullable|exists:users,id',
            'items' => 'nullable|array',
            'items.*.item_id' => 'required_with:items|exists:marketing_items,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'giveaway_status' => 'nullable|in:approved,rejected,none',
            'note' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $opportunity, $user, $validated) {
            // 1. Phối hợp Kỹ thuật
            if ($request->filled('technical_user_id')) {
                $opportunity->technical_user_id = $request->technical_user_id;
                $opportunity->needs_technical = true;
            }

            if ($opportunity->needs_technical) {
                $this->createTechnicalTicketForConfirmedOpportunity($opportunity);
            }

            // 2. Xuất kho vật phẩm Marketing
            $exportedItemsSummary = [];
            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    $item = MarketingItem::lockForUpdate()->find($itemData['item_id']);
                    if ($item && $item->stock_quantity >= $itemData['quantity']) {
                        $newStock = $item->stock_quantity - $itemData['quantity'];
                        $item->update(['stock_quantity' => $newStock]);

                        MarketingItemTransaction::create([
                            'marketing_item_id' => $item->id,
                            'type' => 'export',
                            'quantity' => $itemData['quantity'],
                            'remaining_stock' => $newStock,
                            'opportunity_id' => $opportunity->id,
                            'created_by' => $user->id,
                            'reference_code' => 'EXP-OPP-' . $opportunity->id,
                            'note' => 'Xuất quà tặng cho Cơ hội: ' . $opportunity->name . ' (Duyệt bởi ' . $user->name . ')',
                        ]);

                        $exportedItemsSummary[] = "{$item->name} (x{$itemData['quantity']} {$item->unit})";
                    }
                }
            }

            if (!empty($exportedItemsSummary)) {
                $opportunity->giveaway_status = 'approved';
                $summaryText = implode(', ', $exportedItemsSummary);
                if (empty($opportunity->giveaway)) {
                    $opportunity->giveaway = $summaryText;
                } else {
                    $opportunity->giveaway .= " [Đã xuất kho: " . $summaryText . "]";
                }
                $this->createMarketingTicketForApprovedGiveaway($opportunity);
            } elseif ($request->giveaway_status === 'approved' || ($opportunity->giveaway && $opportunity->giveaway_status === 'pending')) {
                $opportunity->giveaway_status = 'approved';
                $this->createMarketingTicketForApprovedGiveaway($opportunity);
            } elseif ($request->giveaway_status === 'rejected') {
                $opportunity->giveaway_status = 'rejected';
            }

            // 3. Phê duyệt trạng thái sang Confirmed
            $opportunity->status = 'confirmed';
            $opportunity->save();

            // 4. Gửi thông báo cho Sales phụ trách
            if ($opportunity->assigned_to) {
                Notification::create([
                    'user_id' => $opportunity->assigned_to,
                    'type' => 'opportunity_approved',
                    'title' => 'Đợt trình bày đã được BOD phê duyệt',
                    'message' => "BOD ({$user->name}) đã phê duyệt đợt trình bày giải pháp cho hoạt động: \"{$opportunity->name}\".",
                    'link' => route('opportunities.show', $opportunity->id),
                    'icon' => 'fas fa-check-double',
                    'color' => 'green',
                ]);
            }
        });

        return back()->with('success', 'Đã phê duyệt đợt trình bày và hoàn tất điều phối nhân sự / vật phẩm quà tặng.');
    }

    /**
     * Turn the approved Technical coordination request into one traceable
     * work item.  The existence check makes confirmation idempotent.
     */
    private function createTechnicalTicketForConfirmedOpportunity(Opportunity $opportunity): ?TechnicalTicket
    {
        if (!$opportunity->needs_technical) {
            return null;
        }

        $existing = TechnicalTicket::where('opportunity_id', $opportunity->id)->first();
        if ($existing) {
            return $existing;
        }

        $assignedTo = $opportunity->technical_user_id;
        $ticket = TechnicalTicket::create([
            'code' => TechnicalTicket::generateCode(),
            'title' => 'Phối hợp kỹ thuật: ' . $opportunity->name,
            'description' => trim(implode("\n\n", array_filter([
                $opportunity->description,
                $opportunity->materials_required ? 'Vật tư/yêu cầu: ' . $opportunity->materials_required : null,
                'Thời gian: ' . optional($opportunity->activity_date)->format('d/m/Y') . ' ' . ($opportunity->start_time ?: ''),
            ]))),
            'status' => $assignedTo ? 'assigned' : 'open',
            'work_type' => 'event',
            'priority' => 'medium',
            'project_id' => $opportunity->project_id,
            'opportunity_id' => $opportunity->id,
            'customer_id' => $opportunity->customer_id,
            'assigned_to' => $assignedTo,
            'created_by' => auth()->id(),
            'sales_owner_id' => $opportunity->assigned_to,
            'department' => 'Technical',
            'project_name' => $opportunity->project?->name,
            'sla_deadline' => TechnicalTicket::calculateSlaDeadline('medium'),
        ]);

        if ($assignedTo) {
            $ticket->assignedEngineers()->syncWithoutDetaching([$assignedTo]);
        }

        return $ticket;
    }

    /** Users eligible to receive an opportunity's Technical coordination ticket. */
    private function technicalAssignees()
    {
        return User::query()
            ->where(function ($query) {
                $query->whereIn('department', ['Technical', 'Tech', 'Kỹ thuật'])
                    ->orWhereHas('roles', function ($roles) {
                        $roles->whereIn('slug', ['technical_engineer', 'technical_lead']);
                    });
            })
            ->orderBy('name');
    }

    /** Marketing and Technical coordination share an Opportunity only for demos. */
    private function isSolutionPresentationActivity(?string $activityType): bool
    {
        return in_array($activityType, ['demo_online', 'demo_offline'], true);
    }

    /**
     * API to upload an attachment file
     */
    public function uploadAttachment(Request $request, Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB limit
            'note' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('opportunity-attachments', 'public');

            $attachment = OpportunityAttachment::create([
                'opportunity_id' => $opportunity->id,
                'uploaded_by' => auth()->id(),
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'note' => $request->get('note'),
            ]);

            return response()->json([
                'success' => true,
                'attachment' => [
                    'id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_size_formatted' => $attachment->file_size_formatted,
                    'file_icon' => $attachment->file_icon,
                    'note' => $attachment->note,
                    'delete_url' => route('opportunities.delete-attachment', $attachment->id),
                    'download_url' => asset('storage/' . $attachment->file_path),
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Không tìm thấy file để upload.']);
    }

    /**
     * API to delete an attachment file
     */
    public function deleteAttachment(OpportunityAttachment $attachment)
    {
        $this->authorize('update', $attachment->opportunity);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa file đính kèm thành công.']);
    }

    /**
     * Redirect to project creation pre-filled with opportunity details
     */
    public function convertToProject(Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        return redirect()->route('projects.create', [
            'opportunity_id' => $opportunity->id,
            'customer_type' => $opportunity->customer_type,
            'customer_id' => $opportunity->customer_id,
            'contact_id' => $opportunity->contact_id,
            'eu_name_vi' => $opportunity->eu_company_name,
            'eu_contact_name' => $opportunity->eu_contact_name,
            'eu_phone' => $opportunity->eu_phone,
            'eu_email' => $opportunity->eu_email,
            'eu_position' => $opportunity->eu_position,
            'name' => $opportunity->name,
            'description' => $opportunity->description,
        ]);
    }

    /**
     * Approve giveaway request.
     */
    public function approveGiveaway(Opportunity $opportunity)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'director'])) {
            abort(403, 'Unauthorized action.');
        }

        if (blank($opportunity->giveaway)) {
            return back()->with('error', 'Hoạt động này chưa có thông tin quà tặng để duyệt.');
        }

        $opportunity->update(['giveaway_status' => 'approved']);
        $marketingRequest = $this->createMarketingTicketForApprovedGiveaway($opportunity);

        // Notify the assigned sales rep
        if ($opportunity->assigned_to) {
            Notification::create([
                'user_id' => $opportunity->assigned_to,
                'type' => 'giveaway_approved',
                'title' => 'Yêu cầu quà tặng được duyệt',
                'message' => 'Yêu cầu quà tặng/budget cho hoạt động "' . $opportunity->name . '" đã được ' . $user->name . ' duyệt.',
                'link' => route('opportunities.show', $opportunity->id),
                'icon' => 'fas fa-gift',
                'color' => 'green',
            ]);
        }

        // Marketing receives a concrete queue item, not merely a notification
        // that can disappear from the bell after it is read.
        $marketingUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('slug', ['marketing', 'marketing_manager', 'super_admin', 'admin']);
        })->where('id', '!=', auth()->id())->get();

        foreach ($marketingUsers as $marketingUser) {
            Notification::create([
                'user_id' => $marketingUser->id,
                'type' => 'marketing_giveaway_ticket',
                'title' => 'Ticket Marketing: chuẩn bị quà tặng',
                'message' => 'Quà tặng cho hoạt động "' . $opportunity->name . '" đã được duyệt và cần Marketing xử lý.',
                'link' => route('marketing-events.index', ['tab' => 'requests', 'opportunity_id' => $opportunity->id]),
                'icon' => 'fas fa-gift',
                'color' => 'purple',
            ]);
        }

        return back()->with('success', 'Đã duyệt yêu cầu quà tặng/budget.');
    }

    /** Create one traceable Marketing work request for an approved giveaway. */
    private function createMarketingTicketForApprovedGiveaway(Opportunity $opportunity): ?MarketingRequest
    {
        if (blank($opportunity->giveaway)) {
            return null;
        }

        $existing = MarketingRequest::where('opportunity_id', $opportunity->id)
            ->where('support_content', 'giveaway')
            ->first();
        if ($existing) {
            return $existing;
        }

        $ticket = MarketingTicket::create([
            'opportunity_id' => $opportunity->id,
            'type' => 'others',
            'status' => 'in_progress',
            'created_by' => auth()->id(),
        ]);

        $technicalTicket = TechnicalTicket::where('opportunity_id', $opportunity->id)
            ->latest('id')
            ->first();

        return MarketingRequest::create([
            'marketing_ticket_id' => $ticket->id,
            'opportunity_id' => $opportunity->id,
            'support_team' => 'marketing',
            'pic_type' => 'all',
            'support_content' => 'giveaway',
            'support_content_other' => 'Chuẩn bị quà tặng đã duyệt',
            'description' => trim(implode("\n\n", array_filter([
                'Hoạt động: ' . $opportunity->name,
                'Khách hàng: ' . $opportunity->customer_display_name,
                'Quà tặng/Budget: ' . $opportunity->giveaway,
                $opportunity->activity_date ? 'Ngày hoạt động: ' . $opportunity->activity_date->format('d/m/Y') : null,
                $opportunity->materials_required ? 'Yêu cầu chuẩn bị chung: ' . $opportunity->materials_required : null,
                $opportunity->needs_technical
                    ? 'Kỹ thuật phối hợp: ' . ($technicalTicket
                        ? $technicalTicket->code . ' (' . $technicalTicket->status_label . ')'
                        : ($opportunity->technicalUser?->name ?: 'Đang chờ tạo ticket Kỹ thuật'))
                    : null,
                $opportunity->notes,
            ]))),
            'deadline' => $opportunity->activity_date,
            'status' => 'received',
        ]);
    }

    /**
     * Reject giveaway request.
     */
    public function rejectGiveaway(Opportunity $opportunity)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'director'])) {
            abort(403, 'Unauthorized action.');
        }

        $opportunity->update(['giveaway_status' => 'rejected']);

        // Notify the assigned sales rep
        if ($opportunity->assigned_to) {
            Notification::create([
                'user_id' => $opportunity->assigned_to,
                'type' => 'giveaway_rejected',
                'title' => 'Yêu cầu quà tặng bị từ chối',
                'message' => 'Yêu cầu quà tặng/budget cho hoạt động "' . $opportunity->name . '" đã bị ' . $user->name . ' từ chối.',
                'link' => route('opportunities.show', $opportunity->id),
                'icon' => 'fas fa-times-circle',
                'color' => 'red',
            ]);
        }

        return back()->with('success', 'Đã từ chối yêu cầu quà tặng/budget.');
    }
}
