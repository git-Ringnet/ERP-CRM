<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportRequest;
use App\Models\Import;
use App\Models\ImportItem;
use App\Models\Product;
use App\Models\ProductItem;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\TransactionService;
use App\Services\ProductItemService;
use App\Services\NotificationService;
use App\Services\WarehouseJournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PurchaseOrder;

/**
 * ImportController - Handles all import (nhập kho) operations
 * Requirements: 1.1, 1.4
 */
class ImportController extends Controller
{
    protected TransactionService $transactionService;
    protected ProductItemService $productItemService;
    protected NotificationService $notificationService;
    protected WarehouseJournalService $journalService;

    public function __construct(
        TransactionService $transactionService,
        ProductItemService $productItemService,
        NotificationService $notificationService,
        WarehouseJournalService $journalService
    ) {
        $this->transactionService = $transactionService;
        $this->productItemService = $productItemService;
        $this->notificationService = $notificationService;
        $this->journalService = $journalService;
    }

    /**
     * Display a listing of import transactions.
     * Requirements: 1.4 - Display only import transactions
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Import::class);

        $query = Import::with(['warehouse', 'supplier', 'employee', 'items.product']);

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
        $this->authorize('create', Import::class);

        $warehouses = Warehouse::active()->get();
        $employees = User::whereNotNull('employee_code')->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        $code = Import::generateCode();
        
        // Get approved or completed shipping allocations for selection
        $shippingAllocations = \App\Models\ShippingAllocation::with(['purchaseOrder', 'warehouse'])
            ->whereIn('status', ['approved', 'completed'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('imports.create', compact('warehouses', 'employees', 'suppliers', 'code', 'shippingAllocations'));
    }

    /**
     * Store a newly created import.
     * Requirements: 1.5, 1.6
     */
    public function store(ImportRequest $request)
    {
        $this->authorize('create', Import::class);

        try {
            $data = $request->validated();

            // Determine main warehouse_id if all items share the same one
            $warehouseIds = collect($data['items'])->pluck('warehouse_id')->filter()->unique();
            $data['warehouse_id'] = $warehouseIds->count() === 1 ? $warehouseIds->first() : null;

            // Calculate service costs
            $data['shipping_cost'] = $data['shipping_cost'] ?? 0;
            $data['loading_cost'] = $data['loading_cost'] ?? 0;
            $data['inspection_cost'] = $data['inspection_cost'] ?? 0;
            $data['other_cost'] = $data['other_cost'] ?? 0;
            $data['total_service_cost'] = $data['shipping_cost'] + $data['loading_cost'] + $data['inspection_cost'] + $data['other_cost'];

            $import = $this->transactionService->processImport($data);

            // Tạo thông báo cho tất cả users (trừ người tạo)
            $recipientIds = User::where('id', '!=', $import->employee_id)
                ->pluck('id')
                ->toArray();
            if (!empty($recipientIds)) {
                $this->notificationService->notifyImportCreated($import, $recipientIds);
            }

            // Ghi nhật ký kế toán (Lịch sử: Tạo mới)
            try {
                $import->load(['items', 'supplier', 'warehouse']);
                $this->journalService->createForImport($import, 'create');
            } catch (\Exception $journalEx) {
                Log::warning('Không thể tạo bút toán cho phiếu nhập ' . $import->code . ': ' . $journalEx->getMessage());
            }

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
    public function show(Import $import)
    {
        $this->authorize('view', $import);

        if ($import->reference_type === 'purchase_order' && $import->reference_id) {
            $po = \App\Models\PurchaseOrder::find($import->reference_id);
            if ($po) {
                app(\App\Services\PurchaseImportSyncService::class)->syncImportSerialsFromPO($po);
                $import->refresh();
            }
        }

        $import->load(['warehouse', 'employee', 'items.product', 'shippingAllocation.items.product']);

        // Get product items created from this import
        $productItems = ProductItem::where('import_id', $import->id)->get();

        return view('imports.show', compact('import', 'productItems'));
    }

    /**
     * Show the form for editing the specified import.
     */
    public function edit(Import $import)
    {
        $this->authorize('update', $import);

        // Only allow editing pending imports
        if ($import->status !== 'pending') {
            return redirect()->route('imports.show', $import)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu đang chờ xử lý.');
        }

        if ($import->reference_type === 'purchase_order' && $import->reference_id) {
            $po = \App\Models\PurchaseOrder::find($import->reference_id);
            if ($po) {
                app(\App\Services\PurchaseImportSyncService::class)->syncImportSerialsFromPO($po);
                $import->refresh();
            }
        }

        $import->load(['items.product', 'items.warehouse']);
        $warehouses = Warehouse::active()->get();
        $employees = User::whereNotNull('employee_code')->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();

        // Prepare existing items data for JavaScript (include product info for display)
        $existingItems = $import->items->map(function ($item) {
            $serials = [];
            if (!empty($item->serial_number)) {
                $decoded = json_decode($item->serial_number, true);
                if (is_array($decoded)) {
                    $serials = $decoded;
                } elseif (is_string($item->serial_number) && !empty(trim($item->serial_number))) {
                    $serials = [$item->serial_number];
                }
            }

            return [
                'product_id' => $item->product_id,
                'product_code' => $item->product->code ?? '',
                'product_name' => $item->product->name ?? '',
                'warehouse_id' => $item->warehouse_id,
                'quantity' => $item->quantity,
                'serials' => $serials,
                'cost' => $item->cost,
                'comments' => $item->comments ?? '',
                'warranty_months' => $item->warranty_months,
                'expiry_date' => $item->expiry_date ? $item->expiry_date->format('Y-m-d') : null,
            ];
        })->toArray();

        return view('imports.edit', compact('import', 'warehouses', 'employees', 'suppliers', 'existingItems'));
    }

    /**
     * Update the specified import.
     */
    public function update(ImportRequest $request, Import $import)
    {
        $this->authorize('update', $import);

        // Only allow updating pending imports
        if ($import->status !== 'pending') {
            return redirect()->route('imports.show', $import)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu đang chờ xử lý.');
        }

        try {
            $data = $request->validated();

            // Determine main warehouse_id if all items share the same one
            $warehouseIds = collect($data['items'])->pluck('warehouse_id')->filter()->unique();
            $mainWarehouseId = $warehouseIds->count() === 1 ? $warehouseIds->first() : null;

            // Delete old items (ProductItem will only exist if already approved, which shouldn't happen)
            ProductItem::where('import_id', $import->id)->delete();
            $import->items()->delete();

            // Update import
            // Determine total quantity first to calculate service cost per unit
            $totalQty = collect($data['items'])->sum('quantity');
            $import->update([
                'warehouse_id' => $mainWarehouseId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'date' => $data['date'],
                'employee_id' => $data['employee_id'] ?? null,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'loading_cost' => $data['loading_cost'] ?? 0,
                'inspection_cost' => $data['inspection_cost'] ?? 0,
                'other_cost' => $data['other_cost'] ?? 0,
                'total_service_cost' => ($data['shipping_cost'] ?? 0) + ($data['loading_cost'] ?? 0) + ($data['inspection_cost'] ?? 0) + ($data['other_cost'] ?? 0),
                'note' => $data['note'] ?? null,
                'total_qty' => $totalQty,
            ]);

            $serviceCostPerUnit = $import->getServiceCostPerUnit();

            // Create new items
            foreach ($data['items'] as $itemData) {
                // Get serials...
                $serials = $itemData['serials'] ?? [];
                if (empty($serials) && !empty($itemData['serial_list'])) {
                    $serials = preg_split('/[\n,]+/', $itemData['serial_list']);
                    $serials = array_map('trim', $serials);
                }
                $serials = array_values(array_filter($serials, fn($s) => !empty(trim($s))));

                $itemCost = $itemData['cost'] ?? 0;
                $warehousePrice = $itemCost + $serviceCostPerUnit;

                $import->items()->create([
                    'product_id' => $itemData['product_id'],
                    'warehouse_id' => $itemData['warehouse_id'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'serial_number' => !empty($serials) ? json_encode($serials) : null,
                    'comments' => $itemData['comments'] ?? null,
                    'cost' => $itemCost,
                    'warehouse_price' => $warehousePrice,
                    'warranty_months' => $itemData['warranty_months'] ?? null,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);
            }

            $import->update(['total_qty' => $totalQty]);

            // Ghi nhật ký kế toán (Lịch sử: Cập nhật)
            try {
                $import->refresh()->load(['items', 'supplier', 'warehouse']);
                $this->journalService->createForImport($import, 'update');
            } catch (\Exception $journalEx) {
                Log::warning('Không thể cập nhật bút toán cho phiếu nhập ' . $import->code . ': ' . $journalEx->getMessage());
            }

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
    public function destroy(Import $import)
    {
        $this->authorize('delete', $import);

        // Only allow deleting pending or rejected imports
        if (!in_array($import->status, ['pending', 'rejected'])) {
            return redirect()->route('imports.index')
                ->with('error', 'Chỉ có thể xóa phiếu đang chờ xử lý hoặc đã bị từ chối.');
        }

        try {
            // Ghi nhật ký trước khi xoá (Lịch sử: Xoá)
            try {
                $this->journalService->createForImport($import, 'delete');
            } catch (\Exception $journalEx) {
                \Log::warning('Không thể tạo bút toán cho phiếu nhập ' . $import->code . ' khi xóa: ' . $journalEx->getMessage());
            }

            ProductItem::where('import_id', $import->id)->delete();
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
    public function approve(Import $import)
    {
        $this->authorize('approve', $import);

        // Cho phép duyệt nếu phiếu đang pending hoặc đã từng completed nhưng có item mới chưa xử lý
        if (!in_array($import->status, ['pending', 'completed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Trạng thái phiếu không hợp lệ để duyệt.'
            ], 400);
        }

        // Tìm các items chưa được xử lý vào kho
        $unprocessedItems = $import->items()->whereNull('processed_at')->get();

        if ($unprocessedItems->isEmpty()) {
            // Nếu tất cả items đã được xử lý nhưng status vẫn pending → tự sửa thành completed
            if ($import->status === 'pending') {
                $import->update(['status' => 'completed']);
            }

            return response()->json([
                'success' => false,
                'message' => 'Phiếu nhập kho này đã được duyệt hoàn tất trước đó. Tất cả sản phẩm đã được nhập vào kho.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Chỉ truyền các item chưa xử lý vào service xử lý kho
            $this->transactionService->processImport([
                'code' => $import->code,
                'warehouse_id' => $import->warehouse_id,
                'date' => $import->date->format('Y-m-d'),
                'employee_id' => $import->employee_id,
                'note' => $import->note,
                'items' => $unprocessedItems->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'warehouse_id' => $item->warehouse_id ?? $import->warehouse_id,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'cost' => $item->cost,
                    'warranty_months' => $item->warranty_months,
                    'expiry_date' => $item->expiry_date ? $item->expiry_date->format('Y-m-d') : null,
                    'id' => $item->id, // Truyền ID để service biết cập nhật processed_at
                ])->toArray(),
            ], $import);

            // Đánh dấu các item đã xử lý xong
            foreach ($unprocessedItems as $item) {
                $item->update(['processed_at' => now()]);
            }

            // Kiểm tra xem đã về đủ hàng so với PO chưa (nếu có reference PO)
            $allDone = true;
            if ($import->reference_type === 'purchase_order') {
                $po = PurchaseOrder::find($import->reference_id);
                if ($po) {
                    $po->load('items');
                    // Nếu PO vẫn còn món chưa về (không phải received/cancelled) thì chưa xong
                    $hasMoreInPo = $po->items->contains(fn($i) => !in_array($i->status, ['received', 'cancelled']));
                    if ($hasMoreInPo) {
                        $allDone = false;
                    }
                }
            }

            // Cập nhật trạng thái phiếu
            $import->update([
                'status' => $allDone ? 'completed' : 'pending'
            ]);

            // Tạo bút toán kế toán tự động (Lịch sử: Duyệt)
            try {
                $import->load(['items', 'supplier', 'warehouse']);
                $this->journalService->createForImport($import, 'approve');
            } catch (\Exception $journalEx) {
                Log::warning('Không thể tạo bút toán cho phiếu nhập ' . $import->code . ' khi duyệt: ' . $journalEx->getMessage());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $allDone ? 'Toàn bộ phiếu nhập đã được duyệt hoàn tất.' : 'Đã duyệt nhập kho các sản phẩm vừa về. Phiếu vẫn mở để chờ đợt hàng tiếp theo.'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            $message = 'Lỗi cơ sở dữ liệu khi duyệt: ' . $e->getMessage();
            if ($e->errorInfo[1] == 1062) {
                $message = "Một hoặc nhiều Serial đã tồn tại trong hệ thống. Vui lòng kiểm tra lại.";
            }
            return response()->json(['success' => false, 'message' => $message], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi duyệt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a pending import.
     */
    public function reject(Request $request, Import $import)
    {
        $this->authorize('approve', $import);

        // Only allow rejecting pending imports
        if ($import->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể từ chối phiếu đang chờ xử lý.'
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        try {
            $import->update([
                'status' => 'rejected',
                'note' => ($import->note ? $import->note . "\n\n" : '') . "Lý do từ chối: " . $request->reason,
            ]);

            // Ghi nhật ký kế toán (Lịch sử: Từ chối)
            try {
                $this->journalService->createForImport($import, 'reject');
            } catch (\Exception $journalEx) {}

            // Tạo thông báo cho người tạo phiếu
            if ($import->employee_id) {
                $this->notificationService->notifyDocumentRejected($import, 'import', $import->employee_id, $request->reason);
            }

            return response()->json([
                'success' => true,
                'message' => 'Phiếu nhập đã bị từ chối'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi từ chối: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export imports to Excel
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Import::class);

        $filters = $request->only(['warehouse_id', 'status', 'date_from', 'date_to']);
        return \Excel::download(new \App\Exports\ImportsExport($filters), 'phieu-nhap-kho-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Print import voucher
     */
    public function print(Import $import)
    {
        $this->authorize('view', $import);
        $import->load(['warehouse', 'employee', 'items.product']);
        return view('reports.vouchers.phieu-nhap-kho', compact('import'));
    }

    /**
     * Export to Misa Excel
     */
    public function exportMisa(Request $request)
    {
        $this->authorize('viewAny', Import::class);
        $filters = $request->only(['date_from', 'date_to', 'warehouse_id']);
        
        $query = \App\Models\ImportItem::with(['import.warehouse', 'product'])
            ->whereHas('import', function($q) use ($filters) {
                $q->where('status', 'completed');
                if (!empty($filters['date_from'])) $q->whereDate('date', '>=', $filters['date_from']);
                if (!empty($filters['date_to'])) $q->whereDate('date', '<=', $filters['date_to']);
                if (!empty($filters['warehouse_id'])) $q->where('warehouse_id', $filters['warehouse_id']);
            });

        $items = $query->get();
        
        // Debug: Check if we have data
        if ($items->isEmpty()) {
            return back()->with('error', 'Không có dữ liệu phiếu nhập đã hoàn thành trong khoảng thời gian này.');
        }

        return \Excel::download(new \App\Exports\MisaInventoryExport($items, 'import'), 'phieu-nhap-kho-' . date('Ymd') . '.xlsx');
    }
    public function exportMisaSingle(Import $import)
    {
        $this->authorize('view', $import);
        
        $items = \App\Models\ImportItem::with(['import.warehouse', 'product', 'import.supplier'])
            ->where('import_id', $import->id)
            ->get();

        return \Excel::download(new \App\Exports\MisaInventoryExport($items, 'import'), 'phieu-nhap-kho-' . $import->code . '.xlsx');
    }
}
