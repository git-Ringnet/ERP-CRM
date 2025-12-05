<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransactionImporter
{
    private TransactionService $transactionService;
    private array $errors = [];
    private array $warnings = [];

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Import transactions from JSON
     */
    public function importFromJson(string $jsonContent): array
    {
        $this->errors = [];
        $this->warnings = [];
        
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON format: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new \InvalidArgumentException('JSON must contain an array of transactions');
        }

        $imported = 0;
        $skipped = 0;

        foreach ($data as $index => $transactionData) {
            try {
                $this->validateTransactionData($transactionData, $index);
                $this->importTransaction($transactionData);
                $imported++;
            } catch (ValidationException $e) {
                $this->errors[] = "Row {$index}: " . implode(', ', $e->errors());
                $skipped++;
            } catch (\Exception $e) {
                $this->errors[] = "Row {$index}: " . $e->getMessage();
                $skipped++;
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * Import transactions from CSV
     */
    public function importFromCsv(string $csvContent): array
    {
        $this->errors = [];
        $this->warnings = [];
        
        $lines = str_getcsv($csvContent, "\n");
        
        if (empty($lines)) {
            throw new \InvalidArgumentException('CSV file is empty');
        }

        // Parse header
        $header = str_getcsv(array_shift($lines));
        
        $imported = 0;
        $skipped = 0;
        $currentTransaction = null;
        $currentItems = [];

        foreach ($lines as $index => $line) {
            $row = str_getcsv($line);
            
            if (count($row) !== count($header)) {
                $this->errors[] = "Row " . ($index + 2) . ": Column count mismatch";
                $skipped++;
                continue;
            }

            $data = array_combine($header, $row);
            
            try {
                // Check if this is a new transaction (different code)
                if ($currentTransaction === null || $currentTransaction !== $data['Mã giao dịch']) {
                    // Process previous transaction if exists
                    if ($currentTransaction !== null && !empty($currentItems)) {
                        $this->processTransactionFromCsv($currentTransaction, $currentItems);
                        $imported++;
                    }
                    
                    // Start new transaction
                    $currentTransaction = $data['Mã giao dịch'];
                    $currentItems = [];
                }
                
                // Add item to current transaction
                $currentItems[] = [
                    'product_name' => $data['Sản phẩm'],
                    'quantity' => $data['Số lượng SP'],
                    'unit' => $data['Đơn vị'],
                    'warehouse_name' => $data['Kho nguồn'],
                    'to_warehouse_name' => $data['Kho đích'] ?? null,
                    'type' => $data['Loại'],
                    'date' => $data['Ngày'],
                    'employee_name' => $data['Nhân viên'],
                    'note' => $data['Ghi chú'] ?? null,
                ];
                
            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $skipped++;
            }
        }

        // Process last transaction
        if ($currentTransaction !== null && !empty($currentItems)) {
            try {
                $this->processTransactionFromCsv($currentTransaction, $currentItems);
                $imported++;
            } catch (\Exception $e) {
                $this->errors[] = "Last transaction: " . $e->getMessage();
                $skipped++;
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * Validate transaction data
     */
    private function validateTransactionData(array $data, int $index): void
    {
        $validator = Validator::make($data, [
            'type' => 'required|in:import,export,transfer',
            'warehouse.code' => 'required|string',
            'to_warehouse.code' => 'required_if:type,transfer|nullable|string',
            'date' => 'required|date',
            'employee.code' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_code' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Import a single transaction
     */
    private function importTransaction(array $data): void
    {
        // Find warehouse
        $warehouse = Warehouse::where('code', $data['warehouse']['code'])->first();
        if (!$warehouse) {
            throw new \Exception("Warehouse not found: {$data['warehouse']['code']}");
        }

        // Find employee
        $employee = User::where('employee_code', $data['employee']['code'])->first();
        if (!$employee) {
            throw new \Exception("Employee not found: {$data['employee']['code']}");
        }

        // Prepare items
        $items = [];
        foreach ($data['items'] as $itemData) {
            $product = Product::where('code', $itemData['product_code'])->first();
            if (!$product) {
                throw new \Exception("Product not found: {$itemData['product_code']}");
            }

            $items[] = [
                'product_id' => $product->id,
                'quantity' => $itemData['quantity'],
                'unit' => $itemData['unit'],
                'serial_number' => $itemData['serial_number'] ?? null,
            ];
        }

        // Process based on type
        switch ($data['type']) {
            case 'import':
                $this->transactionService->processImport([
                    'warehouse_id' => $warehouse->id,
                    'employee_id' => $employee->id,
                    'date' => $data['date'],
                    'items' => $items,
                    'note' => $data['note'] ?? null,
                ]);
                break;

            case 'export':
                $this->transactionService->processExport([
                    'warehouse_id' => $warehouse->id,
                    'employee_id' => $employee->id,
                    'date' => $data['date'],
                    'items' => $items,
                    'note' => $data['note'] ?? null,
                ]);
                break;

            case 'transfer':
                $toWarehouse = Warehouse::where('code', $data['to_warehouse']['code'])->first();
                if (!$toWarehouse) {
                    throw new \Exception("Destination warehouse not found: {$data['to_warehouse']['code']}");
                }

                $this->transactionService->processTransfer([
                    'warehouse_id' => $warehouse->id,
                    'to_warehouse_id' => $toWarehouse->id,
                    'employee_id' => $employee->id,
                    'date' => $data['date'],
                    'items' => $items,
                    'note' => $data['note'] ?? null,
                ]);
                break;
        }
    }

    /**
     * Process transaction from CSV data
     */
    private function processTransactionFromCsv(string $code, array $items): void
    {
        if (empty($items)) {
            throw new \Exception("No items found for transaction {$code}");
        }

        $firstItem = $items[0];
        
        // Find warehouse
        $warehouse = Warehouse::where('name', $firstItem['warehouse_name'])->first();
        if (!$warehouse) {
            throw new \Exception("Warehouse not found: {$firstItem['warehouse_name']}");
        }

        // Find employee
        $employee = User::where('name', $firstItem['employee_name'])->first();
        if (!$employee) {
            throw new \Exception("Employee not found: {$firstItem['employee_name']}");
        }

        // Prepare items
        $processedItems = [];
        foreach ($items as $itemData) {
            $product = Product::where('name', $itemData['product_name'])->first();
            if (!$product) {
                $this->warnings[] = "Product not found: {$itemData['product_name']}, skipping item";
                continue;
            }

            $processedItems[] = [
                'product_id' => $product->id,
                'quantity' => $itemData['quantity'],
                'unit' => $itemData['unit'],
            ];
        }

        if (empty($processedItems)) {
            throw new \Exception("No valid items found for transaction {$code}");
        }

        // Process based on type
        switch ($firstItem['type']) {
            case 'import':
                $this->transactionService->processImport([
                    'warehouse_id' => $warehouse->id,
                    'employee_id' => $employee->id,
                    'date' => $firstItem['date'],
                    'items' => $processedItems,
                    'note' => $firstItem['note'],
                ]);
                break;

            case 'export':
                $this->transactionService->processExport([
                    'warehouse_id' => $warehouse->id,
                    'employee_id' => $employee->id,
                    'date' => $firstItem['date'],
                    'items' => $processedItems,
                    'note' => $firstItem['note'],
                ]);
                break;

            case 'transfer':
                if (empty($firstItem['to_warehouse_name'])) {
                    throw new \Exception("Destination warehouse required for transfer");
                }

                $toWarehouse = Warehouse::where('name', $firstItem['to_warehouse_name'])->first();
                if (!$toWarehouse) {
                    throw new \Exception("Destination warehouse not found: {$firstItem['to_warehouse_name']}");
                }

                $this->transactionService->processTransfer([
                    'warehouse_id' => $warehouse->id,
                    'to_warehouse_id' => $toWarehouse->id,
                    'employee_id' => $employee->id,
                    'date' => $firstItem['date'],
                    'items' => $processedItems,
                    'note' => $firstItem['note'],
                ]);
                break;

            default:
                throw new \Exception("Invalid transaction type: {$firstItem['type']}");
        }
    }

    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
}
