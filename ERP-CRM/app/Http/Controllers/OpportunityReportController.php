<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OpportunityReportController extends Controller
{
    /**
     * Display the opportunity meeting frequency report dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Opportunity::class);

        // Get filter inputs or retrieve from session
        $periodType = $request->input('period_type', session('opp_report_period_type', 'month'));
        $startDate = $request->input('start_date', session('opp_report_start_date'));
        $endDate = $request->input('end_date', session('opp_report_end_date'));
        $assignedTo = $request->input('assigned_to');
        $customerId = $request->input('customer_id');
        $activityType = $request->input('activity_type');
        $status = $request->input('status');
        $searchCustomer = $request->input('search_customer');

        // Parse predefined periods if custom dates are empty
        if (!$startDate || !$endDate) {
            $dates = $this->parsePredefinedPeriod($periodType);
            $startDate = $dates['start'];
            $endDate = $dates['end'];
        }

        // Store filters in session for stickiness
        session([
            'opp_report_period_type' => $periodType,
            'opp_report_start_date' => $startDate,
            'opp_report_end_date' => $endDate,
        ]);

        // Start build query
        $query = Opportunity::with(['customer', 'assignedTo', 'technicalUser'])
            ->whereBetween('activity_date', [$startDate, $endDate]);

        // Check permission restrictions (Sales Reps see only their own)
        $user = auth()->user();
        $isManager = $user->hasAnyRole(['super_admin', 'admin', 'sales_manager']);
        if (!$isManager) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id)
                  ->orWhere('technical_user_id', $user->id);
            });
        } else {
            // Managers can filter by Sales Rep
            if ($assignedTo) {
                $query->where('assigned_to', $assignedTo);
            }
        }

        // Apply filters
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
        if ($activityType) {
            $query->where('activity_type', $activityType);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($searchCustomer) {
            $query->where(function ($q) use ($searchCustomer) {
                $q->where('eu_company_name', 'like', '%' . $searchCustomer . '%')
                  ->orWhereHas('customer', function ($cq) use ($searchCustomer) {
                      $cq->where('name', 'like', '%' . $searchCustomer . '%');
                  });
            });
        }

        // 1. Summary statistics
        $stats = $this->getSummaryStats(clone $query);

        // 2. Charts Data
        $charts = [
            'timeline' => $this->getTimelineData(clone $query),
            'activity_types' => $this->getActivityTypeData(clone $query),
            'statuses' => $this->getStatusData(clone $query),
            'top_customers' => $this->getTopCustomersData(clone $query),
        ];

        if ($isManager) {
            $charts['top_sales_reps'] = $this->getTopSalesRepsData(clone $query);
        }

        // 3. Paginated detailed list
        $activities = $query->latest('activity_date')
            ->latest('start_time')
            ->paginate(5)
            ->withQueryString();

        // Dropdowns for filtering
        $users = User::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $activityTypes = Opportunity::ACTIVITY_TYPES;
        $statuses = Opportunity::STATUSES;

        return view('opportunities.report', compact(
            'stats', 'charts', 'activities', 'users', 'customers',
            'activityTypes', 'statuses', 'periodType', 'startDate', 'endDate',
            'assignedTo', 'customerId', 'activityType', 'status', 'searchCustomer', 'isManager'
        ));
    }

    /**
     * Compute summary statistics card data.
     */
    private function getSummaryStats($query): array
    {
        $total = $query->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $completionRate = $total > 0 ? ($completed / $total) * 100 : 0;
        $totalDuration = (clone $query)->sum('duration_minutes');
        $avgPotential = (clone $query)->whereNotNull('potential_rating')
            ->where('potential_rating', '>', 0)
            ->avg(DB::raw('CAST(potential_rating AS UNSIGNED)'));

        return [
            'total' => $total,
            'completed' => $completed,
            'completion_rate' => round($completionRate, 1),
            'total_duration' => $totalDuration,
            'avg_potential' => round($avgPotential ?? 0, 1),
        ];
    }

    /**
     * Get meeting count trend over time.
     */
    private function getTimelineData($query): array
    {
        $data = $query->selectRaw('activity_date, COUNT(*) as count')
            ->groupBy('activity_date')
            ->orderBy('activity_date')
            ->get();

        return [
            'labels' => $data->map(fn($item) => $item->activity_date->format('d/m/Y'))->toArray(),
            'counts' => $data->pluck('count')->toArray(),
        ];
    }

    /**
     * Get activity type breakdown count.
     */
    private function getActivityTypeData($query): array
    {
        $data = $query->selectRaw('activity_type, COUNT(*) as count')
            ->groupBy('activity_type')
            ->get();

        $labels = [];
        $counts = [];

        foreach ($data as $item) {
            $label = Opportunity::ACTIVITY_TYPES[$item->activity_type] ?? $item->activity_type;
            $labels[] = $label;
            $counts[] = $item->count;
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
        ];
    }

    /**
     * Get activity status breakdown count.
     */
    private function getStatusData($query): array
    {
        $data = $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $labels = [];
        $counts = [];

        foreach ($data as $item) {
            $label = Opportunity::STATUSES[$item->status] ?? $item->status;
            $labels[] = $label;
            $counts[] = $item->count;
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
        ];
    }

    /**
     * Aggregates and returns top 10 customers/End Users by visit frequency.
     */
    private function getTopCustomersData($query): array
    {
        // Fetch all matching opportunities to resolve polymorphic-like customer display names cleanly
        $opportunities = $query->get();
        $customerCounts = [];

        foreach ($opportunities as $opp) {
            $name = $opp->customer_display_name;
            $customerCounts[$name] = ($customerCounts[$name] ?? 0) + 1;
        }

        arsort($customerCounts);
        $topTen = array_slice($customerCounts, 0, 10, true);

        return [
            'labels' => array_keys($topTen),
            'counts' => array_values($topTen),
        ];
    }

    /**
     * Aggregates and returns top 10 sales reps by activity frequency.
     */
    private function getTopSalesRepsData($query): array
    {
        $data = $query->selectRaw('assigned_to, COUNT(*) as count')
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->with('assignedTo')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'labels' => $data->map(fn($item) => $item->assignedTo->name ?? 'Không xác định')->toArray(),
            'counts' => $data->pluck('count')->toArray(),
        ];
    }

    /**
     * Parse predefined period types to start and end dates.
     */
    private function parsePredefinedPeriod(string $periodType): array
    {
        $now = Carbon::now();

        return match ($periodType) {
            'today' => [
                'start' => $now->copy()->startOfDay()->format('Y-m-d'),
                'end' => $now->copy()->endOfDay()->format('Y-m-d'),
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek()->format('Y-m-d'),
                'end' => $now->copy()->endOfWeek()->format('Y-m-d'),
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $now->copy()->endOfMonth()->format('Y-m-d'),
            ],
            'quarter' => [
                'start' => $now->copy()->startOfQuarter()->format('Y-m-d'),
                'end' => $now->copy()->endOfQuarter()->format('Y-m-d'),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear()->format('Y-m-d'),
                'end' => $now->copy()->endOfYear()->format('Y-m-d'),
            ],
            default => [
                'start' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $now->copy()->endOfMonth()->format('Y-m-d'),
            ],
        };
    }
}
