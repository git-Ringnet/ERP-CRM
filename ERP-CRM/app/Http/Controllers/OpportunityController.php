<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\OpportunityAttachment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Reminder;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $activityTypes = Opportunity::ACTIVITY_TYPES;

        $prefill = [];
        if ($request->has('customer_id')) {
            $prefill['customer_id'] = $request->get('customer_id');
        }

        $technicalManagerId = User::where(function($q) {
            $q->where('position', 'like', '%Technical Manager%')
              ->orWhere('position', 'like', '%Presales Manager%')
              ->orWhere('position', 'like', '%IT Manager%')
              ->orWhere('position', 'like', '%System Administrator%');
        })->first()?->id ?? User::where('email', 'admin@erp.com')->first()?->id ?? null;

        return view('opportunities.create', compact('customers', 'users', 'activityTypes', 'prefill', 'technicalManagerId'));
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

        // Notifications
        if ($opportunity->needs_technical && $opportunity->technical_user_id) {
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
            $q->whereIn('slug', ['sales_manager', 'super_admin', 'admin']);
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
        
        $users = User::orderBy('name')->get();
        $statuses = Opportunity::STATUSES;
        $ratings = Opportunity::POTENTIAL_RATINGS;

        return view('opportunities.show', compact('opportunity', 'users', 'statuses', 'ratings'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Opportunity $opportunity)
    {
        $this->authorize('update', $opportunity);

        $customers = Customer::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $activityTypes = Opportunity::ACTIVITY_TYPES;

        $technicalManagerId = User::where(function($q) {
            $q->where('position', 'like', '%Technical Manager%')
              ->orWhere('position', 'like', '%Presales Manager%')
              ->orWhere('position', 'like', '%IT Manager%')
              ->orWhere('position', 'like', '%System Administrator%');
        })->first()?->id ?? User::where('email', 'admin@erp.com')->first()?->id ?? null;

        return view('opportunities.edit', compact('opportunity', 'customers', 'users', 'activityTypes', 'technicalManagerId'));
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

        // Handle giveaway status changes
        if ($opportunity->giveaway !== $request->giveaway) {
            $validated['giveaway_status'] = !empty($request->giveaway) ? 'pending' : 'none';
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
                $q->whereIn('slug', ['sales_manager', 'super_admin', 'admin']);
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

        if ($validated['status'] === 'completed' && $oldStatus !== 'completed') {
            $managers = User::whereHas('roles', function ($q) {
                $q->whereIn('slug', ['sales_manager', 'super_admin', 'admin']);
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
        if (!$user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            abort(403, 'Unauthorized action.');
        }

        $opportunity->update(['giveaway_status' => 'approved']);

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

        return back()->with('success', 'Đã duyệt yêu cầu quà tặng/budget.');
    }

    /**
     * Reject giveaway request.
     */
    public function rejectGiveaway(Opportunity $opportunity)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
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
