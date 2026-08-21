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

        $query = TechnicalTicket::with(['customer', 'project', 'assignedTo', 'creator', 'assignedEngineers']);

        $currentUserId = auth()->id();
        $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user()->hasRole('technical_lead');

        if (!$isManagerOrAdmin && !$isTechLeadRole) {
            // Non-leads can only see tickets they are associated with, OR unassigned self-pickup types
            $query->where(function ($q) use ($currentUserId) {
                $q->where('created_by', $currentUserId)
                  ->orWhere('sales_owner_id', $currentUserId)
                  ->orWhere('assigned_to', $currentUserId)
                  ->orWhere('team_lead_id', $currentUserId)
                  ->orWhereHas('assignedEngineers', function ($sq) use ($currentUserId) {
                      $sq->where('users.id', $currentUserId);
                  });
                
                // If the user has technical engineer role, allow viewing unassigned non-restricted tickets
                if (auth()->user()->hasRole('technical_engineer')) {
                    $q->orWhere(function ($sub) {
                        $sub->whereNotIn('work_type', ['BOM', 'documentation', 'after_sales'])
                            ->whereDoesntHave('assignedEngineers');
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
            $query->where('status', $request->input('status'));
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

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);
        
        $engineers = User::where('status', 'active')
            ->whereHas('roles', function($q) {
                $q->whereIn('slug', ['technical_lead', 'technical_engineer']);
            })
            ->orderBy('name')
            ->get();
        $customers = Customer::orderBy('name')->get();

        return view('technical.tickets.index', compact('tickets', 'engineers', 'customers'));
    }

    /**
     * Show the form for creating a new technical ticket.
     */
    public function create()
    {
        if (!Gate::allows('create_technical_tickets')) {
            abort(403, 'Bạn không có quyền tạo ticket kỹ thuật.');
        }

        $customers = Customer::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $opportunities = Opportunity::orderBy('name')->get();
        $sales = Sale::orderBy('code')->get();
        $suppliers = Supplier::orderBy('name')->get(); // Vendors
        $engineers = User::where('status', 'active')
            ->whereHas('roles', function($q) {
                $q->whereIn('slug', ['technical_lead', 'technical_engineer']);
            })
            ->orderBy('name')
            ->get();
        $users = User::where('status', 'active')->orderBy('name')->get();
        
        $departments = User::whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');

        return view('technical.tickets.create', compact('customers', 'projects', 'opportunities', 'sales', 'suppliers', 'engineers', 'users', 'departments'));
    }

    public function store(Request $request)
    {
        if (!Gate::allows('create_technical_tickets')) {
            abort(403, 'Bạn không có quyền tạo ticket kỹ thuật.');
        }

        // Normalize assigned_to to array if single value
        if ($request->has('assigned_to') && !is_array($request->assigned_to)) {
            $request->merge(['assigned_to' => array_filter([$request->assigned_to])]);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'work_type' => 'required|string',
            'priority' => 'required|string|in:high,medium',
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
            'team_lead_id' => 'nullable|exists:users,id',
            'department' => 'nullable|string|max:255',
            'project_name' => 'nullable|string|max:255',
            'solution' => 'nullable|string',
            'ticket_details' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:20480', // 20MB max per file
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
        $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user()->hasRole('technical_lead');
        $isTeamLead = $isTechLeadRole || $isManagerOrAdmin;

        if (!$isTeamLead && !empty($assignedIds)) {
            if (count($assignedIds) > 1 || $assignedIds[0] != $currentUserId) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['assigned_to' => 'Chỉ Team Lead hoặc Quản trị viên mới có quyền phân công cho Kỹ sư khác. Kỹ sư chỉ được phép tự nhận (self-pickup) ticket cho chính mình.']);
            }
            if (in_array($request->work_type, ['BOM', 'documentation', 'after_sales'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['assigned_to' => 'Đối với các loại ticket BOM Support, Technical Documents và After-sales support, chỉ Technical Team Lead mới có quyền phân công. Kỹ sư không được phép tự nhận (self-pickup).']);
            }
        }

        $data['code'] = TechnicalTicket::generateCode();
        $data['created_by'] = Auth::id();

        // Calculate SLA if blank
        if (empty($data['sla_deadline'])) {
            $data['sla_deadline'] = TechnicalTicket::calculateSlaDeadline($data['priority']);
        }
        
        $data['assigned_to'] = !empty($assignedIds) ? $assignedIds[0] : null;

        // If assigned to an engineer and status is default (open), switch to assigned
        if (!empty($assignedIds) && (!isset($data['status']) || $data['status'] === 'open')) {
            $data['status'] = 'assigned';
        }

        // Resolve customer_id automatically from system links
        if (empty($data['customer_id'])) {
            if (!empty($data['project_id'])) {
                $project = \App\Models\Project::find($data['project_id']);
                if ($project) {
                    $data['customer_id'] = $project->customer_id;
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
        }

        $ticket = TechnicalTicket::create($data);

        if (!empty($assignedIds)) {
            $ticket->assignedEngineers()->sync($assignedIds);
        }

        // Send notifications
        $requiresLeadAssign = in_array($ticket->work_type, ['BOM', 'documentation', 'after_sales']);
        
        if ($requiresLeadAssign) {
            // ONLY Technical Leads
            $recipients = User::where('status', 'active')
                ->whereHas('roles', function($q) {
                    $q->where('slug', 'technical_lead');
                })->get();
            $msg = "Có ticket kỹ thuật mới cần phân công: {$ticket->code} - {$ticket->title}";
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
                    $q->whereIn('slug', ['technical_lead', 'technical_engineer']);
                })->get();
            $msg = "Có ticket kỹ thuật mới: {$ticket->code} - {$ticket->title}";
            foreach ($recipients as $recipient) {
                \App\Models\Notification::create([
                    'user_id' => $recipient->id,
                    'type' => 'technical_ticket',
                    'title' => 'Ticket Kỹ thuật mới',
                    'message' => $msg,
                    'link' => route('technical-tickets.show', $ticket->id),
                    'icon' => 'exclamation-circle',
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

        // Handle file uploads if present
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
                    ]);
                }
            }
        }

        return redirect()->route('technical-tickets.show', $ticket->id)
            ->with('success_swal', 'Tạo ticket kỹ thuật thành công.');
    }

    /**
     * Self-pickup a ticket.
     */
    public function pickup($id)
    {
        $ticket = TechnicalTicket::findOrFail($id);
        $currentUserId = auth()->id();
        $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user()->hasRole('technical_lead');
        $isTeamLead = $isTechLeadRole || $isManagerOrAdmin;

        // Check if already assigned
        if ($ticket->assignedEngineers()->exists()) {
            return redirect()->back()
                ->withErrors(['general' => 'Ticket này đã được nhận hoặc phân công cho Kỹ sư khác.']);
        }

        // If it's a restricted type and they are not a Team Lead, block it
        if (!$isTeamLead && in_array($ticket->work_type, ['BOM', 'documentation', 'after_sales'])) {
            return redirect()->back()
                ->withErrors(['general' => 'Đối với các loại ticket BOM Support, Technical Documents và After-sales support, chỉ Technical Team Lead mới có quyền phân công. Kỹ sư không được phép tự nhận (self-pickup).']);
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
            'assignedTo', 'creator', 'supportLogs.user', 'attachments.uploader', 'assignedEngineers'
        ])->findOrFail($id);

        $currentUserId = auth()->id();
        $isRequester = ($ticket->created_by === $currentUserId);

        // A user who can create a ticket must be able to open the ticket they
        // just created, even when their role is not allowed to browse all tickets.
        if (!Gate::allows('view_technical_tickets') && !$isRequester) {
            abort(403, 'Bạn không có quyền xem ticket kỹ thuật.');
        }

        $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user()->hasRole('technical_lead');
        $isTicketTeamLead = ($ticket->team_lead_id === $currentUserId);
        $isTeamLead = $isTicketTeamLead || $isManagerOrAdmin || $isTechLeadRole;
        $isSalesOwner = ($ticket->sales_owner_id === $currentUserId);
        $isAssignedEngineer = $ticket->assignedEngineers()->where('users.id', $currentUserId)->exists();

        if (!$isTeamLead) {
            // Check if the user is associated with the ticket
            $isAssociated = $isRequester || $isSalesOwner || $isAssignedEngineer || ($ticket->team_lead_id === $currentUserId);
            
            if (!$isAssociated) {
                // If not associated, check if they are tech engineer and can pick it up (unassigned & non-restricted work type)
                $canViewUnassigned = auth()->user()->hasRole('technical_engineer')
                    && !in_array($ticket->work_type, ['BOM', 'documentation', 'after_sales'])
                    && !$ticket->assignedEngineers()->exists();

                if (!$canViewUnassigned) {
                    abort(403, 'Bạn không có quyền truy cập ticket kỹ thuật này.');
                }
            }
        }

        $engineers = User::where('status', 'active')
            ->whereHas('roles', function($q) {
                $q->whereIn('slug', ['technical_lead', 'technical_engineer']);
            })
            ->orderBy('name')
            ->get();
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

        return view('technical.tickets.show', compact('ticket', 'engineers', 'documentTypes', 'customers'));
    }

    public function edit($id)
    {
        if (!Gate::allows('edit_technical_tickets') || auth()->user()->hasRole('technical_engineer')) {
            abort(403, 'Bạn không có quyền chỉnh sửa ticket kỹ thuật.');
        }

        $ticket = TechnicalTicket::with('assignedEngineers')->findOrFail($id);

        if ($ticket->status === 'closed') {
            abort(403, 'Ticket đã đóng không thể chỉnh sửa.');
        }

        $currentUserId = auth()->id();
        $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user()->hasRole('technical_lead');
        $isTicketTeamLead = ($ticket->team_lead_id === $currentUserId);
        $isTeamLead = $isTicketTeamLead || $isManagerOrAdmin || $isTechLeadRole;
        $isRequester = ($ticket->created_by === $currentUserId);
        $isSalesOwner = ($ticket->sales_owner_id === $currentUserId);
        $isAssignedEngineer = $ticket->assignedEngineers()->where('users.id', $currentUserId)->exists();

        if (in_array($ticket->work_type, ['BOM', 'documentation', 'after_sales'])) {
            if (!$isTeamLead && !$isRequester && !$isSalesOwner && !$isAssignedEngineer) {
                abort(403, 'Bạn không có quyền chỉnh sửa ticket này. Đối với các loại ticket BOM Support, Technical Documents và After-sales support, bạn chỉ được phép chỉnh sửa khi được phân công.');
            }
        }
        $customers = Customer::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $opportunities = Opportunity::orderBy('name')->get();
        $sales = Sale::orderBy('code')->get();
        $suppliers = Supplier::orderBy('name')->get(); // Vendors
        $engineers = User::where('status', 'active')
            ->whereHas('roles', function($q) {
                $q->whereIn('slug', ['technical_lead', 'technical_engineer']);
            })
            ->orderBy('name')
            ->get();
        $users = User::where('status', 'active')->orderBy('name')->get();
        
        $departments = User::whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');

        return view('technical.tickets.edit', compact('ticket', 'customers', 'projects', 'opportunities', 'sales', 'suppliers', 'engineers', 'users', 'departments'));
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

        $request->validate([
            'title' => 'required|string|max:255',
            'work_type' => 'required|string',
            'priority' => 'required|string|in:high,medium',
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
            'team_lead_id' => 'nullable|exists:users,id',
            'department' => 'nullable|string|max:255',
            'project_name' => 'nullable|string|max:255',
            'solution' => 'nullable|string',
            'ticket_details' => 'nullable|array',
        ]);

        $ticket = TechnicalTicket::findOrFail($id);

        if ($ticket->status === 'closed') {
            abort(403, 'Ticket đã đóng không thể chỉnh sửa.');
        }

        $currentUserId = auth()->id();
        $isRequester = ($ticket->created_by === $currentUserId);
        $isTicketTeamLead = ($ticket->team_lead_id === $currentUserId);
        $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user()->hasRole('technical_lead');
        $isTeamLead = $isTicketTeamLead || $isManagerOrAdmin || $isTechLeadRole;
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

        // Constraint 2: Self-Pickup & Assignment limits on update
        if (!$isTeamLead && !empty($assignedIds)) {
            if (count($assignedIds) > 1 || $assignedIds[0] != $currentUserId) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['assigned_to' => 'Chỉ Team Lead hoặc Quản trị viên mới có quyền phân công cho Kỹ sư khác. Kỹ sư chỉ được phép tự nhận (self-pickup) ticket cho chính mình.']);
            }
            
            if ($ticket->assigned_to != $assignedIds[0] && in_array($ticket->work_type, ['BOM', 'documentation', 'after_sales'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['assigned_to' => 'Đối với các loại ticket BOM Support, Technical Documents và After-sales support, chỉ Technical Team Lead mới có quyền phân công. Kỹ sư không được phép tự nhận (self-pickup).']);
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

        $data['assigned_to'] = !empty($assignedIds) ? $assignedIds[0] : null;

        // Resolve customer_id automatically from system links
        if (empty($data['customer_id'])) {
            if (!empty($data['project_id'])) {
                $project = \App\Models\Project::find($data['project_id']);
                if ($project) {
                    $data['customer_id'] = $project->customer_id;
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
            ->with('success_swal', 'Cập nhật ticket kỹ thuật thành công.');
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

        if ($ticket->status === 'closed') {
            abort(403, 'Ticket đã đóng không thể xóa.');
        }
        
        // Delete attachments from storage
        foreach ($ticket->attachments as $attachment) {
            Storage::delete($attachment->file_path);
            $attachment->delete();
        }

        $ticket->delete();

        return redirect()->route('technical-tickets.index')
            ->with('success_swal', 'Xóa ticket kỹ thuật thành công.');
    }

    public function uploadAttachment(Request $request, $id)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:20480', // Max 20MB per file
            'document_type' => 'required|string',
        ]);

        $ticket = TechnicalTicket::findOrFail($id);
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
                    ]);
                    $uploadedCount++;
                }
            }
        }

        if ($uploadedCount > 0) {
            return redirect()->back()->with('success_swal', "Tải lên thành công {$uploadedCount} tài liệu.");
        }

        return redirect()->back()->with('error_swal', 'Không có tài liệu tải lên nào hợp lệ.');
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
        $attachment = TechnicalTicketAttachment::where('technical_ticket_id', $ticketId)
            ->findOrFail($attachmentId);

        // Delete from storage
        Storage::delete($attachment->file_path);
        $attachment->delete();

        return redirect()->back()->with('success_swal', 'Đã xóa tài liệu đính kèm.');
    }

    /**
     * Quick progress update (status and solution).
     */
    public function updateProgress(Request $request, $id)
    {
        $ticket = TechnicalTicket::findOrFail($id);

        $currentUserId = auth()->id();
        $isRequester = ($ticket->created_by === $currentUserId);
        $isTicketTeamLead = ($ticket->team_lead_id === $currentUserId);
        $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user()->hasRole('technical_lead');
        $isTeamLead = $isTicketTeamLead || $isManagerOrAdmin || $isTechLeadRole;
        $isAssignedEngineer = $ticket->assignedEngineers()->where('users.id', $currentUserId)->exists();
        $isTechStaff = auth()->user()->hasAnyRole(['technical_lead', 'technical_engineer', 'super_admin']);

        // Determine action first to apply correct permission check
        $action = $request->input('action', 'update_solution');

        // For progress updates (update_solution), only tech staff can do it
        if ($action === 'update_solution') {
            $canUpdateProgress = $isAssignedEngineer || $isTechLeadRole || auth()->user()->hasAnyRole(['super_admin', 'director']);
            if (!$canUpdateProgress) {
                return redirect()->back()
                    ->withErrors(['general' => 'Chỉ Kỹ sư được phân công, Technical Lead hoặc Quản trị viên mới có quyền cập nhật tiến độ.']);
            }
        } elseif ($action === 'confirm_complete') {
            // For confirm, requester or admin can do it
            if (!$isRequester && !$isManagerOrAdmin && !$isTechLeadRole) {
                return redirect()->back()
                    ->withErrors(['general' => 'Bạn không có quyền thực hiện hành động này.']);
            }
        } else {
            // Unknown action
            if (!$isAssignedEngineer && !$isTeamLead && !$isTechStaff) {
                return redirect()->back()
                    ->withErrors(['general' => 'Bạn không có quyền chỉnh sửa ticket này.']);
            }
        }

        if ($action === 'update_solution') {
            $request->validate([
                'solution' => 'nullable|string',
            ]);

            $data = ['solution' => $request->solution];

            // If engineer checked "completed" checkbox → only log comment, status remains in_progress
            if ($request->has('is_completed') && $request->is_completed == 1) {
                if (in_array($ticket->status, ['open', 'assigned', 'waiting'])) {
                    $data['status'] = 'in_progress';
                }

                // Log to discussion
                $commentText = "[Cập nhật tiến độ] Đã hoàn thành công việc kỹ thuật.";
                if ($request->solution) {
                    $commentText .= "\nPhương án xử lý: " . $request->solution;
                }
                TechnicalTicketComment::create([
                    'technical_ticket_id' => $ticket->id,
                    'user_id' => $currentUserId,
                    'comment' => $commentText,
                ]);
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
        $request->validate([
            'comment' => 'required|string',
        ]);

        $ticket = TechnicalTicket::findOrFail($id);

        TechnicalTicketComment::create([
            'technical_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
        ]);

        return redirect()->back()->with('success_swal', 'Gửi ý kiến trao đổi thành công.');
    }
}
