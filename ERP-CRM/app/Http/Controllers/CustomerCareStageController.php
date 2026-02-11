<?php

namespace App\Http\Controllers;

use App\Models\CustomerCareStage;
use App\Models\Customer;
use App\Models\User;
use App\Http\Requests\CustomerCareStageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerCareStageController extends Controller
{
    /**
     * Display a listing of customer care stages with filters
     */
    public function index(Request $request)
    {
        $query = CustomerCareStage::with(['customer', 'assignedTo', 'createdBy']);

        // Filter by stage
        if ($request->filled('stage')) {
            $query->byStage($request->stage);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        // Filter by assigned user
        if ($request->filled('assigned_to')) {
            $query->assignedTo($request->assigned_to);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('start_date', '<=', $request->date_to);
        }

        // Search by customer name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter overdue
        if ($request->filled('overdue') && $request->overdue === 'yes') {
            $query->overdue();
        }

        $careStages = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get filter options
        $customers = Customer::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('customer-care-stages.index', compact('careStages', 'customers', 'users'));
    }

    /**
     * Show the form for creating a new care stage
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $templates = \App\Models\MilestoneTemplate::with('milestones')->get()->groupBy('stage_type');

        return view('customer-care-stages.create', compact('customers', 'users', 'templates'));
    }

    /**
     * Store a newly created care stage
     */
    public function store(CustomerCareStageRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = auth()->id();

        CustomerCareStage::create($validated);

        return redirect()->route('customer-care-stages.index')
            ->with('success', 'Giai đoạn chăm sóc đã được tạo thành công.');
    }

    /**
     * Display the specified care stage
     */
    public function show(CustomerCareStage $customerCareStage)
    {
        $customerCareStage->load([
            'customer',
            'assignedTo',
            'createdBy',
            'milestones' => function($query) {
                $query->with('completedBy')->orderBy('order');
            },
            'activities' => function($query) {
                $query->with('user', 'createdBy')->latest()->limit(20);
            },
            'communicationLogs' => function($query) {
                $query->with('user')->latest('occurred_at')->limit(50);
            }
        ]);

        $users = User::orderBy('name')->get();
        
        // Get milestone templates for this stage type
        $templates = \App\Models\MilestoneTemplate::forStage($customerCareStage->stage)
            ->with('milestones')
            ->get();

        return view('customer-care-stages.show', compact('customerCareStage', 'users', 'templates'));
    }

    /**
     * Show the form for editing the care stage
     */
    public function edit(CustomerCareStage $customerCareStage)
    {
        $customers = Customer::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('customer-care-stages.edit', compact('customerCareStage', 'customers', 'users'));
    }

    /**
     * Update the specified care stage
     */
    public function update(CustomerCareStageRequest $request, CustomerCareStage $customerCareStage)
    {
        $validated = $request->validated();

        // Auto-set actual completion date when status changes to completed
        if ($validated['status'] === 'completed' && $customerCareStage->status !== 'completed') {
            $validated['actual_completion_date'] = now()->toDateString();
            $validated['completion_percentage'] = 100;
        }

        $customerCareStage->update($validated);

        return redirect()->route('customer-care-stages.show', $customerCareStage)
            ->with('success', 'Giai đoạn chăm sóc đã được cập nhật thành công.');
    }

    /**
     * Remove the specified care stage
     */
    public function destroy(CustomerCareStage $customerCareStage)
    {
        // Check if there are related activities
        if ($customerCareStage->activities()->exists()) {
            return redirect()->route('customer-care-stages.index')
                ->with('error', 'Không thể xóa giai đoạn chăm sóc này vì còn hoạt động liên quan.');
        }

        $customerCareStage->delete();

        return redirect()->route('customer-care-stages.index')
            ->with('success', 'Giai đoạn chăm sóc đã được xóa thành công.');
    }

    /**
     * Update progress percentage
     */
    public function updateProgress(Request $request, CustomerCareStage $customerCareStage)
    {
        $request->validate([
            'completion_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $customerCareStage->update([
            'completion_percentage' => $request->completion_percentage,
        ]);

        // Auto-complete if 100%
        if ($request->completion_percentage == 100 && $customerCareStage->status !== 'completed') {
            $customerCareStage->update([
                'status' => 'completed',
                'actual_completion_date' => now()->toDateString(),
            ]);
        }

        return back()->with('success', 'Tiến độ đã được cập nhật.');
    }

    /**
     * Assign care stage to a user
     */
    public function assignUser(Request $request, CustomerCareStage $customerCareStage)
    {
        $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $customerCareStage->update([
            'assigned_to' => $request->assigned_to,
        ]);

        return back()->with('success', 'Đã phân công thành công.');
    }

    /**
     * Dashboard with statistics
     */
    public function dashboard()
    {
        // Statistics
        $totalActive = CustomerCareStage::whereIn('status', ['not_started', 'in_progress'])->count();
        $totalCompleted = CustomerCareStage::where('status', 'completed')->count();
        $totalOverdue = CustomerCareStage::overdue()->count();
        
        // Completion rate
        $total = CustomerCareStage::count();
        $completionRate = $total > 0 ? round(($totalCompleted / $total) * 100, 1) : 0;

        // By stage
        $byStage = CustomerCareStage::select('stage', DB::raw('count(*) as count'))
            ->groupBy('stage')
            ->get();

        // By status
        $byStatus = CustomerCareStage::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // By priority
        $byPriority = CustomerCareStage::select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get();

        // Recent care stages
        $recentCareStages = CustomerCareStage::with(['customer', 'assignedTo'])
            ->latest()
            ->limit(10)
            ->get();

        // Upcoming milestones
        $upcomingMilestones = \App\Models\CareMilestone::with(['customerCareStage.customer'])
            ->where('is_completed', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', now())
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        // Overdue care stages
        $overdueCareStages = CustomerCareStage::with(['customer', 'assignedTo'])
            ->overdue()
            ->orderBy('target_completion_date')
            ->limit(10)
            ->get();

        // Action Reminders (Phase A)
        // Overdue actions
        $overdueActions = CustomerCareStage::whereNotNull('next_action')
            ->where('next_action_completed', false)
            ->where('next_action_due_at', '<', now())
            ->with(['customer', 'assignedTo'])
            ->orderBy('next_action_due_at')
            ->limit(5)
            ->get();

        // Due today
        $dueTodayActions = CustomerCareStage::whereNotNull('next_action')
            ->where('next_action_completed', false)
            ->whereDate('next_action_due_at', today())
            ->with(['customer', 'assignedTo'])
            ->orderBy('next_action_due_at')
            ->limit(5)
            ->get();

        // Upcoming (next 7 days)
        $upcomingActions = CustomerCareStage::whereNotNull('next_action')
            ->where('next_action_completed', false)
            ->whereBetween('next_action_due_at', [now()->addDay(), now()->addDays(7)])
            ->with(['customer', 'assignedTo'])
            ->orderBy('next_action_due_at')
            ->limit(5)
            ->get();

        return view('customer-care-stages.dashboard', compact(
            'totalActive',
            'totalCompleted',
            'totalOverdue',
            'completionRate',
            'byStage',
            'byStatus',
            'byPriority',
            'recentCareStages',
            'upcomingMilestones',
            'overdueCareStages',
            'overdueActions',
            'dueTodayActions',
            'upcomingActions'
        ));
    }

    /**
     * Get customer details via AJAX for context card
     */
    public function getCustomerDetails(Customer $customer)
    {
        return response()->json([
            'id' => $customer->id,
            'code' => $customer->code,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'type' => $customer->type ?? 'regular',
            'care_history_count' => $customer->careStages()->count(),
            'total_revenue' => 0, // TODO: Calculate from sales
            'created_at_formatted' => $customer->created_at->format('d/m/Y'),
        ]);
    }

    /**
     * Complete next action with outcome tracking
     */
    public function completeAction(Request $request, CustomerCareStage $customerCareStage)
    {
        $outcome = $request->input('outcome');
        
        // Mark action as completed
        $customerCareStage->update([
            'next_action_completed' => true,
        ]);
        
        // Create communication log based on outcome
        $sentimentMap = [
            'success' => 'positive',
            'no_answer' => 'neutral',
            'reschedule' => 'neutral',
            'not_interested' => 'negative',
        ];
        
        $descriptionMap = [
            'success' => 'Đã liên hệ thành công và hoàn tất hành động.',
            'no_answer' => 'Gọi điện nhưng khách hàng không bắt máy. Cần gọi lại sau.',
            'reschedule' => 'Khách hàng yêu cầu hẹn lại vào thời gian khác.',
            'not_interested' => 'Khách hàng hiện tại không quan tâm đến dịch vụ/sản phẩm.',
        ];
        
        \App\Models\CommunicationLog::create([
            'customer_care_stage_id' => $customerCareStage->id,
            'user_id' => auth()->id(),
            'type' => strpos($customerCareStage->next_action, 'Gọi') !== false ? 'call' : 'other',
            'subject' => 'Kết quả: ' . $customerCareStage->next_action,
            'description' => $descriptionMap[$outcome] ?? 'Đã hoàn thành hành động.',
            'sentiment' => $sentimentMap[$outcome] ?? 'neutral',
            'occurred_at' => now(),
        ]);
        
        // Set next action based on outcome
        if ($outcome === 'no_answer' || $outcome === 'reschedule') {
            // Auto-suggest next action for follow-up
            $customerCareStage->update([
                'next_action' => 'Gọi lại khách hàng',
                'next_action_due_at' => now()->addHours(24), // Tomorrow
                'next_action_completed' => false,
            ]);
            
            $message = 'Đã ghi nhận kết quả và tự động tạo hành động tiếp theo: "Gọi lại khách hàng" vào ngày mai.';
        } else {
            $message = 'Đã ghi nhận kết quả hành động thành công.';
        }
        
        return back()->with('success', $message);
    }

    /**
     * Quick snooze (reschedule) next action
     */
    public function snoozeAction(Request $request, CustomerCareStage $customerCareStage)
    {
        $snooze = $request->input('snooze');
        
        $newDueDate = match($snooze) {
            '1h' => now()->addHour(),
            'tomorrow_morning' => now()->addDay()->setTime(9, 0),
            'next_monday' => now()->next('Monday')->setTime(9, 0),
            default => now()->addHours(24),
        };
        
        $customerCareStage->update([
            'next_action_due_at' => $newDueDate,
        ]);
        
        $timeMap = [
            '1h' => '+1 giờ (' . $newDueDate->format('H:i') . ')',
            'tomorrow_morning' => 'sáng mai lúc 9h (' . $newDueDate->format('d/m') . ')',
            'next_monday' => 'Thứ 2 tuần sau lúc 9h (' . $newDueDate->format('d/m') . ')',
        ];
        
        return back()->with('success', 'Đã dời lịch hành động ' . ($timeMap[$snooze] ?? 'thành công') . '.');
    }
}
