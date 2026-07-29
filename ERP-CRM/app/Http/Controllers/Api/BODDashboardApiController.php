<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BODDashboardService;
use App\Models\Project;
use App\Models\Inventory;
use App\Models\MarketingEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BODDashboardApiController extends Controller
{
    public function __construct(
        private BODDashboardService $bodService
    ) {}

    /**
     * Handle AJAX filter updates for BOD Dashboard
     */
    public function filter(Request $request): JsonResponse
    {
        $filters = $request->only([
            'period_type',
            'date_from',
            'date_to',
            'team',
            'sales_id',
            'customer_id',
            'vendor_id',
            'model_code',
            'deal_type',
        ]);

        $data = $this->bodService->getDashboardData($filters, auth()->user());

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get drill-down details modal data (e.g. SLA projects, aged inventory, MKT overruns)
     */
    public function drillDown(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $filters = $request->only(['team', 'sales_id', 'customer_id', 'vendor_id', 'model_code']);

        switch ($type) {
            case 'sla_overdue':
                $items = Project::query()
                    ->when($filters['team'] ?? null, fn($q, $t) => $q->where('assigned_team', $t))
                    ->when($filters['sales_id'] ?? null, fn($q, $s) => $q->where('manager_id', $s))
                    ->when($filters['customer_id'] ?? null, fn($q, $c) => $q->where('customer_id', $c))
                    ->when($filters['vendor_id'] ?? null, fn($q, $v) => $q->where('vendor_id', $v))
                    ->where(function ($q) {
                        $q->where(function($sub) {
                            $sub->whereNull('initial_processed_at')
                                ->whereNotNull('initial_sla_due_at')
                                ->where('initial_sla_due_at', '<', now());
                        })->orWhere(function($sub) {
                            $sub->whereIn('registration_status', ['vendor_processing', 'vendor_reminded', 'processing'])
                                ->whereNotNull('vendor_due_at')
                                ->where('vendor_due_at', '<', now());
                        });
                    })
                    ->latest()
                    ->limit(20)
                    ->get(['id', 'code', 'name', 'registration_status', 'initial_sla_due_at', 'vendor_due_at', 'budget']);

                return response()->json(['success' => true, 'title' => 'Danh sách dự án quá hạn SLA', 'type' => 'projects', 'items' => $items]);

            case 'aged_inventory':
                $items = Inventory::query()
                    ->join('products', 'inventories.product_id', '=', 'products.id')
                    ->join('warehouses', 'inventories.warehouse_id', '=', 'warehouses.id')
                    ->where('inventories.stock', '>', 0)
                    ->where('inventories.updated_at', '<', now()->subDays(90))
                    ->select('inventories.id', 'products.name as product_name', 'products.code as product_code', 'products.model_number', 'warehouses.name as warehouse_name', 'inventories.stock', 'inventories.avg_cost', 'inventories.updated_at')
                    ->limit(20)
                    ->get();

                return response()->json(['success' => true, 'title' => 'Danh sách tồn kho > 90 ngày', 'type' => 'inventory', 'items' => $items]);

            case 'mkt_overrun':
                $items = MarketingEvent::query()
                    ->whereRaw('actual_cost > budget')
                    ->where('budget', '>', 0)
                    ->latest()
                    ->limit(20)
                    ->get(['id', 'code', 'title', 'budget', 'actual_cost', 'status', 'event_date']);

                return response()->json(['success' => true, 'title' => 'Sự kiện MKT vượt ngân sách', 'type' => 'marketing', 'items' => $items]);

            default:
                return response()->json(['success' => false, 'message' => 'Loại drill-down không hợp lệ'], 400);
        }
    }
}
