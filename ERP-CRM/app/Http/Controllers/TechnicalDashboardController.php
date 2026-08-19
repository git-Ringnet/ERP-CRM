<?php

namespace App\Http\Controllers;

use App\Models\TechnicalTicket;
use App\Models\User;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Supplier;
use App\Exports\TechnicalTicketsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class TechnicalDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the Technical Dashboard.
     */
    public function index(Request $request)
    {
        if (!Gate::allows('view_technical_dashboard')) {
            abort(403, 'Bạn không có quyền xem dashboard kỹ thuật.');
        }

        $filters = $request->only([
            'date_from', 'date_to', 'assigned_to', 'customer_id', 'supplier_id', 'project_id', 'work_type', 'priority', 'sla_status', 'created_by'
        ]);

        // Base Query
        $baseQuery = TechnicalTicket::query();

        $now = Carbon::now();

        // Apply filters
        if (!empty($filters['date_from'])) {
            $baseQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $baseQuery->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['assigned_to'])) {
            $baseQuery->where('assigned_to', $filters['assigned_to']);
        }
        if (!empty($filters['customer_id'])) {
            $baseQuery->where('customer_id', $filters['customer_id']);
        }
        if (!empty($filters['supplier_id'])) {
            $baseQuery->where('supplier_id', $filters['supplier_id']);
        }
        if (!empty($filters['project_id'])) {
            $baseQuery->where('project_id', $filters['project_id']);
        }
        if (!empty($filters['work_type'])) {
            $baseQuery->where('work_type', $filters['work_type']);
        }
        if (!empty($filters['priority'])) {
            $baseQuery->where('priority', $filters['priority']);
        }
        if (!empty($filters['created_by'])) {
            $baseQuery->where('created_by', $filters['created_by']);
        }
        if (isset($filters['sla_status']) && $filters['sla_status'] !== '') {
            $sla = $filters['sla_status'];
            if ($sla === 'overdue') {
                $baseQuery->whereNotNull('sla_deadline')
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
                $baseQuery->where(function ($q) use ($now) {
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

        // 1. Team Dashboard Metrics
        $totalTickets = (clone $baseQuery)->count();
        $openTickets = (clone $baseQuery)->whereIn('status', ['open', 'assigned'])->count();
        $closedTickets = (clone $baseQuery)->whereIn('status', ['completed', 'closed'])->count();
        
        $pendingTickets = (clone $baseQuery)->where('status', 'pending')->count();
        $escalateTickets = (clone $baseQuery)->where('status', 'escalate')->count();

        // Overdue & SLA Calculation
        $overdueTickets = (clone $baseQuery)
            ->whereNotNull('sla_deadline')
            ->where(function ($q) use ($now) {
                $q->where(function ($sq) {
                    $sq->whereIn('status', ['completed', 'closed'])
                       ->whereColumn('resolved_at', '>', 'sla_deadline');
                })->orWhere(function ($sq) use ($now) {
                    $sq->whereNotIn('status', ['completed', 'closed'])
                       ->where('sla_deadline', '<', $now);
                });
            })->count();

        $slaTicketsCount = (clone $baseQuery)->whereNotNull('sla_deadline')->count();
        $slaRate = 100;
        if ($slaTicketsCount > 0) {
            $onTimeSlaTickets = $slaTicketsCount - $overdueTickets;
            $slaRate = round(($onTimeSlaTickets / $slaTicketsCount) * 100, 1);
        }

        // 2. Per Engineer Dashboard metrics
        $engineerStats = [];
        $activeUsers = User::where('status', 'active')->orderBy('name')->get();
        
        foreach ($activeUsers as $user) {
            $engQuery = (clone $baseQuery)->where('assigned_to', $user->id);
            $engAssigned = (clone $engQuery)->count();
            
            if ($engAssigned === 0) {
                continue; // Skip engineers with no tickets to keep dashboard clean
            }

            $engCompleted = (clone $engQuery)->whereIn('status', ['completed', 'closed'])->count();
            $engPending = (clone $engQuery)->whereIn('status', ['pending', 'escalate'])->count();
            
            $engOverdue = (clone $engQuery)
                ->whereNotNull('sla_deadline')
                ->where(function ($q) use ($now) {
                    $q->where(function ($sq) {
                        $sq->whereIn('status', ['completed', 'closed'])
                           ->whereColumn('resolved_at', '>', 'sla_deadline');
                    })->orWhere(function ($sq) use ($now) {
                        $sq->whereNotIn('status', ['completed', 'closed'])
                           ->where('sla_deadline', '<', $now);
                    });
                })->count();

            // Avg Processing Time in hours
            $avgMinutes = (clone $engQuery)
                ->whereIn('status', ['completed', 'closed'])
                ->whereNotNull('resolved_at')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_min'))
                ->first()->avg_min;
            
            $avgHours = $avgMinutes ? round($avgMinutes / 60, 1) : 0;

            $engineerStats[] = [
                'engineer' => $user,
                'assigned' => $engAssigned,
                'completed' => $engCompleted,
                'pending' => $engPending,
                'overdue' => $engOverdue,
                'avg_time' => $avgHours,
            ];
        }

        // 3. Sales & Vendor & Project Breakdowns
        $salesStats = (clone $baseQuery)
            ->select('created_by', DB::raw('count(*) as count'))
            ->groupBy('created_by')
            ->with('creator')
            ->orderBy('count', 'desc')
            ->get();

        $vendorStats = (clone $baseQuery)
            ->select('supplier_id', DB::raw('count(*) as count'))
            ->groupBy('supplier_id')
            ->with('supplier')
            ->whereNotNull('supplier_id')
            ->orderBy('count', 'desc')
            ->get();

        $projectStats = (clone $baseQuery)
            ->select('project_id', DB::raw('count(*) as count'))
            ->groupBy('project_id')
            ->with('project')
            ->whereNotNull('project_id')
            ->orderBy('count', 'desc')
            ->get();

        // 4. Categories Breakdown
        $categoryStats = (clone $baseQuery)
            ->select('work_type', DB::raw('count(*) as count'))
            ->groupBy('work_type')
            ->orderBy('count', 'desc')
            ->get();

        // Entity lists for dropdowns
        $engineers = User::where('status', 'active')->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $salesUsers = User::where('status', 'active')
            ->whereHas('roles', function($q) {
                $q->whereIn('slug', ['sales_manager', 'sales_staff', 'super_admin', 'director']);
            })
            ->orderBy('name')
            ->get();

        return view('technical.dashboard', compact(
            'totalTickets', 'openTickets', 'closedTickets', 'pendingTickets', 'escalateTickets', 'overdueTickets', 'slaRate',
            'engineerStats', 'salesStats', 'vendorStats', 'projectStats', 'categoryStats',
            'engineers', 'customers', 'suppliers', 'projects', 'salesUsers', 'filters'
        ));
    }

    /**
     * Export Technical Tickets Report to Excel.
     */
    public function export(Request $request)
    {
        if (!Gate::allows('export_technical_tickets')) {
            abort(403, 'Bạn không có quyền xuất báo cáo kỹ thuật.');
        }

        $filters = $request->all();
        
        return Excel::download(
            new TechnicalTicketsExport($filters),
            'bao_cao_ky_thuat_' . date('Ymd_His') . '.xlsx'
        );
    }
}
