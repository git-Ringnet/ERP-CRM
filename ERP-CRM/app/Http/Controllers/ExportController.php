<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportRequest;
use App\Models\Export;
use App\Models\ExportItem;
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
 * ExportController - Handles all export (xuất kho) operations
 * Requirements: 2.1, 2.4
 */
class ExportController extends Controller
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
     * Display a listing of export transactions.
     * Requirements: 2.4 - Display only export transactions
     */
    public function index(Request $request)
    {
        $query = Export::with(['warehouse', 'employee', 'items', 'project']);

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
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
        $projects = \App\Models\Project::whereIn('status', ['planning', 'in_progress', 'completed'])->orderBy('name')->get();

        return view('exports.index', compact('exports', 'warehouses', 'projects'));
    }

    /**
     * Show the form for creating a new export.
     */
    public function create()
    {
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();
        $employees = User::whereNotNull('employee_code')->get();
        $projects = \App\Models\Project::whereIn('status', ['planning', 'in_progress'])->orderBy('name')->get();
        $code = Export::generateCode();

        return view('exports.create', compact('warehouses', 'products', 'employees', 'projects', 'code'));
    }

    /**
     * Store a newly created export.
     * Requirements: 2.6, 2.7
     */
    public function store(ExportRequest $request)
    {
        try {
            $data = $request->validated();

            $export = $this->transactionService->processExport($data);

            // Tạo thông báo cho tất cả users (trừ người tạo)
            $recipientIds = User::where('id', '!=', $export->employee_id)
                ->pluck('id')
                ->toArray();
            if (!empty($recipientIds)) {
                $this->notificationService->notifyExportCreated($export, $recipientIds);
            }

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
    public function show(Export $export)
    {
        $export->load(['warehouse', 'employee', 'items.product', 'project']);

        // Get exported product items (serials) grouped by product_id
        $exportedItems = ProductItem::where('export_id', $export->id)
            ->get()
            ->groupBy('product_id');

        return view('exports.show', compact('export', 'exportedItems'));
    }

    /**
     * Show the form for editing the specified export.
     */
    public function edit(Export $export)
    {
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
    public function update(ExportRequest $request, Export $export)
    {
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
    public function destroy(Export $export)
    {
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
    public function approve(Export $export)
    {
        if ($export->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể duyệt phiếu đang chờ xử lý.'
            ], 400);
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

            // Tạo thông báo cho người tạo phiếu
            if ($export->employee_id) {
                $this->notificationService->notifyDocumentApproved($export, 'export', $export->employee_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Phiếu xuất đã được duyệt'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi duyệt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a pending export.
     */
    public function reject(Request $request, Export $export)
    {
        if ($export->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể từ chối phiếu đang chờ xử lý.'
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        try {
            $export->update([
                'status' => 'rejected',
                'note' => ($export->note ? $export->note . "\n\n" : '') . "Lý do từ chối: " . $request->reason,
            ]);

            // Tạo thông báo cho người tạo phiếu
            if ($export->employee_id) {
                $this->notificationService->notifyDocumentRejected($export, 'export', $export->employee_id, $request->reason);
            }

            return response()->json([
                'success' => true,
                'message' => 'Phiếu xuất đã bị từ chối'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi từ chối: ' . $e->getMessage()
            ], 500);
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

    /**
     * Export exports to Excel
     */
    public function exportToExcel(Request $request)
    {
        $filters = $request->only(['warehouse_id', 'status', 'date_from', 'date_to']);
        return \Excel::download(new \App\Exports\ExportsExport($filters), 'phieu-xuat-kho-' . date('Y-m-d') . '.xlsx');
    }
}
