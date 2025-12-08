<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryTransactionRequest;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\TransactionService;
use App\Services\TransactionExporter;
use App\Services\TransactionImporter;
use Illuminate\Http\Request;

class InventoryTransactionController extends Controller
{
    protected $transactionService;
    protected $transactionExporter;
    protected $transactionImporter;

    public function __construct(
        TransactionService $transactionService,
        TransactionExporter $transactionExporter,
        TransactionImporter $transactionImporter
    ) {
        $this->transactionService = $transactionService;
        $this->transactionExporter = $transactionExporter;
        $this->transactionImporter = $transactionImporter;
    }

    /**
     * Display a listing of transactions.
     */
    public function index(Request $request)
    {
        $query = InventoryTransaction::with(['warehouse', 'toWarehouse', 'employee', 'items']);

        // Filter by type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filter by warehouse
        if ($request->filled('warehouse_id')) {
            $query->byWarehouse($request->warehouse_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by date range
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        // Search by code
        if ($request->filled('search')) {
            $query->where('code', 'like', "%{$request->search}%");
        }

        $transactions = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $warehouses = Warehouse::active()->get();

        return view('transactions.index', compact('transactions', 'warehouses'));
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request)
    {
        $type = $request->get('type', 'import');
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();
        $employees = User::whereNotNull('employee_code')->get();
        $code = $this->transactionService->generateTransactionCode($type);

        // Route to specific view based on type
        $view = match($type) {
            'import' => 'transactions.import',
            'export' => 'transactions.export',
            'transfer' => 'transactions.transfer',
            default => 'transactions.import',
        };

        return view($view, compact('type', 'warehouses', 'products', 'employees', 'code'));
    }

    /**
     * Store a newly created transaction.
     */
    public function store(InventoryTransactionRequest $request)
    {
        try {
            $data = $request->validated();

            // Process based on type
            $transaction = match($data['type']) {
                'import' => $this->transactionService->processImport($data),
                'export' => $this->transactionService->processExport($data),
                'transfer' => $this->transactionService->processTransfer($data),
            };

            return redirect()->route('transactions.show', $transaction->id)
                ->with('success', 'Tạo giao dịch thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show(InventoryTransaction $transaction)
    {
        $transaction->load(['warehouse', 'toWarehouse', 'employee', 'items.product']);

        return view('transactions.show', compact('transaction'));
    }

    /**
     * Show the form for editing a transaction (only if pending).
     */
    public function edit(InventoryTransaction $transaction)
    {
        // Only allow editing pending transactions
        if ($transaction->status !== 'pending') {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Chỉ có thể chỉnh sửa giao dịch đang chờ xử lý.');
        }

        $transaction->load(['items.product']);
        $warehouses = Warehouse::active()->get();
        $products = Product::orderBy('name')->get();
        $employees = User::whereNotNull('employee_code')->get();

        // Route to specific edit view based on type
        $view = match($transaction->type) {
            'import' => 'transactions.edit-import',
            'export' => 'transactions.edit-export',
            'transfer' => 'transactions.edit-transfer',
            default => 'transactions.edit-import',
        };

        return view($view, compact('transaction', 'warehouses', 'products', 'employees'));
    }

    /**
     * Update the specified transaction.
     */
    public function update(InventoryTransactionRequest $request, InventoryTransaction $transaction)
    {
        // Only allow updating pending transactions
        if ($transaction->status !== 'pending') {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Chỉ có thể chỉnh sửa giao dịch đang chờ xử lý.');
        }

        try {
            $data = $request->validated();

            // Delete old items
            $transaction->items()->delete();

            // Update transaction
            $transaction->update([
                'warehouse_id' => $data['warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'] ?? null,
                'date' => $data['date'],
                'employee_id' => $data['employee_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // Create new items and calculate total quantity
            $totalQty = 0;
            foreach ($data['items'] as $itemData) {
                $transaction->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? null,
                    'cost' => $itemData['cost'] ?? null,
                    'serial_number' => $itemData['serial_number'] ?? null,
                ]);
                $totalQty += $itemData['quantity'];
            }

            // Update total quantity
            $transaction->update(['total_qty' => $totalQty]);

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Cập nhật giao dịch thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * Approve a pending transaction.
     */
    public function approve(InventoryTransaction $transaction)
    {
        // Only allow approving pending transactions
        if ($transaction->status !== 'pending') {
            return redirect()->route('transactions.show', $transaction)
                ->with('error', 'Chỉ có thể duyệt giao dịch đang chờ xử lý.');
        }

        try {
            // Process based on type
            match($transaction->type) {
                'import' => $this->transactionService->processImport([
                    'code' => $transaction->code,
                    'warehouse_id' => $transaction->warehouse_id,
                    'date' => $transaction->date->format('Y-m-d'),
                    'employee_id' => $transaction->employee_id,
                    'note' => $transaction->note,
                    'items' => $transaction->items->map(fn($item) => [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'cost' => $item->cost,
                    ])->toArray(),
                ], $transaction),
                'export' => $this->transactionService->processExport([
                    'code' => $transaction->code,
                    'warehouse_id' => $transaction->warehouse_id,
                    'date' => $transaction->date->format('Y-m-d'),
                    'employee_id' => $transaction->employee_id,
                    'note' => $transaction->note,
                    'items' => $transaction->items->map(fn($item) => [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'serial_number' => $item->serial_number,
                    ])->toArray(),
                ], $transaction),
                'transfer' => $this->transactionService->processTransfer([
                    'code' => $transaction->code,
                    'warehouse_id' => $transaction->warehouse_id,
                    'to_warehouse_id' => $transaction->to_warehouse_id,
                    'date' => $transaction->date->format('Y-m-d'),
                    'employee_id' => $transaction->employee_id,
                    'note' => $transaction->note,
                    'items' => $transaction->items->map(fn($item) => [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'serial_number' => $item->serial_number,
                    ])->toArray(),
                ], $transaction),
            };

            return redirect()->route('transactions.show', $transaction)
                ->with('success', 'Duyệt giao dịch thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi duyệt: ' . $e->getMessage());
        }
    }

    /**
     * Delete a pending transaction.
     */
    public function destroy(InventoryTransaction $transaction)
    {
        // Only allow deleting pending transactions
        if ($transaction->status !== 'pending') {
            return redirect()->route('transactions.index')
                ->with('error', 'Chỉ có thể xóa giao dịch đang chờ xử lý.');
        }

        try {
            // Delete transaction items first
            $transaction->items()->delete();
            
            // Delete transaction
            $transaction->delete();

            return redirect()->route('transactions.index')
                ->with('success', 'Xóa giao dịch thành công.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi xóa: ' . $e->getMessage());
        }
    }

    /**
     * Export transactions to CSV
     */
    public function exportCsv(Request $request)
    {
        $query = InventoryTransaction::with(['warehouse', 'toWarehouse', 'employee', 'items.product']);

        // Apply same filters as index
        if ($request->filled('type')) {
            $query->byType($request->type);
        }
        if ($request->filled('warehouse_id')) {
            $query->byWarehouse($request->warehouse_id);
        }
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        $transactions = $query->orderBy('date', 'desc')->get();

        $csv = $this->transactionExporter->exportToCsv($transactions);
        $filename = $this->transactionExporter->getFilename('csv', 'transactions');

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export transactions to JSON
     */
    public function exportJson(Request $request)
    {
        $query = InventoryTransaction::with(['warehouse', 'toWarehouse', 'employee', 'items.product']);

        // Apply same filters as index
        if ($request->filled('type')) {
            $query->byType($request->type);
        }
        if ($request->filled('warehouse_id')) {
            $query->byWarehouse($request->warehouse_id);
        }
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        $transactions = $query->orderBy('date', 'desc')->get();

        $json = $this->transactionExporter->exportToJson($transactions);
        $filename = $this->transactionExporter->getFilename('json', 'transactions');

        return response($json)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Show import form for CSV/JSON data
     */
    public function importForm()
    {
        return view('transactions.import-data');
    }

    /**
     * Import transactions from file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,json,txt',
            'format' => 'required|in:csv,json',
        ]);

        try {
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());
            $format = $request->input('format');

            $result = match($format) {
                'csv' => $this->transactionImporter->importFromCsv($content),
                'json' => $this->transactionImporter->importFromJson($content),
            };

            if ($result['imported'] > 0) {
                $message = "Đã import thành công {$result['imported']} giao dịch.";
                if ($result['skipped'] > 0) {
                    $message .= " Bỏ qua {$result['skipped']} giao dịch.";
                }
                
                return redirect()->route('transactions.index')
                    ->with('success', $message)
                    ->with('import_errors', $result['errors'])
                    ->with('import_warnings', $result['warnings']);
            } else {
                return redirect()->back()
                    ->with('error', 'Không có giao dịch nào được import.')
                    ->with('import_errors', $result['errors']);
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Lỗi khi import: ' . $e->getMessage());
        }
    }
}
