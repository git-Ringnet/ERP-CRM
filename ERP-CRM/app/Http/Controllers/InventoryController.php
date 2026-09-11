<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Warehouse;
use App\Models\ProductItem;
use App\Models\InventoryCustomColumn;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of inventory.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Inventory::class);
        $currentUser = $request->user();
        $canManageWarehouse = $currentUser && (
            $currentUser->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff']) ||
            $currentUser->department === 'Warehouse'
        );

        // --- 1. Query Detail Lists for the 3 new tabs (Stocking, Project, R & NFR) ---
        $itemsBaseQuery = ProductItem::with([
            'product', 
            'warehouse', 
            'import.supplier',
            'import.purchaseOrder.supplier',
            'import.purchaseOrder.items.saleOrderRequestItem.saleOrderRequest.creator', 
            'import.purchaseOrder.sale.project'
        ])
        ->leftJoin('imports as inventory_imports', 'product_items.import_id', '=', 'inventory_imports.id')
        ->select(
            'product_items.product_id',
            // Several inbound batches belonging to the same PO are one
            // operational stock row.  Keep one representative import only
            // for loading the PO/vendor relations shown by the table.
            DB::raw('MAX(product_items.import_id) as import_id'),
            'product_items.warehouse_id',
            DB::raw('SUM(product_items.quantity) as quantity'),
            DB::raw('GROUP_CONCAT(product_items.sku ORDER BY product_items.sku SEPARATOR ", ") as sku'),
            DB::raw('GROUP_CONCAT(product_items.id) as item_ids'),
            // One operational row per product / inbound lot / warehouse. The
            // borrower is deliberately aggregated instead of becoming part of
            // the grouping key, otherwise one PO is fragmented into many rows.
            DB::raw("GROUP_CONCAT(CONCAT(COALESCE(NULLIF(product_items.borrower, ''), '__unallocated__'), '||', product_items.quantity) SEPARATOR ',') as borrower_allocations"),
            DB::raw('MAX(product_items.comments) as comments'),
            DB::raw('MAX(product_items.custom_fields) as custom_fields'),
            DB::raw('MAX(product_items.updated_at) as updated_at')
        )
        ->where('product_items.status', ProductItem::STATUS_IN_STOCK)
        ->groupBy(
            'product_items.product_id',
            'product_items.warehouse_id',
            DB::raw("CASE WHEN inventory_imports.reference_type = 'purchase_order' THEN inventory_imports.reference_id ELSE -product_items.import_id END")
        );

        // Filter by warehouse for detail lists
        if ($request->filled('warehouse_id')) {
            $itemsBaseQuery->where('product_items.warehouse_id', $request->warehouse_id);
        }

        // Operational filters: allow warehouse staff to narrow stock by the
        // commercial source of the item, not only by product name.
        if ($request->filled('vendor_id')) {
            $vendorId = $request->vendor_id;
            $itemsBaseQuery->whereHas('import', function ($query) use ($vendorId) {
                $query->where('supplier_id', $vendorId)
                    ->orWhereHas('purchaseOrder', fn ($po) => $po->where('supplier_id', $vendorId));
            });
        }

        if ($request->filled('po_code')) {
            $poCode = $request->po_code;
            $itemsBaseQuery->whereHas('import.purchaseOrder', fn ($query) => $query->where('code', 'like', "%{$poCode}%"));
        }

        if ($request->filled('sales_id')) {
            $salesId = $request->sales_id;
            $itemsBaseQuery->where(function ($query) use ($salesId) {
                $query->whereHas('import.purchaseOrder.sale', fn ($sale) => $sale->where('user_id', $salesId))
                    ->orWhereHas('import.purchaseOrder.items.saleOrderRequestItem.saleOrderRequest', fn ($requestQuery) => $requestQuery->where('created_by', $salesId));
            });
        }

        if ($request->filled('project_id')) {
            $projectId = $request->project_id;
            $itemsBaseQuery->whereHas('import.purchaseOrder.sale', fn ($sale) => $sale->where('project_id', $projectId));
        }

        // Filter by search for detail lists (searches across all visible attributes)
        if ($request->filled('search')) {
            $search = $request->search;
            $itemsBaseQuery->where(function ($q) use ($search) {
                $q->whereHas('product', function ($pQ) use ($search) {
                    $pQ->where('name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%");
                })
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('borrower', 'like', "%{$search}%")
                ->orWhere('comments', 'like', "%{$search}%")
                ->orWhereHas('import.purchaseOrder', function ($poQ) use ($search) {
                    $poQ->where('code', 'like', "%{$search}%")
                        ->orWhereHas('sale', function ($sQ) use ($search) {
                            $sQ->where('customer_name', 'like', "%{$search}%")
                               ->orWhereHas('project', function ($projQ) use ($search) {
                                   $projQ->where('name', 'like', "%{$search}%");
                               });
                        })
                        ->orWhereHas('creator', function ($uQ) use ($search) {
                            $uQ->where('name', 'like', "%{$search}%");
                        });
                })
                ->orWhereHas('import.purchaseOrder.items.saleOrderRequestItem', function ($soriQ) use ($search) {
                    $soriQ->where('eu_name_mst', 'like', "%{$search}%")
                          ->orWhereHas('saleOrderRequest.creator', function ($uQ) use ($search) {
                              $uQ->where('name', 'like', "%{$search}%");
                          });
                });
            });
        }

        // Resolve warehouse IDs
        $projectWarehouseId = Warehouse::where('code', 'WH_PROJECT')->value('id');
        $runrateWarehouseId = Warehouse::where('code', 'WH_RUNRATE')->value('id');
        $licenseWarehouseId = Warehouse::where('code', 'WH_LICENSE')->value('id');
        $rmodelWarehouseId = Warehouse::where('code', 'WH_WARRANTY')->value('id');

        // Clone queries for separate lists
        $projectQuery = (clone $itemsBaseQuery)->where('product_items.warehouse_id', $projectWarehouseId);
        $runrateQuery = (clone $itemsBaseQuery)->where('product_items.warehouse_id', $runrateWarehouseId);
        $licenseQuery = (clone $itemsBaseQuery)->where('product_items.warehouse_id', $licenseWarehouseId);
        $rmodelQuery = (clone $itemsBaseQuery)->where('product_items.warehouse_id', $rmodelWarehouseId);

        $activeTab = $request->get('tab', 'runrate');

        // A stock search can match a different warehouse category from the
        // one the user happened to be viewing.  Keep the selected tab when it
        // has data, but switch to the first matching category when it does
        // not, so a valid result is never presented as an empty list.
        $autoSelectedTab = null;
        $shouldAutoSelectTab = $request->boolean('auto_switch_tab');

        $tabQueries = [
            'project' => $projectQuery,
            'runrate' => $runrateQuery,
            'license' => $licenseQuery,
            'rmodel' => $rmodelQuery,
        ];

        if ($shouldAutoSelectTab && isset($tabQueries[$activeTab])) {
            $currentTabHasResults = (clone $tabQueries[$activeTab])->limit(1)->get()->isNotEmpty();

            if (!$currentTabHasResults) {
                foreach (['project', 'runrate', 'license', 'rmodel'] as $tab) {
                    if ((clone $tabQueries[$tab])->limit(1)->get()->isNotEmpty()) {
                        $autoSelectedTab = $tab;
                        $activeTab = $tab;
                        break;
                    }
                }
            }
        }

        if ($autoSelectedTab) {
            $query = $request->query();
            $query['tab'] = $activeTab;
            unset($query['auto_switch_tab']);
            unset($query['page_project'], $query['page_runrate'], $query['page_license'], $query['page_rmodel']);

            return redirect()
                ->route('inventory.index', $query)
                ->with('inventory_auto_selected_tab', $autoSelectedTab);
        }

        $autoSelectedTab = session('inventory_auto_selected_tab');

        // Paginate separate lists
        $projectItems = $activeTab === 'project' 
            ? $projectQuery->orderBy('updated_at', 'desc')->paginate(20, ['*'], 'page_project')
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'page_project']);

        $runrateItems = $activeTab === 'runrate' 
            ? $runrateQuery->orderBy('updated_at', 'desc')->paginate(20, ['*'], 'page_runrate')
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'page_runrate']);

        $licenseItems = $activeTab === 'license' 
            ? $licenseQuery->orderBy('updated_at', 'desc')->paginate(20, ['*'], 'page_license')
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'page_license']);

        $rmodelItems = $activeTab === 'rmodel' 
            ? $rmodelQuery->orderBy('updated_at', 'desc')->paginate(20, ['*'], 'page_rmodel')
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, 1, ['path' => $request->url(), 'query' => $request->query(), 'pageName' => 'page_rmodel']);

        // Keep each visible page scannable. Project stock is arranged by its
        // commercial chain (project / Sales Order / PO); the other stock tabs
        // remain arranged by vendor.
        foreach ([$projectItems, $runrateItems, $licenseItems, $rmodelItems] as $paginator) {
            $paginator->setCollection(
                $paginator->getCollection()
                    ->map(function ($item) {
                        $allocations = [];
                        foreach (array_filter(explode(',', (string) $item->borrower_allocations)) as $entry) {
                            [$borrower, $quantity] = array_pad(explode('||', $entry, 2), 2, 0);
                            $label = $borrower === '__unallocated__' ? 'Chưa phân bổ' : $borrower;
                            $allocations[$label] = ($allocations[$label] ?? 0) + (int) $quantity;
                        }

                        $item->borrower_display = collect($allocations)
                            ->map(fn ($quantity, $label) => "{$label} ({$quantity})")
                            ->implode(', ');

                        return $item;
                    })
                    ->sortBy(function ($item) use ($activeTab) {
                        if ($activeTab === 'project') {
                            $purchaseOrder = $item->import?->purchaseOrder;
                            $sale = $purchaseOrder?->sale;
                            $project = $sale?->project;

                            return strtolower(implode('|', [
                                $project?->name ?: ($item->project_name ?: 'zzz'),
                                $sale?->code ?: 'no-sales-order',
                                $purchaseOrder?->code ?: 'no-purchase-order',
                                $item->product?->name ?: '',
                            ]));
                        }

                        return strtolower($item->import?->supplier?->name ?: $item->import?->purchaseOrder?->supplier?->name ?: 'zzz');
                    })
                    ->values()
            );
        }

        // Load custom columns definitions
        $projectColumns = InventoryCustomColumn::where('tab', 'project')->get();
        $runrateColumns = InventoryCustomColumn::where('tab', 'runrate')->get();
        $licenseColumns = InventoryCustomColumn::where('tab', 'license')->get();
        $rmodelColumns = InventoryCustomColumn::where('tab', 'rmodel')->get();

        // Get filter options
        $warehouses = Warehouse::active()->get();
        $vendors = \App\Models\Supplier::orderBy('name')->get(['id', 'name', 'code']);
        $salesUsers = \App\Models\User::where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_code']);
        $projects = \App\Models\Project::orderByDesc('updated_at')->limit(300)->get(['id', 'code', 'name']);

        return view('inventory.index', compact(
            'projectItems', 
            'runrateItems', 
            'licenseItems', 
            'rmodelItems', 
            'projectColumns', 
            'runrateColumns', 
            'licenseColumns', 
            'rmodelColumns', 
            'warehouses', 
            'vendors',
            'salesUsers',
            'projects',
            'canManageWarehouse',
            'autoSelectedTab',
            'activeTab'
        ));
    }

    /**
     * Display the specified inventory.
     */
    public function show(Request $request, Inventory $inventory)
    {
        $this->authorize('view', $inventory);

        $inventory->load(['product', 'warehouse']);

        // Inventory is the aggregate balance. Product items provide the
        // operational trace: which import/PO brought it in and which sale or
        // project it is assigned or exported to.
        $traceItems = ProductItem::query()
            ->with([
                'import.supplier:id,name,code',
                'import.purchaseOrder:id,code,supplier_id,sale_id,created_by',
                'import.purchaseOrder.supplier:id,name,code',
                'import.purchaseOrder.creator:id,name',
                'import.purchaseOrder.sale:id,code,user_id,project_id,customer_id',
                'import.purchaseOrder.sale.user:id,name',
                'import.purchaseOrder.sale.project:id,code,name',
                'export:id,code,reference_type,reference_id,project_id,customer_id,employee_id,date,status',
                'export.sale:id,code,user_id,project_id,customer_id',
                'export.sale.user:id,name',
                'export.project:id,code,name',
                'export.customer:id,name',
            ])
            ->where('product_id', $inventory->product_id)
            ->where('warehouse_id', $inventory->warehouse_id)
            ->latest('updated_at')
            ->limit(100)
            ->get();

        $backUrl = route('inventory.index');
        if ($request->boolean('from_warehouse') && (int) $request->input('warehouse_context') === (int) $inventory->warehouse_id) {
            $backUrl = route('warehouses.show', array_filter([
                'warehouse' => $inventory->warehouse_id,
                'search' => $request->input('return_search'),
                'stock_status' => $request->input('return_stock_status'),
            ], fn ($value) => $value !== null && $value !== ''));
        }

        return view('inventory.show', compact('inventory', 'traceItems', 'backUrl'));
    }

    /**
     * Display low stock items.
     */
    public function lowStock()
    {
        $this->authorize('viewAny', Inventory::class);

        $inventories = $this->inventoryService->getLowStockItems();

        return view('inventory.low-stock', compact('inventories'));
    }

    /**
     * Display expiring items.
     */
    public function expiringSoon()
    {
        $this->authorize('viewAny', Inventory::class);

        $inventories = $this->inventoryService->getExpiringItems(30);

        return view('inventory.expiring', compact('inventories'));
    }

    public function export(Request $request)
    {
        $this->authorize('export', Inventory::class);

        $filters = $request->only(['warehouse_id', 'product_id', 'tab', 'search']);
        
        $tab = $filters['tab'] ?? 'runrate';
        $tabLabel = match($tab) {
            'project' => 'hang-du-an',
            'runrate' => 'hang-runrate',
            'license' => 'hang-license',
            'rmodel' => 'hang-bao-hanh',
            default => 'hang-runrate'
        };

        return \Excel::download(
            new \App\Exports\InventoryExport($filters), 
            'ton-kho-' . $tabLabel . '-' . date('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Store a new custom column for a tab.
     */
    public function storeCustomColumn(Request $request)
    {
        $validated = $request->validate([
            'tab' => ['required', 'string', 'in:project,runrate,license,rmodel'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $key = \Illuminate\Support\Str::slug($validated['name'], '_');

        // Check if key already exists for this tab
        $exists = InventoryCustomColumn::where('tab', $validated['tab'])
            ->where('key', $key)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Cột với tên này đã tồn tại trong tab này.'
            ], 422);
        }

        $column = InventoryCustomColumn::create([
            'tab' => $validated['tab'],
            'name' => $validated['name'],
            'key' => $key,
        ]);

        return response()->json([
            'success' => true,
            'column' => $column,
            'message' => 'Thêm cột thành công.'
        ]);
    }

    /**
     * Delete a custom column and clean up values.
     */
    public function deleteCustomColumn($id)
    {
        $column = InventoryCustomColumn::findOrFail($id);
        $key = $column->key;

        DB::beginTransaction();
        try {
            // Delete column definition
            $column->delete();

            // Clean up values from product_items JSON custom_fields
            ProductItem::whereNotNull('custom_fields')
                ->update([
                    'custom_fields' => DB::raw("JSON_REMOVE(custom_fields, '$.\"$key\"')")
                ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Xóa cột và dọn dẹp dữ liệu thành công.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update warehouse-managed custom fields only.
     * Borrower allocation must always go through the borrow-ticket workflow,
     * which records approval, notification and an audit trail.
     */
    public function updateItem(Request $request, $id)
    {
        $user = $request->user();
        $canManageWarehouse = $user && (
            $user->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff']) ||
            $user->department === 'Warehouse'
        );

        if (!$canManageWarehouse) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ nhân sự kho được cập nhật thông tin vận hành kho.'
            ], 403);
        }

        $ids = explode(',', $id);
        $items = ProductItem::whereIn('id', $ids)->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thiết bị.'
            ], 404);
        }

        $validated = $request->validate([
            'custom_fields' => ['nullable', 'array'],
        ]);

        foreach ($items as $item) {
            if (isset($validated['custom_fields'])) {
                $currentFields = $item->custom_fields ?: [];
                // Merge custom fields
                foreach ($validated['custom_fields'] as $key => $value) {
                    if ($value === null || $value === '') {
                        unset($currentFields[$key]);
                    } else {
                        $currentFields[$key] = $value;
                    }
                }
                $item->custom_fields = $currentFields;
            }

            $item->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công.'
        ]);
    }
}
