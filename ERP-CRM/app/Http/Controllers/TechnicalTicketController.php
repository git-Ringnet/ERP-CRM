<?php

namespace App\Http\Controllers;

use App\Models\TechnicalTicket;
use App\Models\TechnicalTicketAttachment;
use App\Models\TechnicalTicketComment;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Opportunity;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TechnicalTicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of technical tickets.
     */
    public function index(Request $request)
    {
        if (!Gate::allows('view_technical_tickets')) {
            abort(403, 'Bạn không có quyền xem ticket kỹ thuật.');
        }

        // Auto-close: Tickets confirmed completed (status=completed) for more than 3 days → auto-close
        TechnicalTicket::where('status', 'completed')
            ->where('resolved_at', '<=', Carbon::now()->subDays(3))
            ->update(['status' => 'closed']);

        $query = TechnicalTicket::with(['customer', 'project', 'assignedTo', 'creator', 'assignedEngineers', 'activeEngineers', 'formerEngineers'])
            ->withCount(['comments', 'supportLogs', 'attachments']);

        $currentUser = auth()->user();
        $currentUserId = $currentUser->id;
        $isManagerOrAdmin = $currentUser->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechStaff = $currentUser->hasAnyRole(['technical_engineer', 'technical_lead']);

        if (!$isManagerOrAdmin) {
            $query->where(function ($q) use ($currentUserId, $isTechStaff) {
                $q->where('created_by', $currentUserId)
                  ->orWhere('sales_owner_id', $currentUserId)
                  ->orWhere('team_lead_id', $currentUserId)
                  ->orWhereJsonContains('co_lead_ids', (int)$currentUserId)
                  ->orWhereJsonContains('co_lead_ids', (string)$currentUserId)
                  ->orWhereHas('assignedEngineers', function ($sq) use ($currentUserId) {
                      $sq->where('users.id', $currentUserId);
                  });
                
                // If technical staff: can view tickets assigned to any non-lead engineer
                if ($isTechStaff) {
                    $q->orWhereHas('activeEngineers', function ($sq) {
                        $sq->whereRaw('users.id != technical_tickets.team_lead_id');
                    });
                }
            });
        }

        // Search code or title
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('status')) {
            if ($request->input('status') !== 'all') {
                $query->where('status', $request->input('status'));
            }
        } else {
            // Mặc định: Ẩn các ticket Đã đóng (closed) và Hoàn thành (completed)
            // Chỉ hiển thị khi người dùng tìm kiếm từ khóa hoặc chủ động lọc trạng thái
            if (!$request->filled('search')) {
                $query->whereNotIn('status', ['completed', 'closed']);
            }
        }

        if ($request->filled('work_type')) {
            $query->where('work_type', $request->input('work_type'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('sla_status')) {
            $sla = $request->input('sla_status');
            $now = Carbon::now();
            if ($sla === 'overdue') {
                $query->whereNotNull('sla_deadline')
                    ->where(function ($q) use ($now) {
                        $q->where(function ($sq) {
                            $sq->whereIn('status', ['completed', 'closed'])
                               ->whereColumn('resolved_at', '>', 'sla_deadline');
                        })->orWhere(function ($sq) use ($now) {
                            $sq->whereNotIn('status', ['completed', 'closed'])
                               ->where('sla_deadline', '<', $now);
                        });
                    });
            } elseif ($sla === 'ontime') {
                $query->where(function ($q) use ($now) {
                    $q->where(function ($sq) {
                        $sq->whereIn('status', ['completed', 'closed'])
                           ->whereColumn('resolved_at', '<=', 'sla_deadline');
                    })->orWhere(function ($sq) use ($now) {
                        $sq->whereNotIn('status', ['completed', 'closed'])
                           ->where(function ($tsq) use ($now) {
                               $tsq->whereNull('sla_deadline')
                                   ->orWhere('sla_deadline', '>=', $now);
                           });
                    });
                });
            }
        }

        // Sắp xếp: Ưu tiên những ticket có trao đổi (bình luận / nhật ký / cập nhật) gần đây nhất lên đầu
        $query->select('technical_tickets.*')
            ->selectRaw("GREATEST(
                COALESCE(technical_tickets.updated_at, technical_tickets.created_at),
                COALESCE((SELECT MAX(created_at) FROM technical_ticket_comments WHERE technical_ticket_comments.technical_ticket_id = technical_tickets.id), '1970-01-01 00:00:00'),
                COALESCE((SELECT MAX(created_at) FROM technical_support_logs WHERE technical_support_logs.technical_ticket_id = technical_tickets.id), '1970-01-01 00:00:00')
            ) as last_activity_at")
            ->orderBy('last_activity_at', 'desc')
            ->orderBy('technical_tickets.id', 'desc');

        $tickets = $query->paginate(15);
        
        $engineers = User::where('status', 'active')
            ->whereHas('roles', function($q) {
                $q->whereIn('slug', ['technical_lead', 'technical_engineer']);
            })
            ->orderBy('name')
            ->get();
        $customers = Customer::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $projects = $this->getProjectsForTicketUser();
        $salesUsers = User::where('status', 'active')
            ->whereHas('roles', function($q) {
                $q->whereIn('slug', ['sales_manager', 'sales_staff', 'sales', 'super_admin', 'director']);
            })
            ->orderBy('name')
            ->get();

        return view('technical.tickets.index', compact('tickets', 'engineers', 'customers', 'suppliers', 'projects', 'salesUsers'));
    }

    /**
     * Show the form for creating a new technical ticket.
     */
    public function create(Request $request)
    {
        if (!Gate::allows('create_technical_tickets')) {
            abort(403, 'Bạn không có quyền tạo ticket kỹ thuật.');
        }

        $customers = Customer::orderBy('name')->get();
        $projects = $this->getProjectsForTicketUser();
        $selectedProjectId = $request->input('project_id');
        $opportunities = Opportunity::orderBy('name')->get();
        $sales = Sale::orderBy('code')->get();
        $suppliers = Supplier::orderBy('name')->get(); // Vendors
        $engineers = User::where('status', 'active')
            ->where(function($q) {
                $q->whereHas('roles', function($rq) {
                    $rq->whereIn('slug', ['technical_lead', 'technical_engineer']);
                })->orWhereHas('userGroups');
            })
            ->with(['userGroups:id,name,code', 'roles:id,name,slug'])
            ->orderBy('name')
            ->get();
        if ($engineers->isEmpty()) {
            $engineers = User::where('status', 'active')->with(['userGroups:id,name,code', 'roles:id,name,slug'])->orderBy('name')->get();
        }
        $users = User::where('status', 'active')->orderBy('name')->get();
        
        $userGroups = UserGroup::where('status', 'active')->with(['leader', 'members'])->orderBy('name')->get();
        $leads = $this->getTechnicalLeads();

        $departments = User::whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');

        $currentUser = auth()->user();
        $isTechnicalLead = $currentUser->hasAnyRole(['super_admin', 'director', 'admin', 'technical_lead'])
            || UserGroup::where('leader_id', $currentUser->id)->exists();

        return view('technical.tickets.create', compact('customers', 'projects', 'opportunities', 'sales', 'suppliers', 'engineers', 'users', 'userGroups', 'leads', 'departments', 'selectedProjectId', 'isTechnicalLead'));
    }

    /**
     * Check duplicate / existing tickets for a project or project name.
     */
    public function checkDuplicate(Request $request)
    {
        $projectId = $request->input('project_id');
        $projectName = trim((string) $request->input('project_name'));
        $excludeId = $request->input('exclude_id');

        if (!$projectId && mb_strlen($projectName) < 2) {
            return response()->json([
                'has_duplicate' => false,
                'has_active' => false,
                'count' => 0,
                'duplicates' => []
            ]);
        }

        $query = TechnicalTicket::with(['creator', 'assignedEngineers', 'customer', 'project']);

        if ($projectId) {
            $query->where(function ($q) use ($projectId, $projectName) {
                $q->where('project_id', $projectId);
                if (mb_strlen($projectName) >= 3) {
                    $q->orWhere('project_name', 'like', "%{$projectName}%")
                      ->orWhere('title', 'like', "%{$projectName}%");
                }
            });
        } else {
            $query->where(function ($q) use ($projectName) {
                $q->where('project_name', 'like', "%{$projectName}%")
                  ->orWhere('title', 'like', "%{$projectName}%");
            });
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $tickets = $query->latest()->limit(10)->get();

        $duplicates = $tickets->map(function ($ticket) {
            $engineers = $ticket->assignedEngineers->pluck('name')->join(', ');
            return [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'title' => $ticket->title,
                'work_type' => $ticket->work_type,
                'work_type_label' => $ticket->work_type_label,
                'status' => $ticket->status,
                'status_label' => $ticket->status_label,
                'status_color' => $ticket->status_color,
                'is_active_status' => in_array($ticket->status, ['open', 'assigned', 'pending', 'escalate']),
                'creator_name' => $ticket->creator ? $ticket->creator->name : '-',
                'engineers' => $engineers ?: 'Chưa phân công',
                'created_at' => $ticket->created_at ? $ticket->created_at->format('d/m/Y H:i') : '-',
                'created_at_humans' => $ticket->created_at ? $ticket->created_at->diffForHumans() : '-',
                'show_url' => route('technical-tickets.show', $ticket->id)
            ];
        });

        $activeCount = $duplicates->where('is_active_status', true)->count();

        return response()->json([
            'has_duplicate' => $duplicates->isNotEmpty(),
            'has_active' => $activeCount > 0,
            'count' => $duplicates->count(),
            'active_count' => $activeCount,
            'duplicates' => $duplicates->values()
        ]);
    }

    public function store(Request $request)
    {
        if (!Gate::allows('create_technical_tickets')) {
            abort(403, 'Bạn không có quyền tạo ticket kỹ thuật.');
        }

        $currentUser = auth()->user();
        $isManagerOrAdmin = $currentUser->hasAnyRole(['super_admin', 'director', 'admin', 'sales_manager']);
        $isTechLeadRole = $currentUser->hasRole('technical_lead');
        $isGroupLead = UserGroup::where('leader_id', $currentUser->id)->exists();
        $isTechnicalLead = $isTechLeadRole || $isManagerOrAdmin || $isGroupLead;

        // If creator is not technical lead, do not allow setting assigned_to or co_lead_ids
        if (!$isTechnicalLead) {
            $request->merge([
                'assigned_to' => [],
                'co_lead_ids' => []
            ]);
        }

        // Normalize assigned_to to array if single value
        if ($request->has('assigned_to') && !is_array($request->assigned_to)) {
            $request->merge(['assigned_to' => array_filter([$request->assigned_to])]);
        }

        if ($request->has('co_lead_ids') && !is_array($request->co_lead_ids)) {
            $request->merge(['co_lead_ids' => array_filter([$request->co_lead_ids])]);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'work_type' => 'required|string',
            'priority' => 'required|string|in:high,medium',
            'team_lead_id' => 'required|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'sale_id' => 'nullable|exists:sales,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'assigned_to' => 'nullable|array',
            'assigned_to.*' => 'exists:users,id',
            'sla_deadline' => 'nullable|date',
            'description' => 'nullable|string',
            'sales_owner_id' => 'nullable|exists:users,id',
            'user_group_id' => 'nullable|exists:user_groups,id',
            'co_lead_ids' => 'nullable|array',
            'co_lead_ids.*' => 'exists:users,id',
            'department' => 'nullable|string|max:255',
            'project_name' => 'nullable|string|max:255',
            'solution' => 'nullable|string',
            'ticket_details' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:20480', // 20MB max per file
        ], [
            'team_lead_id.required' => 'Vui lòng chọn Trưởng nhóm (Lead chính) phụ trách quản lý ticket này.',
            'team_lead_id.exists' => 'Trưởng nhóm được chọn không hợp lệ.',
        ]);

        $data = $request->all();

        // Constraint 1: When changing status to assigned or in_progress, must specify assigned_to
        $assignedIds = $request->assigned_to ? (array) $request->assigned_to : [];
        if (in_array($request->status ?? 'open', ['assigned', 'in_progress']) && empty($assignedIds)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['assigned_to' => 'Trạng thái "' . ($request->status === 'assigned' ? 'Đã phân công' : 'Đang thực hiện') . '" yêu cầu phải chỉ định Kỹ sư thực hiện.']);
        }

        // Constraint 2: Self-Pickup & Assignment limits on creation
        $currentUserId = auth()->id();
        $isTeamLead = $isTechnicalLead;

        if (!$isTeamLead && !empty($assignedIds)) {
            if (count($assignedIds) > 1 || $assignedIds[0] != $currentUserId) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['assigned_to' => 'Chỉ Team Lead hoặc Quản trị viên mới có quyền phân công cho Kỹ sư khác. Kỹ sư chỉ được phép tự nhận (self-pickup) ticket cho chính mình.']);
            }
            if (in_array($request->work_type, TechnicalTicket::getLeaderOnlyWorkTypes())) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['assigned_to' => 'Đối với loại ticket này, chỉ Technical Team Lead hoặc Quản trị viên mới có quyền tiếp nhận và phân công. Kỹ sư không được phép tự nhận (self-pickup).']);
            }
        }

        $data['code'] = TechnicalTicket::generateCode();
        $data['created_by'] = Auth::id();

        // Calculate SLA if blank
        if (empty($data['sla_deadline'])) {
            $data['sla_deadline'] = TechnicalTicket::calculateSlaDeadline($data['priority']);
        }
        
        $data['assigned_to'] = !empty($assignedIds) ? $assignedIds[0] : null;

        // Normalize POC devices if present
        if (isset($data['ticket_details']['poc_devices']) && is_array($data['ticket_details']['poc_devices'])) {
            $filteredDevices = [];
            foreach ($data['ticket_details']['poc_devices'] as $dev) {
                if (!empty($dev['name'])) {
                    $filteredDevices[] = [
                        'name' => trim($dev['name']),
                        'quantity' => max(1, intval($dev['quantity'] ?? 1)),
                        'note' => trim($dev['note'] ?? ''),
                    ];
                }
            }
            $data['ticket_details']['poc_devices'] = $filteredDevices;
            if (!empty($filteredDevices)) {
                $data['ticket_details']['poc_model'] = implode(', ', array_filter(array_column($filteredDevices, 'name')));
                $data['ticket_details']['poc_quantity'] = array_sum(array_column($filteredDevices, 'quantity'));
            }
        }

        // If assigned to an engineer and status is default (open), switch to assigned
        if (!empty($assignedIds) && (!isset($data['status']) || $data['status'] === 'open')) {
            $data['status'] = 'assigned';
        }

        // Resolve customer_id, project_name, and sales_owner_id automatically from system links
        if (!empty($data['project_id'])) {
            $project = \App\Models\Project::find($data['project_id']);
            if ($project) {
                if (empty($data['customer_id'])) {
                    $data['customer_id'] = $project->customer_id;
                }
                if (empty($data['project_name'])) {
                    $data['project_name'] = $project->name;
                }
                if (empty($data['sales_owner_id'])) {
                    $data['sales_owner_id'] = $project->manager_id;
                }
            }
        }
        if (empty($data['customer_id']) && !empty($data['opportunity_id'])) {
            $opportunity = \App\Models\Opportunity::find($data['opportunity_id']);
            if ($opportunity) {
                $data['customer_id'] = $opportunity->customer_id;
            }
        }
        if (empty($data['customer_id']) && !empty($data['sale_id'])) {
            $sale = \App\Models\Sale::find($data['sale_id']);
            if ($sale) {
                $data['customer_id'] = $sale->customer_id;
            }
        }

        $ticket = TechnicalTicket::create($data);

        if (!empty($assignedIds)) {
            $ticket->assignedEngineers()->sync($assignedIds);
        }

        // Notify designated primary team lead
        if (!empty($ticket->team_lead_id) && $ticket->team_lead_id != Auth::id()) {
            \App\Models\Notification::create([
                'user_id' => $ticket->team_lead_id,
                'type' => 'technical_ticket',
                'title' => 'Bạn được chỉ định phụ trách Ticket Kỹ thuật mới',
                'message' => "Bạn là Lead chính phụ trách ticket: {$ticket->code} - {$ticket->title}. Vui lòng kiểm tra và phân công kỹ sư xử lý.",
                'link' => route('technical-tickets.show', $ticket->id),
                'icon' => 'user-tie',
                'color' => 'purple',
                'is_read' => false,
            ]);
        }

        // Send notifications
        $requiresLeadAssign = $ticket->isLeaderOnly();
        $isFromProject = !empty($data['project_id']);
        
        if ($isFromProject && !empty($data['assigned_to'])) {
            // Notify assigned Tech Lead about project ticket
            $assignedUser = User::find($data['assigned_to']);
            if ($assignedUser) {
                \App\Models\Notification::create([
                    'user_id' => $assignedUser->id,
                    'type' => 'technical_ticket',
                    'title' => 'Ticket mới từ Dự án',
                    'message' => "Bạn được phân công phụ trách ticket kỹ thuật từ dự án: {$ticket->code} - {$ticket->title}. Vui lòng kiểm tra và phân công kỹ sư xử lý nếu cần.",
                    'link' => route('technical-tickets.show', $ticket->id),
                    'icon' => 'project-diagram',
                    'color' => 'blue',
                    'is_read' => false,
                ]);
            }
        } elseif ($requiresLeadAssign) {
            // ONLY Technical Leads
            $recipients = User::where('status', 'active')
                ->whereHas('roles', function($q) {
                    $q->where('slug', 'technical_lead');
                })->get();
            $msg = "Có ticket kỹ thuật mới cần phân công (Chỉ Lead): {$ticket->code} - {$ticket->title}";
            foreach ($recipients as $recipient) {
                \App\Models\Notification::create([
                    'user_id' => $recipient->id,
                    'type' => 'technical_ticket',
                    'title' => 'Phân công Ticket Kỹ thuật',
                    'message' => $msg,
                    'link' => route('technical-tickets.show', $ticket->id),
                    'icon' => 'exclamation-circle',
                    'color' => 'orange',
                    'is_read' => false,
                ]);
            }
        } else {
            // ALL Tech staff
            $recipients = User::where('status', 'active')
                ->whereHas('roles', function($q) {
                    $q->where('slug', ['technical_lead', 'technical_engineer']);
                })->get();
            $msg = "Có ticket kỹ thuật mới sẵn sàng pickup: {$ticket->code} - {$ticket->title}";
            foreach ($recipients as $recipient) {
                \App\Models\Notification::create([
                    'user_id' => $recipient->id,
                    'type' => 'technical_ticket',
                    'title' => 'Ticket Kỹ thuật mới (Tự nhận)',
                    'message' => $msg,
                    'link' => route('technical-tickets.show', $ticket->id),
                    'icon' => 'ticket-alt',
                    'color' => 'blue',
                    'is_read' => false,
                ]);
            }
        }

        // Notify assigned engineers if any
        if (!empty($assignedIds)) {
            foreach ($assignedIds as $engId) {
                \App\Models\Notification::create([
                    'user_id' => $engId,
                    'type' => 'technical_ticket',
                    'title' => 'Phân công Ticket Kỹ thuật',
                    'message' => "Bạn đã được phân công phụ trách ticket: {$ticket->code} - {$ticket->title}",
                    'link' => route('technical-tickets.show', $ticket->id),
                    'icon' => 'exclamation-circle',
                    'color' => 'green',
                    'is_read' => false,
                ]);
            }
        }

        // Handle file uploads if present (Initial attachments created with ticket)
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $path = $file->storeAs(
                        'technical_tickets/' . $ticket->id,
                        time() . '_' . $originalName
                    );

                    TechnicalTicketAttachment::create([
                        'technical_ticket_id' => $ticket->id,
                        'file_path' => $path,
                        'file_name' => $originalName,
                        'file_size' => $file->getSize(),
                        'document_type' => 'Khác',
                        'uploaded_by' => Auth::id(),
                        'is_initial' => true,
                    ]);
                }
            }
        }

        return redirect()->route('technical-tickets.show', $ticket->id)
            ->with('success', 'Tạo ticket kỹ thuật thành công.');
    }

    /**
     * Self-pickup a ticket.
     */
    public function pickup($id)
    {
        $ticket = TechnicalTicket::findOrFail($id);
        $currentUserId = auth()->id();

        // Check if already assigned
        if ($ticket->assignedEngineers()->exists()) {
            return redirect()->back()
                ->withErrors(['general' => 'Ticket này đã được nhận hoặc phân công cho Kỹ sư khác.']);
        }

        // Kiểm tra quyền nhận ticket từ ma trận phân quyền
        if (!auth()->user()->can('pickup_technical_tickets')) {
            return redirect()->back()
                ->withErrors(['general' => 'Bạn không có quyền nhận (pickup) ticket kỹ thuật theo cấu hình ma trận phân quyền.']);
        }

        // Check if user has pickup permission based on work type permission matrix
        if (!$ticket->canUserPickup(auth()->user())) {
            return redirect()->back()
                ->withErrors(['general' => 'Bạn không có quyền tự nhận (pickup) loại ticket này theo cấu hình ma trận phân quyền.']);
        }

        // Assign to current user and change status to assigned
        $ticket->update([
            'assigned_to' => $currentUserId,
            'status' => 'assigned'
        ]);
        $ticket->assignedEngineers()->sync([$currentUserId]);

        return redirect()->route('technical-tickets.show', $ticket->id)
            ->with('success_swal', 'Bạn đã nhận (pickup) ticket kỹ thuật thành công.');
    }

    /**
     * Display the specified technical ticket.
     */
    public function show($id)
    {
        $ticket = TechnicalTicket::with([
            'customer', 'project', 'opportunity', 'sale', 'supplier', 
            'assignedTo', 'creator', 'teamLead', 'supportLogs.user', 'attachments.uploader', 
            'assignedEngineers', 'activeEngineers', 'formerEngineers'
        ])->findOrFail($id);

        if (!$ticket->canUserView(auth()->user())) {
            abort(403, 'Bạn không có quyền xem ticket kỹ thuật này.');
        }

        $canComment = $ticket->canUserComment(auth()->user());
        $canUpdateProgress = $ticket->canUserUpdateProgress(auth()->user());
        $canAttachFile = $ticket->canUserAttachFile(auth()->user());
        $canHandover = $ticket->canUserHandover(auth()->user());
        $isReadOnly = !$canComment && !$canUpdateProgress;

        $engineers = User::where('status', 'active')
            ->where(function($q) {
                $q->whereHas('roles', function($rq) {
                    $rq->whereIn('slug', ['technical_lead', 'technical_engineer']);
                })->orWhereHas('userGroups');
            })
            ->with(['userGroups:id,name,code', 'roles:id,name,slug'])
            ->orderBy('name')
            ->get();
        if ($engineers->isEmpty()) {
            $engineers = User::where('status', 'active')->with(['userGroups:id,name,code', 'roles:id,name,slug'])->orderBy('name')->get();
        }

        $leads = $this->getTechnicalLeads($ticket);

        $customers = Customer::orderBy('name')->get();
        
        // Categorized Document Types
        $documentTypes = [
            'biên bản mượn thiết bị' => 'Biên bản mượn thiết bị',
            'biên bản bàn giao' => 'Biên bản bàn giao',
            'biên bản nghiệm thu' => 'Biên bản nghiệm thu',
            'BOM' => 'Bản chào giá / BOM thiết bị',
            'Datasheet' => 'Datasheet sản phẩm',
            'Spec' => 'Specification (Thông số kỹ thuật)',
            'HLD/LLD' => 'Thiết kế HLD/LLD',
            'Proposal' => 'Đề xuất giải pháp (Proposal)',
            'Slide' => 'Slide trình bày / Demo',
            'File cấu hình' => 'File cấu hình hệ thống',
            'Logs' => 'Logs thiết bị / lỗi',
            'hình ảnh hiện trường' => 'Hình ảnh hiện trường',
            'Plan/Báo cáo PoC' => 'Kế hoạch / Báo cáo PoC',
            'tài liệu hướng dẫn' => 'Tài liệu hướng dẫn sử dụng',
            'Khác' => 'Tài liệu khác',
        ];

        return view('technical.tickets.show', compact('ticket', 'engineers', 'leads', 'documentTypes', 'customers', 'canComment', 'canUpdateProgress', 'canAttachFile', 'canHandover', 'isReadOnly'));
    }

    public function edit($id)
    {
        if (!Gate::allows('edit_technical_tickets') || auth()->user()->hasRole('technical_engineer')) {
            abort(403, 'Bạn không có quyền chỉnh sửa ticket kỹ thuật.');
        }

        $ticket = TechnicalTicket::with('assignedEngineers')->findOrFail($id);

        if (in_array($ticket->status, ['in_progress', 'waiting', 'completed', 'closed'])) {
            abort(403, 'Ticket đã chuyển sang trạng thái "' . $ticket->status_label . '", không được phép chỉnh sửa.');
        }

        $currentUserId = auth()->id();
        $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'admin', 'sales_manager']);
        $isTechLeadRole = auth()->user()->hasRole('technical_lead');
        $isGroupLead = UserGroup::where('leader_id', $currentUserId)->exists();
        $isTicketTeamLead = ($ticket->team_lead_id === $currentUserId);
        $isTechnicalLead = $isTicketTeamLead || $isManagerOrAdmin || $isTechLeadRole || $isGroupLead;
        $isTeamLead = $isTechnicalLead;
        $isRequester = ($ticket->created_by === $currentUserId);
        $isSalesOwner = ($ticket->sales_owner_id === $currentUserId);
        $isAssignedEngineer = $ticket->assignedEngineers()->where('users.id', $currentUserId)->exists();

        if ($ticket->isLeaderOnly()) {
            if (!$isTeamLead && !$isRequester && !$isSalesOwner && !$isAssignedEngineer) {
                abort(403, 'Bạn không có quyền chỉnh sửa ticket này. Đối với các loại ticket chỉ Leader tiếp nhận, bạn chỉ được phép chỉnh sửa khi được phân công.');
            }
        }
        $customers = Customer::orderBy('name')->get();
        $projects = $this->getProjectsForTicketUser($ticket->project_id);
        $opportunities = Opportunity::orderBy('name')->get();
        $sales = Sale::orderBy('code')->get();
        $suppliers = Supplier::orderBy('name')->get(); // Vendors
        $engineers = User::where('status', 'active')
            ->where(function($q) {
                $q->whereHas('roles', function($rq) {
                    $rq->whereIn('slug', ['technical_lead', 'technical_engineer']);
                })->orWhereHas('userGroups');
            })
            ->with(['userGroups:id,name,code', 'roles:id,name,slug'])
            ->orderBy('name')
            ->get();
        if ($engineers->isEmpty()) {
            $engineers = User::where('status', 'active')->with(['userGroups:id,name,code', 'roles:id,name,slug'])->orderBy('name')->get();
        }
        $users = User::where('status', 'active')->orderBy('name')->get();
        
        $userGroups = UserGroup::where('status', 'active')->with(['leader', 'members'])->orderBy('name')->get();
        $leads = $this->getTechnicalLeads($ticket);

        $departments = User::whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');

        return view('technical.tickets.edit', compact('ticket', 'customers', 'projects', 'opportunities', 'sales', 'suppliers', 'engineers', 'users', 'userGroups', 'leads', 'departments', 'isTechnicalLead'));
    }

    public function update(Request $request, $id)
    {
        if (!Gate::allows('edit_technical_tickets') || auth()->user()->hasRole('technical_engineer')) {
            abort(403, 'Bạn không có quyền chỉnh sửa ticket kỹ thuật.');
        }

        // Normalize assigned_to to array if single value
        if ($request->has('assigned_to') && !is_array($request->assigned_to)) {
            $request->merge(['assigned_to' => array_filter([$request->assigned_to])]);
        }

        if ($request->has('co_lead_ids') && !is_array($request->co_lead_ids)) {
            $request->merge(['co_lead_ids' => array_filter([$request->co_lead_ids])]);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'work_type' => 'required|string',
            'priority' => 'required|string|in:high,medium',
            'team_lead_id' => 'required|exists:users,id',
            'status' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'sale_id' => 'nullable|exists:sales,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'assigned_to' => 'nullable|array',
            'assigned_to.*' => 'exists:users,id',
            'sla_deadline' => 'nullable|date',
            'description' => 'nullable|string',
            'sales_owner_id' => 'nullable|exists:users,id',
            'user_group_id' => 'nullable|exists:user_groups,id',
            'co_lead_ids' => 'nullable|array',
            'co_lead_ids.*' => 'exists:users,id',
            'department' => 'nullable|string|max:255',
            'project_name' => 'nullable|string|max:255',
            'solution' => 'nullable|string',
            'ticket_details' => 'nullable|array',
        ], [
            'team_lead_id.required' => 'Vui lòng chọn Trưởng nhóm (Lead chính) phụ trách quản lý ticket này.',
            'team_lead_id.exists' => 'Trưởng nhóm được chọn không hợp lệ.',
        ]);

        $ticket = TechnicalTicket::findOrFail($id);

        if (in_array($ticket->status, ['in_progress', 'waiting', 'completed', 'closed'])) {
            abort(403, 'Ticket đã chuyển sang trạng thái "' . $ticket->status_label . '", không được phép chỉnh sửa.');
        }

        $currentUserId = auth()->id();
        $isRequester = ($ticket->created_by === $currentUserId);
        $isTicketTeamLead = ($ticket->team_lead_id === $currentUserId);
        $isCoLead = is_array($ticket->co_lead_ids) && in_array($currentUserId, $ticket->co_lead_ids);
          $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user()->hasRole('technical_lead');
        $isGroupLead = UserGroup::where('leader_id', $currentUserId)->exists();
        $isTeamLead = $isTicketTeamLead || $isCoLead || $isManagerOrAdmin || $isTechLeadRole || $isGroupLead;
        $isAssignedEngineer = $ticket->assignedEngineers()->where('users.id', $currentUserId)->exists();
        $isTechStaff = auth()->user()->can('manage_technical_support_logs') || auth()->user()->can('edit_technical_tickets');

        // Check overall edit permission
        if (!$isRequester && !$isAssignedEngineer && !$isTeamLead && !$isTechStaff) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'Bạn không có quyền chỉnh sửa ticket này. Chỉ Người yêu cầu, Kỹ sư thực hiện, Team Lead hoặc Quản trị viên mới được phép chỉnh sửa.']);
        }

        // Constraint 1: When changing status to assigned or in_progress, must specify assigned_to
        $assignedIds = $request->assigned_to ? (array) $request->assigned_to : [];
        $statusToCheck = $request->status ?? $ticket->status;
        if (in_array($statusToCheck, ['assigned', 'in_progress']) && empty($assignedIds)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['assigned_to' => 'Trạng thái "' . ($statusToCheck === 'assigned' ? 'Đã phân công' : 'Đang thực hiện') . '" yêu cầu phải chỉ định Kỹ sư thực hiện.']);
        }

        // Check complete permission on status change
        if ($statusToCheck === 'completed' && !auth()->user()->can('complete_technical_tickets')) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['status' => 'Bạn không có quyền chuyển trạng thái ticket sang Hoàn tất.']);
        }
        if ($statusToCheck === 'closed' && !$isTeamLead) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['status' => 'Chỉ Technical Team Lead hoặc Quản trị viên mới được phép Đóng ticket.']);
        }

        // Constraint 2: Self-Pickup & Assignment limits on update
        if (!$isTeamLead && !empty($assignedIds)) {
            if (count($assignedIds) > 1 || $assignedIds[0] != $currentUserId) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['assigned_to' => 'Chỉ Team Lead hoặc Quản trị viên mới có quyền phân công cho Kỹ sư khác. Kỹ sư chỉ được phép tự nhận (self-pickup) ticket cho chính mình.']);
            }
            
            if ($ticket->assigned_to != $assignedIds[0] && $ticket->isLeaderOnly()) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['assigned_to' => 'Đối với loại ticket này, chỉ Technical Team Lead hoặc Quản lý mới có quyền phân công. Kỹ sư không được phép tự nhận (self-pickup).']);
            }
        }

        // Constraint 2.5: Once assigned, only Team Lead can re-assign to someone else
        if (!$isTeamLead && $ticket->assignedEngineers()->exists()) {
            $existingIds = $ticket->assignedEngineers()->pluck('users.id')->toArray();
            sort($existingIds);
            sort($assignedIds);
            if ($existingIds !== $assignedIds) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['assigned_to' => 'Ticket này đã được nhận hoặc phân công cho Kỹ sư khác. Chỉ Technical Team Lead mới có quyền thay đổi Kỹ sư phụ trách.']);
            }
        }

        // Constraint 3: Phản hồi kết quả (Bước 6)
        if ($request->status === 'completed' && $ticket->status !== 'completed') {
            $hasLogs = $ticket->supportLogs()->exists();
            $hasAttachments = $ticket->attachments()->exists();
            $hasSolution = !empty($request->solution) || !empty($ticket->solution);
            
            if (!$hasLogs && !$hasAttachments && !$hasSolution) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['solution' => 'Để hoàn thành ticket (Bước 6: Phản hồi kết quả), bạn phải điền giải pháp kỹ thuật, viết nhật ký hỗ trợ (support log) hoặc đính kèm tài liệu bàn giao.']);
            }
        }

        // Constraint 4: Xác nhận hoàn tất (Bước 7)
        if ($ticket->status === 'completed' && $statusToCheck !== 'completed' && !$isRequester && !$isTeamLead) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['status' => 'Ticket đang ở trạng thái Hoàn thành. Chỉ Người yêu cầu hoặc Team Lead mới có quyền xác nhận điều chỉnh hoặc đóng ticket.']);
        }

        // Constraint 5: Đóng Ticket (Bước 8)
        if ($statusToCheck === 'closed' && $ticket->status !== 'closed' && !$isTeamLead) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['status' => 'Chỉ Technical Team Lead hoặc Quản trị viên mới được phép chuyển trạng thái Đóng (Closed) ticket.']);
        }

        $data = $request->all();

        // Preserve status if not submitted
        if (!isset($data['status'])) {
            $data['status'] = $ticket->status;
        }

        // If assigned to an engineer and status is default (open), switch to assigned automatically
        if (!empty($assignedIds) && $data['status'] === 'open') {
            $data['status'] = 'assigned';
        }

        // Calculate SLA if blank or if priority changed and SLA was blank or matches previous auto-calculation
        if (empty($data['sla_deadline'])) {
            $data['sla_deadline'] = TechnicalTicket::calculateSlaDeadline($data['priority'], $ticket->created_at);
        }

        // Handle timestamps on resolution
        if (in_array($data['status'], ['completed', 'closed'])) {
            if (!in_array($ticket->status, ['completed', 'closed'])) {
                $data['resolved_at'] = Carbon::now();
            }
        } else {
            $data['resolved_at'] = null;
        }

        if (!$isTeamLead) {
            $data['co_lead_ids'] = $ticket->co_lead_ids;
            if (empty($assignedIds)) {
                $assignedIds = $ticket->assignedEngineers()->pluck('users.id')->toArray();
                $data['assigned_to'] = $ticket->assigned_to;
            }
        }

        $data['assigned_to'] = !empty($assignedIds) ? $assignedIds[0] : null;

        // Normalize POC devices if present
        if (isset($data['ticket_details']['poc_devices']) && is_array($data['ticket_details']['poc_devices'])) {
            $filteredDevices = [];
            foreach ($data['ticket_details']['poc_devices'] as $dev) {
                if (!empty($dev['name'])) {
                    $filteredDevices[] = [
                        'name' => trim($dev['name']),
                        'quantity' => max(1, intval($dev['quantity'] ?? 1)),
                        'note' => trim($dev['note'] ?? ''),
                    ];
                }
            }
            $data['ticket_details']['poc_devices'] = $filteredDevices;
            if (!empty($filteredDevices)) {
                $data['ticket_details']['poc_model'] = implode(', ', array_filter(array_column($filteredDevices, 'name')));
                $data['ticket_details']['poc_quantity'] = array_sum(array_column($filteredDevices, 'quantity'));
            }
        }

        // Resolve customer_id, project_name, and sales_owner_id automatically from system links
        if (!empty($data['project_id'])) {
            $project = \App\Models\Project::find($data['project_id']);
            if ($project) {
                if (empty($data['customer_id'])) {
                    $data['customer_id'] = $project->customer_id;
                }
                if (empty($data['project_name'])) {
                    $data['project_name'] = $project->name;
                }
                if (empty($data['sales_owner_id'])) {
                    $data['sales_owner_id'] = $project->manager_id;
                }
            }
        }
        if (empty($data['customer_id']) && !empty($data['opportunity_id'])) {
            $opportunity = \App\Models\Opportunity::find($data['opportunity_id']);
            if ($opportunity) {
                $data['customer_id'] = $opportunity->customer_id;
            }
        }
        if (empty($data['customer_id']) && !empty($data['sale_id'])) {
            $sale = \App\Models\Sale::find($data['sale_id']);
            if ($sale) {
                $data['customer_id'] = $sale->customer_id;
            }
        }

        $previousAssignedIds = $ticket->assignedEngineers()->pluck('users.id')->toArray();
        $ticket->update($data);
        $ticket->assignedEngineers()->sync($assignedIds);

        // Find newly assigned engineers (in $assignedIds but not in $previousAssignedIds)
        $newlyAssignedIds = array_diff($assignedIds, $previousAssignedIds);
        foreach ($newlyAssignedIds as $engId) {
            \App\Models\Notification::create([
                'user_id' => $engId,
                'type' => 'technical_ticket',
                'title' => 'Phân công Ticket Kỹ thuật',
                'message' => "Bạn đã được phân công phụ trách ticket: {$ticket->code} - {$ticket->title}",
                'link' => route('technical-tickets.show', $ticket->id),
                'icon' => 'exclamation-circle',
                'color' => 'green',
                'is_read' => false,
            ]);
        }

        return redirect()->route('technical-tickets.show', $ticket->id)
            ->with('success', 'Cập nhật ticket kỹ thuật thành công.');
    }

    /**
     * Remove the specified technical ticket.
     */
    public function destroy($id)
    {
        if (!Gate::allows('delete_technical_tickets')) {
            abort(403, 'Bạn không có quyền xóa ticket kỹ thuật.');
        }

        $ticket = TechnicalTicket::findOrFail($id);

        if (!in_array($ticket->status, ['open', 'assigned'])) {
            abort(403, 'Ticket đã chuyển sang trạng thái "' . $ticket->status_label . '", không được phép xóa.');
        }
        
        // Delete attachments from storage
        foreach ($ticket->attachments as $attachment) {
            Storage::delete($attachment->file_path);
            $attachment->delete();
        }

        $ticket->delete();

        return redirect()->route('technical-tickets.index')
            ->with('success', 'Xóa ticket kỹ thuật thành công.');
    }

    public function uploadAttachment(Request $request, $id)
    {
        $ticket = TechnicalTicket::findOrFail($id);

        if (!$ticket->canUserAttachFile(auth()->user())) {
            abort(403, 'Bạn chỉ có quyền xem ticket này, không được phép tải lên tài liệu.');
        }

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:20480', // Max 20MB per file
            'document_type' => 'required|string',
        ]);

        $uploadedCount = 0;

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if ($file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $path = $file->storeAs(
                        'technical_tickets/' . $ticket->id,
                        time() . '_' . uniqid() . '_' . $originalName
                    );

                    TechnicalTicketAttachment::create([
                        'technical_ticket_id' => $ticket->id,
                        'file_path' => $path,
                        'file_name' => $originalName,
                        'file_size' => $file->getSize(),
                        'document_type' => $request->input('document_type'),
                        'uploaded_by' => Auth::id(),
                        'is_initial' => false,
                    ]);
                    $uploadedCount++;
                }
            }
        }

        if ($uploadedCount > 0) {
            return redirect()->back()->with('success', "Tải lên thành công {$uploadedCount} tài liệu.");
        }

        return redirect()->back()->with('error', 'Không có tài liệu tải lên nào hợp lệ.');
    }

    /**
     * Download a ticket attachment.
     */
    public function downloadAttachment($ticketId, $attachmentId)
    {
        if (!Gate::allows('view_technical_tickets')) {
            abort(403);
        }

        $attachment = TechnicalTicketAttachment::where('technical_ticket_id', $ticketId)
            ->findOrFail($attachmentId);

        if (!Storage::exists($attachment->file_path)) {
            abort(404, 'Tài liệu không tồn tại trên hệ thống.');
        }

        return Storage::download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Delete a ticket attachment.
     */
    public function deleteAttachment($ticketId, $attachmentId)
    {
        abort(403, 'Tài liệu đính kèm trên ticket kỹ thuật không được phép xóa.');
    }

    /**
     * Quick progress update (status and solution).
     */
    public function updateProgress(Request $request, $id)
    {
        $ticket = TechnicalTicket::findOrFail($id);

        $currentUserId = auth()->id();
        $isRequester = ($ticket->created_by === $currentUserId);
        $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user()->hasRole('technical_lead');
        $isTicketTeamLead = ($ticket->team_lead_id === $currentUserId);
        $isTeamLead = $isTicketTeamLead || $isManagerOrAdmin || $isTechLeadRole;

        // Determine action first to apply correct permission check
        $action = $request->input('action', 'update_solution');

        // For progress updates (update_solution), only active assignees or leads can do it
        if ($action === 'update_solution') {
            if (!$ticket->canUserUpdateProgress(auth()->user())) {
                return redirect()->back()
                    ->withErrors(['general' => 'Bạn không có quyền cập nhật tiến độ cho ticket này (chỉ Kỹ sư đang thực hiện hoặc Lead mới có quyền).']);
            }
        } elseif ($action === 'confirm_complete') {
            if (!auth()->user()->can('complete_technical_tickets') && !auth()->user()->hasAnyRole(['super_admin', 'director'])) {
                return redirect()->back()
                    ->withErrors(['general' => 'Bạn không có quyền bấm hoàn thành ticket kỹ thuật.']);
            }
            if (!$isRequester && !auth()->user()->hasAnyRole(['super_admin', 'director']) && !$isTechLeadRole) {
                return redirect()->back()
                    ->withErrors(['general' => 'Bạn không có quyền thực hiện hành động này.']);
            }
        } elseif ($action === 'close_ticket') {
            if (!$isTeamLead) {
                abort(403, 'Chỉ Technical Team Lead hoặc Quản trị viên mới được phép Đóng ticket.');
            }
        } else {
            if (!$ticket->canUserUpdateProgress(auth()->user())) {
                return redirect()->back()
                    ->withErrors(['general' => 'Bạn không có quyền chỉnh sửa ticket này.']);
            }
        }

        if ($action === 'update_solution') {
            $request->validate([
                'solution' => 'nullable|string',
            ]);

            $data = ['solution' => $request->solution];

            // If engineer checked "completed" checkbox → mark completed and notify sales to review & close
            if ($request->has('is_completed') && $request->is_completed == 1) {
                if (!auth()->user()->can('complete_technical_tickets')) {
                    return redirect()->back()
                        ->withErrors(['general' => 'Bạn không có quyền đánh dấu hoàn thành ticket kỹ thuật.']);
                }
                $data['status'] = 'completed';
                $data['resolved_at'] = Carbon::now();

                // Log to discussion
                $commentText = "[Hoàn thành kỹ thuật] Kỹ sư đã xử lý xong công việc kỹ thuật.";
                if ($request->solution) {
                    $commentText .= "\nPhương án xử lý: " . $request->solution;
                }
                TechnicalTicketComment::create([
                    'technical_ticket_id' => $ticket->id,
                    'user_id' => $currentUserId,
                    'comment' => $commentText,
                ]);

                // Notify Sales / Requester / Owner to review and close ticket
                $salesNotifyIds = array_filter(array_unique([$ticket->created_by, $ticket->sales_owner_id, $ticket->team_lead_id]));
                foreach ($salesNotifyIds as $notifyUserId) {
                    if ($notifyUserId && $notifyUserId !== $currentUserId) {
                        \App\Models\Notification::create([
                            'user_id' => $notifyUserId,
                            'type' => 'technical_ticket_completed',
                            'title' => 'Ticket Kỹ thuật đã hoàn thành',
                            'message' => "Kỹ sư đã hoàn thành xử lý ticket: {$ticket->code} - {$ticket->title}. Vui lòng xem xét và Đóng (Close) ticket.",
                            'link' => route('technical-tickets.show', $ticket->id),
                            'icon' => 'check-circle',
                            'color' => 'green',
                            'is_read' => false,
                        ]);
                    }
                }
            } elseif ($request->has('is_waiting') && $request->is_waiting == 1) {
                // Waiting for Customer/Partner/Vendor
                $data['status'] = 'waiting';

                $commentText = "[Cập nhật tiến độ] Chuyển sang trạng thái Chờ phản hồi từ Khách hàng / Đối tác / Nhà cung cấp.";
                if ($request->solution) {
                    $commentText .= "\nPhương án xử lý: " . $request->solution;
                }
                TechnicalTicketComment::create([
                    'technical_ticket_id' => $ticket->id,
                    'user_id' => $currentUserId,
                    'comment' => $commentText,
                ]);
            } else {
                // If it is currently open/assigned/waiting, promote to in_progress
                if (in_array($ticket->status, ['open', 'assigned', 'waiting'])) {
                    $data['status'] = 'in_progress';
                }

                // Log solution update to discussion
                if ($request->solution) {
                    TechnicalTicketComment::create([
                        'technical_ticket_id' => $ticket->id,
                        'user_id' => $currentUserId,
                        'comment' => "[Cập nhật tiến độ] Cập nhật phương án xử lý:\n" . $request->solution,
                    ]);
                }
            }

            $ticket->update($data);
            return redirect()->back()->with('success_swal', 'Cập nhật tiến độ ticket thành công.');

        } elseif ($action === 'confirm_complete') {
            // Step 7: Requester confirms → Completed
            $ticket->update([
                'status' => 'completed',
                'resolved_at' => $ticket->resolved_at ?? \Carbon\Carbon::now(),
            ]);

            TechnicalTicketComment::create([
                'technical_ticket_id' => $ticket->id,
                'user_id' => $currentUserId,
                'comment' => '[Xác nhận hoàn tất] Người yêu cầu đã xác nhận kết quả xử lý hoàn tất.',
            ]);

            return redirect()->back()->with('success_swal', 'Xác nhận hoàn tất ticket thành công.');

        } elseif ($action === 'close_ticket') {
            // Step 8: Tech Lead / System closes the ticket
            if (!$isTeamLead) {
                abort(403, 'Chỉ Technical Team Lead hoặc Quản trị viên mới được phép Đóng ticket.');
            }

            $ticket->update([
                'status' => 'closed',
                'resolved_at' => $ticket->resolved_at ?? \Carbon\Carbon::now(),
            ]);

            TechnicalTicketComment::create([
                'technical_ticket_id' => $ticket->id,
                'user_id' => $currentUserId,
                'comment' => '[Đóng Ticket] Ticket đã được đóng bởi Technical Team Lead / Quản trị viên.',
            ]);

            return redirect()->back()->with('success_swal', 'Đóng ticket thành công.');
        }

        return redirect()->back()->with('error_swal', 'Hành động không hợp lệ.');
    }

    /**
     * Store a comment on a technical ticket.
     */
    public function storeComment(Request $request, $id)
    {
        $ticket = TechnicalTicket::findOrFail($id);

        if (!$ticket->canUserComment(auth()->user())) {
            abort(403, 'Bạn chỉ có quyền xem ticket này, không được phép gửi trao đổi.');
        }

        $request->validate([
            'comment' => 'required|string',
        ]);

        TechnicalTicketComment::create([
            'technical_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
        ]);

        return redirect()->back()->with('success_swal', 'Gửi ý kiến trao đổi thành công.');
    }

    /**
     * Handover (Bàn giao) ticket to new engineers / leads.
     */
    public function handover(Request $request, $id)
    {
        $ticket = TechnicalTicket::findOrFail($id);

        if (!$ticket->canUserHandover(auth()->user())) {
            abort(403, 'Bạn không có quyền thực hiện bàn giao ticket này.');
        }

        $request->validate([
            'assigned_to' => 'required|array|min:1',
            'assigned_to.*' => 'exists:users,id',
            'new_team_lead_id' => 'nullable|exists:users,id',
            'new_co_lead_ids' => 'nullable|array',
            'new_co_lead_ids.*' => 'exists:users,id',
            'handover_note' => 'required|string',
        ]);

        $currentUserId = auth()->id();
        $newAssignedIds = array_map('intval', (array)$request->assigned_to);

        // Get current active engineer IDs
        $currentActiveIds = $ticket->activeEngineers->pluck('id')->toArray();
        if (empty($currentActiveIds) && $ticket->assigned_to) {
            $currentActiveIds = [(int)$ticket->assigned_to];
        }

        // Deactivate current active engineers who are not in newAssignedIds
        $toDeactivate = array_diff($currentActiveIds, $newAssignedIds);
        foreach ($toDeactivate as $oldId) {
            \DB::table('technical_ticket_engineers')
                ->where('technical_ticket_id', $ticket->id)
                ->where('user_id', $oldId)
                ->update([
                    'is_active' => false,
                    'handed_over_at' => Carbon::now(),
                    'handed_over_by' => $currentUserId,
                    'handover_note' => $request->handover_note,
                    'updated_at' => Carbon::now(),
                ]);
        }

        // Attach or activate new assignees
        foreach ($newAssignedIds as $newId) {
            $exists = \DB::table('technical_ticket_engineers')
                ->where('technical_ticket_id', $ticket->id)
                ->where('user_id', $newId)
                ->first();
            
            if ($exists) {
                \DB::table('technical_ticket_engineers')
                    ->where('technical_ticket_id', $ticket->id)
                    ->where('user_id', $newId)
                    ->update([
                        'is_active' => true,
                        'updated_at' => Carbon::now(),
                    ]);
            } else {
                \DB::table('technical_ticket_engineers')->insert([
                    'technical_ticket_id' => $ticket->id,
                    'user_id' => $newId,
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        $updateData = [
            'assigned_to' => $newAssignedIds[0] ?? null,
        ];

        if ($request->filled('new_team_lead_id')) {
            $updateData['team_lead_id'] = $request->new_team_lead_id;
        }
        if ($request->has('new_co_lead_ids')) {
            $updateData['co_lead_ids'] = array_map('intval', (array)$request->new_co_lead_ids);
        }

        $ticket->update($updateData);

        $newUsers = User::whereIn('id', $newAssignedIds)->pluck('name')->toArray();
        $oldUsers = User::whereIn('id', $toDeactivate)->pluck('name')->toArray();
        $oldText = !empty($oldUsers) ? implode(', ', $oldUsers) : 'Kỹ sư cũ';
        $newText = implode(', ', $newUsers);

        // Log discussion comment
        $commentText = "[Bàn giao Ticket] Bàn giao từ {$oldText} sang Kỹ sư phụ trách mới: {$newText}.\nNội dung / Ghi chú bàn giao: " . $request->handover_note;
        TechnicalTicketComment::create([
            'technical_ticket_id' => $ticket->id,
            'user_id' => $currentUserId,
            'comment' => $commentText,
        ]);

        // Notify new assignees
        foreach ($newAssignedIds as $engId) {
            if ($engId !== $currentUserId) {
                \App\Models\Notification::create([
                    'user_id' => $engId,
                    'type' => 'technical_ticket_handover',
                    'title' => 'Bàn giao Ticket Kỹ thuật',
                    'message' => "Bạn đã nhận bàn giao ticket: {$ticket->code} - {$ticket->title}",
                    'link' => route('technical-tickets.show', $ticket->id),
                    'icon' => 'exchange-alt',
                    'color' => 'blue',
                    'is_read' => false,
                ]);
            }
        }

        return redirect()->route('technical-tickets.show', $ticket->id)
            ->with('success_swal', 'Đã bàn giao ticket thành công cho Kỹ sư mới.');
    }

    /**
     * Get list of projects visible for the current user when creating/editing/filtering technical tickets.
     * For sales accounts, only show projects they registered (manager_id = auth id).
     */
    protected function getProjectsForTicketUser(?int $includeProjectId = null)
    {
        $user = auth()->user();
        if (!$user) {
            return collect();
        }

        $isPrivilegedUser = $user->hasAnyRole([
            'super_admin', 'director', 'technical_lead', 'technical_engineer', 
            'admin', 'purchase_manager', 'purchase_staff'
        ]);
        $isSales = $user->hasAnyRole(['sales_staff', 'sales_manager', 'sales']) || in_array($user->department, ['Sales', 'Phòng Kinh doanh', 'Kinh doanh', 'BU1', 'BU2', 'BU3']);

        if (!$isPrivilegedUser || $isSales) {
            return Project::where(function ($q) use ($user, $includeProjectId) {
                $q->where('manager_id', $user->id);
                if ($includeProjectId) {
                    $q->orWhere('id', $includeProjectId);
                }
            })->orderBy('name')->get();
        }

        return Project::orderBy('name')->get();
    }

    /**
     * Get list of technical leads (for primary lead and co-leads).
     * Strictly restricted to technical roles and technical groups.
     */
    protected function getTechnicalLeads($ticket = null)
    {
        $leads = User::where('status', 'active')
            ->where(function($q) {
                $q->whereHas('roles', function($rq) {
                    $rq->whereIn('slug', ['technical_lead']);
                })->orWhereHas('leadingGroups', function($gq) {
                    $gq->where(function($sq) {
                        $sq->where('department', 'like', '%kỹ thuật%')
                           ->orWhere('department', 'like', '%technical%')
                           ->orWhere('name', 'like', '%kỹ thuật%')
                           ->orWhere('name', 'like', '%technical%')
                           ->orWhereHas('members.roles', function($mrq) {
                               $mrq->whereIn('slug', ['technical_lead', 'technical_engineer']);
                           });
                    });
                });
            })
            ->with(['userGroups:id,name,code', 'roles:id,name,slug'])
            ->orderBy('name')
            ->get();

        if ($leads->isEmpty()) {
            $leads = User::where('status', 'active')
                ->whereHas('roles', function($rq) {
                    $rq->whereIn('slug', ['technical_lead']);
                })
                ->with(['userGroups:id,name,code', 'roles:id,name,slug'])
                ->orderBy('name')
                ->get();
        }

        if ($ticket) {
            $selectedLeadIds = array_filter(array_merge([$ticket->team_lead_id], is_array($ticket->co_lead_ids) ? $ticket->co_lead_ids : []));
            if (!empty($selectedLeadIds)) {
                $existingLeads = User::whereIn('id', $selectedLeadIds)
                    ->with(['userGroups:id,name,code', 'roles:id,name,slug'])
                    ->get();
                $leads = $leads->merge($existingLeads)->unique('id')->sortBy('name')->values();
            }
        }

        return $leads;
    }

    /**
     * Resolve default Technical Lead user if needed.
     */
    protected function resolveTechnicalLead(): ?User
    {
        return User::where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->where('slug', 'technical_lead'))
            ->first()
            ?: User::where('status', 'active')
                ->whereHas('leadingGroups', fn ($gq) => $gq->where('department', 'like', '%kỹ thuật%')->orWhere('name', 'like', '%kỹ thuật%'))
                ->first();
    }
}
