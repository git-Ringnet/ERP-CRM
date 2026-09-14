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
        $query = Opportunity::with(['customer', 'assignedTo', 'technicalUser', 'project'])
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

        // 1. Neglected Customers (Khách hàng lâu chưa gặp / cần chăm sóc lại)
        $neglectedCustomers = $this->getNeglectedCustomers($user, $isManager, $assignedTo);

        // 2. Summary statistics
        $stats = $this->getSummaryStats(clone $query, count($neglectedCustomers));

        // 3. Clean Charts Data (Chỉ giữ 2 biểu đồ có giá trị thực tế nhất)
        $charts = [
            'top_customers' => $this->getTopCustomersData(clone $query),
        ];

        if ($isManager) {
            $charts['top_sales_reps'] = $this->getTopSalesRepsData(clone $query);
        }

        // 4. Paginated detailed activities list (15 dòng)
        $activities = $query->latest('activity_date')
            ->latest('start_time')
            ->paginate(15)
            ->withQueryString();

        // Dropdowns for filtering
        $users = User::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $activityTypes = Opportunity::ACTIVITY_TYPES;
        $statuses = Opportunity::STATUSES;

        return view('opportunities.report', compact(
            'stats', 'charts', 'activities', 'neglectedCustomers', 'users', 'customers',
            'activityTypes', 'statuses', 'periodType', 'startDate', 'endDate',
            'assignedTo', 'customerId', 'activityType', 'status', 'searchCustomer', 'isManager'
        ));
    }

    /**
     * Compute practical summary statistics.
     */
    private function getSummaryStats($query, int $neglectedCount): array
    {
        $total = $query->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
        
        // Đếm số khách hàng / đối tác duy nhất được chăm sóc trong kỳ
        $uniquePartnerCount = (clone $query)->whereNotNull('customer_id')->distinct('customer_id')->count('customer_id');
        $uniqueEuCount = (clone $query)->whereNull('customer_id')->whereNotNull('eu_company_name')->distinct('eu_company_name')->count('eu_company_name');
        $uniqueCustomers = $uniquePartnerCount + $uniqueEuCount;

        // Số cơ hội đã chuyển đổi thành Dự án thành công
        $convertedProjects = (clone $query)->whereNotNull('project_id')->count();
        $conversionRate = $total > 0 ? round(($convertedProjects / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'completion_rate' => $completionRate,
            'unique_customers' => $uniqueCustomers,
            'converted_projects' => $convertedProjects,
            'conversion_rate' => $conversionRate,
            'neglected_count' => $neglectedCount,
        ];
    }

    /**
     * Lấy danh sách khách hàng đang bị "bỏ quên" (quá 30 ngày chưa có cuộc gặp nào hoặc chưa từng được gặp).
     */
    private function getNeglectedCustomers(User $user, bool $isManager, $assignedTo = null)
    {
        $custQuery = Customer::query();

        if (!$isManager) {
            $custQuery->where(function ($q) use ($user) {
                $q->where('am', $user->id)
                  ->orWhere('am', 'like', '%' . $user->name . '%')
                  ->orWhereHas('opportunities', function ($oq) use ($user) {
                      $oq->where('assigned_to', $user->id);
                  });
            });
        } elseif ($assignedTo) {
            $assignedUser = User::find($assignedTo);
            if ($assignedUser) {
                $custQuery->where(function ($q) use ($assignedUser) {
                    $q->where('am', $assignedUser->id)
                      ->orWhere('am', 'like', '%' . $assignedUser->name . '%')
                      ->orWhereHas('opportunities', function ($oq) use ($assignedUser) {
                          $oq->where('assigned_to', $assignedUser->id);
                      });
                });
            }
        }

        // Lấy tất cả khách hàng kèm ngày gặp gần nhất
        $customers = $custQuery->withMax('opportunities as last_meeting_date', 'activity_date')
            ->withCount('opportunities as total_meetings')
            ->get();

        $now = Carbon::now();

        $neglected = $customers->map(function ($c) use ($now) {
            if ($c->last_meeting_date) {
                $lastDate = Carbon::parse($c->last_meeting_date);
                $c->days_since = (int) $lastDate->diffInDays($now, false);
                $c->last_meeting_formatted = $lastDate->format('d/m/Y');
            } else {
                $c->days_since = 999;
                $c->last_meeting_formatted = 'Chưa từng gặp';
            }

            // Gán mức độ cảnh báo
            if ($c->days_since >= 90 || $c->days_since === 999) {
                $c->alert_level = 'high'; // Đỏ: Báo động khẩn cấp
                $c->alert_text = $c->days_since === 999 ? 'Chưa từng gặp' : ($c->days_since . ' ngày');
            } elseif ($c->days_since >= 60) {
                $c->alert_level = 'medium'; // Cam: Cần chú ý
                $c->alert_text = $c->days_since . ' ngày';
            } else {
                $c->alert_level = 'low'; // Vàng: Nhắc nhở
                $c->alert_text = $c->days_since . ' ngày';
            }

            return $c;
        })
        ->filter(function ($c) {
            return $c->days_since >= 30;
        })
        ->sortByDesc('days_since')
        ->values();

        return $neglected;
    }

    /**
     * Top 10 khách hàng được tiếp cận nhiều nhất trong kỳ.
     */
    private function getTopCustomersData($query): array
    {
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
     * Năng suất tiếp cận của từng Sales (Manager).
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

