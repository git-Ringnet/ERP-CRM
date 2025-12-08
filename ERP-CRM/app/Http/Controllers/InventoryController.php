<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;

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
        $query = Inventory::with(['product', 'warehouse']);

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->byWarehouse($request->warehouse_id);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->byProduct($request->product_id);
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->lowStock();
            } elseif ($request->stock_status === 'out') {
                $query->where('stock', '<=', 0);
            } elseif ($request->stock_status === 'available') {
                $query->where('stock', '>', 0);
            }
        }

        // Filter by expiry
        if ($request->filled('expiry_filter')) {
            if ($request->expiry_filter === 'expiring') {
                $query->expiringSoon(30);
            } elseif ($request->expiry_filter === 'expired') {
                $query->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', now());
            }
        }

        // Search by product name or code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $inventories = $query->orderBy('updated_at', 'desc')->paginate(10);

        // Get filter options
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();

        return view('inventory.index', compact('inventories', 'warehouses', 'products'));
    }

    /**
     * Display the specified inventory.
     */
    public function show(Inventory $inventory)
    {
        $inventory->load(['product', 'warehouse']);

        return view('inventory.show', compact('inventory'));
    }

    /**
     * Display low stock items.
     */
    public function lowStock()
    {
        $inventories = $this->inventoryService->getLowStockItems();

        return view('inventory.low-stock', compact('inventories'));
    }

    /**
     * Display expiring items.
     */
    public function expiringSoon()
    {
        $inventories = $this->inventoryService->getExpiringItems(30);

        return view('inventory.expiring', compact('inventories'));
    }
}
