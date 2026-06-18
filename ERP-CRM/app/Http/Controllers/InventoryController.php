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

        // Identify POs linked to exactly 1 unique Sale (SO/Project)
        $projectPoIds = DB::table(DB::raw("(
            SELECT id AS purchase_order_id, sale_id
            FROM purchase_orders
            WHERE sale_id IS NOT NULL
            
            UNION
            
            SELECT poi.purchase_order_id, sor.sale_id
            FROM purchase_order_items poi
            JOIN sale_order_request_items sori ON poi.sale_order_request_item_id = sori.id
            JOIN sale_order_requests sor ON sori.sale_order_request_id = sor.id
            WHERE sor.sale_id IS NOT NULL
        ) as po_sos"))
        ->groupBy('purchase_order_id')
        ->having(DB::raw('COUNT(DISTINCT sale_id)'), '=', 1)
        ->pluck('purchase_order_id')
        ->toArray();

        // Clone queries for separate lists
        $stockingQuery = clone $itemsBaseQuery;
        $projectQuery = clone $itemsBaseQuery;
        $rmodelQuery = clone $itemsBaseQuery;

        // A. Tab 3: R & NFR Model (Products ending in 'R' or 'NFR')
        $rmodelQuery->whereHas('product', function($q) {
            $q->where('code', 'like', '%R')
              ->orWhere('code', 'like', '%NFR');
        });

        // B. Tab 1: Stocking (Exclude R and NFR models, PO has 0 or > 1 unique SOs)
        $stockingQuery->whereHas('product', function($q) {
            $q->where('code', 'not like', '%R')
              ->where('code', 'not like', '%NFR');
        })->where(function($q) use ($projectPoIds) {
            $q->whereNull('import_id')
              ->orWhereHas('import', function($impQ) use ($projectPoIds) {
                  $impQ->where(function($subQ) use ($projectPoIds) {
                      $subQ->where('reference_type', '!=', 'purchase_order')
                           ->orWhereNull('reference_id')
                           ->orWhereNotIn('reference_id', $projectPoIds);
                  });
              });
        });

        // C. Tab 2: Project (Exclude R and NFR models, PO has exactly 1 unique SO/project)
        $projectQuery->whereHas('product', function($q) {
            $q->where('code', 'not like', '%R')
              ->where('code', 'not like', '%NFR');
        })->whereHas('import', function($impQ) use ($projectPoIds) {
            $impQ->where('reference_type', 'purchase_order')
                 ->whereIn('reference_id', $projectPoIds);
        });

        // Paginate separate lists (20 items/page as requested)
        $stockingItems = $stockingQuery->orderBy('updated_at', 'desc')->paginate(20, ['*'], 'page_stocking');
        $projectItems = $projectQuery->orderBy('updated_at', 'desc')->paginate(20, ['*'], 'page_project');
        $rmodelItems = $rmodelQuery->orderBy('updated_at', 'desc')->paginate(20, ['*'], 'page_rmodel');

        // Load custom columns definitions
        $stockingColumns = InventoryCustomColumn::where('tab', 'stocking')->get();
        $projectColumns = InventoryCustomColumn::where('tab', 'project')->get();
        $rmodelColumns = InventoryCustomColumn::where('tab', 'rmodel')->get();

        // Get filter options
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();

        return view('inventory.index', compact(
            'stockingItems', 
            'projectItems', 
            'rmodelItems', 
            'stockingColumns', 
            'projectColumns', 
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
        
        $tab = $filters['tab'] ?? 'stocking';
        $tabLabel = match($tab) {
            'project' => 'hang-du-an',
            'rmodel' => 'hang-R-va-NFR',
            default => 'hang-stocking'
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
            'tab' => ['required', 'string', 'in:stocking,project,rmodel'],
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
