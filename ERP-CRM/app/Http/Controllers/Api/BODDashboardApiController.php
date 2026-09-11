<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BODDashboardService;
use App\Models\Project;
use App\Models\Inventory;
use App\Models\MarketingEvent;
use App\Models\MarketingRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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

        try {
            $data = $this->bodService->getDashboardData($filters, auth()->user());

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Unable to apply BOD dashboard filters', [
                'filters' => $filters,
                'user_id' => auth()->id(),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể tải dữ liệu theo bộ lọc. Vui lòng thử lại.',
            ], 500);
        }
    }

    /**
     * Get drill-down details modal data (e.g. SLA projects, aged inventory, MKT overruns)
     */
    public function drillDown(Request $request): JsonResponse
    {
        $type = $request->input('type');
        $filters = $this->bodService->resolveFilters(
            $request->only(['period_type', 'date_from', 'date_to', 'team', 'sales_id', 'customer_id', 'vendor_id', 'model_code', 'deal_type']),
            auth()->user()
        );

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
                    ->select('inventories.id', 'products.name as product_name', 'products.code as product_code', 'warehouses.name as warehouse_name', 'inventories.stock', 'inventories.avg_cost', 'inventories.updated_at')
                    ->limit(20)
                    ->get();

                return response()->json(['success' => true, 'title' => 'Danh sách tồn kho > 90 ngày', 'type' => 'inventory', 'items' => $items]);

            case 'mkt_overrun':
                $items = MarketingEvent::query()
                    ->when($filters['vendor_id'], fn ($q, $v) => $q->where('vendor_id', $v))
                    ->whereRaw('actual_cost > budget')
                    ->where('budget', '>', 0)
                    ->latest()
                    ->limit(20)
                    ->get(['id', 'code', 'title', 'budget', 'actual_cost', 'status', 'event_date']);

                return response()->json(['success' => true, 'title' => 'Sự kiện MKT vượt ngân sách', 'type' => 'marketing', 'items' => $items]);

            case 'nearing_expiry':
                $items = $this->projectQuery($filters)
                    ->whereNotIn('registration_status', ['closed_won', 'closed_lost', 'cancelled'])
                    ->where('updated_at', '<', now()->subDays(60))
                    ->latest('updated_at')->limit(50)->get();
                return response()->json(['success' => true, 'title' => 'Dự án chưa cập nhật trên 60 ngày', 'type' => 'projects', 'items' => $items]);

            case 'pipeline_active':
                $items = $this->projectQuery($filters, true)
                    ->whereNotIn('registration_status', ['closed_won', 'closed_lost', 'cancelled', 'expired'])
                    ->latest()->limit(50)->get();
                return response()->json(['success' => true, 'title' => 'Dự án Pipeline đang hoạt động', 'type' => 'projects', 'items' => $items]);

            case 'pipeline_status':
                $status = $request->string('status')->toString();
                $items = $this->projectQuery($filters)
                    ->when($status, fn ($query) => $query->where('registration_status', $status))
                    ->latest()->limit(50)->get();
                return response()->json(['success' => true, 'title' => 'Dự án theo trạng thái', 'type' => 'projects', 'items' => $items]);

            case 'inventory':
                $items = Inventory::query()->with(['product:id,name,code', 'warehouse:id,name'])
                    ->when($filters['model_code'], fn ($q, $m) => $q->whereHas('product', fn ($p) => $p->where('code', 'like', "%{$m}%")->orWhere('name', 'like', "%{$m}%")))
                    ->where('stock', '>', 0)->orderByDesc('updated_at')->limit(50)
                    ->get(['id', 'product_id', 'warehouse_id', 'stock', 'min_stock', 'avg_cost', 'updated_at']);
                return response()->json(['success' => true, 'title' => 'Chi tiết tồn kho khả dụng', 'type' => 'inventory', 'items' => $items]);

            case 'marketing_events':
                $items = MarketingEvent::query()->when($filters['vendor_id'], fn ($q, $v) => $q->where('vendor_id', $v))
                    ->whereBetween('event_date', [$filters['date_from'], $filters['date_to']])
                    ->latest('event_date')->limit(50)
                    ->get(['id', 'code', 'title', 'budget', 'actual_cost', 'status', 'event_date']);
                return response()->json(['success' => true, 'title' => 'Sự kiện Marketing trong kỳ', 'type' => 'marketing', 'items' => $items]);

            case 'marketing_tickets':
                $items = MarketingRequest::query()->with('event:id,code,title')
                    ->when($filters['vendor_id'], fn ($q, $v) => $q->whereHas('event', fn ($event) => $event->where('vendor_id', $v)))
                    ->latest()->limit(50)
                    ->get(['id', 'marketing_event_id', 'code', 'support_content', 'status', 'deadline', 'completed_at']);
                return response()->json(['success' => true, 'title' => 'Chi tiết yêu cầu Marketing', 'type' => 'tickets', 'items' => $items]);

            default:
                return response()->json(['success' => false, 'message' => 'Loại drill-down không hợp lệ'], 400);
        }
    }

    /**
     * Get full deep details of an individual deal or sale for drawer quick-view
     */
    public function entityDetail(Request $request): JsonResponse
    {
        $type = $request->input('type'); // 'project', 'deal', 'sale', 'order'
        $id = (int) $request->input('id');

        if (!$type || !$id) {
            return response()->json(['success' => false, 'message' => 'Tham số không hợp lệ'], 400);
        }

        $detail = $this->bodService->getEntityDetail($type, $id);

        if (!$detail) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy dữ liệu chi tiết'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detail,
        ]);
    }

    private function projectQuery(array $filters, bool $applyDate = false)
    {
        return Project::query()->with(['customer:id,name', 'vendor:id,name', 'manager:id,name'])
            ->when($applyDate, fn ($q) => $q->whereBetween('created_at', [$filters['date_from'], $filters['date_to']]))
            ->when($filters['team'], fn ($q, $v) => $q->where('assigned_team', $v))
            ->when($filters['sales_id'], fn ($q, $v) => $q->where('manager_id', $v))
            ->when($filters['customer_id'], fn ($q, $v) => $q->where('customer_id', $v))
            ->when($filters['vendor_id'], fn ($q, $v) => $q->where('vendor_id', $v))
            ->when($filters['deal_type'], fn ($q, $v) => $q->where('deal_type', $v))
            ->select(['id', 'code', 'name', 'customer_id', 'vendor_id', 'manager_id', 'registration_status', 'budget', 'order_value', 'updated_at', 'initial_sla_due_at', 'vendor_due_at']);
    }
}
