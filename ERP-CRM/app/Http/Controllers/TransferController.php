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
use App\Services\NotificationService;
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
    protected NotificationService $notificationService;

    public function __construct(
        TransactionService $transactionService,
        ProductItemService $productItemService,
        InventoryService $inventoryService,
        NotificationService $notificationService
    ) {
        $this->transactionService = $transactionService;
        $this->productItemService = $productItemService;
        $this->inventoryService = $inventoryService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of transfer transactions.
     */
    public function index(Request $request)
    {
        $query = InventoryTransaction::with(['warehouse', 'toWarehouse', 'employee', 'items'])
            ->where('type', 'transfer');

        if ($request->filled('warehouse_id')) {
            $query->where(function ($q) use ($request) {
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
     */
    public function store(TransferRequest $request)
    {
        try {
            $data = $request->validated();
            $data['type'] = 'transfer';

            $transfer = $this->transactionService->processTransfer($data);

            // Tạo thông báo cho tất cả users (trừ người tạo)
            $recipientIds = User::where('id', '!=', $transfer->employee_id)
                ->pluck('id')
                ->toArray();
            if (!empty($recipientIds)) {
                $this->notificationService->notifyTransferCreated($transfer, $recipientIds);
            }

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

        $existingItems = $transfer->items->map(function ($item) use ($transfer) {
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
                'warehouse_id' => $transfer->warehouse_id,
                'to_warehouse_id' => $transfer->to_warehouse_id,
                'quantity' => $item->quantity,
                'comments' => $item->comments ?? '',
                'product_item_ids' => $productItemIds,
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

            // Get warehouse_id from first item
            $warehouseId = $data['items'][0]['warehouse_id'] ?? $transfer->warehouse_id;
            $toWarehouseId = $data['items'][0]['to_warehouse_id'] ?? $transfer->to_warehouse_id;

            $transfer->update([
                'warehouse_id' => $warehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'date' => $data['date'],
                'employee_id' => $data['employee_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            $totalQty = 0;
            foreach ($data['items'] as $itemData) {
                // Store selected product_item_ids as JSON (unique values only)
                $productItemIds = $itemData['product_item_ids'] ?? [];
                $productItemIds = array_filter($productItemIds, fn ($id) => !empty($id));
                $productItemIds = array_unique($productItemIds);

                $transfer->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'serial_number' => !empty($productItemIds) ? json_encode(array_values($productItemIds)) : null,
                    'comments' => $itemData['comments'] ?? null,
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
     */
    public function approve(InventoryTransaction $transfer)
    {
        if ($transfer->type !== 'transfer') {
            abort(404);
        }

        if ($transfer->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể duyệt phiếu đang chờ xử lý.'
            ], 400);
        }

        try {
            // Pass existing transaction to processTransfer for approval
            $this->transactionService->processTransfer([], $transfer);

            // Tạo thông báo cho người tạo phiếu
            if ($transfer->employee_id) {
                $this->notificationService->notifyDocumentApproved($transfer, 'transfer', $transfer->employee_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Phiếu chuyển kho đã được duyệt'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi duyệt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a pending transfer.
     */
    public function reject(Request $request, InventoryTransaction $transfer)
    {
        if ($transfer->type !== 'transfer') {
            abort(404);
        }

        if ($transfer->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể từ chối phiếu đang chờ xử lý.'
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        try {
            $transfer->update([
                'status' => 'rejected',
                'note' => ($transfer->note ? $transfer->note . "\n\n" : '') . "Lý do từ chối: " . $request->reason,
            ]);

            // Tạo thông báo cho người tạo phiếu
            if ($transfer->employee_id) {
                $this->notificationService->notifyDocumentRejected($transfer, 'transfer', $transfer->employee_id, $request->reason);
            }

            return response()->json([
                'success' => true,
                'message' => 'Phiếu chuyển kho đã bị từ chối'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi từ chối: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available product items (SKUs) for transfer from source warehouse.
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
