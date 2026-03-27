<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportRequest;
use App\Models\Export;
use App\Models\ExportItem;
use App\Models\Product;
use App\Models\ProductItem;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\TransactionService;
use App\Services\ProductItemService;
use App\Services\InventoryService;
use App\Services\NotificationService;
use App\Services\WarehouseJournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
    protected WarehouseJournalService $journalService;

    public function __construct(
        TransactionService $transactionService,
        ProductItemService $productItemService,
        InventoryService $inventoryService,
        NotificationService $notificationService,
        WarehouseJournalService $journalService
    ) {
        $this->transactionService = $transactionService;
        $this->productItemService = $productItemService;
        $this->inventoryService = $inventoryService;
        $this->notificationService = $notificationService;
        $this->journalService = $journalService;
    }

    /**
     * Display a listing of export transactions.
     * Requirements: 2.4 - Display only export transactions
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Export::class);

        $query = Export::with(['warehouse', 'employee', 'items', 'project', 'customer']);

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
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
        $customers = \App\Models\Customer::orderBy('name')->get();

        return view('exports.index', compact('exports', 'warehouses', 'projects', 'customers'));
    }

    /**
     * Show the form for creating a new export.
     */
    public function create()
    {
        $this->authorize('create', Export::class);

        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();
        $employees = User::whereNotNull('employee_code')->get();
        $projects = \App\Models\Project::whereIn('status', ['planning', 'in_progress'])->orderBy('name')->get();
        $customers = \App\Models\Customer::orderBy('name')->get();
        $code = Export::generateCode();

        return view('exports.create', compact('warehouses', 'products', 'employees', 'projects', 'customers', 'code'));
    }

    /**
     * Store a newly created export.
     * Requirements: 2.6, 2.7
     */
    public function store(ExportRequest $request)
    {
        $this->authorize('create', Export::class);

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

            // Ghi nhật ký kế toán (Lịch sử: Tạo mới)
            try {
                $export->load(['items', 'warehouse', 'project', 'customer']);
                $this->journalService->createForExport($export, 'create');
            } catch (\Exception $journalEx) {
                Log::warning('Không thể tạo bút toán cho phiếu xuất ' . $export->code . ': ' . $journalEx->getMessage());
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
        $this->authorize('view', $export);

        $export->load(['warehouse', 'employee', 'items.product', 'project', 'customer']);

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
        $this->authorize('update', $export);

        if ($export->status !== 'pending') {
            return redirect()->route('exports.show', $export)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu đang chờ xử lý.');
        }

        $export->load(['items.product']);
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();
        $employees = User::whereNotNull('employee_code')->get();
        $projects = \App\Models\Project::whereIn('status', ['planning', 'in_progress'])->orderBy('name')->get();
        $customers = \App\Models\Customer::orderBy('name')->get();

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
                'requested_quantity' => $item->requested_quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
                'comments' => $item->comments ?? '',
                'product_item_ids' => $productItemIds,
            ];
        })->toArray();

        return view('exports.edit', compact('export', 'warehouses', 'products', 'employees', 'projects', 'customers', 'existingItems'));
    }

    /**
     * Update the specified export.
     */
    public function update(ExportRequest $request, Export $export)
    {
        $this->authorize('update', $export);

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
                'project_id' => $data['project_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
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
                    'requested_quantity' => $itemData['requested_quantity'] ?? null,
                    'serial_number' => !empty($productItemIds) ? json_encode(array_values($productItemIds)) : null,
                    'comments' => $itemData['comments'] ?? null,
                    'is_liquidation' => isset($itemData['is_liquidation']) ? (bool) $itemData['is_liquidation'] : false,
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'total' => $itemData['total'] ?? (($itemData['unit_price'] ?? 0) * $itemData['quantity']),
                ]);
                $totalQty += $itemData['quantity'];
            }

            $export->update(['total_qty' => $totalQty]);

            // Ghi nhật ký kế toán (Lịch sử: Cập nhật)
            try {
                $export->refresh()->load(['items', 'warehouse', 'project', 'customer']);
                $this->journalService->createForExport($export, 'update');
            } catch (\Exception $journalEx) {
                Log::warning('Không thể cập nhật bút toán cho phiếu xuất ' . $export->code . ': ' . $journalEx->getMessage());
            }

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
        $this->authorize('delete', $export);

        // Only allow deleting pending or rejected exports
        if (!in_array($export->status, ['pending', 'rejected'])) {
            return redirect()->route('exports.index')
                ->with('error', 'Chỉ có thể xóa phiếu đang chờ xử lý hoặc đã bị từ chối.');
        }

        try {
            $export->items()->delete();
            $export->delete();

            // Ghi nhật ký kế toán (Lịch sử: Xóa)
            try {
                $this->journalService->createForExport($export, 'delete');
            } catch (\Exception $journalEx) {
                Log::warning('Không thể tạo bút toán cho phiếu xuất ' . $export->code . ': ' . $journalEx->getMessage());
            }

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
        $this->authorize('approve', $export);

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

            // Tạo bút toán kế toán tự động
            try {
                $export->load(['items', 'warehouse', 'project', 'customer']);
                $this->journalService->createForExport($export, 'approve');
            } catch (\Exception $journalEx) {
                Log::warning('Không thể tạo bút toán cho phiếu xuất ' . $export->code . ': ' . $journalEx->getMessage());
            }

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
        $this->authorize('approve', $export);

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

            // Ghi nhật ký (Lịch sử: Từ chối)
            try {
                $this->journalService->createForExport($export, 'reject');
            } catch (\Exception $journalEx) {}

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
        $this->authorize('create', Export::class);

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

        // Get avg_cost from inventory
        $inventory = \App\Models\Inventory::where('product_id', $request->product_id)
            ->where('warehouse_id', $request->warehouse_id)
            ->first();

        return response()->json([
            'items' => $itemsWithSerial,
            'noSkuCount' => $noSkuCount,
            'avg_cost' => $inventory ? $inventory->avg_cost : 0,
        ]);
    }

    /**
     * Export exports to Excel
     */
    public function exportToExcel(Request $request)
    {
        $this->authorize('viewAny', Export::class);

        $filters = $request->only(['warehouse_id', 'status', 'date_from', 'date_to']);
        return \Excel::download(new \App\Exports\ExportsExport($filters), 'phieu-xuat-kho-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Print export voucher
     */
    public function print(Export $export)
    {
        $this->authorize('view', $export);
        $export->load(['warehouse', 'employee', 'items.product', 'project', 'customer']);
        return view('reports.vouchers.phieu-xuat-kho', compact('export'));
    }

    public function exportVoucherToExcel(Export $export)
    {
        $this->authorize('view', $export);
        $export->load(['warehouse', 'employee', 'items.product', 'project', 'customer']);

        return \Excel::download(
            new \App\Exports\ExportVoucherExport($export),
            'phieu-xuat-kho-' . $export->code . '.xlsx'
        );
    }


    /**
     * Export to Misa Excel
     */
    public function exportMisa(Request $request)
    {
        $this->authorize('viewAny', Export::class);
        $filters = $request->only(['date_from', 'date_to', 'warehouse_id']);
        
        $query = \App\Models\ExportItem::with(['export.warehouse', 'product'])
            ->whereHas('export', function($q) use ($filters) {
                $q->where('status', 'completed');
                if (!empty($filters['date_from'])) $q->whereDate('date', '>=', $filters['date_from']);
                if (!empty($filters['date_to'])) $q->whereDate('date', '<=', $filters['date_to']);
                if (!empty($filters['warehouse_id'])) $q->where('warehouse_id', $filters['warehouse_id']);
            });

        return \Excel::download(new \App\Exports\MisaInventoryExport($query->get(), 'export'), 'phieu-xuat-kho-' . date('Ymd') . '.xlsx');
    }

    public function exportMisaSingle(Export $export)
    {
        $this->authorize('view', $export);
        
        $items = \App\Models\ExportItem::with(['export.warehouse', 'product', 'export.customer', 'export.project'])
            ->where('export_id', $export->id)
            ->get();

        return \Excel::download(new \App\Exports\MisaInventoryExport($items, 'export'), 'phieu-xuat-kho-' . $export->code . '.xlsx');
    }
}
