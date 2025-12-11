<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportRequest;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\TransactionService;
use App\Services\ProductItemService;
use Illuminate\Http\Request;

/**
 * ImportController - Handles all import (nhập kho) operations
 * Requirements: 1.1, 1.4
 */
class ImportController extends Controller
{
    protected TransactionService $transactionService;
    protected ProductItemService $productItemService;

    public function __construct(
        TransactionService $transactionService,
        ProductItemService $productItemService
    ) {
        $this->transactionService = $transactionService;
        $this->productItemService = $productItemService;
    }

    /**
     * Display a listing of import transactions.
     * Requirements: 1.4 - Display only import transactions
     */
    public function index(Request $request)
    {
        $query = InventoryTransaction::with(['warehouse', 'employee', 'items'])
            ->where('type', 'import'); // Filter only imports

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

        $imports = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $warehouses = Warehouse::active()->get();

        return view('imports.index', compact('imports', 'warehouses'));
    }

    /**
     * Show the form for creating a new import.
     */
    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();
        $employees = User::whereNotNull('employee_code')->get();
        $code = InventoryTransaction::generateCode('import');

        return view('imports.create', compact('warehouses', 'products', 'employees', 'code'));
    }

    /**
     * Store a newly created import.
     * Requirements: 1.5, 1.6
     */
    public function store(ImportRequest $request)
    {
        try {
            $data = $request->validated();
            $data['type'] = 'import';

            $import = $this->transactionService->processImport($data);

            return redirect()->route('imports.show', $import)
                ->with('success', 'Tạo phiếu nhập kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified import.
     */
    public function show(InventoryTransaction $import)
    {
        // Ensure it's an import transaction
        if ($import->type !== 'import') {
            abort(404);
        }

        $import->load(['warehouse', 'employee', 'items.product']);
        
        // Get product items created from this import
        $productItems = ProductItem::where('inventory_transaction_id', $import->id)->get();

        return view('imports.show', compact('import', 'productItems'));
    }

    /**
     * Show the form for editing the specified import.
     */
    public function edit(InventoryTransaction $import)
    {
        // Ensure it's an import transaction
        if ($import->type !== 'import') {
            abort(404);
        }

        // Only allow editing pending imports
        if ($import->status !== 'pending') {
            return redirect()->route('imports.show', $import)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu đang chờ xử lý.');
        }

        $import->load(['items.product']);
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();
        $employees = User::whereNotNull('employee_code')->get();

        // Prepare existing items data for JavaScript
        $existingItems = $import->items->map(function($item) use ($import) {
            $productItems = ProductItem::where('inventory_transaction_id', $import->id)
                ->where('product_id', $item->product_id)
                ->get();
            
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit' => $item->unit ?? '',
                'description' => $item->description ?? '',
                'comments' => $item->comments ?? '',
                'cost_usd' => $productItems->first()->cost_usd ?? null,
                'skus' => $productItems->pluck('sku')->filter(fn($sku) => !str_starts_with($sku, 'NOSKU'))->values()->toArray(),
                'price_tiers' => $productItems->first()->price_tiers ?? [],
            ];
        })->toArray();

        return view('imports.edit', compact('import', 'warehouses', 'products', 'employees', 'existingItems'));
    }

    /**
     * Update the specified import.
     */
    public function update(ImportRequest $request, InventoryTransaction $import)
    {
        // Ensure it's an import transaction
        if ($import->type !== 'import') {
            abort(404);
        }

        // Only allow updating pending imports
        if ($import->status !== 'pending') {
            return redirect()->route('imports.show', $import)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu đang chờ xử lý.');
        }

        try {
            $data = $request->validated();

            // Delete old items and product items
            ProductItem::where('inventory_transaction_id', $import->id)->delete();
            $import->items()->delete();

            // Update import
            $import->update([
                'warehouse_id' => $data['warehouse_id'],
                'date' => $data['date'],
                'employee_id' => $data['employee_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // Create new items
            $totalQty = 0;
            foreach ($data['items'] as $itemData) {
                $import->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? null,
                    'description' => $itemData['description'] ?? null,
                    'comments' => $itemData['comments'] ?? null,
                    'cost' => $itemData['cost_usd'] ?? 0,
                ]);
                $totalQty += $itemData['quantity'];

                // Create product items
                $skus = $itemData['skus'] ?? [];
                $priceData = [
                    'description' => $itemData['description'] ?? null,
                    'cost_usd' => $itemData['cost_usd'] ?? 0,
                    'price_tiers' => $itemData['price_tiers'] ?? null,
                    'comments' => $itemData['comments'] ?? null,
                ];

                $this->productItemService->createItemsFromImport(
                    $itemData['product_id'],
                    $itemData['quantity'],
                    $skus,
                    $priceData,
                    $data['warehouse_id'],
                    $import->id
                );
            }

            $import->update(['total_qty' => $totalQty]);

            return redirect()->route('imports.show', $import)
                ->with('success', 'Cập nhật phiếu nhập kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified import.
     */
    public function destroy(InventoryTransaction $import)
    {
        // Ensure it's an import transaction
        if ($import->type !== 'import') {
            abort(404);
        }

        // Only allow deleting pending imports
        if ($import->status !== 'pending') {
            return redirect()->route('imports.index')
                ->with('error', 'Chỉ có thể xóa phiếu đang chờ xử lý.');
        }

        try {
            ProductItem::where('inventory_transaction_id', $import->id)->delete();
            $import->items()->delete();
            $import->delete();

            return redirect()->route('imports.index')
                ->with('success', 'Xóa phiếu nhập kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi xóa: ' . $e->getMessage());
        }
    }

    /**
     * Approve a pending import.
     */
    public function approve(InventoryTransaction $import)
    {
        // Ensure it's an import transaction
        if ($import->type !== 'import') {
            abort(404);
        }

        // Only allow approving pending imports
        if ($import->status !== 'pending') {
            return redirect()->route('imports.show', $import)
                ->with('error', 'Chỉ có thể duyệt phiếu đang chờ xử lý.');
        }

        try {
            $this->transactionService->processImport([
                'code' => $import->code,
                'warehouse_id' => $import->warehouse_id,
                'date' => $import->date->format('Y-m-d'),
                'employee_id' => $import->employee_id,
                'note' => $import->note,
                'items' => $import->items->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'cost' => $item->cost,
                ])->toArray(),
            ], $import);

            return redirect()->route('imports.show', $import)
                ->with('success', 'Duyệt phiếu nhập kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi duyệt: ' . $e->getMessage());
        }
    }
}
