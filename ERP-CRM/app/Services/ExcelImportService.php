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
     * Updated: New format per customer request
     * Columns: STT | Part Number / FRU | Tổng Slg kho vật lý | Slg. Chi tiết | Số Serial | Ngày nhập kho | Ghi chú
     */
    public function generateProductTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        
        // Instructions Sheet
        $instructionsSheet = $spreadsheet->createSheet(0);
        $instructionsSheet->setTitle('Huong Dan');
        $instructionsSheet->setCellValue('A1', 'Hướng Dẫn Import Sản Phẩm');
        $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $instructions = [
            ['Cột', 'Mô tả', 'Bắt buộc', 'Định dạng'],
            ['STT', 'Số thứ tự (tự động đánh số)', 'Không', 'Số (1, 2, 3...)'],
            ['Part Number / FRU', 'Mã sản phẩm (Part Number)', 'Có', 'Text (VD: WAX510D-EU0101F)'],
            ['Tổng Slg kho vật lý', 'Tổng số lượng của mã sản phẩm này', 'Không', 'Số (để tham khảo)'],
            ['Slg. Chi tiết', 'Số lượng chi tiết (luôn = 1 vì mỗi dòng là 1 serial)', 'Có', 'Số (mặc định: 1)'],
            ['Số Serial', 'Số serial của sản phẩm', 'Có', 'Text (VD: S252L14100502)'],
            ['Ngày nhập kho', 'Ngày nhập kho', 'Có', 'Ngày (DD/MM/YYYY hoặc YYYY-MM-DD)'],
            ['Tên sản phẩm', 'Tên đầy đủ của sản phẩm', 'Không', 'Text'],
            ['Danh mục', 'Danh mục sản phẩm (A-Z)', 'Không', 'Chữ cái (A-Z)'],
            ['Đơn vị', 'Đơn vị tính', 'Không', 'Text (VD: Cái, Hộp)'],
            ['Bảo hành (tháng)', 'Thời gian bảo hành mặc định', 'Không', 'Số (VD: 12, 24, 36)'],
            ['Ghi chú', 'Ghi chú thêm', 'Không', 'Text'],
        ];
        
        $row = 3;
        foreach ($instructions as $instruction) {
            $instructionsSheet->fromArray($instruction, null, 'A' . $row);
            $row++;
        }
        
        // Style header row
        $instructionsSheet->getStyle('A3:D3')->getFont()->setBold(true);
        $instructionsSheet->getStyle('A3:D3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        
        // Auto-size columns for instructions
        foreach (range('A', 'D') as $col) {
            $instructionsSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Data Sheet
        $dataSheet = $spreadsheet->createSheet(1);
        $dataSheet->setTitle('Du Lieu');
        
        $headers = ['STT', 'Part Number / FRU', 'Tổng Slg kho vật lý', 'Slg. Chi tiết', 'Số Serial', 'Ngày nhập kho', 'Tên sản phẩm', 'Danh mục', 'Đơn vị', 'Bảo hành (tháng)', 'Ghi chú'];
        $dataSheet->fromArray($headers, null, 'A1');
        $dataSheet->getStyle('A1:K1')->getFont()->setBold(true);
        $dataSheet->getStyle('A1:K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
        $dataSheet->getStyle('A1:K1')->getFont()->getColor()->setRGB('FFFFFF');
        
        // Example rows (theo format của khách hàng)
        $examples = [
            [1, 'ST4000VN006', 2, 1, 'WW67EWKA', '12/3/2025', 'Seagate IronWolf 4TB', 'A', 'Cái', 36, 'Nhập bên Maxlink 2'],
            [2, 'ST4000VN006', '', 1, 'WW67H60T', '12/3/2025', '', '', '', '', ''],
            [3, 'XGS2220-30F-US0101F', 4, 1, 'S242L02014561', '12/4/2025', 'Zyxel XGS2220-30F Switch', 'A', 'Cái', 24, ''],
            [4, 'XGS2220-30F-US0101F', '', 1, 'S242L02014573', '12/4/2025', '', '', '', '', ''],
            [5, 'XGS2220-30F-US0101F', '', 1, 'S242L02014518', '12/4/2025', '', '', '', '', ''],
            [6, 'XGS2220-30F-US0101F', '', 1, 'S242L02014515', '12/4/2025', '', '', '', '', ''],
            [7, 'WAX510D-EU0101F', 28, 1, 'S252L14101325', '12/4/2025', 'Zyxel WAX510D Access Point', 'A', 'Cái', 12, ''],
            [8, 'WAX510D-EU0101F', '', 1, 'S252L14100502', '12/4/2025', '', '', '', '', ''],
            [9, 'WAX510D-EU0101F', '', 1, 'S252L14101273', '12/4/2025', '', '', '', '', ''],
            [10, 'WAX510D-EU0101F', '', 1, 'S252L14101019', '12/4/2025', '', '', '', '', ''],
            [11, 'WAX510D-EU0101F', '', 1, 'S252L14101012', '12/4/2025', '', '', '', '', ''],
            [12, 'WAX510D-EU0101F', '', 1, 'S252L14100702', '12/4/2025', '', '', '', '', ''],
        ];
        
        $row = 2;
        foreach ($examples as $example) {
            $dataSheet->fromArray($example, null, 'A' . $row);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $dataSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add borders
        $lastRow = $row - 1;
        $dataSheet->getStyle("A1:K{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
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
     * Updated: New format per customer request
     */
    public function generateInventoryTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        
        // Instructions Sheet
        $instructionsSheet = $spreadsheet->createSheet(0);
        $instructionsSheet->setTitle('Huong Dan');
        $instructionsSheet->setCellValue('A1', 'Hướng Dẫn Import Kho Hàng');
        $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $instructions = [
            ['Cột', 'Mô tả', 'Bắt buộc', 'Định dạng'],
            ['STT', 'Số thứ tự (tự động đánh số)', 'Không', 'Số (1, 2, 3...)'],
            ['Part Number / FRU', 'Mã sản phẩm (phải tồn tại trong hệ thống)', 'Có', 'Text (VD: WAX510D-EU0101F)'],
            ['Tổng Slg kho vật lý', 'Tổng số lượng của mã sản phẩm này', 'Không', 'Số (để tham khảo)'],
            ['Slg. Chi tiết', 'Số lượng chi tiết (luôn = 1 vì mỗi dòng là 1 serial)', 'Có', 'Số (mặc định: 1)'],
            ['Số Serial', 'Số serial của sản phẩm', 'Có', 'Text (VD: S252L14100502)'],
            ['Ngày nhập kho', 'Ngày nhập kho', 'Có', 'Ngày (DD/MM/YYYY hoặc YYYY-MM-DD)'],
            ['Kho', 'Mã kho nhập (phải tồn tại trong hệ thống)', 'Có', 'Text (VD: WH01)'],
            ['Giá vốn (USD)', 'Giá vốn bằng USD', 'Không', 'Số thập phân (VD: 100.50)'],
            ['Bảng giá JSON', 'Bảng giá theo năm (JSON)', 'Không', '[{"name":"1yr","price":120}]'],
            ['Ghi chú', 'Ghi chú thêm', 'Không', 'Text'],
        ];
        
        $row = 3;
        foreach ($instructions as $instruction) {
            $instructionsSheet->fromArray($instruction, null, 'A' . $row);
            $row++;
        }
        
        // Style header row
        $instructionsSheet->getStyle('A3:D3')->getFont()->setBold(true);
        $instructionsSheet->getStyle('A3:D3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        
        // Auto-size columns for instructions
        foreach (range('A', 'D') as $col) {
            $instructionsSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Data Sheet
        $dataSheet = $spreadsheet->createSheet(1);
        $dataSheet->setTitle('Du Lieu');
        
        $headers = ['STT', 'Part Number / FRU', 'Tổng Slg kho vật lý', 'Slg. Chi tiết', 'Số Serial', 'Ngày nhập kho', 'Kho', 'Giá vốn (USD)', 'Bảng giá JSON', 'Ghi chú'];
        $dataSheet->fromArray($headers, null, 'A1');
        $dataSheet->getStyle('A1:J1')->getFont()->setBold(true);
        $dataSheet->getStyle('A1:J1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
        $dataSheet->getStyle('A1:J1')->getFont()->getColor()->setRGB('FFFFFF');
        
        // Example rows (theo format của khách hàng)
        $examples = [
            [1, 'ST4000VN006', 2, 1, 'WW67EWKA', '12/3/2025', 'WH01', 80.00, '', 'Nhập bên Maxlink 2'],
            [2, 'ST4000VN006', '', 1, 'WW67H60T', '12/3/2025', 'WH01', 80.00, '', ''],
            [3, 'XGS2220-30F-US0101F', 4, 1, 'S242L02014561', '12/4/2025', 'WH01', 250.00, '[{"name":"1yr","price":300}]', ''],
            [4, 'XGS2220-30F-US0101F', '', 1, 'S242L02014573', '12/4/2025', 'WH01', 250.00, '[{"name":"1yr","price":300}]', ''],
            [5, 'XGS2220-30F-US0101F', '', 1, 'S242L02014518', '12/4/2025', 'WH01', 250.00, '', ''],
            [6, 'XGS2220-30F-US0101F', '', 1, 'S242L02014515', '12/4/2025', 'WH01', 250.00, '', ''],
            [7, 'WAX510D-EU0101F', 28, 1, 'S252L14101325', '12/4/2025', 'WH01', 150.00, '', ''],
            [8, 'WAX510D-EU0101F', '', 1, 'S252L14100502', '12/4/2025', 'WH01', 150.00, '', ''],
            [9, 'WAX510D-EU0101F', '', 1, 'S252L14101273', '12/4/2025', 'WH01', 150.00, '', ''],
        ];
        
        $row = 2;
        foreach ($examples as $example) {
            $dataSheet->fromArray($example, null, 'A' . $row);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $dataSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add borders
        $lastRow = $row - 1;
        $dataSheet->getStyle("A1:J{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        $spreadsheet->setActiveSheetIndex(1);
        
        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'inventory_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return $tempFile;
    }

    /**
     * Import products from Excel file and create inventory
     * Updated: Import sản phẩm + nhập kho (tạo product_items + tính tồn kho)
     * Columns: STT | Part Number / FRU | Tổng Slg kho vật lý | Slg. Chi tiết | Số Serial | Ngày nhập kho | Tên sản phẩm | Danh mục | Đơn vị | Ghi chú
     */
    public function importProducts($filePath, $warehouseId = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        // Remove header row
        $headers = array_shift($rows);
        
        $productsCreated = 0;
        $itemsImported = 0;
        $errors = [];
        $productCache = []; // Cache products to avoid repeated queries
        
        // Validate warehouse
        $warehouse = null;
        if ($warehouseId) {
            $warehouse = Warehouse::find($warehouseId);
            if (!$warehouse) {
                return ['success' => false, 'imported' => 0, 'errors' => ['Kho không tồn tại']];
            }
        }
        
        DB::beginTransaction();
        try {
            // Group items by date for creating import transactions
            $groupedItems = [];
            
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Column mapping:
                // 0: STT (ignored)
                // 1: Part Number / FRU (product code)
                // 2: Tổng Slg kho vật lý (ignored - for reference only)
                // 3: Slg. Chi tiết (quantity - usually 1)
                // 4: Số Serial
                // 5: Ngày nhập kho
                // 6: Tên sản phẩm
                // 7: Danh mục
                // 8: Đơn vị
                // 9: Bảo hành (tháng)
                // 10: Ghi chú
                
                $productCode = trim($row[1] ?? '');
                $quantity = $row[3] ?? 1;
                $serial = trim($row[4] ?? '');
                $dateRaw = $row[5] ?? date('Y-m-d');
                $productName = trim($row[6] ?? '');
                $category = strtoupper(trim($row[7] ?? 'A'));
                $unit = trim($row[8] ?? 'Cái');
                $warrantyMonths = $row[9] ?? null;
                $note = trim($row[10] ?? '');
                
                // Skip if no product code
                if (empty($productCode)) {
                    continue;
                }
                
                // Skip if no serial
                if (empty($serial)) {
                    $errors[] = "Dòng {$rowNumber}: Thiếu số Serial";
                    continue;
                }
                
                // Parse date
                $importDate = $this->parseDate($dateRaw);
                if (!$importDate) {
                    $errors[] = "Dòng {$rowNumber}: Ngày nhập kho không hợp lệ '{$dateRaw}'";
                    continue;
                }
                
                // Parse warranty months (optional)
                $warrantyMonthsValue = null;
                if (!empty($warrantyMonths) && is_numeric($warrantyMonths)) {
                    $warrantyMonthsValue = (int) $warrantyMonths;
                    if ($warrantyMonthsValue < 0 || $warrantyMonthsValue > 120) {
                        $warrantyMonthsValue = null;
                    }
                }

                // Get or create product
                if (!isset($productCache[$productCode])) {
                    $product = Product::where('code', $productCode)->first();
                    
                    if (!$product) {
                        // Create new product
                        if (empty($productName)) {
                            $productName = $productCode;
                        }
                        if (!preg_match('/^[A-Z]$/', $category)) {
                            $category = 'A';
                        }
                        if (empty($unit)) {
                            $unit = 'Cái';
                        }
                        
                        $product = Product::create([
                            'code' => $productCode,
                            'name' => $productName,
                            'category' => $category,
                            'unit' => $unit,
                            'warranty_months' => $warrantyMonthsValue,
                            'description' => null,
                            'note' => null,
                        ]);
                        $productsCreated++;
                    } else {
                        // Update warranty_months if provided and product doesn't have one
                        if ($warrantyMonthsValue !== null && empty($product->warranty_months)) {
                            $product->update(['warranty_months' => $warrantyMonthsValue]);
                        }
                    }
                    
                    $productCache[$productCode] = $product;
                }
                
                $product = $productCache[$productCode];
                
                // Validate quantity
                $quantity = is_numeric($quantity) && $quantity > 0 ? (int)$quantity : 1;
                
                // Group by date
                if (!isset($groupedItems[$importDate])) {
                    $groupedItems[$importDate] = [];
                }
                
                $groupedItems[$importDate][] = [
                    'product_id' => $product->id,
                    'product_code' => $productCode,
                    'quantity' => $quantity,
                    'serial' => $serial,
                    'note' => $note,
                    'row_number' => $rowNumber,
                ];
            }
            
            if (!empty($errors)) {
                DB::rollBack();
                return ['success' => false, 'imported' => 0, 'errors' => $errors];
            }

            // Check for duplicate serials in database and within the file
            $allSerials = [];
            $duplicatesInFile = [];
            $duplicatesInDb = [];

            foreach ($groupedItems as $date => $items) {
                foreach ($items as $item) {
                    $key = "{$item['product_id']}:{$item['serial']}";
                    
                    // Check duplicate within file
                    if (isset($allSerials[$key])) {
                        $duplicatesInFile[] = "Dòng {$item['row_number']}: Serial '{$item['serial']}' bị trùng với dòng {$allSerials[$key]}";
                    } else {
                        $allSerials[$key] = $item['row_number'];
                    }
                }
            }

            // Check duplicates in database (batch check for performance)
            $serialsByProduct = [];
            foreach ($groupedItems as $date => $items) {
                foreach ($items as $item) {
                    if (!isset($serialsByProduct[$item['product_id']])) {
                        $serialsByProduct[$item['product_id']] = [
                            'serials' => [],
                            'product_code' => $item['product_code'],
                        ];
                    }
                    $serialsByProduct[$item['product_id']]['serials'][] = $item['serial'];
                }
            }

            foreach ($serialsByProduct as $productId => $data) {
                $existingSerials = ProductItem::where('product_id', $productId)
                    ->whereIn('sku', $data['serials'])
                    ->pluck('sku')
                    ->toArray();
                
                if (!empty($existingSerials)) {
                    foreach ($existingSerials as $existingSerial) {
                        $duplicatesInDb[] = "Serial '{$existingSerial}' đã tồn tại trong hệ thống cho sản phẩm '{$data['product_code']}'";
                    }
                }
            }

            if (!empty($duplicatesInFile) || !empty($duplicatesInDb)) {
                DB::rollBack();
                $allDuplicateErrors = array_merge($duplicatesInFile, $duplicatesInDb);
                return ['success' => false, 'imported' => 0, 'errors' => $allDuplicateErrors];
            }
            
            // Create import transactions for each date
            foreach ($groupedItems as $date => $items) {
                $transactionData = [
                    'type' => 'import',
                    'warehouse_id' => $warehouse->id,
                    'date' => $date,
                    'note' => 'Import từ Excel',
                    'items' => [],
                ];
                
                foreach ($items as $item) {
                    $transactionData['items'][] = [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'skus' => [$item['serial']],
                        'cost_usd' => 0,
                        'price_tiers' => [],
                        'description' => null,
                        'comments' => $item['note'] ?: null,
                        'create_product_items' => true,
                    ];
                    $itemsImported++;
                }
                
                $this->transactionService->processImport($transactionData);
            }
            
            DB::commit();
            
            $message = "Đã import thành công: {$itemsImported} sản phẩm vào kho";
            if ($productsCreated > 0) {
                $message .= " (tạo mới {$productsCreated} mã sản phẩm)";
            }
            
            return [
                'success' => true, 
                'imported' => $itemsImported,
                'products_created' => $productsCreated,
                'errors' => [],
                'message' => $message
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            return ['success' => false, 'imported' => 0, 'errors' => ['Exception: ' . $e->getMessage()]];
        }
    }

    /**
     * Import inventory from Excel file
     * Requirements: 3.3, 3.4, 3.5, 3.7, 7.3, 7.4, 7.5
     * Updated: New format per customer request
     * Columns: STT | Part Number / FRU | Tổng Slg kho vật lý | Slg. Chi tiết | Số Serial | Ngày nhập kho | Kho | Giá vốn (USD) | Bảng giá JSON | Ghi chú
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
                
                // New column mapping:
                // 0: STT (ignored)
                // 1: Part Number / FRU (product code)
                // 2: Tổng Slg kho vật lý (ignored - for reference only)
                // 3: Slg. Chi tiết (quantity - usually 1)
                // 4: Số Serial (SKU)
                // 5: Ngày nhập kho (transaction date)
                // 6: Kho (warehouse code)
                // 7: Giá vốn (USD) (cost)
                // 8: Bảng giá JSON (price tiers)
                // 9: Ghi chú (comments)
                
                $productCode = trim($row[1] ?? '');
                $quantity = $row[3] ?? 1;
                $serial = trim($row[4] ?? '');
                $transactionDateRaw = $row[5] ?? date('Y-m-d');
                $warehouseCode = trim($row[6] ?? '');
                $costUsd = $row[7] ?? 0;
                $priceTiersJson = $row[8] ?? '[]';
                $comments = $row[9] ?? null;
                
                // Skip if no product code
                if (empty($productCode)) {
                    continue;
                }
                
                // Parse date (support DD/MM/YYYY and YYYY-MM-DD)
                $transactionDate = $this->parseDate($transactionDateRaw);
                if (!$transactionDate) {
                    $errors[] = "Dòng {$rowNumber}: Ngày nhập kho không hợp lệ '{$transactionDateRaw}'";
                    continue;
                }
                
                // Validate product exists
                $product = Product::where('code', $productCode)->first();
                if (!$product) {
                    $errors[] = "Dòng {$rowNumber}: Mã sản phẩm '{$productCode}' không tồn tại";
                    continue;
                }
                
                // Validate warehouse exists
                if (empty($warehouseCode)) {
                    $errors[] = "Dòng {$rowNumber}: Thiếu mã kho";
                    continue;
                }
                $warehouse = Warehouse::where('code', $warehouseCode)->first();
                if (!$warehouse) {
                    $errors[] = "Dòng {$rowNumber}: Mã kho '{$warehouseCode}' không tồn tại";
                    continue;
                }
                
                // Validate quantity (default to 1)
                $quantity = is_numeric($quantity) && $quantity > 0 ? (int)$quantity : 1;
                
                // Validate serial
                if (empty($serial)) {
                    $errors[] = "Dòng {$rowNumber}: Thiếu số Serial";
                    continue;
                }
                
                // Validate and parse price_tiers JSON
                $priceTiers = $this->parsePriceTiers($priceTiersJson);
                if ($priceTiers === false) {
                    $errors[] = "Dòng {$rowNumber}: JSON bảng giá không hợp lệ";
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
                    'product_code' => $productCode,
                    'quantity' => $quantity,
                    'sku' => $serial,
                    'cost_usd' => (float)$costUsd,
                    'price_tiers' => $priceTiers,
                    'description' => null,
                    'comments' => $comments,
                    'row_number' => $rowNumber,
                ];
            }
            
            if (!empty($errors)) {
                DB::rollBack();
                return ['success' => false, 'imported' => 0, 'errors' => $errors];
            }

            // Check for duplicate serials in database and within the file
            $allSerials = [];
            $duplicatesInFile = [];
            $duplicatesInDb = [];

            foreach ($groupedRows as $key => $group) {
                foreach ($group['items'] as $item) {
                    $serialKey = "{$item['product_id']}:{$item['sku']}";
                    
                    // Check duplicate within file
                    if (isset($allSerials[$serialKey])) {
                        $duplicatesInFile[] = "Dòng {$item['row_number']}: Serial '{$item['sku']}' bị trùng với dòng {$allSerials[$serialKey]}";
                    } else {
                        $allSerials[$serialKey] = $item['row_number'];
                    }
                }
            }

            // Check duplicates in database (batch check for performance)
            $serialsByProduct = [];
            foreach ($groupedRows as $key => $group) {
                foreach ($group['items'] as $item) {
                    if (!isset($serialsByProduct[$item['product_id']])) {
                        $serialsByProduct[$item['product_id']] = [
                            'serials' => [],
                            'product_code' => $item['product_code'],
                        ];
                    }
                    $serialsByProduct[$item['product_id']]['serials'][] = $item['sku'];
                }
            }

            foreach ($serialsByProduct as $productId => $data) {
                $existingSerials = ProductItem::where('product_id', $productId)
                    ->whereIn('sku', $data['serials'])
                    ->pluck('sku')
                    ->toArray();
                
                if (!empty($existingSerials)) {
                    foreach ($existingSerials as $existingSerial) {
                        $duplicatesInDb[] = "Serial '{$existingSerial}' đã tồn tại trong hệ thống cho sản phẩm '{$data['product_code']}'";
                    }
                }
            }

            if (!empty($duplicatesInFile) || !empty($duplicatesInDb)) {
                DB::rollBack();
                $allDuplicateErrors = array_merge($duplicatesInFile, $duplicatesInDb);
                return ['success' => false, 'imported' => 0, 'errors' => $allDuplicateErrors];
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
     * Parse date from various formats
     * Supports: DD/MM/YYYY, D/M/YYYY, YYYY-MM-DD
     */
    protected function parseDate($dateString): ?string
    {
        if (empty($dateString)) {
            return date('Y-m-d');
        }
        
        $dateString = trim($dateString);
        
        // Already in YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return $dateString;
        }
        
        // DD/MM/YYYY or D/M/YYYY format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateString, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "{$year}-{$month}-{$day}";
        }
        
        // Try to parse with strtotime
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }
        
        return null;
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
