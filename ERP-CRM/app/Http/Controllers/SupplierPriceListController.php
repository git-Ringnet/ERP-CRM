<?php

namespace App\Http\Controllers;

use App\Models\SupplierPriceList;
use App\Models\SupplierPriceListItem;
use App\Models\Supplier;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SupplierPriceListController extends Controller
{
    // Cấu hình mapping mặc định cho từng nhà cung cấp
    private array $supplierPresets = [
        'fortinet' => [
            'name' => 'Fortinet',
            'skipSheets' => ['Cover Sheet', 'Index', 'General Info', 'Changes', 'Dataset', 'Price List Changes'],
            'headerKeywords' => ['sku', 'part number', 'item', 'description', 'price'],
            'columnPatterns' => [
                'sku' => ['SKU', 'Part Number', 'Item', 'Product SKU'],
                'product_name' => ['UNIT', 'Product', 'Product Name', 'Item', 'Identifier'],
                'description' => ['Description', 'Desc', 'Product Description', 'Description #1'],
                'price' => ['Price', 'Unit Price', 'List Price', 'MSRP'],
                'category' => ['Category', 'Product Category', 'Type'],
                'price_1yr' => ['1yr Contract', '1 Year', '1Yr', 'Replace DD by 12', 'Replaces DD by 12'],
                'price_2yr' => ['2yr Contract', '2 Year', '2Yr', 'Replace DD by 24', 'Replaces DD by 24'],
                'price_3yr' => ['3yr Contract', '3 Year', '3Yr', 'Replace DD by 36', 'Replaces DD by 36'],
                'price_4yr' => ['4yr Contract', '4 Year', '4Yr', 'Replace DD by 48', 'Replaces DD by 48'],
                'price_5yr' => ['5yr Contract', '5 Year', '5Yr', 'Replace DD by 60', 'Replaces DD by 60'],
            ],
        ],
        'cisco' => [
            'name' => 'Cisco',
            'skipSheets' => ['Cover', 'Summary', 'Instructions'],
            'headerKeywords' => ['part number', 'description', 'list price'],
            'columnPatterns' => [
                'sku' => ['Part Number', 'SKU', 'Product ID'],
                'product_name' => ['Product Name', 'Product', 'Name'],
                'description' => ['Description', 'Product Description'],
                'price' => ['List Price', 'Price', 'Unit Price'],
                'category' => ['Category', 'Product Family', 'Series'],
            ],
        ],
        'default' => [
            'name' => 'Mặc định (Đa năng)',
            'skipSheets' => ['cover', 'summary', 'instructions', 'notes', 'readme', 'general info', 'change log'],
            'headerKeywords' => [
                'sku', 'code', 'price', 'description', 
                'part number', 'part #', 'part no', 'part_no', 'model', 'product id',
                'list price', 'msrp', 'unit price', 'amount', 'cost',
                'product', 'item', 'name', 'title'
            ],
            'columnPatterns' => [
                'sku' => [
                    'SKU', 'SKU#', 'Part Number', 'Part No', 'Part#', 'PartNo', 'Code', 'Mã', 'Mã SP', 
                    'Product Code', 'Item Number', 'Item#', 'Model', 'Model Number', 'MTM', 'Material', 
                    'Product ID', 'P/N', 'Item Code'
                ],
                'product_name' => [
                    'Product', 'Product Name', 'Name', 'Tên', 'Sản phẩm', 'Description Short', 
                    'Item Name', 'Model Name', 'Title', 'Item Description', 'Short Description', 'Product Title'
                ],
                'description' => [
                    'Description', 'Desc', 'Mô tả', 'Details', 'Product Description', 
                    'Long Description', 'Specification', 'Specs', 'Full Description'
                ],
                'price' => [
                    'Price', 'Giá', 'List Price', 'Unit Price', 'MSRP', 'Base Price', 'Net Price',
                    'USD Price', 'Price USD', 'Unit Cost', 'Extended Price', 'Amount', 'Global Price List', 
                    'GPL', 'Standard Price', 'Cost'
                ],
                'category' => [
                    'Category', 'Danh mục', 'Type', 'Loại', 'Product Type', 'Product Family', 
                    'Product Group', 'Series', 'Product Line', 'Class', 'Classification'
                ],
                'price_1yr' => [
                    '1yr Contract', '1 Year', '1Yr', 'Replace DD by 12', 'Replaces DD by 12', '1 Năm', 
                    '12 Months', '1Y Support', 'Support 1Y'
                ],
                'price_2yr' => [
                    '2yr Contract', '2 Year', '2Yr', 'Replace DD by 24', 'Replaces DD by 24', '2 Năm', 
                    '24 Months', '2Y Support', 'Support 2Y'
                ],
                'price_3yr' => [
                    '3yr Contract', '3 Year', '3Yr', 'Replace DD by 36', 'Replaces DD by 36', '3 Năm', 
                    '36 Months', '3Y Support', 'Support 3Y'
                ],
                'price_4yr' => [
                    '4yr Contract', '4 Year', '4Yr', 'Replace DD by 48', 'Replaces DD by 48', '4 Năm', 
                    '48 Months', '4Y Support', 'Support 4Y'
                ],
                'price_5yr' => [
                    '5yr Contract', '5 Year', '5Yr', 'Replace DD by 60', 'Replaces DD by 60', '5 Năm', 
                    '60 Months', '5Y Support', 'Support 5Y'
                ],
            ],
        ],
    ];

    public function index(Request $request)
    {
        $query = SupplierPriceList::with('supplier', 'createdBy')
            ->withCount('items');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $priceLists = $query->orderBy('created_at', 'desc')->paginate(15);
        $suppliers = Supplier::orderBy('name')->get();

        return view('supplier-price-lists.index', compact('priceLists', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('supplier-price-lists.create', compact('suppliers'));
    }

    public function show(SupplierPriceList $supplierPriceList)
    {
        $supplierPriceList->load('supplier', 'createdBy');

        $items = $supplierPriceList->items()
            ->when(request('search'), function ($q) {
                $search = request('search');
                $q->where(function ($sq) use ($search) {
                    $sq->where('sku', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(request('category'), fn($q) => $q->where('category', request('category')))
            ->orderBy('category')
            ->orderBy('sku')
            ->paginate(50);

        $categories = $supplierPriceList->items()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return view('supplier-price-lists.show', compact('supplierPriceList', 'items', 'categories'));
    }

    public function showImportForm()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('supplier-price-lists.import', compact('suppliers'));
    }

    public function analyzeFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200',
        ]);

        try {
            $file = $request->file('file');
            $tempPath = $file->store('temp/price-imports');
            $fullPath = Storage::path($tempPath);

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);

            $sheets = [];
            foreach ($spreadsheet->getSheetNames() as $index => $sheetName) {
                $worksheet = $spreadsheet->getSheet($index);
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();

                $previewData = [];
                for ($row = 1; $row <= min(10, $highestRow); $row++) {
                    $rowData = [];
                    foreach (range('A', $highestColumn) as $col) {
                        $rowData[] = $worksheet->getCell($col . $row)->getValue();
                    }
                    $previewData[] = $rowData;
                }

                $sheets[] = [
                    'index' => $index,
                    'name' => $sheetName,
                    'rowCount' => $highestRow,
                    'columnCount' => ord($highestColumn) - ord('A') + 1,
                    'preview' => $previewData,
                ];
            }

            return response()->json([
                'success' => true,
                'fileName' => $file->getClientOriginalName(),
                'tempPath' => $tempPath,
                'sheets' => $sheets,
            ]);

        } catch (\Exception $e) {
            Log::error('Error analyzing Excel file: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi đọc file: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function getSheetData(Request $request)
    {
        $request->validate([
            'temp_path' => 'required|string',
            'sheet_index' => 'required|integer',
            'header_row' => 'required|integer|min:1',
        ]);

        try {
            $fullPath = Storage::path($request->temp_path);
            if (!file_exists($fullPath)) {
                return response()->json(['success' => false, 'message' => 'File không tồn tại'], 404);
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getSheet($request->sheet_index);

            $headerRow = $request->header_row;
            $highestColumn = $worksheet->getHighestColumn();
            $highestRow = $worksheet->getHighestRow();

            $headers = [];
            foreach (range('A', $highestColumn) as $col) {
                $value = $worksheet->getCell($col . $headerRow)->getValue();
                $headers[] = [
                    'column' => $col,
                    'index' => ord($col) - ord('A'),
                    'name' => $value ?? '',
                ];
            }

            $previewData = [];
            for ($row = $headerRow + 1; $row <= min($headerRow + 20, $highestRow); $row++) {
                $rowData = [];
                foreach (range('A', $highestColumn) as $col) {
                    $rowData[] = $worksheet->getCell($col . $row)->getValue();
                }
                $previewData[] = $rowData;
            }

            return response()->json([
                'success' => true,
                'headers' => $headers,
                'preview' => $previewData,
                'totalRows' => $highestRow - $headerRow,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi đọc sheet: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function autoDetectMapping(Request $request)
    {
        $request->validate([
            'headers' => 'required|array',
            'supplier_type' => 'nullable|string',
        ]);

        $supplierType = $request->supplier_type ?? 'default';
        $preset = $this->supplierPresets[$supplierType] ?? $this->supplierPresets['default'];

        // Convert headers from [{index: 0, name: 'A'}, ...] to [0 => 'A', ...]
        $fileHeaders = [];
        // Convert headers from [{index: 0, name: 'A'}, ...] to [0 => 'A', ...]
        $fileHeaders = [];
        foreach ($request->input('headers', []) as $header) {
            if (isset($header['index'])) {
                $fileHeaders[$header['index']] = $header['name'] ?? '';
            }
        }

        $mapping = $this->autoDetectMappingFromHeaders($fileHeaders, $preset);

        return response()->json([
            'success' => true,
            'mapping' => $mapping,
        ]);
    }


    public function import(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'temp_path' => 'required|string',
            'currency' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'price_type' => 'required|in:list,partner,cost',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
            'sheets' => 'required|array|min:1',
            'sheets.*.index' => 'required|integer',
            'sheets.*.name' => 'required|string',
            'sheets.*.header_row' => 'required|integer|min:1',
            'import_mode' => 'required|in:create,update,replace',
        ]);

        $fullPath = Storage::path($request->temp_path);
        if (!file_exists($fullPath)) {
            return response()->json(['success' => false, 'message' => 'File không tồn tại'], 404);
        }

        DB::beginTransaction();
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);

            // Lấy supplier để xác định preset
            $supplier = Supplier::find($request->supplier_id);
            $supplierName = strtolower($supplier->name ?? '');
            $supplierType = str_contains($supplierName, 'fortinet') ? 'fortinet' :
                (str_contains($supplierName, 'cisco') ? 'cisco' : 'default');
            $preset = $this->supplierPresets[$supplierType] ?? $this->supplierPresets['default'];

            // Tạo hoặc cập nhật price list
            $priceList = null;
            if ($request->import_mode === 'update' && $request->filled('price_list_id')) {
                $priceList = SupplierPriceList::findOrFail($request->price_list_id);
                $priceList->update([
                    'name' => $request->name,
                    'currency' => $request->currency,
                    'exchange_rate' => $request->exchange_rate,
                    'price_type' => $request->price_type,
                    'effective_date' => $request->effective_date,
                    'expiry_date' => $request->expiry_date,
                ]);
            } else {
                $priceList = SupplierPriceList::create([
                    'code' => SupplierPriceList::generateCode($request->supplier_id),
                    'name' => $request->name,
                    'supplier_id' => $request->supplier_id,
                    'file_name' => basename($request->temp_path),
                    'currency' => $request->currency,
                    'exchange_rate' => $request->exchange_rate,
                    'price_type' => $request->price_type,
                    'effective_date' => $request->effective_date,
                    'expiry_date' => $request->expiry_date,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);
            }

            if ($request->import_mode === 'replace') {
                $priceList->items()->delete();
            }

            $importLog = [
                'imported_at' => now()->toISOString(),
                'sheets' => [],
                'total_items' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];

            foreach ($request->sheets as $sheetConfig) {
                $sheetName = $sheetConfig['name'];

                // Kiểm tra xem sheet có trong danh sách skip không
                $shouldSkip = false;
                foreach ($preset['skipSheets'] as $skipPattern) {
                    if (stripos($sheetName, $skipPattern) !== false) {
                        $shouldSkip = true;
                        break;
                    }
                }

                if ($shouldSkip) {
                    Log::info("Skipping sheet '{$sheetName}' - in skip list");
                    $importLog['sheets'][] = [
                        'name' => $sheetName,
                        'rows_processed' => 0,
                        'items_created' => 0,
                        'items_updated' => 0,
                        'items_skipped' => 0,
                        'skipped_reason' => 'Sheet trong danh sách bỏ qua',
                    ];
                    continue;
                }

                $worksheet = $spreadsheet->getSheet($sheetConfig['index']);
                $configHeaderRow = $sheetConfig['header_row'];
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();

                // Tự động tìm dòng header nếu dòng được chỉ định trống
                $headerRow = $this->findHeaderRow($worksheet, $configHeaderRow, $highestColumn, $preset);

                if ($headerRow === null) {
                    Log::info("Skipping sheet '{$sheetName}' - cannot find header row");
                    $importLog['sheets'][] = [
                        'name' => $sheetName,
                        'rows_processed' => 0,
                        'items_created' => 0,
                        'items_updated' => 0,
                        'items_skipped' => 0,
                        'skipped_reason' => 'Không tìm thấy dòng header',
                    ];
                    continue;
                }

                Log::debug("Sheet '{$sheetName}' - using header row: {$headerRow}");

                // Lấy headers từ file
                $fileHeaders = [];
                foreach (range('A', $highestColumn) as $col) {
                    $value = $worksheet->getCell($col . $headerRow)->getValue();
                    $fileHeaders[ord($col) - ord('A')] = strtolower(trim($value ?? ''));
                }

                Log::debug("Processing sheet: {$sheetConfig['name']}, headers:", $fileHeaders);

                // Tự động detect mapping nếu không có mapping được gửi lên hoặc mapping rỗng
                $mapping = $sheetConfig['mapping'] ?? [];

                // Luôn chạy auto-detect để tìm các cột có thể bị thiếu trong mapping gửi lên (ví dụ: các cột contract)
                $detectedMapping = $this->autoDetectMappingFromHeaders($fileHeaders, $preset);

                // Nếu mapping rỗng (không gửi từ UI), dùng full detected
                if (empty($mapping) || !isset($mapping['sku']) || $mapping['sku'] === '') {
                    $mapping = $detectedMapping;
                } else {
                    // Nếu đã có mapping từ UI, fill thêm các cột thiếu từ detected
                    foreach ($detectedMapping as $field => $index) {
                        if (!isset($mapping[$field]) || $mapping[$field] === '') {
                            $mapping[$field] = $index;
                            Log::debug("Added missing field '{$field}' to mapping from auto-detect (column {$index})");
                        }
                    }
                }

                // Kiểm tra cấu trúc sheet có hợp lệ không
                if (!$this->isValidSheetStructure($mapping, $fileHeaders)) {
                    Log::info("Skipping sheet '{$sheetConfig['name']}' - invalid structure (no valid SKU column)");
                    $importLog['sheets'][] = [
                        'name' => $sheetConfig['name'],
                        'rows_processed' => 0,
                        'items_created' => 0,
                        'items_updated' => 0,
                        'items_skipped' => 0,
                        'skipped_reason' => 'Không tìm thấy cột SKU hợp lệ',
                    ];
                    continue;
                }

                $sheetLog = [
                    'name' => $sheetConfig['name'],
                    'rows_processed' => 0,
                    'items_created' => 0,
                    'items_updated' => 0,
                    'items_skipped' => 0,
                ];

                for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                    $rowData = [];
                    foreach (range('A', $highestColumn) as $col) {
                        $cellValue = $worksheet->getCell($col . $row)->getValue();
                        // Xử lý formula - chỉ lấy giá trị hiển thị
                        if (is_string($cellValue) && str_starts_with($cellValue, '=')) {
                            $cellValue = $worksheet->getCell($col . $row)->getCalculatedValue();
                        }
                        $rowData[ord($col) - ord('A')] = $cellValue;
                    }

                    // Lấy giá trị theo mapping
                    $sku = isset($mapping['sku']) && $mapping['sku'] !== '' ? trim((string) ($rowData[$mapping['sku']] ?? '')) : '';
                    $productName = isset($mapping['product_name']) && $mapping['product_name'] !== '' ? trim((string) ($rowData[$mapping['product_name']] ?? '')) : '';

                    // Bỏ qua dòng không có SKU
                    if (empty($sku)) {
                        $sheetLog['items_skipped']++;
                        continue;
                    }

                    // Lọc bỏ các dòng không hợp lệ
                    if (!$this->isValidSku($sku)) {
                        $sheetLog['items_skipped']++;
                        continue;
                    }

                    $itemData = [
                        'supplier_price_list_id' => $priceList->id,
                        'sku' => mb_substr(trim((string) $sku), 0, 255),
                        'product_name' => mb_substr(trim((string) ($productName ?: $sku)), 0, 65000),
                        'description' => isset($mapping['description']) && $mapping['description'] !== ''
                            ? mb_substr(trim((string) ($rowData[$mapping['description']] ?? '')), 0, 65000)
                            : null,
                        'category' => isset($mapping['category']) && $mapping['category'] !== ''
                            ? mb_substr(trim((string) ($rowData[$mapping['category']] ?? '')), 0, 255)
                            : null,
                        'list_price' => isset($mapping['price']) && $mapping['price'] !== ''
                            ? $this->parsePrice($rowData[$mapping['price']] ?? null)
                            : null,
                        'price_1yr' => isset($mapping['price_1yr']) && $mapping['price_1yr'] !== ''
                            ? $this->parsePrice($rowData[$mapping['price_1yr']] ?? null)
                            : null,
                        'price_2yr' => isset($mapping['price_2yr']) && $mapping['price_2yr'] !== ''
                            ? $this->parsePrice($rowData[$mapping['price_2yr']] ?? null)
                            : null,
                        'price_3yr' => isset($mapping['price_3yr']) && $mapping['price_3yr'] !== ''
                            ? $this->parsePrice($rowData[$mapping['price_3yr']] ?? null)
                            : null,
                        'price_4yr' => isset($mapping['price_4yr']) && $mapping['price_4yr'] !== ''
                            ? $this->parsePrice($rowData[$mapping['price_4yr']] ?? null)
                            : null,
                        'price_5yr' => isset($mapping['price_5yr']) && $mapping['price_5yr'] !== ''
                            ? $this->parsePrice($rowData[$mapping['price_5yr']] ?? null)
                            : null,
                        'source_sheet' => mb_substr($sheetConfig['name'], 0, 255),
                    ];

                    // Kiểm tra xem có giá nào không
                    $hasPrice = $itemData['list_price'] || $itemData['price_1yr'] || $itemData['price_2yr'] || $itemData['price_3yr'] || $itemData['price_4yr'] || $itemData['price_5yr'];
                    if (!$hasPrice) {
                        $sheetLog['items_skipped']++;
                        continue;
                    }

                    if ($request->import_mode === 'update') {
                        $existing = SupplierPriceListItem::where('supplier_price_list_id', $priceList->id)
                            ->where('sku', $sku)
                            ->first();

                        if ($existing) {
                            $existing->update($itemData);
                            $sheetLog['items_updated']++;
                        } else {
                            SupplierPriceListItem::create($itemData);
                            $sheetLog['items_created']++;
                        }
                    } else {
                        SupplierPriceListItem::create($itemData);
                        $sheetLog['items_created']++;
                    }

                    $sheetLog['rows_processed']++;
                }

                $importLog['sheets'][] = $sheetLog;
                $importLog['total_items'] += $sheetLog['rows_processed'];
                $importLog['created'] += $sheetLog['items_created'];
                $importLog['updated'] += $sheetLog['items_updated'];
                $importLog['skipped'] += $sheetLog['items_skipped'];
            }

            $priceList->update(['import_log' => $importLog]);

            // Xóa file tạm
            Storage::delete($request->temp_path);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Import thành công!',
                'price_list_id' => $priceList->id,
                'import_log' => $importLog,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing price list: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tìm dòng header trong sheet
     * Quét từ dòng 1-15 để tìm dòng có chứa các từ khóa header
     */
    private function findHeaderRow($worksheet, int $suggestedRow, string $highestColumn, array $preset): ?int
    {
        // Mở rộng keywords để match nhiều format hơn (bao gồm Fortinet)
        $headerKeywords = [
            'sku',
            'part number',
            'part#',
            'part #',
            'partnumber',
            'part_no',
            'part no',
            'model',
            'code',
            'product',
            'description',
            'desc',
            'price',
            'usd',
            'msrp',
            'amount',
            'cost',
            'item',
            'unit',
            'contract',
            'forticare',
            'list',
            'p/n'
        ];

        $bestRow = null;
        $maxMatches = 0;

        // Quét 50 dòng đầu để tìm dòng header tốt nhất
        // Fortinet có thể có nhiều dòng text ở trên
        $rowsToCheck = range(1, 50);
        if ($suggestedRow > 0 && !in_array($suggestedRow, $rowsToCheck)) {
            array_unshift($rowsToCheck, $suggestedRow);
        }

        foreach ($rowsToCheck as $row) {
            $rowValues = [];
            $nonEmptyCount = 0;

            foreach (range('A', $highestColumn) as $col) {
                // Đọc cell, clean ký tự xuống dòng
                $cellVal = $worksheet->getCell($col . $row)->getValue();
                // Clean basic
                $val = strtolower(trim((string) $cellVal));
                $val = preg_replace('/_x000d_|\r\n|\r|\n/i', ' ', $val);
                $val = preg_replace('/\s+/', ' ', $val);
                $val = trim($val);

                $rowValues[] = $val;
                if (!empty($val)) {
                    $nonEmptyCount++;
                }
            }

            // Cần ít nhất 2 cột có giá trị
            if ($nonEmptyCount < 2) {
                continue;
            }

            // Kiểm tra xem có chứa từ khóa header không
            $keywordMatches = 0;
            foreach ($rowValues as $value) {
                // Dùng logic clean aggressive để match keyword (hỗ trợ Unicode)
                $cleanValue = preg_replace('/[^a-z0-9\p{L}]/u', '', mb_strtolower($value));

                foreach ($headerKeywords as $keyword) {
                    $cleanKeyword = preg_replace('/[^a-z0-9\p{L}]/u', '', mb_strtolower($keyword));
                    if (str_contains($cleanValue, $cleanKeyword)) {
                        $keywordMatches++;
                        // Mỗi cell chỉ count 1 keyword để tránh duplicate match
                        break;
                    }
                }
            }

            // Nếu dòng này có nhiều match hơn dòng trước -> update best row
            // Ưu tiên dòng có nhiều match nhất
            if ($keywordMatches > $maxMatches) {
                $maxMatches = $keywordMatches;
                $bestRow = $row;
            }
        }

        // Nếu tìm thấy dòng có >= 2 match -> Return
        if ($maxMatches >= 2) {
            Log::debug("Found best header at row {$bestRow} with {$maxMatches} matches");
            return $bestRow;
        }

        // Log thêm thông tin debug khi không tìm thấy header
        Log::debug("Could not find header row - checked rows 1-50", [
            'sheetName' => $worksheet->getTitle(),
            'highestColumn' => $highestColumn
        ]);

        return null;
    }

    /**
     * Tự động detect mapping từ headers của file
     */
    private function autoDetectMappingFromHeaders(array $fileHeaders, array $preset): array
    {
        $mapping = [];

        // Log headers để debug
        Log::debug('Auto-detect mapping for headers:', $fileHeaders);

        foreach ($preset['columnPatterns'] as $field => $patterns) {
            foreach ($patterns as $pattern) {
                $patternLower = strtolower($pattern);
                foreach ($fileHeaders as $index => $headerName) {
                    // Bỏ qua header trống
                    if (empty($headerName))
                        continue;

                    // Normalize header: Chỉ giữ lại chữ cái và số để so sánh (Aggressive cleaning hỗ trợ Unicode)
                    $cleanHeader = preg_replace('/[^a-z0-9\p{L}]/u', '', mb_strtolower($headerName));
                    $cleanPattern = preg_replace('/[^a-z0-9\p{L}]/u', '', mb_strtolower($pattern));

                    // Match chính xác hoặc header chứa pattern
                    $isMatch = $cleanHeader === $cleanPattern ||
                        str_contains($cleanHeader, $cleanPattern);

                    // LOG DEBUG CHI TIẾT
                    if ($field === 'price_1yr') {
                        Log::debug("Checking price_1yr: Header='{$cleanHeader}' vs Pattern='{$cleanPattern}' -> Match=" . ($isMatch ? 'YES' : 'NO'));
                    }

                    // Enhancement: Specific exclusion for 'price' field to avoid matching Contract columns
                    if ($field === 'price' && $isMatch) {
                        $rawHeader = strtolower($headerName);
                        if (str_contains($rawHeader, 'year') || str_contains($rawHeader, 'yr') || str_contains($rawHeader, 'contract')) {
                            $isMatch = false;
                        }
                    }

                    if ($isMatch) {
                        $mapping[$field] = $index;
                        Log::debug("Mapped field '{$field}' to column {$index} (match: '{$cleanPattern}' inside '{$cleanHeader}')");
                        break 2;
                    }
                }
            }
        }

        Log::debug('Final mapping:', $mapping);

        return $mapping;
    }

    /**
     * Kiểm tra sheet có cấu trúc hợp lệ để import không
     */
    private function isValidSheetStructure(array $mapping, array $fileHeaders): bool
    {
        // Phải có cột SKU
        if (!isset($mapping['sku'])) {
            return false;
        }

        // Header của cột SKU không được là date, number, hoặc các từ không liên quan
        $skuHeader = $fileHeaders[$mapping['sku']] ?? '';
        $invalidSkuHeaders = ['date', 'ngày', 'added', 'created', 'updated', 'time', 'id', '#'];

        foreach ($invalidSkuHeaders as $invalid) {
            if (str_contains($skuHeader, $invalid)) {
                return false;
            }
        }

        return true;
    }

    private function parsePrice($value): ?float
    {
        if ($value === null || $value === '')
            return null;
        if (is_numeric($value))
            return (float) $value;

        // Remove currency symbols and commas
        $cleaned = preg_replace('/[^0-9.-]/', '', (string) $value);
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Kiểm tra SKU có hợp lệ không
     * Lọc bỏ các dòng ghi chú, HYPERLINK, dòng trống, v.v.
     */
    private function isValidSku(string $sku): bool
    {
        // Bỏ qua nếu quá ngắn
        if (strlen($sku) < 3) {
            return false;
        }

        // Bỏ qua nếu bắt đầu bằng các ký tự đặc biệt
        if (preg_match('/^[=\(\*\-\+\#\@]/', $sku)) {
            return false;
        }

        // Bỏ qua nếu chứa HYPERLINK hoặc formula
        if (stripos($sku, 'HYPERLINK') !== false || stripos($sku, '=HYPERLINK') !== false) {
            return false;
        }

        // Bỏ qua các dòng ghi chú thường gặp
        $skipPatterns = [
            'cover sheet',
            'please refer',
            'contact fortinet',
            'renewals team',
            'upgrade quotations',
            'forticare contracts',
            'most fortigate',
            'include 10 vdom',
            'respective data',
            'note:',
            'notes:',
            '(*)',
            'see ',
            'refer to',
        ];

        $skuLower = strtolower($sku);
        foreach ($skipPatterns as $pattern) {
            if (str_contains($skuLower, $pattern)) {
                return false;
            }
        }

        // Bỏ qua nếu có quá nhiều dấu cách (có thể là câu mô tả)
        if (substr_count($sku, ' ') > 10) {
            return false;
        }

        // Bỏ qua nếu bắt đầu bằng số và có dấu ngoặc (như "(1) contact...")
        if (preg_match('/^\(\d+\)/', $sku)) {
            return false;
        }

        return true;
    }

    public function destroy(SupplierPriceList $supplierPriceList)
    {
        DB::beginTransaction();
        try {
            $supplierPriceList->items()->delete();
            $supplierPriceList->delete();
            DB::commit();

            return redirect()->route('supplier-price-lists.index')
                ->with('success', 'Đã xóa bảng giá nhà cung cấp.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function toggle(SupplierPriceList $supplierPriceList)
    {
        $supplierPriceList->update(['is_active' => !$supplierPriceList->is_active]);
        $status = $supplierPriceList->is_active ? 'kích hoạt' : 'tắt';
        return back()->with('success', "Đã {$status} bảng giá.");
    }

    public function searchItems(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        $query = SupplierPriceListItem::with('priceList.supplier')
            ->whereHas('priceList', fn($q) => $q->where('is_active', true));

        if ($request->filled('supplier_id')) {
            $query->whereHas('priceList', fn($q) => $q->where('supplier_id', $request->supplier_id));
        }

        $search = $request->q;
        $items = $query->where(function ($q) use ($search) {
            $q->where('sku', 'like', "%{$search}%")
                ->orWhere('product_name', 'like', "%{$search}%");
        })
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'items' => $items->map(fn($item) => [
                'id' => $item->id,
                'sku' => $item->sku,
                'product_name' => $item->product_name,
                'description' => $item->description,
                'list_price' => $item->list_price,
                'price_1yr' => $item->price_1yr,
                'price_3yr' => $item->price_3yr,
                'price_5yr' => $item->price_5yr,
                'currency' => $item->priceList->currency,
                'supplier' => $item->priceList->supplier->name,
            ]),
        ]);
    }

    /**
     * Apply prices from a price list to ProductItems in inventory
     * Matches by SKU and updates cost_usd
     */
    private function cleanSku($sku)
    {
        // Remove all non-printable characters and extra whitespace
        return preg_replace('/[^\x20-\x7E]/', '', trim($sku));
    }

    public function applyPrices(Request $request, SupplierPriceList $supplierPriceList)
    {
        $request->validate([
            'price_field' => 'required|in:list_price,price_1yr,price_2yr,price_3yr,price_4yr,price_5yr',
            'update_mode' => 'required|in:all,empty_only',
        ]);

        DB::beginTransaction();
        try {
            $priceField = $request->price_field;
            $updateMode = $request->update_mode;
            $exchangeRate = $supplierPriceList->exchange_rate ?: 1;

            // Get all items from this price list that have the selected price field
            $priceListItems = $supplierPriceList->items()
                ->whereNotNull($priceField)
                ->where($priceField, '>', 0)
                ->get();

            $updated = 0;
            $skipped = 0;
            $notFound = 0;

            foreach ($priceListItems as $priceItem) {
                $cleanSku = $this->cleanSku($priceItem->sku);
                if (empty($cleanSku))
                    continue;

                // Match Products by code (exact or LIKE)
                // Note: The user specified that price list SKUs match Product codes, not individual Item serials.
                $products = \App\Models\Product::where('code', $cleanSku)
                    ->orWhere('code', 'LIKE', trim($priceItem->sku))
                    ->with('items')
                    ->get();

                if ($products->isEmpty()) {
                    $notFound++;
                    continue;
                }

                foreach ($products as $product) {
                    // Update all items of this product
                    foreach ($product->items as $item) {
                        if ($updateMode === 'empty_only' && $item->cost_usd > 0) {
                            continue;
                        }

                        $item->update([
                            'cost_usd' => $priceItem->$priceField,
                        ]);
                        $updated++;
                    }
                }
            }

            DB::commit();

            $message = "Đã cập nhật giá cho {$updated} sản phẩm trong kho.";
            if ($notFound > 0) {
                $message .= " {$notFound} SKU không tìm thấy trong kho.";
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'updated' => $updated,
                    'not_found' => $notFound,
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error applying prices: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi áp dụng giá: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Lỗi áp dụng giá: ' . $e->getMessage());
        }
    }

    /**
     * Preview which items will be affected by applying prices
     */
    public function previewApplyPrices(Request $request, SupplierPriceList $supplierPriceList)
    {
        $priceField = $request->get('price_field', 'list_price');
        $updateMode = $request->get('update_mode', 'all');

        // Get price list items with the selected price
        $priceListItems = $supplierPriceList->items()
            ->whereNotNull($priceField)
            ->where($priceField, '>', 0)
            ->get(['sku', 'product_name', $priceField]);

        $preview = [];
        $matchCount = 0;

        foreach ($priceListItems as $priceItem) {
            $cleanSku = $this->cleanSku($priceItem->sku);
            if (empty($cleanSku))
                continue;

            // Match Products by code (exact or LIKE)
            $products = \App\Models\Product::where('code', $cleanSku)
                ->orWhere('code', 'LIKE', trim($priceItem->sku))
                ->with(['items.warehouse', 'items'])
                ->get();

            if ($products->isEmpty()) {
                continue;
            }

            foreach ($products as $product) {
                foreach ($product->items as $item) {
                    if ($updateMode === 'empty_only' && $item->cost_usd > 0) {
                        continue;
                    }

                    $matchCount++;

                    // Limit preview to 100 items
                    if (count($preview) < 100) {
                        $preview[] = [
                            'sku' => $item->sku, // Display Item SKU/Serial
                            'product_name' => $product->name,
                            'warehouse' => $item->warehouse->name ?? 'N/A',
                            'current_cost' => $item->cost_usd,
                            'new_cost' => $priceItem->$priceField,
                            'quantity' => 1 // Items are individual units
                        ];
                    }
                }
            }
        }



        return response()->json([
            'success' => true,
            'total_price_items' => $priceListItems->count(),
            'match_count' => $matchCount,
            'preview' => $preview,
            'exchange_rate' => $supplierPriceList->exchange_rate,
            'currency' => $supplierPriceList->currency,
        ]);
    }

    /**
     * Update pricing configuration for a price list
     */
    public function updatePricingConfig(Request $request, SupplierPriceList $supplierPriceList)
    {
        $request->validate([
            'supplier_discount_percent' => 'nullable|numeric|min:0|max:100',
            'margin_percent' => 'nullable|numeric|min:0',
            'shipping_percent' => 'nullable|numeric|min:0',
            'shipping_fixed' => 'nullable|numeric|min:0',
            'other_fees' => 'nullable|numeric|min:0',
        ]);

        try {
            $supplierPriceList->update([
                'supplier_discount_percent' => $request->input('supplier_discount_percent', 0),
                'margin_percent' => $request->input('margin_percent', 0),
                'shipping_percent' => $request->input('shipping_percent', 0),
                'shipping_fixed' => $request->input('shipping_fixed', 0),
                'other_fees' => $request->input('other_fees', 0),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã lưu cấu hình giá thành công!',
                ]);
            }

            return back()->with('success', 'Đã lưu cấu hình giá thành công!');

        } catch (\Exception $e) {
            Log::error('Error updating pricing config: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi lưu cấu hình: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Lỗi lưu cấu hình: ' . $e->getMessage());
        }
    }
}
