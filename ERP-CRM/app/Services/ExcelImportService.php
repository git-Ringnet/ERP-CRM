<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductItem;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class ExcelImportService
{
    protected $productItemService;
    protected $transactionService;

    public function __construct(ProductItemService $productItemService, TransactionService $transactionService)
    {
        $this->productItemService = $productItemService;
        $this->transactionService = $transactionService;
    }

    /**
     * Generate Product Excel template
     * Requirements: 6.1, 6.3, 6.5
     */
    public function generateProductTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        
        // Instructions Sheet
        $instructionsSheet = $spreadsheet->createSheet(0);
        $instructionsSheet->setTitle('Instructions');
        $instructionsSheet->setCellValue('A1', 'Product Import Template - Instructions');
        $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $instructions = [
            ['Column', 'Description', 'Required', 'Format'],
            ['Code', 'Unique product code', 'Yes', 'Text (e.g., SP001)'],
            ['Name', 'Product name', 'Yes', 'Text'],
            ['Category', 'Product category (single letter A-Z)', 'Yes', 'Single letter (A-Z)'],
            ['Unit', 'Unit of measurement', 'No', 'Text (e.g., Cái, Hộp, Kg)'],
            ['Description', 'Product description', 'No', 'Text'],
            ['Note', 'Additional notes', 'No', 'Text'],
        ];
        
        $row = 3;
        foreach ($instructions as $instruction) {
            $instructionsSheet->fromArray($instruction, null, 'A' . $row);
            $row++;
        }
        
        // Data Sheet
        $dataSheet = $spreadsheet->createSheet(1);
        $dataSheet->setTitle('Products');
        
        $headers = ['Code', 'Name', 'Category', 'Unit', 'Description', 'Note'];
        $dataSheet->fromArray($headers, null, 'A1');
        $dataSheet->getStyle('A1:F1')->getFont()->setBold(true);
        
        // Example rows
        $examples = [
            ['SP001', 'Chuột không dây Logitech MH85', 'A', 'Cái', 'Chuột không dây cao cấp', 'Hàng chính hãng'],
            ['SP002', 'Bàn phím cơ Keychron K2', 'A', 'Cái', 'Bàn phím cơ 84 phím', ''],
        ];
        
        $row = 2;
        foreach ($examples as $example) {
            $dataSheet->fromArray($example, null, 'A' . $row);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $dataSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $spreadsheet->setActiveSheetIndex(1);
        
        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'product_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return $tempFile;
    }

    /**
     * Generate Inventory Excel template
     * Requirements: 6.2, 6.4, 6.5
     */
    public function generateInventoryTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        
        // Instructions Sheet
        $instructionsSheet = $spreadsheet->createSheet(0);
        $instructionsSheet->setTitle('Instructions');
        $instructionsSheet->setCellValue('A1', 'Inventory Import Template - Instructions');
        $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $instructions = [
            ['Column', 'Description', 'Required', 'Format'],
            ['Product_Code', 'Product code (must exist in system)', 'Yes', 'Text (e.g., SP001)'],
            ['Warehouse_Code', 'Warehouse code (must exist in system)', 'Yes', 'Text (e.g., WH01)'],
            ['Quantity', 'Quantity to import', 'Yes', 'Number (e.g., 10)'],
            ['SKU', 'Stock Keeping Unit (leave empty for auto NO_SKU)', 'No', 'Text (e.g., SKU001)'],
            ['Cost_USD', 'Cost in USD', 'No', 'Decimal (e.g., 100.50)'],
            ['Price_Tiers_JSON', 'Price tiers in JSON format', 'No', '[{"name":"1yr","price":120},{"name":"2yr":"price":200}]'],
            ['Description', 'Item description', 'No', 'Text'],
            ['Comments', 'Additional comments', 'No', 'Text'],
            ['Transaction_Date', 'Import date', 'Yes', 'Date (YYYY-MM-DD)'],
        ];
        
        $row = 3;
        foreach ($instructions as $instruction) {
            $instructionsSheet->fromArray($instruction, null, 'A' . $row);
            $row++;
        }
        
        // Data Sheet
        $dataSheet = $spreadsheet->createSheet(1);
        $dataSheet->setTitle('Inventory');
        
        $headers = ['Product_Code', 'Warehouse_Code', 'Quantity', 'SKU', 'Cost_USD', 'Price_Tiers_JSON', 'Description', 'Comments', 'Transaction_Date'];
        $dataSheet->fromArray($headers, null, 'A1');
        $dataSheet->getStyle('A1:I1')->getFont()->setBold(true);
        
        // Example rows
        $examples = [
            ['SP001', 'WH01', 1, 'SKU-MH85-001', 100.00, '[{"name":"1yr","price":120},{"name":"2yr","price":200}]', 'Chuột Logitech MH85', 'Hàng mới', date('Y-m-d')],
            ['SP001', 'WH01', 1, '', 100.00, '[{"name":"1yr","price":120}]', 'Không có SKU', 'Sẽ tự động tạo NO_SKU', date('Y-m-d')],
            ['SP002', 'WH02', 2, 'SKU-K2-001', 150.00, '[]', 'Bàn phím Keychron', '', date('Y-m-d')],
        ];
        
        $row = 2;
        foreach ($examples as $example) {
            $dataSheet->fromArray($example, null, 'A' . $row);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $dataSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $spreadsheet->setActiveSheetIndex(1);
        
        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'inventory_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return $tempFile;
    }

    /**
     * Import products from Excel file
     * Requirements: 3.2, 7.1, 7.2, 7.6
     */
    public function importProducts($filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        // Remove header row
        $headers = array_shift($rows);
        
        $imported = 0;
        $errors = [];
        
        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because of header and 0-index
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $data = [
                    'code' => $row[0] ?? null,
                    'name' => $row[1] ?? null,
                    'category' => $row[2] ?? null,
                    'unit' => $row[3] ?? null,
                    'description' => $row[4] ?? null,
                    'note' => $row[5] ?? null,
                ];
                
                // Validate
                $validator = Validator::make($data, [
                    'code' => 'required|unique:products,code',
                    'name' => 'required',
                    'category' => 'required|regex:/^[A-Z]$/',
                    'unit' => 'nullable|string',
                    'description' => 'nullable|string',
                    'note' => 'nullable|string',
                ]);
                
                if ($validator->fails()) {
                    $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    continue;
                }
                
                // Create product
                Product::create($data);
                $imported++;
            }
            
            if (!empty($errors)) {
                DB::rollBack();
                return ['success' => false, 'imported' => 0, 'errors' => $errors];
            }
            
            DB::commit();
            return ['success' => true, 'imported' => $imported, 'errors' => []];
            
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'imported' => 0, 'errors' => ['Exception: ' . $e->getMessage()]];
        }
    }

    /**
     * Import inventory from Excel file
     * Requirements: 3.3, 3.4, 3.5, 3.7, 7.3, 7.4, 7.5
     */
    public function importInventory($filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        // Remove header row
        $headers = array_shift($rows);
        
        $imported = 0;
        $errors = [];
        
        DB::beginTransaction();
        try {
            // Group rows by transaction date and warehouse
            $groupedRows = [];
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $productCode = $row[0] ?? null;
                $warehouseCode = $row[1] ?? null;
                $quantity = $row[2] ?? null;
                $sku = $row[3] ?? '';
                $costUsd = $row[4] ?? 0;
                $priceTiersJson = $row[5] ?? '[]';
                $description = $row[6] ?? null;
                $comments = $row[7] ?? null;
                $transactionDate = $row[8] ?? date('Y-m-d');
                
                // Validate product exists
                $product = Product::where('code', $productCode)->first();
                if (!$product) {
                    $errors[] = "Row {$rowNumber}: Product code '{$productCode}' not found";
                    continue;
                }
                
                // Validate warehouse exists
                $warehouse = Warehouse::where('code', $warehouseCode)->first();
                if (!$warehouse) {
                    $errors[] = "Row {$rowNumber}: Warehouse code '{$warehouseCode}' not found";
                    continue;
                }
                
                // Validate quantity
                if (!is_numeric($quantity) || $quantity <= 0) {
                    $errors[] = "Row {$rowNumber}: Invalid quantity '{$quantity}'";
                    continue;
                }
                
                // Validate and parse price_tiers JSON
                $priceTiers = $this->parsePriceTiers($priceTiersJson);
                if ($priceTiers === false) {
                    $errors[] = "Row {$rowNumber}: Invalid JSON in Price_Tiers_JSON";
                    continue;
                }
                
                // Group by date and warehouse
                $key = $transactionDate . '_' . $warehouse->id;
                if (!isset($groupedRows[$key])) {
                    $groupedRows[$key] = [
                        'warehouse_id' => $warehouse->id,
                        'date' => $transactionDate,
                        'items' => [],
                    ];
                }
                
                $groupedRows[$key]['items'][] = [
                    'product_id' => $product->id,
                    'quantity' => (int)$quantity,
                    'sku' => $sku,
                    'cost_usd' => (float)$costUsd,
                    'price_tiers' => $priceTiers,
                    'description' => $description,
                    'comments' => $comments,
                ];
            }
            
            if (!empty($errors)) {
                DB::rollBack();
                return ['success' => false, 'imported' => 0, 'errors' => $errors];
            }
            
            // Create transactions for each group
            foreach ($groupedRows as $group) {
                $transactionData = [
                    'type' => 'import',
                    'warehouse_id' => $group['warehouse_id'],
                    'date' => $group['date'],
                    'note' => 'Imported from Excel',
                    'items' => [],
                ];
                
                foreach ($group['items'] as $item) {
                    $transactionData['items'][] = [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'skus' => !empty($item['sku']) ? [$item['sku']] : [],
                        'cost_usd' => $item['cost_usd'],
                        'price_tiers' => $item['price_tiers'],
                        'description' => $item['description'],
                        'comments' => $item['comments'],
                        'create_product_items' => true,
                    ];
                }
                
                $this->transactionService->processImport($transactionData);
                $imported += count($group['items']);
            }
            
            DB::commit();
            return ['success' => true, 'imported' => $imported, 'errors' => []];
            
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'imported' => 0, 'errors' => ['Exception: ' . $e->getMessage()]];
        }
    }

    /**
     * Parse price_tiers JSON string
     * Requirements: 3.4, 7.5
     */
    public function parsePriceTiers($jsonString)
    {
        if (empty($jsonString) || $jsonString === '[]') {
            return [];
        }
        
        $decoded = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        // Validate structure
        if (!is_array($decoded)) {
            return false;
        }
        
        foreach ($decoded as $tier) {
            if (!isset($tier['name']) || !isset($tier['price'])) {
                return false;
            }
        }
        
        return $decoded;
    }
}
