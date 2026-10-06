<?php

namespace App\Http\Controllers;

use App\Models\TechnicalTicket;
use App\Models\TechnicalSupportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TechnicalSupportLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of technical support logs.
     */
    public function index(Request $request)
    {
        if (!Gate::allows('manage_technical_support_logs')) {
            abort(403, 'Bạn không có quyền xem nhật ký hỗ trợ.');
        }

        $query = TechnicalSupportLog::with(['ticket', 'user']);

        $currentUserId = auth()->id();
        $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user()->hasRole('technical_lead');

        // Non-leads/admins only see their own support logs or logs for their assigned tickets
        if (!$isManagerOrAdmin && !$isTechLeadRole) {
            if (auth()->user()->hasRole('technical_engineer')) {
                $query->where(function ($q) use ($currentUserId) {
                    $q->where('user_id', $currentUserId)
                      ->orWhereHas('ticket', function ($tq) use ($currentUserId) {
                          $tq->where('assigned_to', $currentUserId)
                             ->orWhereHas('assignedEngineers', function ($eq) use ($currentUserId) {
                                 $eq->where('users.id', $currentUserId);
                             });
                      });
                });
            } else {
                $query->whereHas('ticket', function ($tq) use ($currentUserId) {
                    $tq->where('created_by', $currentUserId)
                       ->orWhere('sales_owner_id', $currentUserId);
                });
            }
        }

        // Filters
        if ($request->filled('date_from')) {
            $query->whereDate('log_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('log_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('ticket_id')) {
            $query->where('technical_ticket_id', $request->input('ticket_id'));
        }
        if ($request->filled('work_category')) {
            $query->where('work_category', $request->input('work_category'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('support_content', 'like', "%{$search}%")
                  ->orWhere('customer_info', 'like', "%{$search}%")
                  ->orWhere('contact_info', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $supportLogs = $query->orderBy('log_date', 'desc')->orderBy('created_at', 'desc')->paginate(15);

        $engineers = \App\Models\User::where('status', 'active')->orderBy('name')->get();
        $tickets = TechnicalTicket::orderBy('code', 'desc')->get();
        $customers = \App\Models\Customer::orderBy('name')->get();

        return view('technical.support_logs.index', compact('supportLogs', 'engineers', 'tickets', 'customers'));
    }

    public function export(Request $request)
    {
        if (!Gate::allows('manage_technical_support_logs')) {
            abort(403, 'Bạn không có quyền xuất nhật ký hỗ trợ.');
        }

        $query = TechnicalSupportLog::with(['ticket', 'user'])->orderByDesc('log_date')->orderByDesc('id');
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin', 'director', 'sales_manager', 'technical_lead'])) {
            $query->where('user_id', $user->id);
        }
        foreach (['date_from', 'date_to', 'user_id', 'ticket_id', 'work_category'] as $field) {
            if (!$request->filled($field)) continue;
            match ($field) {
                'date_from' => $query->whereDate('log_date', '>=', $request->$field),
                'date_to' => $query->whereDate('log_date', '<=', $request->$field),
                'ticket_id' => $query->where('technical_ticket_id', $request->$field),
                default => $query->where($field, $request->$field),
            };
        }

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Ngày', 'Kỹ sư', 'Loại công việc', 'Ticket', 'Nội dung', 'Khách hàng', 'Liên hệ', 'Trạng thái', 'Ghi chú']);
            foreach ($query->cursor() as $log) {
                fputcsv($out, [$log->log_date?->format('d/m/Y'), $log->user?->name, $log->work_category_label, $log->ticket?->code, $log->support_content, $log->customer_info, $log->contact_info, $log->status_label, $log->notes]);
            }
            fclose($out);
        }, 'nhat-ky-ky-thuat-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Store a newly created support log from the centralized list.
     */
    public function storeCentralized(Request $request)
    {
        if (!Gate::allows('manage_technical_support_logs')) {
            abort(403, 'Bạn không có quyền quản lý nhật ký hỗ trợ.');
        }

        $request->validate([
            'technical_ticket_id' => 'nullable|exists:technical_tickets,id',
            'log_date' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'work_category' => 'required|in:on_call,after_hours',
            'support_content' => 'required|string',
            'status' => 'required|string',
            'serial_number' => 'nullable|string|max:255',
            'customer_info' => 'nullable|string|max:255',
            'contact_info' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $log = new TechnicalSupportLog($request->all());
        $log->save();

        // Update the ticket status based on the report if ticket is specified
        if ($request->filled('technical_ticket_id')) {
            $ticket = TechnicalTicket::findOrFail($request->input('technical_ticket_id'));
            $oldStatus = $ticket->status;
            $newStatus = $request->input('status');

            if ($newStatus === 'completed' && !auth()->user()->can('complete_technical_tickets')) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['status' => 'Bạn không có quyền chuyển trạng thái ticket sang Hoàn tất.']);
            }
            
            $ticketUpdateData = ['status' => $newStatus];
            
            if (in_array($newStatus, ['completed', 'closed'])) {
                if (!in_array($oldStatus, ['completed', 'closed'])) {
                    $ticketUpdateData['resolved_at'] = Carbon::now();
                }
            } else {
                $ticketUpdateData['resolved_at'] = null;
            }
            
            $ticket->update($ticketUpdateData);
        }

        return redirect()->route('technical.support-logs.index')
            ->with('success', 'Đã thêm nhật ký hỗ trợ mới thành công.');
    }

    /**
     * Store a newly created support log.
     */
    public function store(Request $request, $ticketId)
    {
        if (!Gate::allows('manage_technical_support_logs')) {
            abort(403, 'Bạn không có quyền quản lý nhật ký hỗ trợ.');
        }

        $ticket = TechnicalTicket::findOrFail($ticketId);

        if (!$ticket->canUserUpdateProgress(auth()->user())) {
            abort(403, 'Bạn không có quyền thêm nhật ký hỗ trợ cho ticket này (chỉ Kỹ sư đang thực hiện hoặc Lead mới có quyền).');
        }

        $request->validate([
            'log_date' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'work_category' => 'required|in:on_call,after_hours',
            'support_content' => 'required|string',
            'status' => 'required|string',
            'serial_number' => 'nullable|string|max:255',
            'customer_info' => 'nullable|string|max:255',
            'contact_info' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $log = new TechnicalSupportLog($request->all());
        $log->technical_ticket_id = $ticket->id;
        $log->save();

        // Update the ticket status based on the latest report
        $oldStatus = $ticket->status;
        $newStatus = $request->input('status');

        if ($newStatus === 'completed' && !auth()->user()->can('complete_technical_tickets')) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['status' => 'Bạn không có quyền chuyển trạng thái ticket sang Hoàn tất.']);
        }
        
        $ticketUpdateData = ['status' => $newStatus];
        
        if (in_array($newStatus, ['completed', 'closed'])) {
            if (!in_array($oldStatus, ['completed', 'closed'])) {
                $ticketUpdateData['resolved_at'] = Carbon::now();
            }
        } else {
            $ticketUpdateData['resolved_at'] = null;
        }
        
        $ticket->update($ticketUpdateData);

        return redirect()->route('technical-tickets.show', $ticket->id)
            ->with('success', 'Đã thêm nhật ký hỗ trợ mới và cập nhật trạng thái ticket.');
    }

    /**
     * Update the specified support log.
     */
    public function update(Request $request, $ticketId, $id)
    {
        if (!Gate::allows('manage_technical_support_logs')) {
            abort(403, 'Bạn không có quyền quản lý nhật ký hỗ trợ.');
        }

        $ticket = TechnicalTicket::findOrFail($ticketId);

        if (!$ticket->canUserUpdateProgress(auth()->user())) {
            abort(403, 'Bạn không có quyền chỉnh sửa nhật ký hỗ trợ của ticket này.');
        }

        $log = TechnicalSupportLog::where('technical_ticket_id', $ticket->id)->findOrFail($id);

        $request->validate([
            'log_date' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'work_category' => 'required|in:on_call,after_hours',
            'support_content' => 'required|string',
            'status' => 'required|string',
            'serial_number' => 'nullable|string|max:255',
            'customer_info' => 'nullable|string|max:255',
            'contact_info' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $log->update($request->all());

        // Update the ticket status based on the latest updated report
        $newStatus = $request->input('status');

        if ($newStatus === 'completed' && !auth()->user()->can('complete_technical_tickets')) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['status' => 'Bạn không có quyền chuyển trạng thái ticket sang Hoàn tất.']);
        }

        $ticketUpdateData = ['status' => $newStatus];
        
        if (in_array($newStatus, ['completed', 'closed'])) {
            $ticketUpdateData['resolved_at'] = Carbon::now();
        } else {
            $ticketUpdateData['resolved_at'] = null;
        }
        
        $ticket->update($ticketUpdateData);

        return redirect()->route('technical-tickets.show', $ticket->id)
            ->with('success', 'Cập nhật nhật ký hỗ trợ thành công.');
    }

    /**
     * Remove the specified support log.
     */
    public function destroy($ticketId, $id)
    {
        if (!Gate::allows('manage_technical_support_logs')) {
            abort(403, 'Bạn không có quyền quản lý nhật ký hỗ trợ.');
        }

        $ticket = TechnicalTicket::findOrFail($ticketId);

        if (!$ticket->canUserUpdateProgress(auth()->user())) {
            abort(403, 'Bạn không có quyền xóa nhật ký hỗ trợ của ticket này.');
        }

        $log = TechnicalSupportLog::where('technical_ticket_id', $ticketId)->findOrFail($id);
        $log->delete();

        return redirect()->route('technical-tickets.show', $ticketId)
            ->with('success', 'Đã xóa nhật ký hỗ trợ.');
    }

    /**
     * Update centralized support log.
     */
    public function updateCentralized(Request $request, $id)
    {
        if (!Gate::allows('manage_technical_support_logs')) {
            abort(403, 'Bạn không có quyền quản lý nhật ký hỗ trợ.');
        }

        $log = TechnicalSupportLog::findOrFail($id);

        $request->validate([
            'technical_ticket_id' => 'nullable|exists:technical_tickets,id',
            'log_date' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'work_category' => 'required|in:on_call,after_hours',
            'support_content' => 'required|string',
            'status' => 'required|string',
            'serial_number' => 'nullable|string|max:255',
            'customer_info' => 'nullable|string|max:255',
            'contact_info' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $log->update($request->all());

        // Update the ticket status based on the report if ticket is specified
        if ($request->filled('technical_ticket_id')) {
            $ticket = TechnicalTicket::findOrFail($request->input('technical_ticket_id'));
            $newStatus = $request->input('status');
            $ticketUpdateData = ['status' => $newStatus];
            
            if (in_array($newStatus, ['completed', 'closed'])) {
                $ticketUpdateData['resolved_at'] = Carbon::now();
            } else {
                $ticketUpdateData['resolved_at'] = null;
            }
            
            $ticket->update($ticketUpdateData);
        }

        return redirect()->route('technical.support-logs.index')
            ->with('success', 'Cập nhật nhật ký hỗ trợ thành công.');
    }

    /**
     * Remove centralized support log.
     */
    public function destroyCentralized($id)
    {
        if (!Gate::allows('manage_technical_support_logs')) {
            abort(403, 'Bạn không có quyền quản lý nhật ký hỗ trợ.');
        }

        $log = TechnicalSupportLog::findOrFail($id);
        $log->delete();

        return redirect()->route('technical.support-logs.index')
            ->with('success', 'Đã xóa nhật ký hỗ trợ.');
    }
}
