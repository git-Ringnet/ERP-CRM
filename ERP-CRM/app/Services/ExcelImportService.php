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
     * Updated: New format with warehouse column - import directly to warehouse from Excel
     * Columns: STT | Part Number / FRU | Tổng Slg kho vật lý | Slg. Chi tiết | Số Serial | Ngày nhập kho | Kho | Nhà cung cấp | Tên sản phẩm | Danh mục | Đơn vị | Bảo hành (tháng) | Ghi chú
     */
    public function generateProductTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sản Phẩm');

        $headers = ['STT', 'Part Number / FRU', 'Tổng Slg kho vật lý', 'Slg. Chi tiết', 'Số Serial', 'Ngày nhập kho', 'Kho', 'Nhà cung cấp', 'Tên sản phẩm', 'Danh mục', 'Đơn vị', 'Bảo hành (tháng)', 'Ghi chú'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $sheet->getStyle('A1:M1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:M1')->getFont()->getColor()->setRGB('FFFFFF');

        $examples = [
            [1, 'ST4000VN006', 2, 1, 'WW67EWKA', '12/3/2025', 'WH0001', 'Công ty Maxlink 2', 'Seagate IronWolf 4TB', 'A', 'Cái', 36, 'Nhập bên Maxlink 2'],
            [2, 'ST4000VN006', '', 1, 'WW67H60T', '12/3/2025', 'WH0001', 'Công ty Maxlink 2', '', '', '', '', ''],
            [3, 'XGS2220-30F-US0101F', 4, 1, 'S242L02014561', '12/4/2025', 'Kho Hà Nội', 'Zyxel Vietnam', 'Zyxel XGS2220-30F Switch', 'A', 'Cái', 24, ''],
            [4, 'XGS2220-30F-US0101F', '', 1, 'S242L02014573', '12/4/2025', 'Kho Hà Nội', 'Zyxel Vietnam', '', '', '', '', ''],
            [5, 'XGS2220-30F-US0101F', '', 1, 'S242L02014518', '12/4/2025', 'WH0002', 'Zyxel Vietnam', '', '', '', '', ''],
            [6, 'XGS2220-30F-US0101F', '', 1, 'S242L02014515', '12/4/2025', 'WH0002', 'Zyxel Vietnam', '', '', '', '', ''],
            [7, 'WAX510D-EU0101F', 28, 1, 'S252L14101325', '12/4/2025', 'WH0001', 'Zyxel Vietnam', 'Zyxel WAX510D Access Point', 'A', 'Cái', 12, ''],
            [8, 'WAX510D-EU0101F', '', 1, 'S252L14100502', '12/4/2025', 'WH0001', 'Zyxel Vietnam', '', '', '', '', ''],
            [9, 'WAX510D-EU0101F', '', 1, 'S252L14101273', '12/4/2025', 'Kho Đà Nẵng', 'Zyxel Vietnam', '', '', '', '', ''],
            [10, 'WAX510D-EU0101F', '', 1, 'S252L14101019', '12/4/2025', 'Kho Đà Nẵng', 'Zyxel Vietnam', '', '', '', '', ''],
            [11, 'WAX510D-EU0101F', '', 1, 'S252L14101012', '12/4/2025', 'WH0003', 'Zyxel Vietnam', '', '', '', '', ''],
            [12, 'WAX510D-EU0101F', '', 1, 'S252L14100702', '12/4/2025', 'WH0003', 'Zyxel Vietnam', '', '', '', '', ''],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:M{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

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
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nhập Kho');

        $headers = ['stt', 'part_number_fru', 'tong_slg_kho_vat_ly', 'slg_chi_tiet', 'so_serial', 'ngay_nhap_kho', 'kho', 'gia_von_usd', 'bang_gia_json', 'ghi_chu'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:J1')->getFont()->getColor()->setRGB('FFFFFF');

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
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:J{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $tempFile = tempnam(sys_get_temp_dir(), 'inventory_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }

    /**
     * Import products from Excel file and create inventory
     * Updated: Import sản phẩm + nhập kho với cột Kho trong Excel (mã kho hoặc tên kho)
     * Columns: STT | Part Number / FRU | Tổng Slg kho vật lý | Slg. Chi tiết | Số Serial | Ngày nhập kho | Kho | Tên sản phẩm | Danh mục | Đơn vị | Bảo hành (tháng) | Ghi chú
     */
    public function importProducts($filePath, $warehouseId = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        // Remove header row
        $headers = array_shift($rows);
        
        $productsCreated = 0;
        $suppliersCreated = 0;
        $itemsImported = 0;
        $errors = [];
        $productCache = []; // Cache products to avoid repeated queries
        $warehouseCache = []; // Cache warehouses to avoid repeated queries
        $supplierCache = []; // Cache suppliers to avoid repeated queries
        
        // Pre-load all active warehouses for lookup
        $allWarehouses = Warehouse::active()->get();
        foreach ($allWarehouses as $wh) {
            $warehouseCache[strtolower($wh->code)] = $wh;
            $warehouseCache[strtolower($wh->name)] = $wh;
        }
        
        // Pre-load all suppliers for lookup
        $allSuppliers = \App\Models\Supplier::all();
        foreach ($allSuppliers as $sup) {
            $supplierCache[strtolower($sup->code ?? '')] = $sup;
            $supplierCache[strtolower($sup->name)] = $sup;
        }
        
        // Fallback warehouse if provided (for backward compatibility)
        $fallbackWarehouse = null;
        if ($warehouseId) {
            $fallbackWarehouse = Warehouse::find($warehouseId);
        }
        
        DB::beginTransaction();
        try {
            // Group items by date and warehouse for creating import transactions
            $groupedItems = [];
            
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Column mapping (updated with Nhà cung cấp column):
                // 0: STT (ignored)
                // 1: Part Number / FRU (product code)
                // 2: Tổng Slg kho vật lý (ignored - for reference only)
                // 3: Slg. Chi tiết (quantity - usually 1)
                // 4: Số Serial
                // 5: Ngày nhập kho
                // 6: Kho (mã kho hoặc tên kho)
                // 7: Nhà cung cấp (tên hoặc mã nhà cung cấp)
                // 8: Tên sản phẩm
                // 9: Danh mục
                // 10: Đơn vị
                // 11: Bảo hành (tháng)
                // 12: Ghi chú
                
                $productCode = trim($row[1] ?? '');
                $quantity = $row[3] ?? 1;
                $serial = trim($row[4] ?? '');
                $dateRaw = $row[5] ?? date('Y-m-d');
                $warehouseInput = trim($row[6] ?? '');
                $supplierInput = trim($row[7] ?? '');
                $productName = trim($row[8] ?? '');
                $category = strtoupper(trim($row[9] ?? 'A'));
                $unit = trim($row[10] ?? 'Cái');
                $warrantyMonths = $row[11] ?? null;
                $note = trim($row[12] ?? '');
                
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
                
                // Validate warehouse (from Excel column or fallback)
                $warehouse = null;
                if (!empty($warehouseInput)) {
                    $warehouseKey = strtolower($warehouseInput);
                    if (isset($warehouseCache[$warehouseKey])) {
                        $warehouse = $warehouseCache[$warehouseKey];
                    } else {
                        $errors[] = "Dòng {$rowNumber}: Kho '{$warehouseInput}' không tồn tại (nhập mã kho hoặc tên kho)";
                        continue;
                    }
                } elseif ($fallbackWarehouse) {
                    $warehouse = $fallbackWarehouse;
                } else {
                    $errors[] = "Dòng {$rowNumber}: Thiếu thông tin kho";
                    continue;
                }
                
                // Validate supplier (optional, auto-create if not exists)
                $supplier = null;
                if (!empty($supplierInput)) {
                    $supplierKey = strtolower($supplierInput);
                    if (isset($supplierCache[$supplierKey])) {
                        $supplier = $supplierCache[$supplierKey];
                    } else {
                        // Auto-create supplier if not exists
                        $supplier = \App\Models\Supplier::create([
                            'code' => 'SUP' . str_pad(\App\Models\Supplier::count() + 1, 4, '0', STR_PAD_LEFT),
                            'name' => $supplierInput,
                            'email' => '',
                            'phone' => '',
                        ]);
                        $supplierCache[$supplierKey] = $supplier;
                        $supplierCache[strtolower($supplier->code)] = $supplier;
                        $suppliersCreated++;
                    }
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
                
                // Group by date AND warehouse AND supplier
                $groupKey = $importDate . '_' . $warehouse->id . '_' . ($supplier ? $supplier->id : '0');
                if (!isset($groupedItems[$groupKey])) {
                    $groupedItems[$groupKey] = [
                        'date' => $importDate,
                        'warehouse_id' => $warehouse->id,
                        'supplier_id' => $supplier ? $supplier->id : null,
                        'items' => [],
                    ];
                }
                
                $groupedItems[$groupKey]['items'][] = [
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

            foreach ($groupedItems as $groupKey => $group) {
                foreach ($group['items'] as $item) {
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
            foreach ($groupedItems as $groupKey => $group) {
                foreach ($group['items'] as $item) {
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
            
            // Create import transactions for each date + warehouse + supplier combination
            foreach ($groupedItems as $groupKey => $group) {
                $transactionData = [
                    'warehouse_id' => $group['warehouse_id'],
                    'supplier_id' => $group['supplier_id'],
                    'date' => $group['date'],
                    'note' => 'Import từ Excel',
                    'items' => [],
                ];
                
                // Group items by product_id to merge serials
                $productItems = [];
                foreach ($group['items'] as $item) {
                    $productId = $item['product_id'];
                    if (!isset($productItems[$productId])) {
                        $productItems[$productId] = [
                            'product_id' => $productId,
                            'quantity' => 0,
                            'skus' => [],
                            'comments' => [],
                        ];
                    }
                    $productItems[$productId]['quantity'] += $item['quantity'];
                    $productItems[$productId]['skus'][] = $item['serial'];
                    if (!empty($item['note'])) {
                        $productItems[$productId]['comments'][] = $item['note'];
                    }
                    $itemsImported++;
                }
                
                // Build transaction items from grouped products
                foreach ($productItems as $productItem) {
                    $transactionData['items'][] = [
                        'product_id' => $productItem['product_id'],
                        'warehouse_id' => $group['warehouse_id'],
                        'quantity' => $productItem['quantity'],
                        'serials' => $productItem['skus'],
                        'cost' => 0,
                        'comments' => !empty($productItem['comments']) ? implode('; ', array_unique($productItem['comments'])) : null,
                    ];
                }
                
                $this->transactionService->processImport($transactionData);
            }
            
            DB::commit();
            
            $message = "Đã import thành công: {$itemsImported} sản phẩm vào kho";
            if ($productsCreated > 0) {
                $message .= " (tạo mới {$productsCreated} mã sản phẩm)";
            }
            if ($suppliersCreated > 0) {
                $message .= " (tạo mới {$suppliersCreated} nhà cung cấp)";
            }
            
            return [
                'success' => true, 
                'imported' => $itemsImported,
                'products_created' => $productsCreated,
                'suppliers_created' => $suppliersCreated,
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
