<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\MarketingEvent;
use App\Models\MarketingRequest;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Project;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BODDashboardService
{
    /**
     * Get complete BOD Dashboard data based on filters and user RBAC.
     *
     * @param array $filters
     * @param User|null $authUser
     * @return array
     */
    public function getDashboardData(array $filters = [], ?User $authUser = null): array
    {
        $parsedFilters = $this->parseFilters($filters);
        
        // RBAC Scoping check
        $this->applyRbacToFilters($parsedFilters, $authUser);

        $bottlenecks = $this->getBottleneckMetrics($parsedFilters);
        $pipelineData = $this->getPipelineMetrics($parsedFilters);
        $inventoryData = $this->getInventoryMetrics($parsedFilters);
        $marketingData = $this->getMarketingMetrics($parsedFilters);
        $kpiMatrix = $this->getKpiSummaryMatrix($pipelineData, $inventoryData, $marketingData, $parsedFilters);
        
        // 360 Cross-View contextual data if specific entity filtered
        $crossView360 = $this->getCrossViewData($parsedFilters);

        // Filter options for dropdowns
        $filterOptions = $this->getFilterOptions($authUser);

        return [
            'filters' => $parsedFilters,
            'filter_options' => $filterOptions,
            'bottlenecks' => $bottlenecks,
            'pipeline' => $pipelineData,
            'inventory' => $inventoryData,
            'marketing' => $marketingData,
            'kpi_matrix' => $kpiMatrix,
            'cross_view_360' => $crossView360,
        ];
    }

    /**
     * Parse date and filter parameters
     */
    private function parseFilters(array $filters): array
    {
        $periodType = $filters['period_type'] ?? 'month';
        $now = Carbon::now();

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
            $dateTo = Carbon::parse($filters['date_to'])->endOfDay();
            $periodType = 'custom';
        } else {
            [$dateFrom, $dateTo] = match ($periodType) {
                'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
                'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
                default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            };
        }

        return [
            'period_type' => $periodType,
            'date_from' => $dateFrom->format('Y-m-d H:i:s'),
            'date_to' => $dateTo->format('Y-m-d H:i:s'),
            'team' => $filters['team'] ?? null,
            'sales_id' => $filters['sales_id'] ?? null,
            'customer_id' => $filters['customer_id'] ?? null,
            'vendor_id' => $filters['vendor_id'] ?? null,
            'model_code' => $filters['model_code'] ?? null,
            'deal_type' => $filters['deal_type'] ?? null,
        ];
    }

    /**
     * Enforce RBAC rules on filter bounds
     */
    private function applyRbacToFilters(array &$filters, ?User $authUser): void
    {
        if (!$authUser) {
            return;
        }

        // If user is BOD / Admin (has role BOD or Admin or SuperAdmin or positions like BOD/CEO), no scope restriction
        if ($authUser->hasRole('BOD') || $authUser->hasRole('Admin') || $authUser->hasRole('Super Admin') || $authUser->position === 'BOD') {
            return;
        }

        // If user is Manager / Team Leader
        if ($authUser->position === 'Manager' || str_contains(strtolower($authUser->position ?? ''), 'leader') || $authUser->hasRole('Manager')) {
            if (empty($filters['team']) && $authUser->department) {
                $filters['team'] = $authUser->department;
            }
            return;
        }

        // Standard Sales User
        $filters['sales_id'] = $authUser->id;
    }

    /**
     * Get Bottleneck Indicators across all modules
     */
    public function getBottleneckMetrics(array $filters): array
    {
        $now = Carbon::now();

        // 1. Projects Overdue SLA (PM intake SLA < 4h or Vendor response overdue)
        $pmSlaOverdue = Project::query()
            ->when($filters['team'], fn($q) => $q->where('assigned_team', $filters['team']))
            ->when($filters['sales_id'], fn($q) => $q->where('manager_id', $filters['sales_id']))
            ->when($filters['customer_id'], fn($q) => $q->where('customer_id', $filters['customer_id']))
            ->when($filters['vendor_id'], fn($q) => $q->where('vendor_id', $filters['vendor_id']))
            ->whereNull('initial_processed_at')
            ->whereNotNull('initial_sla_due_at')
            ->where('initial_sla_due_at', '<', $now)
            ->count();

        $vendorSlaOverdue = Project::query()
            ->when($filters['team'], fn($q) => $q->where('assigned_team', $filters['team']))
            ->when($filters['sales_id'], fn($q) => $q->where('manager_id', $filters['sales_id']))
            ->when($filters['customer_id'], fn($q) => $q->where('customer_id', $filters['customer_id']))
            ->when($filters['vendor_id'], fn($q) => $q->where('vendor_id', $filters['vendor_id']))
            ->whereIn('registration_status', ['vendor_processing', 'vendor_reminded', 'processing'])
            ->whereNotNull('vendor_due_at')
            ->where('vendor_due_at', '<', $now)
            ->count();

        $totalSlaOverdue = $pmSlaOverdue + $vendorSlaOverdue;

        // 2. Aged Inventory (> 90 days unmoved or stored)
        $agedInventoryQuery = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->when($filters['vendor_id'], function($q, $vId) {
                return $q->where('products.brand', $vId)
                         ->orWhereIn('products.id', function($sub) use ($vId) {
                             $sub->select('product_id')->from('supplier_price_list_items')
                                 ->join('supplier_price_lists', 'supplier_price_list_items.supplier_price_list_id', '=', 'supplier_price_lists.id')
                                 ->where('supplier_price_lists.supplier_id', $vId);
                         });
            })
            ->when($filters['model_code'], fn($q, $m) => $q->where('products.model_number', 'like', "%{$m}%")->orWhere('products.code', 'like', "%{$m}%"))
            ->where('inventories.stock', '>', 0)
            ->where('inventories.updated_at', '<', $now->copy()->subDays(90));

        $agedInventoryCount = (clone $agedInventoryQuery)->count();
        $agedInventoryValue = (clone $agedInventoryQuery)->sum(DB::raw('stock * avg_cost'));

        // 3. Marketing Budget Overruns (actual_cost > budget)
        $mktOverrunCount = MarketingEvent::query()
            ->when($filters['vendor_id'], fn($q, $v) => $q->where('vendor_id', $v))
            ->whereRaw('actual_cost > budget')
            ->where('budget', '>', 0)
            ->count();

        // 4. Projects Nearing Expiry (No update in 60-90 days or due for 30-day update)
        $nearingExpiryCount = Project::query()
            ->when($filters['team'], fn($q) => $q->where('assigned_team', $filters['team']))
            ->when($filters['sales_id'], fn($q) => $q->where('manager_id', $filters['sales_id']))
            ->whereNotIn('registration_status', ['closed_won', 'closed_lost', 'cancelled'])
            ->where('updated_at', '<', $now->copy()->subDays(60))
            ->count();

        return [
            'total_sla_overdue' => $totalSlaOverdue,
            'pm_sla_overdue' => $pmSlaOverdue,
            'vendor_sla_overdue' => $vendorSlaOverdue,
            'aged_inventory_count' => $agedInventoryCount,
            'aged_inventory_value' => (float) $agedInventoryValue,
            'mkt_overrun_count' => $mktOverrunCount,
            'nearing_expiry_count' => $nearingExpiryCount,
        ];
    }

    /**
     * Get Pipeline & ĐKDA Metrics (Khối 1)
     */
    public function getPipelineMetrics(array $filters): array
    {
        $query = Project::query()
            ->when($filters['date_from'] && $filters['date_to'], function ($q) use ($filters) {
                $q->whereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
            })
            ->when($filters['team'], fn($q, $t) => $q->where('assigned_team', $t))
            ->when($filters['sales_id'], fn($q, $s) => $q->where('manager_id', $s))
            ->when($filters['customer_id'], fn($q, $c) => $q->where('customer_id', $c))
            ->when($filters['vendor_id'], fn($q, $v) => $q->where('vendor_id', $v))
            ->when($filters['deal_type'], fn($q, $d) => $q->where('deal_type', $d));

        // Active project count & pipeline value
        $activeProjects = (clone $query)->whereNotIn('registration_status', ['closed_won', 'closed_lost', 'cancelled', 'expired']);
        $totalActiveCount = $activeProjects->count();
        $totalPipelineValue = $activeProjects->sum(DB::raw('COALESCE(order_value, budget, 0)'));

        // Status breakdown
        $statusBreakdown = Project::query()
            ->when($filters['team'], fn($q, $t) => $q->where('assigned_team', $t))
            ->when($filters['sales_id'], fn($q, $s) => $q->where('manager_id', $s))
            ->when($filters['customer_id'], fn($q, $c) => $q->where('customer_id', $c))
            ->when($filters['vendor_id'], fn($q, $v) => $q->where('vendor_id', $v))
            ->select('registration_status', DB::raw('count(*) as count'), DB::raw('SUM(COALESCE(order_value, budget, 0)) as total_val'))
            ->groupBy('registration_status')
            ->get()
            ->pluck('count', 'registration_status')
            ->toArray();

        // Total projects in scope for rate calculations
        $totalInScope = (clone $query)->count();
        $closedWonCount = $statusBreakdown['closed_won'] ?? 0;
        $closedLostCount = $statusBreakdown['closed_lost'] ?? 0;
        $expiredCount = $statusBreakdown['expired'] ?? 0;
        $duplicateCount = $statusBreakdown['duplicate'] ?? 0;

        $winRate = $totalInScope > 0 ? round(($closedWonCount / $totalInScope) * 100, 1) : 0;
        $lossRate = $totalInScope > 0 ? round(($closedLostCount / $totalInScope) * 100, 1) : 0;

        // Top Vendors by Project Value
        $topVendors = Project::query()
            ->whereNotNull('vendor_id')
            ->join('suppliers', 'projects.vendor_id', '=', 'suppliers.id')
            ->select('suppliers.name as vendor_name', DB::raw('COUNT(projects.id) as project_count'), DB::raw('SUM(COALESCE(projects.order_value, projects.budget, 0)) as total_value'))
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_value')
            ->limit(5)
            ->get();

        // Top Customers by Project Value
        $topCustomers = Project::query()
            ->whereNotNull('customer_id')
            ->join('customers', 'projects.customer_id', '=', 'customers.id')
            ->select('customers.name as customer_name', DB::raw('COUNT(projects.id) as project_count'), DB::raw('SUM(COALESCE(projects.order_value, projects.budget, 0)) as total_value'))
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_value')
            ->limit(5)
            ->get();

        return [
            'total_active_count' => $totalActiveCount,
            'total_pipeline_value' => (float) $totalPipelineValue,
            'status_breakdown' => $statusBreakdown,
            'total_in_scope' => $totalInScope,
            'closed_won_count' => $closedWonCount,
            'closed_lost_count' => $closedLostCount,
            'expired_count' => $expiredCount,
            'duplicate_count' => $duplicateCount,
            'win_rate' => $winRate,
            'loss_rate' => $lossRate,
            'top_vendors' => $topVendors,
            'top_customers' => $topCustomers,
        ];
    }

    /**
     * Get Inventory & Stock Metrics (Khối 2)
     */
    public function getInventoryMetrics(array $filters): array
    {
        $invQuery = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->when($filters['model_code'], fn($q, $m) => $q->where('products.model_number', 'like', "%{$m}%")->orWhere('products.code', 'like', "%{$m}%"));

        $totalStock = (clone $invQuery)->sum('inventories.stock');
        $totalValuation = (clone $invQuery)->sum(DB::raw('inventories.stock * inventories.avg_cost'));
        $lowStockCount = (clone $invQuery)->whereColumn('inventories.stock', '<', 'inventories.min_stock')->count();

        // Product Items Breakdown (Available, Borrowed, Reserved)
        $itemStats = ProductItem::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $activeBorrowedCount = $itemStats['borrowed'] ?? 0;
        $activeReservedCount = $itemStats['reserved'] ?? 0;
        $availableCount = $itemStats['in_stock'] ?? 0;

        // Recent Exports in period
        $exportCount = DB::table('exports')
            ->whereBetween('date', [$filters['date_from'], $filters['date_to']])
            ->count();

        return [
            'total_stock' => (int) $totalStock,
            'total_valuation' => (float) $totalValuation,
            'low_stock_count' => $lowStockCount,
            'available_count' => $availableCount,
            'borrowed_count' => $activeBorrowedCount,
            'reserved_count' => $activeReservedCount,
            'export_count' => $exportCount,
        ];
    }

    /**
     * Get Marketing Metrics (Khối 3)
     */
    public function getMarketingMetrics(array $filters): array
    {
        $query = MarketingEvent::query()
            ->when($filters['vendor_id'], fn($q, $v) => $q->where('vendor_id', $v))
            ->when($filters['date_from'] && $filters['date_to'], function ($q) use ($filters) {
                $q->whereBetween('event_date', [$filters['date_from'], $filters['date_to']]);
            });

        $totalBudget = (clone $query)->sum('budget');
        $totalActualCost = (clone $query)->sum('actual_cost');
        $activeEventsCount = (clone $query)->whereIn('status', ['approved', 'in_progress', 'pending_approval'])->count();

        // Marketing Tickets SLA
        $totalTickets = MarketingRequest::query()->count();
        $onTimeTickets = MarketingRequest::query()
            ->whereNotNull('completed_at')
            ->whereNotNull('deadline')
            ->whereColumn('completed_at', '<=', 'deadline')
            ->count();

        $ticketSlaRate = $totalTickets > 0 ? round(($onTimeTickets / $totalTickets) * 100, 1) : 100;

        return [
            'total_budget' => (float) $totalBudget,
            'total_actual_cost' => (float) $totalActualCost,
            'active_events_count' => $activeEventsCount,
            'total_tickets' => $totalTickets,
            'on_time_tickets' => $onTimeTickets,
            'ticket_sla_rate' => $ticketSlaRate,
        ];
    }

    /**
     * Get KPI Summary Matrix (Khối 4 - Section D)
     */
    private function getKpiSummaryMatrix(array $pipeline, array $inventory, array $marketing, array $filters): array
    {
        return [
            'pipeline_group' => [
                'total_pipeline_val' => $pipeline['total_pipeline_value'],
                'win_rate' => $pipeline['win_rate'],
                'overdue_sla_count' => $pipeline['closed_won_count'],
            ],
            'inventory_group' => [
                'total_stock' => $inventory['total_stock'],
                'avg_holding_days' => 45,
                'export_count' => $inventory['export_count'],
            ],
            'marketing_group' => [
                'total_actual_cost' => $marketing['total_actual_cost'],
                'ticket_sla_rate' => $marketing['ticket_sla_rate'],
                'active_events' => $marketing['active_events_count'],
            ],
            'summary_group' => [
                'top_customers' => $pipeline['top_customers'],
                'top_vendors' => $pipeline['top_vendors'],
            ]
        ];
    }

    /**
     * Get Contextual 360 Cross View Data when a specific entity is selected
     */
    private function getCrossViewData(array $filters): ?array
    {
        // 1. Customer 360 Profile
        if (!empty($filters['customer_id'])) {
            $customer = Customer::find($filters['customer_id']);
            if ($customer) {
                $projects = Project::where('customer_id', $customer->id)->latest()->limit(10)->get();
                $sales = Sale::where('customer_id', $customer->id)->latest()->limit(10)->get();
                $totalRevenue = Sale::where('customer_id', $customer->id)->sum('total_amount');

                return [
                    'type' => 'customer',
                    'entity_id' => $customer->id,
                    'name' => $customer->name,
                    'company_name' => $customer->name_en ?? $customer->name,
                    'projects' => $projects,
                    'sales' => $sales,
                    'total_revenue' => (float) $totalRevenue,
                ];
            }
        }

        // 2. Vendor 360 View
        if (!empty($filters['vendor_id'])) {
            $vendor = Supplier::find($filters['vendor_id']);
            if ($vendor) {
                $projects = Project::where('vendor_id', $vendor->id)->latest()->limit(10)->get();
                $events = MarketingEvent::where('vendor_id', $vendor->id)->latest()->limit(10)->get();
                $pipelineVal = Project::where('vendor_id', $vendor->id)->sum(DB::raw('COALESCE(order_value, budget, 0)'));

                return [
                    'type' => 'vendor',
                    'entity_id' => $vendor->id,
                    'name' => $vendor->name,
                    'code' => $vendor->code,
                    'projects' => $projects,
                    'marketing_events' => $events,
                    'pipeline_value' => (float) $pipelineVal,
                ];
            }
        }

        // 3. Model Cross View
        if (!empty($filters['model_code'])) {
            $modelCode = $filters['model_code'];
            $products = Product::where('model_number', 'like', "%{$modelCode}%")
                ->orWhere('code', 'like', "%{$modelCode}%")
                ->get();

            $productIds = $products->pluck('id')->toArray();
            $stock = Inventory::whereIn('product_id', $productIds)->sum('stock');
            $valuation = Inventory::whereIn('product_id', $productIds)->sum(DB::raw('stock * avg_cost'));

            return [
                'type' => 'model',
                'model_code' => $modelCode,
                'matching_products_count' => count($products),
                'stock' => (int) $stock,
                'valuation' => (float) $valuation,
            ];
        }

        return null;
    }

    /**
     * Get Filter Options for Dropdowns
     */
    private function getFilterOptions(?User $authUser): array
    {
        $teams = User::whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department')
            ->toArray();

        $salesUsers = User::whereNotNull('employee_code')
            ->select('id', 'name', 'department', 'employee_code')
            ->orderBy('name')
            ->get();

        $customers = Customer::select('id', 'name', 'name_en', 'abv_name', 'tax_code')
            ->orderBy('name')
            ->limit(200)
            ->get();

        $vendors = Supplier::select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        $dealTypes = [
            'runrate' => 'Runrate (Hàng thương mại)',
            'project' => 'Dự án (Project Deal)',
            'hang_r' => 'Hàng R (Bảo hành/Thay thế)',
            'poc' => 'POC (Hàng demo/thử nghiệm)',
        ];

        return [
            'teams' => $teams,
            'sales_users' => $salesUsers,
            'customers' => $customers,
            'vendors' => $vendors,
            'deal_types' => $dealTypes,
        ];
    }
}
