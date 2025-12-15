<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportRequest;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\TransactionService;
use App\Services\ProductItemService;
use App\Services\InventoryService;
use Illuminate\Http\Request;

/**
 * ExportController - Handles all export (xuất kho) operations
 * Requirements: 2.1, 2.4
 */
class ExportController extends Controller
{
    protected TransactionService $transactionService;
    protected ProductItemService $productItemService;
    protected InventoryService $inventoryService;

    public function __construct(
        TransactionService $transactionService,
        ProductItemService $productItemService,
        InventoryService $inventoryService
    ) {
        $this->transactionService = $transactionService;
        $this->productItemService = $productItemService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Display a listing of export transactions.
     * Requirements: 2.4 - Display only export transactions
     */
    public function index(Request $request)
    {
        $query = InventoryTransaction::with(['warehouse', 'employee', 'items'])
            ->where('type', 'export'); // Filter only exports

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Search by code
        if ($request->filled('search')) {
            $query->where('code', 'like', "%{$request->search}%");
        }

        $exports = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $warehouses = Warehouse::active()->get();

        return view('exports.index', compact('exports', 'warehouses'));
    }

    /**
     * Show the form for creating a new export.
     */
    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();
        $employees = User::whereNotNull('employee_code')->get();
        $code = InventoryTransaction::generateCode('export');

        return view('exports.create', compact('warehouses', 'products', 'employees', 'code'));
    }

    /**
     * Store a newly created export.
     * Requirements: 2.6, 2.7
     */
    public function store(ExportRequest $request)
    {
        try {
            $data = $request->validated();
            $data['type'] = 'export';

            $export = $this->transactionService->processExport($data);

            return redirect()->route('exports.show', $export)
                ->with('success', 'Tạo phiếu xuất kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified export.
     */
    public function show(InventoryTransaction $export)
    {
        if ($export->type !== 'export') {
            abort(404);
        }

        $export->load(['warehouse', 'employee', 'items.product']);

        // Get exported product items (serials) grouped by product_id
        $exportedItems = ProductItem::where('inventory_transaction_id', $export->id)
            ->get()
            ->groupBy('product_id');

        return view('exports.show', compact('export', 'exportedItems'));
    }

    /**
     * Show the form for editing the specified export.
     */
    public function edit(InventoryTransaction $export)
    {
        if ($export->type !== 'export') {
            abort(404);
        }

        if ($export->status !== 'pending') {
            return redirect()->route('exports.show', $export)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu đang chờ xử lý.');
        }

        $export->load(['items.product']);
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();
        $employees = User::whereNotNull('employee_code')->get();

        $existingItems = $export->items->map(function ($item) use ($export) {
            // Get selected product_item_ids from serial_number JSON
            $productItemIds = [];
            if (!empty($item->serial_number)) {
                $decoded = json_decode($item->serial_number, true);
                if (is_array($decoded)) {
                    $productItemIds = $decoded;
                }
            }

            return [
                'product_id' => $item->product_id,
                'warehouse_id' => $export->warehouse_id,
                'quantity' => $item->quantity,
                'comments' => $item->comments ?? '',
                'product_item_ids' => $productItemIds,
            ];
        })->toArray();

        return view('exports.edit', compact('export', 'warehouses', 'products', 'employees', 'existingItems'));
    }

    /**
     * Update the specified export.
     */
    public function update(ExportRequest $request, InventoryTransaction $export)
    {
        if ($export->type !== 'export') {
            abort(404);
        }

        if ($export->status !== 'pending') {
            return redirect()->route('exports.show', $export)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu đang chờ xử lý.');
        }

        try {
            $data = $request->validated();

            $export->items()->delete();

            // Get warehouse_id from first item
            $warehouseId = $data['items'][0]['warehouse_id'] ?? $export->warehouse_id;

            $export->update([
                'warehouse_id' => $warehouseId,
                'date' => $data['date'],
                'employee_id' => $data['employee_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            $totalQty = 0;
            foreach ($data['items'] as $itemData) {
                // Store selected product_item_ids as JSON (unique values only)
                $productItemIds = $itemData['product_item_ids'] ?? [];
                $productItemIds = array_filter($productItemIds, fn($id) => !empty($id));
                $productItemIds = array_unique($productItemIds);

                $export->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'serial_number' => !empty($productItemIds) ? json_encode(array_values($productItemIds)) : null,
                    'comments' => $itemData['comments'] ?? null,
                ]);
                $totalQty += $itemData['quantity'];
            }

            $export->update(['total_qty' => $totalQty]);

            return redirect()->route('exports.show', $export)
                ->with('success', 'Cập nhật phiếu xuất kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified export.
     */
    public function destroy(InventoryTransaction $export)
    {
        if ($export->type !== 'export') {
            abort(404);
        }

        if ($export->status !== 'pending') {
            return redirect()->route('exports.index')
                ->with('error', 'Chỉ có thể xóa phiếu đang chờ xử lý.');
        }

        try {
            $export->items()->delete();
            $export->delete();

            return redirect()->route('exports.index')
                ->with('success', 'Xóa phiếu xuất kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi xóa: ' . $e->getMessage());
        }
    }

    /**
     * Approve a pending export.
     * Requirements: 2.7 - Validate stock before approving
     */
    public function approve(InventoryTransaction $export)
    {
        if ($export->type !== 'export') {
            abort(404);
        }

        if ($export->status !== 'pending') {
            return redirect()->route('exports.show', $export)
                ->with('error', 'Chỉ có thể duyệt phiếu đang chờ xử lý.');
        }

        try {
            $this->transactionService->processExport([
                'code' => $export->code,
                'warehouse_id' => $export->warehouse_id,
                'date' => $export->date->format('Y-m-d'),
                'employee_id' => $export->employee_id,
                'note' => $export->note,
                'items' => $export->items->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                ])->toArray(),
            ], $export);

            return redirect()->route('exports.show', $export)
                ->with('success', 'Duyệt phiếu xuất kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi duyệt: ' . $e->getMessage());
        }
    }

    /**
     * Get available product items (SKUs) for export.
     * Requirements: 2.5
     * Returns: items with serial (not NOSKU) and count of items without serial (NOSKU)
     */
    public function getAvailableItems(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        // Get items with real serial (not NOSKU)
        $itemsWithSerial = ProductItem::where('product_id', $request->product_id)
            ->where('warehouse_id', $request->warehouse_id)
            ->where('status', ProductItem::STATUS_IN_STOCK)
            ->where('sku', 'not like', 'NOSKU%')
            ->select('id', 'sku', 'cost_usd', 'price_tiers')
            ->get();

        // Count items without serial (NOSKU)
        $noSkuCount = ProductItem::where('product_id', $request->product_id)
            ->where('warehouse_id', $request->warehouse_id)
            ->where('status', ProductItem::STATUS_IN_STOCK)
            ->where('sku', 'like', 'NOSKU%')
            ->count();

        return response()->json([
            'items' => $itemsWithSerial,
            'noSkuCount' => $noSkuCount,
        ]);
    }
}
