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
        
        // Detailed Datasets for BOD & Manager direct oversight
        $detailedDeals = $this->getDetailedDeals($parsedFilters);
        $salesOrders = $this->getDetailedSalesOrders($parsedFilters);
        $salesPerformance = $this->getSalesPerformanceMatrix($parsedFilters);
        $detailedInventory = $this->getDetailedInventoryList($parsedFilters);
        $detailedMarketing = $this->getDetailedMarketingList($parsedFilters);

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
            'detailed_deals' => $detailedDeals,
            'sales_orders' => $salesOrders,
            'sales_performance' => $salesPerformance,
            'detailed_inventory' => $detailedInventory,
            'detailed_marketing' => $detailedMarketing,
            'cross_view_360' => $crossView360,
        ];
    }

    /** Resolve filters for drill-down requests using the same RBAC scope as KPI data. */
    public function resolveFilters(array $filters = [], ?User $authUser = null): array
    {
        $parsedFilters = $this->parseFilters($filters);
        $this->applyRbacToFilters($parsedFilters, $authUser);

        return $parsedFilters;
    }

    /**
     * Parse date and filter parameters
     */
    private function parseFilters(array $filters): array
    {
        $periodType = $filters['period_type'] ?? 'month';
        $now = Carbon::now();

        // Date inputs only apply to the explicit custom range. The UI retains
        // the previous calculated range, so accepting them for quick periods
        // (month/year/...) would silently override the newly selected period.
        if ($periodType === 'custom' && !empty($filters['date_from']) && !empty($filters['date_to'])) {
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

        // Roles are stored as stable slugs, not their display names.  Checking
        // values such as "Admin" here made the seeded `super_admin` account
        // fall through to the Sales scope and caused the BOD dashboard to show
        // only that user's orders.
        // `view_all_sales` is deliberately included here so an administrator
        // can make dashboard access flexible through the role matrix or a
        // per-user permission, without editing source code or a job title.
        if ($authUser->hasAnyRole(['director', 'admin', 'super_admin'])
            || $authUser->can('view_all_sales')
            || ($authUser->position && in_array(strtoupper($authUser->position), ['BOD', 'CEO', 'DIRECTOR', 'GIÁM ĐỐC', 'SYSTEM ADMINISTRATOR']))) {
            return;
        }

        // If user is Manager / Team Leader
        if ($authUser->position === 'Manager'
            || str_contains(strtolower($authUser->position ?? ''), 'leader')
            || $authUser->hasAnyRole(['sales_manager', 'warehouse_manager', 'purchase_manager', 'technical_lead'])
            || $authUser->can('view_business_dashboard')) {
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
                return $q->whereIn('products.id', function($sub) use ($vId) {
                    $sub->select('product_id')->from('supplier_price_list_items')
                        ->join('supplier_price_lists', 'supplier_price_list_items.supplier_price_list_id', '=', 'supplier_price_lists.id')
                        ->where('supplier_price_lists.supplier_id', $vId);
                });
            })
            ->when($filters['model_code'], function ($q, $m) {
                $q->where(function ($product) use ($m) {
                    $product->where('products.code', 'like', "%{$m}%")
                        ->orWhere('products.name', 'like', "%{$m}%");
                });
            })
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
     * Get Detailed Projects & Deals with rich columns for direct BOD/Manager view
     */
    public function getDetailedDeals(array $filters): array
    {
        $now = Carbon::now();

        $query = Project::query()
            ->with([
                'customer:id,name,name_en,abv_name,tax_code,phone',
                'vendor:id,name,code',
                'manager:id,name,department,employee_code,email',
            ])
            ->when($filters['team'], fn($q, $t) => $q->where('assigned_team', $t))
            ->when($filters['sales_id'], fn($q, $s) => $q->where('manager_id', $s))
            ->when($filters['customer_id'], fn($q, $c) => $q->where('customer_id', $c))
            ->when($filters['vendor_id'], fn($q, $v) => $q->where('vendor_id', $v))
            ->when($filters['deal_type'], fn($q, $d) => $q->where('deal_type', $d))
            ->when($filters['period_type'] !== 'all' && $filters['date_from'] && $filters['date_to'], function ($q) use ($filters) {
                $q->whereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
            });

        $deals = $query->latest('updated_at')->limit(150)->get();

        $mappedDeals = $deals->map(function ($project) use ($now) {
            $isPmSlaOverdue = empty($project->initial_processed_at) && $project->initial_sla_due_at && $project->initial_sla_due_at < $now;
            $isVendorSlaOverdue = in_array($project->registration_status, ['vendor_processing', 'vendor_reminded', 'processing']) && $project->vendor_due_at && $project->vendor_due_at < $now;
            $isNearingExpiry = !in_array($project->registration_status, ['closed_won', 'closed_lost', 'cancelled']) && $project->updated_at < $now->copy()->subDays(60);

            $dealVal = (float) ($project->order_value > 0 ? $project->order_value : ($project->budget > 0 ? $project->budget : 0));

            return [
                'id' => $project->id,
                'code' => $project->code ?? ('PRJ-' . str_pad($project->id, 5, '0', STR_PAD_LEFT)),
                'name' => $project->name,
                'customer_name' => $project->customer ? ($project->customer->abv_name ?: $project->customer->name) : ($project->customer_name ?: 'N/A'),
                'customer_tax_code' => $project->customer?->tax_code,
                'eu_name' => $project->eu_name_vi ?: ($project->eu_name_abbr ?: ($project->eu_name_en ?: '')),
                'vendor_name' => $project->vendor?->name ?: 'N/A',
                'vendor_code' => $project->vendor?->code ?: '',
                'manager_name' => $project->manager?->name ?: 'Chưa gán',
                'manager_team' => $project->assigned_team ?: ($project->manager?->department ?: 'N/A'),
                'deal_type' => $project->deal_type ?: 'project',
                'stage' => $project->stage ?: 'Discovery',
                'registration_status' => $project->registration_status ?: 'pending_intake',
                'budget' => (float) $project->budget,
                'order_value' => (float) $project->order_value,
                'deal_value' => $dealVal,
                'po_code' => $project->po_code,
                'order_date' => $project->order_date ? Carbon::parse($project->order_date)->format('d/m/Y') : null,
                'initial_sla_due_at' => $project->initial_sla_due_at ? Carbon::parse($project->initial_sla_due_at)->format('d/m/Y H:i') : null,
                'vendor_due_at' => $project->vendor_due_at ? Carbon::parse($project->vendor_due_at)->format('d/m/Y') : null,
                'last_updated' => $project->updated_at ? $project->updated_at->diffForHumans() : '',
                'updated_at_raw' => $project->updated_at ? $project->updated_at->format('d/m/Y H:i') : '',
                'is_sla_overdue' => $isPmSlaOverdue || $isVendorSlaOverdue,
                'is_pm_sla_overdue' => $isPmSlaOverdue,
                'is_vendor_sla_overdue' => $isVendorSlaOverdue,
                'is_nearing_expiry' => $isNearingExpiry,
                'close_reason' => $project->close_reason,
            ];
        });

        return [
            'total_count' => $mappedDeals->count(),
            'total_value' => (float) $mappedDeals->sum('deal_value'),
            'items' => $mappedDeals->toArray(),
        ];
    }

    /**
     * Get Detailed Sales Orders (Đơn hàng bán) with Margin, P&L, Payment tracking
     */
    public function getDetailedSalesOrders(array $filters): array
    {
        $query = Sale::query()
            ->with([
                'customer:id,name,abv_name,tax_code,phone',
                'user:id,name,department,employee_code',
                'project:id,code,name',
                'paymentSchedules',
            ])
            ->when($filters['team'], function ($q, $team) {
                $q->whereHas('user', fn($u) => $u->where('department', $team));
            })
            ->when($filters['sales_id'], fn($q, $s) => $q->where('user_id', $s))
            ->when($filters['customer_id'], fn($q, $c) => $q->where('customer_id', $c))
            // A sale may contain items from more than one vendor/project, so
            // apply these dashboard filters through the order lines instead
            // of assuming a vendor exists on the sales header.
            ->when($filters['vendor_id'], fn($q, $v) => $q->whereHas('items', fn($items) => $items->where('supplier_id', $v)))
            ->when($filters['model_code'], function ($q, $model) {
                $q->whereHas('items', function ($items) use ($model) {
                    $items->where(function ($item) use ($model) {
                        $item->where('product_name', 'like', "%{$model}%")
                            ->orWhereHas('product', fn($product) => $product
                                ->where('code', 'like', "%{$model}%")
                                ->orWhere('name', 'like', "%{$model}%"));
                    });
                });
            })
            ->when($filters['deal_type'], function ($q, $dealType) {
                $q->where(function ($sale) use ($dealType) {
                    $sale->whereHas('project', fn($project) => $project->where('deal_type', $dealType))
                        ->orWhereHas('items.project', fn($project) => $project->where('deal_type', $dealType));

                    // Runrate orders do not necessarily have a Project row.
                    if ($dealType === 'runrate') {
                        $sale->orWhere(function ($retail) {
                            $retail->where('type', 'retail')->whereNull('project_id');
                        });
                    }
                });
            })
            ->when($filters['period_type'] !== 'all' && $filters['date_from'] && $filters['date_to'], function ($q) use ($filters) {
                $q->where(function ($sq) use ($filters) {
                    $sq->whereBetween('date', [$filters['date_from'], $filters['date_to']])
                       ->orWhereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
                });
            });

        $orders = $query->latest('date')->latest('id')->limit(150)->get();

        $totalRevenue = 0;
        $totalCost = 0;
        $totalMargin = 0;
        $totalPaid = 0;
        $totalDebt = 0;

        $mappedOrders = $orders->map(function ($order) use (&$totalRevenue, &$totalCost, &$totalMargin, &$totalPaid, &$totalDebt) {
            // P&L calculates profit from revenue excluding VAT. Use the same
            // base here; `total` is the invoice/payment amount including VAT.
            $rev = (float) $order->subtotal * (1 - ((float) $order->discount / 100));
            $cost = (float) $order->cost;
            $margin = $order->margin !== null ? (float) $order->margin : ($rev - $cost);
            $marginPct = $rev > 0 ? round(($margin / $rev) * 100, 1) : 0;
            $paid = (float) $order->paid_amount;
            $debt = (float) ($order->debt_amount ?: max(0, $rev - $paid));

            $totalRevenue += $rev;
            $totalCost += $cost;
            $totalMargin += $margin;
            $totalPaid += $paid;
            $totalDebt += $debt;

            return [
                'id' => $order->id,
                'code' => $order->code,
                'date' => $order->date ? Carbon::parse($order->date)->format('d/m/Y') : ($order->created_at ? $order->created_at->format('d/m/Y') : ''),
                'customer_name' => $order->customer ? ($order->customer->abv_name ?: $order->customer->name) : ($order->customer_name ?: 'N/A'),
                'customer_tax_code' => $order->customer?->tax_code,
                'sales_name' => $order->user?->name ?: 'N/A',
                'sales_team' => $order->user?->department ?: 'N/A',
                'project_code' => $order->project?->code,
                'project_name' => $order->project?->name,
                'total_revenue' => $rev,
                'total_including_vat' => (float) $order->total,
                'cost' => $cost,
                'margin' => $margin,
                'margin_percent' => $marginPct,
                'paid_amount' => $paid,
                'debt_amount' => $debt,
                'payment_status' => $order->payment_status ?: ($paid >= $rev && $rev > 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')),
                'status' => $order->status ?: 'confirmed',
                'pl_status' => $order->pl_status ?: 'pending',
                'delivery_date' => $order->delivery_date ? Carbon::parse($order->delivery_date)->format('d/m/Y') : null,
                'invoice_date' => $order->invoice_date ? Carbon::parse($order->invoice_date)->format('d/m/Y') : null,
                'payment_due_date' => $order->payment_due_date ? Carbon::parse($order->payment_due_date)->format('d/m/Y') : null,
            ];
        });

        $avgMarginPct = $totalRevenue > 0 ? round(($totalMargin / $totalRevenue) * 100, 1) : 0;

        return [
            'total_count' => $mappedOrders->count(),
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'total_margin' => $totalMargin,
            'avg_margin_percent' => $avgMarginPct,
            'total_paid' => $totalPaid,
            'total_debt' => $totalDebt,
            'items' => $mappedOrders->toArray(),
        ];
    }

    /**
     * Get Sales & Team Performance Matrix
     */
    public function getSalesPerformanceMatrix(array $filters): array
    {
        $salesUsers = User::query()
            ->when($filters['team'], fn($q, $t) => $q->where('department', $t))
            ->when($filters['sales_id'], fn($q, $s) => $q->where('id', $s))
            ->where(function($q) {
                $q->whereNotNull('employee_code')
                  ->orWhereHas('roles', fn($r) => $r->whereIn('name', ['Sales', 'Sales Executive', 'Account Manager']));
            })
            ->select('id', 'name', 'department', 'employee_code')
            ->get();

        $performance = [];

        foreach ($salesUsers as $user) {
            // Projects by this sales rep
            $projectsQuery = Project::where('manager_id', $user->id);
            $totalDeals = (clone $projectsQuery)->count();
            $activeDeals = (clone $projectsQuery)->whereNotIn('registration_status', ['closed_won', 'closed_lost', 'cancelled', 'expired'])->count();
            $pipelineVal = (clone $projectsQuery)->whereNotIn('registration_status', ['closed_won', 'closed_lost', 'cancelled', 'expired'])
                ->sum(DB::raw('COALESCE(order_value, budget, 0)'));

            $wonCount = (clone $projectsQuery)->where('registration_status', 'closed_won')->count();
            $wonValue = (clone $projectsQuery)->where('registration_status', 'closed_won')->sum(DB::raw('COALESCE(order_value, budget, 0)'));
            $lostCount = (clone $projectsQuery)->where('registration_status', 'closed_lost')->count();
            $winRate = $totalDeals > 0 ? round(($wonCount / $totalDeals) * 100, 1) : 0;

            // Overdue SLA deals for this sales rep
            $overdueDeals = (clone $projectsQuery)
                ->where(function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereNull('initial_processed_at')
                           ->whereNotNull('initial_sla_due_at')
                           ->where('initial_sla_due_at', '<', now());
                    })->orWhere(function ($sq) {
                        $sq->whereIn('registration_status', ['vendor_processing', 'vendor_reminded', 'processing'])
                           ->whereNotNull('vendor_due_at')
                           ->where('vendor_due_at', '<', now());
                    });
                })->count();

            // Sales orders by this sales rep
            $salesOrdersQuery = Sale::where('user_id', $user->id)
                ->when($filters['period_type'] !== 'all' && $filters['date_from'] && $filters['date_to'], function ($q) use ($filters) {
                    $q->where(function ($sq) use ($filters) {
                        $sq->whereBetween('date', [$filters['date_from'], $filters['date_to']])
                            ->orWhereBetween('created_at', [$filters['date_from'], $filters['date_to']]);
                    });
                });
            $ordersCount = (clone $salesOrdersQuery)->count();
            $salesRevenue = (clone $salesOrdersQuery)->sum(DB::raw('subtotal * (1 - COALESCE(discount, 0) / 100)'));
            $salesCost = (clone $salesOrdersQuery)->sum('cost');
            $salesMargin = (clone $salesOrdersQuery)->sum(DB::raw(
                'COALESCE(margin, (subtotal * (1 - COALESCE(discount, 0) / 100)) - cost)'
            ));
            $marginPct = $salesRevenue > 0 ? round(($salesMargin / $salesRevenue) * 100, 1) : 0;

            if ($totalDeals > 0 || $ordersCount > 0) {
                $performance[] = [
                    'sales_id' => $user->id,
                    'sales_name' => $user->name,
                    'employee_code' => $user->employee_code ?: ('SALES-' . $user->id),
                    'team' => $user->department ?: 'Sales',
                    'total_deals' => $totalDeals,
                    'active_deals' => $activeDeals,
                    'pipeline_value' => (float) $pipelineVal,
                    'won_count' => $wonCount,
                    'won_value' => (float) $wonValue,
                    'lost_count' => $lostCount,
                    'win_rate' => $winRate,
                    'overdue_deals' => $overdueDeals,
                    'orders_count' => $ordersCount,
                    'sales_revenue' => (float) $salesRevenue,
                    'sales_margin' => (float) $salesMargin,
                    'margin_percent' => $marginPct,
                ];
            }
        }

        // Sort by sales revenue or pipeline value desc
        usort($performance, fn($a, $b) => ($b['sales_revenue'] + $b['pipeline_value']) <=> ($a['sales_revenue'] + $a['pipeline_value']));

        return $performance;
    }

    /**
     * Get Detailed Inventory List with Aging and Warehouse distribution
     */
    public function getDetailedInventoryList(array $filters): array
    {
        $now = Carbon::now();

        $query = Inventory::query()
            ->with(['product:id,name,code,category,unit', 'warehouse:id,name,code'])
            ->when($filters['vendor_id'], function ($q, $vendorId) {
                $q->whereHas('product.supplierPriceListItems.priceList', function ($priceList) use ($vendorId) {
                    $priceList->where('supplier_id', $vendorId);
                });
            })
            ->when($filters['model_code'], function($q, $m) {
                $q->whereHas('product', fn($p) => $p->where('code', 'like', "%{$m}%")->orWhere('name', 'like', "%{$m}%"));
            })
            ->where('stock', '>', 0);

        $items = $query->orderByDesc('updated_at')->limit(100)->get();

        $totalStock = 0;
        $totalValuation = 0;
        $agedCount = 0;
        $agedValue = 0;

        $mapped = $items->map(function ($inv) use ($now, &$totalStock, &$totalValuation, &$agedCount, &$agedValue) {
            $stock = (int) $inv->stock;
            $avgCost = (float) $inv->avg_cost;
            $val = $stock * $avgCost;

            $updatedAt = $inv->updated_at ?: $inv->created_at;
            $daysInStock = $updatedAt ? $now->diffInDays($updatedAt) : 0;
            $isAged = $daysInStock > 90;

            $totalStock += $stock;
            $totalValuation += $val;
            if ($isAged) {
                $agedCount++;
                $agedValue += $val;
            }

            return [
                'id' => $inv->id,
                'product_id' => $inv->product_id,
                'product_name' => $inv->product?->name ?: 'N/A',
                'product_code' => $inv->product?->code ?: '',
                'category' => $inv->product?->category ?: '',
                'unit' => $inv->product?->unit ?: '',
                'warehouse_name' => $inv->warehouse?->name ?: 'Kho chính',
                'stock' => $stock,
                'min_stock' => (int) $inv->min_stock,
                'avg_cost' => $avgCost,
                'total_value' => $val,
                'days_in_stock' => $daysInStock,
                'is_aged' => $isAged,
                'updated_at' => $updatedAt ? $updatedAt->format('d/m/Y') : '',
            ];
        });

        return [
            'total_stock' => $totalStock,
            'total_valuation' => $totalValuation,
            'aged_count' => $agedCount,
            'aged_value' => $agedValue,
            'items' => $mapped->toArray(),
        ];
    }

    /**
     * Get Detailed Marketing Events List
     */
    public function getDetailedMarketingList(array $filters): array
    {
        $query = MarketingEvent::query()
            ->with(['vendor:id,name,code', 'creator:id,name'])
            ->when($filters['vendor_id'], fn($q, $v) => $q->where('vendor_id', $v))
            ->when($filters['period_type'] !== 'all' && $filters['date_from'] && $filters['date_to'], function ($q) use ($filters) {
                $q->whereBetween('event_date', [$filters['date_from'], $filters['date_to']]);
            });

        $events = $query->latest('event_date')->limit(50)->get();

        $mapped = $events->map(function ($ev) {
            $budget = (float) $ev->budget;
            $actual = (float) $ev->actual_cost;
            $isOverrun = $actual > $budget && $budget > 0;
            $diff = $actual - $budget;

            $ticketCount = MarketingRequest::where('marketing_event_id', $ev->id)->count();
            $ticketCompleted = MarketingRequest::where('marketing_event_id', $ev->id)->whereNotNull('completed_at')->count();

            return [
                'id' => $ev->id,
                'code' => $ev->code,
                'title' => $ev->title,
                'vendor_name' => $ev->vendor?->name ?: 'N/A',
                'event_date' => $ev->event_date ? Carbon::parse($ev->event_date)->format('d/m/Y') : '',
                'budget' => $budget,
                'actual_cost' => $actual,
                'variance' => $diff,
                'is_overrun' => $isOverrun,
                'status' => $ev->status ?: 'planned',
                'ticket_count' => $ticketCount,
                'ticket_completed' => $ticketCompleted,
            ];
        });

        return [
            'total_count' => $mapped->count(),
            'total_budget' => (float) $mapped->sum('budget'),
            'total_actual' => (float) $mapped->sum('actual_cost'),
            'items' => $mapped->toArray(),
        ];
    }

    /**
     * Get Deep Entity Details for Quick-View Drawer / Modal
     */
    public function getEntityDetail(string $type, int $id): ?array
    {
        if ($type === 'project' || $type === 'deal') {
            $project = Project::with([
                'customer',
                'vendor',
                'manager',
                'initialProcessedBy',
                'sales',
                'opportunities',
                'saleItems.product',
                'notes.user',
                'statusUpdates.user',
            ])->find($id);

            if (!$project) return null;

            return [
                'type' => 'project',
                'id' => $project->id,
                'code' => $project->code,
                'name' => $project->name,
                'description' => $project->description,
                'customer' => $project->customer ? [
                    'id' => $project->customer->id,
                    'name' => $project->customer->name,
                    'tax_code' => $project->customer->tax_code,
                    'phone' => $project->customer->phone,
                    'email' => $project->customer->email,
                    'address' => $project->customer->address,
                ] : null,
                'end_user' => [
                    'name' => $project->eu_name_vi ?: $project->eu_name_en,
                    'tax_code' => $project->eu_tax_code,
                    'industry' => $project->eu_industry,
                    'province' => $project->eu_province,
                ],
                'vendor' => $project->vendor ? [
                    'id' => $project->vendor->id,
                    'name' => $project->vendor->name,
                    'code' => $project->vendor->code,
                ] : null,
                'manager' => $project->manager ? [
                    'id' => $project->manager->id,
                    'name' => $project->manager->name,
                    'department' => $project->manager->department,
                    'employee_code' => $project->manager->employee_code,
                ] : null,
                'assigned_team' => $project->assigned_team,
                'deal_type' => $project->deal_type,
                'stage' => $project->stage,
                'registration_status' => $project->registration_status,
                'budget' => (float) $project->budget,
                'order_value' => (float) $project->order_value,
                'po_code' => $project->po_code,
                'order_date' => $project->order_date ? Carbon::parse($project->order_date)->format('d/m/Y') : null,
                'initial_sla_due_at' => $project->initial_sla_due_at ? Carbon::parse($project->initial_sla_due_at)->format('d/m/Y H:i') : null,
                'vendor_due_at' => $project->vendor_due_at ? Carbon::parse($project->vendor_due_at)->format('d/m/Y') : null,
                'vendor_deal_id' => $project->vendor_deal_id,
                'close_reason' => $project->close_reason,
                'close_note' => $project->close_note,
                'created_at' => $project->created_at ? $project->created_at->format('d/m/Y H:i') : '',
                'updated_at' => $project->updated_at ? $project->updated_at->format('d/m/Y H:i') : '',
                'bom_data' => $project->bom_data,
                'sales_list' => $project->sales->map(fn($s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'total' => (float) $s->total,
                    'status' => $s->status,
                    'date' => $s->date ? Carbon::parse($s->date)->format('d/m/Y') : '',
                ]),
                'notes' => $project->notes->map(fn($n) => [
                    'user_name' => $n->user?->name ?: 'Hệ thống',
                    'content' => $n->content,
                    'created_at' => $n->created_at ? $n->created_at->format('d/m/Y H:i') : '',
                ]),
                'status_updates' => $project->statusUpdates->map(fn($u) => [
                    'user_name' => $u->user?->name ?: 'Sales',
                    'stage' => $u->stage,
                    'forecast_stage' => $u->forecast_stage,
                    'note' => $u->note,
                    'created_at' => $u->created_at ? $u->created_at->format('d/m/Y H:i') : '',
                ]),
            ];
        }

        if ($type === 'sale' || $type === 'order') {
            $sale = Sale::with([
                'customer',
                'user',
                'project',
                'items.product',
                'paymentSchedules',
                'expenses',
            ])->find($id);

            if (!$sale) return null;

            return [
                'type' => 'sale',
                'id' => $sale->id,
                'code' => $sale->code,
                'customer' => $sale->customer ? [
                    'name' => $sale->customer->name,
                    'tax_code' => $sale->customer->tax_code,
                    'phone' => $sale->customer->phone,
                    'address' => $sale->customer->address,
                ] : null,
                'sales_rep' => $sale->user ? [
                    'name' => $sale->user->name,
                    'department' => $sale->user->department,
                    'employee_code' => $sale->user->employee_code,
                ] : null,
                'project' => $sale->project ? [
                    'code' => $sale->project->code,
                    'name' => $sale->project->name,
                ] : null,
                'date' => $sale->date ? Carbon::parse($sale->date)->format('d/m/Y') : '',
                'delivery_date' => $sale->delivery_date ? Carbon::parse($sale->delivery_date)->format('d/m/Y') : '',
                'invoice_date' => $sale->invoice_date ? Carbon::parse($sale->invoice_date)->format('d/m/Y') : '',
                'subtotal' => (float) $sale->subtotal,
                'vat_amount' => (float) $sale->vat_amount,
                'revenue_excluding_vat' => (float) $sale->subtotal * (1 - ((float) $sale->discount / 100)),
                'total' => (float) $sale->total,
                'cost' => (float) $sale->cost,
                // `0` is a valid margin. Do not use truthiness here, and do
                // not fall back to VAT-inclusive invoice total for P&L.
                'margin' => $sale->margin !== null
                    ? (float) $sale->margin
                    : ((float) $sale->subtotal * (1 - ((float) $sale->discount / 100)) - (float) $sale->cost),
                'margin_percent' => (float) $sale->margin_percent,
                'paid_amount' => (float) $sale->paid_amount,
                'debt_amount' => (float) $sale->debt_amount,
                'payment_status' => $sale->payment_status,
                'status' => $sale->status,
                'pl_status' => $sale->pl_status,
                'note' => $sale->note,
                'items' => $sale->items->map(fn($item) => [
                    'product_name' => $item->product?->name ?: $item->item_name,
                    'product_code' => $item->product?->code ?: $item->item_code,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->price,
                    'cost_price' => (float) $item->cost_price,
                    'total' => (float) $item->total,
                    'margin' => (float) ($item->total - ($item->quantity * $item->cost_price)),
                ]),
                'payment_schedules' => $sale->paymentSchedules->map(fn($ps) => [
                    'milestone_name' => $ps->milestone_name,
                    'percentage' => (float) $ps->percentage,
                    'amount' => (float) $ps->amount,
                    'due_date' => $ps->due_date ? Carbon::parse($ps->due_date)->format('d/m/Y') : '',
                    'status' => $ps->status,
                ]),
            ];
        }

        return null;
    }

    /**
     * Get Inventory & Stock Metrics (Khối 2)
     */
    public function getInventoryMetrics(array $filters): array
    {
        $invQuery = Inventory::query()
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->when($filters['vendor_id'], function ($q, $vendorId) {
                $q->whereIn('products.id', function ($products) use ($vendorId) {
                    $products->select('product_id')
                        ->from('supplier_price_list_items')
                        ->join('supplier_price_lists', 'supplier_price_list_items.supplier_price_list_id', '=', 'supplier_price_lists.id')
                        ->where('supplier_price_lists.supplier_id', $vendorId);
                });
            })
            ->when($filters['model_code'], function ($q, $m) {
                $q->where(function ($product) use ($m) {
                    $product->where('products.code', 'like', "%{$m}%")
                        ->orWhere('products.name', 'like', "%{$m}%");
                });
            });

        $totalStock = (clone $invQuery)->sum('inventories.stock');
        $totalValuation = (clone $invQuery)->sum(DB::raw('inventories.stock * inventories.avg_cost'));
        $lowStockCount = (clone $invQuery)->whereColumn('inventories.stock', '<', 'inventories.min_stock')->count();

        // Product Items Breakdown (Available, Borrowed, Reserved)
        $itemStats = ProductItem::query()
            ->when($filters['vendor_id'] || $filters['model_code'], function ($q) use ($filters) {
                $q->whereHas('product', function ($product) use ($filters) {
                    if ($filters['vendor_id']) {
                        $product->whereHas('supplierPriceListItems.priceList', function ($priceList) use ($filters) {
                            $priceList->where('supplier_id', $filters['vendor_id']);
                        });
                    }

                    if ($filters['model_code']) {
                        $product->where(function ($model) use ($filters) {
                            $model->where('code', 'like', "%{$filters['model_code']}%")
                                ->orWhere('name', 'like', "%{$filters['model_code']}%");
                        });
                    }
                });
            })
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
                // The sales table stores the invoice amount in `total`; the
                // previous `total_amount` reference caused every customer
                // filter request to fail with a SQL error.
                $totalRevenue = Sale::where('customer_id', $customer->id)->sum('total');

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
            $products = Product::where('code', 'like', "%{$modelCode}%")
                ->orWhere('name', 'like', "%{$modelCode}%")
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
