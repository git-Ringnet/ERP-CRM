<?php

namespace App\Http\Controllers;

use App\Models\TechnicalTicket;
use App\Models\TechnicalTicketAttachment;
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

        $query = TechnicalTicket::with(['customer', 'project', 'assignedTo', 'creator']);

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
        
        $engineers = User::where('status', 'active')->orderBy('name')->get();
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
        $engineers = User::where('status', 'active')->orderBy('name')->get();
        $users = User::where('status', 'active')->orderBy('name')->get();
        
        $departments = User::whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');

        return view('technical.tickets.create', compact('customers', 'projects', 'opportunities', 'sales', 'suppliers', 'engineers', 'users', 'departments'));
    }

    /**
     * Store a newly created technical ticket.
     */
    public function store(Request $request)
    {
        if (!Gate::allows('create_technical_tickets')) {
            abort(403, 'Bạn không có quyền tạo ticket kỹ thuật.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'work_type' => 'required|string',
            'priority' => 'required|string',
            'customer_id' => 'nullable|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'sale_id' => 'nullable|exists:sales,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'assigned_to' => 'nullable|exists:users,id',
            'sla_deadline' => 'nullable|date',
            'description' => 'nullable|string',
            'sales_owner_id' => 'nullable|exists:users,id',
            'team_lead_id' => 'nullable|exists:users,id',
            'department' => 'nullable|string|max:255',
            'project_name' => 'nullable|string|max:255',
            'solution' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:20480', // 20MB max per file
        ]);

        $data = $request->all();
        $data['code'] = TechnicalTicket::generateCode();
        $data['created_by'] = Auth::id();
        
        // If assigned to an engineer and status is default (open), switch to assigned
        if (isset($data['assigned_to']) && (!isset($data['status']) || $data['status'] === 'open')) {
            $data['status'] = 'assigned';
        }

        $ticket = TechnicalTicket::create($data);

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
     * Display the specified technical ticket.
     */
    public function show($id)
    {
        if (!Gate::allows('view_technical_tickets')) {
            abort(403, 'Bạn không có quyền xem ticket kỹ thuật.');
        }

        $ticket = TechnicalTicket::with([
            'customer', 'project', 'opportunity', 'sale', 'supplier', 
            'assignedTo', 'creator', 'supportLogs.user', 'attachments.uploader'
        ])->findOrFail($id);

        $engineers = User::where('status', 'active')->orderBy('name')->get();
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

    /**
     * Show the form for editing the specified technical ticket.
     */
    public function edit($id)
    {
        if (!Gate::allows('edit_technical_tickets')) {
            abort(403, 'Bạn không có quyền chỉnh sửa ticket kỹ thuật.');
        }

        $ticket = TechnicalTicket::findOrFail($id);
        $customers = Customer::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $opportunities = Opportunity::orderBy('name')->get();
        $sales = Sale::orderBy('code')->get();
        $suppliers = Supplier::orderBy('name')->get(); // Vendors
        $engineers = User::where('status', 'active')->orderBy('name')->get();
        $users = User::where('status', 'active')->orderBy('name')->get();
        
        $departments = User::whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');

        return view('technical.tickets.edit', compact('ticket', 'customers', 'projects', 'opportunities', 'sales', 'suppliers', 'engineers', 'users', 'departments'));
    }

    /**
     * Update the specified technical ticket.
     */
    public function update(Request $request, $id)
    {
        if (!Gate::allows('edit_technical_tickets')) {
            abort(403, 'Bạn không có quyền chỉnh sửa ticket kỹ thuật.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'work_type' => 'required|string',
            'priority' => 'required|string',
            'status' => 'required|string',
            'customer_id' => 'nullable|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'sale_id' => 'nullable|exists:sales,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'assigned_to' => 'nullable|exists:users,id',
            'sla_deadline' => 'nullable|date',
            'description' => 'nullable|string',
            'sales_owner_id' => 'nullable|exists:users,id',
            'team_lead_id' => 'nullable|exists:users,id',
            'department' => 'nullable|string|max:255',
            'project_name' => 'nullable|string|max:255',
            'solution' => 'nullable|string',
        ]);

        $ticket = TechnicalTicket::findOrFail($id);
        $data = $request->all();

        // Handle timestamps on resolution
        if (in_array($data['status'], ['completed', 'closed'])) {
            if (!in_array($ticket->status, ['completed', 'closed'])) {
                $data['resolved_at'] = Carbon::now();
            }
        } else {
            $data['resolved_at'] = null;
        }

        $ticket->update($data);

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
        
        // Delete attachments from storage
        foreach ($ticket->attachments as $attachment) {
            Storage::delete($attachment->file_path);
            $attachment->delete();
        }

        $ticket->delete();

        return redirect()->route('technical-tickets.index')
            ->with('success_swal', 'Xóa ticket kỹ thuật thành công.');
    }

    /**
     * Upload an attachment to a technical ticket.
     */
    public function uploadAttachment(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // Max 20MB
            'document_type' => 'required|string',
        ]);

        $ticket = TechnicalTicket::findOrFail($id);

        if ($request->file('file')->isValid()) {
            $file = $request->file('file');
            
            // Store file securely
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
                'document_type' => $request->input('document_type'),
                'uploaded_by' => Auth::id(),
            ]);

            return redirect()->back()->with('success_swal', 'Tải lên tài liệu thành công.');
        }

        return redirect()->back()->with('error_swal', 'File tải lên không hợp lệ.');
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
}
