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
use Illuminate\Support\Facades\Log;

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

        $query = Import::with(['warehouse', 'supplier', 'employee', 'items']);

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
        
        // Load products with calculated cost but optimize the query
        // Only eager load the relationships needed for cost calculation
        $products = Product::with(['supplierPriceListItems' => function($query) {
                $query->with(['priceList' => function($q) {
                    $q->where('is_active', true)
                      ->where(function($q2) {
                          $q2->whereNull('effective_date')
                             ->orWhere('effective_date', '<=', now());
                      });
                }]);
            }])
            ->select('id', 'code', 'name', 'unit', 'category')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                // Only calculate cost if there are price list items
                $product->default_cost = $product->supplierPriceListItems->isNotEmpty() 
                    ? $product->calculated_cost 
                    : 0;
                // Unset the relationship to reduce memory
                unset($product->supplierPriceListItems);
                return $product;
            });
            
        $employees = User::whereNotNull('employee_code')->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        $code = Import::generateCode();
        
        // Get approved or completed shipping allocations for selection
        // Allow both approved and completed allocations to be reused
        $shippingAllocations = \App\Models\ShippingAllocation::with(['purchaseOrder', 'warehouse'])
            ->whereIn('status', ['approved', 'completed'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('imports.create', compact('warehouses', 'products', 'employees', 'suppliers', 'code', 'shippingAllocations'));
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

        $import->load(['items.product', 'items.warehouse']);
        $warehouses = Warehouse::active()->get();
        
        // Load products with calculated cost but optimize the query
        // Only eager load the relationships needed for cost calculation
        $products = Product::with(['supplierPriceListItems' => function($query) {
                $query->with(['priceList' => function($q) {
                    $q->where('is_active', true)
                      ->where(function($q2) {
                          $q2->whereNull('effective_date')
                             ->orWhere('effective_date', '<=', now());
                      });
                }]);
            }])
            ->select('id', 'code', 'name', 'unit', 'category')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                // Only calculate cost if there are price list items
                $product->default_cost = $product->supplierPriceListItems->isNotEmpty() 
                    ? $product->calculated_cost 
                    : 0;
                // Unset the relationship to reduce memory
                unset($product->supplierPriceListItems);
                return $product;
            });
            
        $employees = User::whereNotNull('employee_code')->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();

        // Prepare existing items data for JavaScript
        $existingItems = $import->items->map(function ($item) {
            // Get serials from serial_number JSON field
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
                'warehouse_id' => $item->warehouse_id,
                'quantity' => $item->quantity,
                'serials' => $serials,
                'cost' => $item->cost,
                'comments' => $item->comments ?? '',
            ];
        })->toArray();

        return view('imports.edit', compact('import', 'warehouses', 'products', 'employees', 'suppliers', 'existingItems'));
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
            ]);

            // Create new items (store serials as JSON, ProductItem created on approve)
            $totalQty = 0;
            foreach ($data['items'] as $itemData) {
                // Get serials from array or from serial_list textarea
                $serials = $itemData['serials'] ?? [];
                if (empty($serials) && !empty($itemData['serial_list'])) {
                    // Parse serial_list (newline or comma separated)
                    $serials = preg_split('/[\n,]+/', $itemData['serial_list']);
                    $serials = array_map('trim', $serials);
                }
                // Filter out empty serials
                $serials = array_values(array_filter($serials, fn($s) => !empty(trim($s))));

                $import->items()->create([
                    'product_id' => $itemData['product_id'],
                    'warehouse_id' => $itemData['warehouse_id'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'serial_number' => !empty($serials) ? json_encode($serials) : null,
                    'comments' => $itemData['comments'] ?? null,
                    'cost' => $itemData['cost'] ?? 0,
                ]);
                $totalQty += $itemData['quantity'];
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

        // Only allow approving pending imports
        if ($import->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể duyệt phiếu đang chờ xử lý.'
            ], 400);
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

            // Tạo bút toán kế toán tự động (Lịch sử: Duyệt)
            try {
                $import->load(['items', 'supplier', 'warehouse']);
                $this->journalService->createForImport($import, 'approve');
            } catch (\Exception $journalEx) {
                Log::warning('Không thể tạo bút toán cho phiếu nhập ' . $import->code . ' khi duyệt: ' . $journalEx->getMessage());
            }

            // Tạo thông báo cho người tạo phiếu
            if ($import->employee_id) {
                $this->notificationService->notifyDocumentApproved($import, 'import', $import->employee_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Phiếu nhập đã được duyệt'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $message = 'Lỗi cơ sở dữ liệu khi duyệt: ' . $e->getMessage();
            
            // Handle duplicate entry error (1062)
            if ($e->errorInfo[1] == 1062) {
                // Extract SKU from message if possible: "Duplicate entry '1-sa' for key '...'"
                if (preg_match("/Duplicate entry '(.*)' for key/", $e->getMessage(), $matches)) {
                    $duplicated = $matches[1];
                    // Remove product_id prefix if it's there (e.g., "1-sa" -> "sa")
                    $parts = explode('-', $duplicated);
                    $sku = count($parts) > 1 ? end($parts) : $duplicated;
                    $message = "Serial '{$sku}' đã tồn tại trong hệ thống. Vui lòng kiểm tra lại.";
                } else {
                    $message = "Một hoặc nhiều Serial đã tồn tại trong hệ thống. Vui lòng kiểm tra lại.";
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => $message
            ], 400); // 400 is better for validation/duplicate errors
        } catch (\Exception $e) {
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
