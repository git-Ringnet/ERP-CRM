<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
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
 * TransferController - Handles all transfer (chuyển kho) operations
 * Requirements: 3.1, 3.4
 */
class TransferController extends Controller
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
     * Display a listing of transfer transactions.
     * Requirements: 3.4 - Display only transfer transactions
     */
    public function index(Request $request)
    {
        $query = InventoryTransaction::with(['warehouse', 'toWarehouse', 'employee', 'items'])
            ->where('type', 'transfer');

        if ($request->filled('warehouse_id')) {
            $query->where(function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id)
                  ->orWhere('to_warehouse_id', $request->warehouse_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where('code', 'like', "%{$request->search}%");
        }

        $transfers = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $warehouses = Warehouse::active()->get();

        return view('transfers.index', compact('transfers', 'warehouses'));
    }

    /**
     * Show the form for creating a new transfer.
     */
    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();
        $employees = User::whereNotNull('employee_code')->get();
        $code = InventoryTransaction::generateCode('transfer');

        return view('transfers.create', compact('warehouses', 'products', 'employees', 'code'));
    }

    /**
     * Store a newly created transfer.
     * Requirements: 3.5, 3.6, 3.7
     */
    public function store(TransferRequest $request)
    {
        try {
            $data = $request->validated();
            $data['type'] = 'transfer';

            $transfer = $this->transactionService->processTransfer($data);

            return redirect()->route('transfers.show', $transfer)
                ->with('success', 'Tạo phiếu chuyển kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified transfer.
     */
    public function show(InventoryTransaction $transfer)
    {
        if ($transfer->type !== 'transfer') {
            abort(404);
        }

        $transfer->load(['warehouse', 'toWarehouse', 'employee', 'items.product']);

        return view('transfers.show', compact('transfer'));
    }

    /**
     * Show the form for editing the specified transfer.
     */
    public function edit(InventoryTransaction $transfer)
    {
        if ($transfer->type !== 'transfer') {
            abort(404);
        }

        if ($transfer->status !== 'pending') {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu đang chờ xử lý.');
        }

        $transfer->load(['items.product']);
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();
        $employees = User::whereNotNull('employee_code')->get();

        $existingItems = $transfer->items->map(function($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit' => $item->unit ?? '',
            ];
        })->toArray();

        return view('transfers.edit', compact('transfer', 'warehouses', 'products', 'employees', 'existingItems'));
    }

    /**
     * Update the specified transfer.
     */
    public function update(TransferRequest $request, InventoryTransaction $transfer)
    {
        if ($transfer->type !== 'transfer') {
            abort(404);
        }

        if ($transfer->status !== 'pending') {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu đang chờ xử lý.');
        }

        try {
            $data = $request->validated();

            $transfer->items()->delete();

            $transfer->update([
                'warehouse_id' => $data['warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'date' => $data['date'],
                'employee_id' => $data['employee_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            $totalQty = 0;
            foreach ($data['items'] as $itemData) {
                $transfer->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? null,
                ]);
                $totalQty += $itemData['quantity'];
            }

            $transfer->update(['total_qty' => $totalQty]);

            return redirect()->route('transfers.show', $transfer)
                ->with('success', 'Cập nhật phiếu chuyển kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified transfer.
     */
    public function destroy(InventoryTransaction $transfer)
    {
        if ($transfer->type !== 'transfer') {
            abort(404);
        }

        if ($transfer->status !== 'pending') {
            return redirect()->route('transfers.index')
                ->with('error', 'Chỉ có thể xóa phiếu đang chờ xử lý.');
        }

        try {
            $transfer->items()->delete();
            $transfer->delete();

            return redirect()->route('transfers.index')
                ->with('success', 'Xóa phiếu chuyển kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi xóa: ' . $e->getMessage());
        }
    }

    /**
     * Approve a pending transfer.
     * Requirements: 3.7 - Validate stock before approving
     */
    public function approve(InventoryTransaction $transfer)
    {
        if ($transfer->type !== 'transfer') {
            abort(404);
        }

        if ($transfer->status !== 'pending') {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Chỉ có thể duyệt phiếu đang chờ xử lý.');
        }

        try {
            $this->transactionService->processTransfer([
                'code' => $transfer->code,
                'warehouse_id' => $transfer->warehouse_id,
                'to_warehouse_id' => $transfer->to_warehouse_id,
                'date' => $transfer->date->format('Y-m-d'),
                'employee_id' => $transfer->employee_id,
                'note' => $transfer->note,
                'items' => $transfer->items->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                ])->toArray(),
            ], $transfer);

            return redirect()->route('transfers.show', $transfer)
                ->with('success', 'Duyệt phiếu chuyển kho thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi duyệt: ' . $e->getMessage());
        }
    }
}
