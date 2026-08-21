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
            ->with('success_swal', 'Đã thêm nhật ký hỗ trợ mới thành công.');
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

        $request->validate([
            'log_date' => 'required|date',
            'user_id' => 'required|exists:users,id',
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
            ->with('success_swal', 'Đã thêm nhật ký hỗ trợ mới và cập nhật trạng thái ticket.');
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
        $log = TechnicalSupportLog::where('technical_ticket_id', $ticket->id)->findOrFail($id);

        $request->validate([
            'log_date' => 'required|date',
            'user_id' => 'required|exists:users,id',
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
        $ticketUpdateData = ['status' => $newStatus];
        
        if (in_array($newStatus, ['completed', 'closed'])) {
            $ticketUpdateData['resolved_at'] = Carbon::now();
        } else {
            $ticketUpdateData['resolved_at'] = null;
        }
        
        $ticket->update($ticketUpdateData);

        return redirect()->route('technical-tickets.show', $ticket->id)
            ->with('success_swal', 'Cập nhật nhật ký hỗ trợ thành công.');
    }

    /**
     * Remove the specified support log.
     */
    public function destroy($ticketId, $id)
    {
        if (!Gate::allows('manage_technical_support_logs')) {
            abort(403, 'Bạn không có quyền quản lý nhật ký hỗ trợ.');
        }

        $log = TechnicalSupportLog::where('technical_ticket_id', $ticketId)->findOrFail($id);
        $log->delete();

        return redirect()->route('technical-tickets.show', $ticketId)
            ->with('success_swal', 'Đã xóa nhật ký hỗ trợ.');
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
            ->with('success_swal', 'Cập nhật nhật ký hỗ trợ thành công.');
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
            ->with('success_swal', 'Đã xóa nhật ký hỗ trợ.');
    }
}
