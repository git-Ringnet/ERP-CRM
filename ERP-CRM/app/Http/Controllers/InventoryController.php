<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
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

        // --- 1. Query Detail Lists for the 3 new tabs (Stocking, Project, R & NFR) ---
        $itemsBaseQuery = ProductItem::with([
            'product', 
            'warehouse', 
            'import.purchaseOrder.items.saleOrderRequestItem.saleOrderRequest.creator', 
            'import.purchaseOrder.sale.project'
        ])
        ->select(
            'product_id',
            'import_id',
            'warehouse_id',
            'borrower',
            'comments',
            'custom_fields',
            DB::raw('SUM(quantity) as quantity'),
            DB::raw('GROUP_CONCAT(sku ORDER BY sku SEPARATOR ", ") as sku'),
            DB::raw('GROUP_CONCAT(id) as item_ids'),
            DB::raw('MAX(updated_at) as updated_at')
        )
        ->where('status', ProductItem::STATUS_IN_STOCK)
        ->groupBy('product_id', 'import_id', 'warehouse_id', 'borrower', 'comments', 'custom_fields');

        // Filter by warehouse for detail lists
        if ($request->filled('warehouse_id')) {
            $itemsBaseQuery->where('warehouse_id', $request->warehouse_id);
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
        $projectQuery = (clone $itemsBaseQuery)->where('warehouse_id', $projectWarehouseId);
        $runrateQuery = (clone $itemsBaseQuery)->where('warehouse_id', $runrateWarehouseId);
        $licenseQuery = (clone $itemsBaseQuery)->where('warehouse_id', $licenseWarehouseId);
        $rmodelQuery = (clone $itemsBaseQuery)->where('warehouse_id', $rmodelWarehouseId);

        $activeTab = $request->get('tab', 'runrate');

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

        // Load custom columns definitions
        $projectColumns = InventoryCustomColumn::where('tab', 'project')->get();
        $runrateColumns = InventoryCustomColumn::where('tab', 'runrate')->get();
        $licenseColumns = InventoryCustomColumn::where('tab', 'license')->get();
        $rmodelColumns = InventoryCustomColumn::where('tab', 'rmodel')->get();

        // Get filter options
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();

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
            'products'
        ));
    }

    /**
     * Display the specified inventory.
     */
    public function show(Inventory $inventory)
    {
        $this->authorize('view', $inventory);

        $inventory->load(['product', 'warehouse']);

        return view('inventory.show', compact('inventory'));
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
     * Update a product item (borrower, comments, or custom fields).
     */
    public function updateItem(Request $request, $id)
    {
        $ids = explode(',', $id);
        $items = ProductItem::whereIn('id', $ids)->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thiết bị.'
            ], 404);
        }

        $validated = $request->validate([
            'borrower' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'custom_fields' => ['nullable', 'array'],
        ]);

        foreach ($items as $item) {
            if (array_key_exists('borrower', $validated)) {
                $item->borrower = $validated['borrower'];
            }

            if (array_key_exists('comments', $validated)) {
                $item->comments = $validated['comments'];
            }

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
