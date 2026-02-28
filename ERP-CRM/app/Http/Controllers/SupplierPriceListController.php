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
use Smalot\PdfParser\Parser;

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
                'price_1yr' => ['1yr Contract', '1 yr Contract', '1 Year', '1Yr', 'Replace DD by 12', 'Replaces DD by 12'],
                'price_2yr' => ['2yr Contract', '2 yr Contract', '2 Year', '2Yr', 'Replace DD by 24', 'Replaces DD by 24'],
                'price_3yr' => ['3yr Contract', '3 yr Contract', '3 Year', '3Yr', 'Replace DD by 36', 'Replaces DD by 36'],
                'price_4yr' => ['4yr Contract', '4 yr Contract', '4 Year', '4Yr', 'Replace DD by 48', 'Replaces DD by 48'],
                'price_5yr' => ['5yr Contract', '5 yr Contract', '5 Year', '5Yr', 'Replace DD by 60', 'Replaces DD by 60'],
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
        'qnap' => [
            'name' => 'QNAP',
            'skipSheets' => ['cover', 'index', 'summary'],
            'headerKeywords' => ['p/n', 'segment', 'msrp', 'purchase price', 'suggested dealer price'],
            'columnPatterns' => [
                'sku' => ['P/N', 'Part Number', 'Part No', 'Model', 'SKU'],
                'product_name' => ['Description', 'Product', 'Product Name', 'Model'],
                'description' => ['Description', 'Descriptions', 'Full Description'],
                'price' => ['MSRP without VAT', 'MSRP', 'List Price', 'Purchase Price', 'Suggested Dealer Price'],
                'category' => ['Segment', 'Category', 'Product Family', 'Series', 'HDD Type'],
            ],
        ],
        'sonicwall' => [
            'name' => 'SonicWall',
            'skipSheets' => ['links', 'cover', 'instructions'],
            'headerKeywords' => ['sku', 'isrp', 'disti', 'msrp', 'description', 'dealer price'],
            'columnPatterns' => [
                'sku' => ['SonicWALL SKU', 'SKU', 'Part Number', 'P/N'],
                'product_name' => ['Description', 'Product Description', 'SonicWALL Product Description'],
                'description' => ['Description', 'Full Description'],
                'price' => ['NEW ISRP', 'NEW DISTI', 'MSRP (USD)', 'Distributor Price (USD)', 'Reseller Price (USD)', 'MSRP', 'List Price'],
                'category' => ['Category', 'Product Family'],
            ],
        ],
        'default' => [
            'name' => 'Mặc định (Đa năng)',
            'skipSheets' => ['cover', 'summary', 'instructions', 'notes', 'readme', 'general info', 'change log'],
            'headerKeywords' => [
                'sku', 'code', 'price', 'description', 
                'part number', 'part #', 'part no', 'part_no', 'model', 'product id', 'p/n',
                'list price', 'msrp', 'unit price', 'amount', 'cost',
                'product', 'item', 'name', 'title', 'segment'
            ],
            'columnPatterns' => [
                'sku' => [
                    'P/N', 'Part Number', 'Part No', 'Part#', 'PartNo', 'SKU', 'SKU#', 'Product Code', 
                    'Item Number', 'Item#', 'Model', 'Model Number', 'MTM', 'Material', 
                    'Product ID', 'Item Code', 'Code', 'Mã', 'Mã SP',
                    'Marketing No.', 'Marketing No', 'Part No.'
                ],
                'product_name' => [
                    'Product', 'Product Name', 'Name', 'Tên', 'Sản phẩm', 'Description Short', 
                    'Item Name', 'Model Name', 'Title', 'Item Description', 'Short Description', 'Product Title',
                    'Description', 'Descriptions'
                ],
                'description' => [
                    'Description', 'Desc', 'Mô tả', 'Details', 'Product Description', 
                    'Long Description', 'Specification', 'Specs', 'Full Description', 'Descriptions'
                ],
                'price' => [
                    'Price', 'Giá', 'List Price', 'Unit Price', 'MSRP', 'Base Price', 'Net Price',
                    'USD Price', 'Price USD', 'Unit Cost', 'Extended Price', 'Amount', 'Global Price List', 
                    'GPL', 'Standard Price', 'Cost', 'List Price ($US)', 'List Price (USD)',
                    'MSRP without VAT', 'Purchase Price', 'Suggested Dealer Price'
                ],
                'category' => [
                    'Category', 'Danh mục', 'Type', 'Loại', 'Product Type', 'Product Family', 
                    'Product Group', 'Series', 'Product Line', 'Class', 'Classification',
                    'Segment', 'HDD Type'
                ],
                'price_1yr' => [
                    '1yr Contract', '1 Year', '1Yr', 'Replace DD by 12', 'Replaces DD by 12', '1 Năm', 
                    '12 Months', '1Y Support', 'Support 1Y', 'Gold List Price', 'Gold Price'
                ],
                'price_2yr' => [
                    '2yr Contract', '2 Year', '2Yr', 'Replace DD by 24', 'Replaces DD by 24', '2 Năm', 
                    '24 Months', '2Y Support', 'Support 2Y', 'Silver List Price', 'Silver Price'
                ],
                'price_3yr' => [
                    '3yr Contract', '3 Year', '3Yr', 'Replace DD by 36', 'Replaces DD by 36', '3 Năm', 
                    '36 Months', '3Y Support', 'Support 3Y', 'Bronze List Price', 'Bronze Price'
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

    public function edit(SupplierPriceList $supplierPriceList)
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('supplier-price-lists.edit', compact('supplierPriceList', 'suppliers'));
    }

    public function update(Request $request, SupplierPriceList $supplierPriceList)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'supplier_id' => 'required|exists:suppliers,id',
            'currency' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'price_type' => 'required|in:list,partner,cost',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
        ]);

        $supplierPriceList->update([
            'name' => $request->name,
            'supplier_id' => $request->supplier_id,
            'currency' => $request->currency,
            'exchange_rate' => $request->exchange_rate,
            'price_type' => $request->price_type,
            'effective_date' => $request->effective_date,
            'expiry_date' => $request->expiry_date,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('supplier-price-lists.index')
            ->with('success', 'Cập nhật bảng giá thành công.');
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
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('source_sheet', 'like', "%{$search}%");
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

        // Xác định các cột động dựa trên custom_columns
        $priceColumns = [];
        $standardFields = ['list_price', 'price_1yr', 'price_2yr', 'price_3yr', 'price_4yr', 'price_5yr'];

        if ($supplierPriceList->custom_columns && is_array($supplierPriceList->custom_columns)) {
            foreach ($supplierPriceList->custom_columns as $col) {
                // Check if this key is actually a standard field
                $isStandard = in_array($col['key'], $standardFields);
                
                $priceColumns[] = [
                    'key' => $col['key'],
                    'label' => $col['label'],
                    'is_custom' => !$isStandard,
                    'type' => $col['type'] ?? (str_starts_with($col['key'], 'custom_') ? 'price' : 'text')
                ];
            }
        } else {
            // Fallback: kiểm tra các cột cố định có dữ liệu không
            $sampleItem = $supplierPriceList->items()->first();
            
            if ($sampleItem) {
                foreach ($standardFields as $col) {
                    if ($sampleItem->$col !== null) {
                        $label = match($col) {
                            'list_price' => 'Giá gốc',
                            'price_1yr' => '1yr',
                            'price_2yr' => '2yr',
                            'price_3yr' => '3yr',
                            'price_4yr' => '4yr',
                            'price_5yr' => '5yr',
                            default => $col
                        };
                        $priceColumns[] = [
                            'key' => $col,
                            'label' => $label,
                            'is_custom' => false
                        ];
                    }
                }
            }
        }

        return view('supplier-price-lists.show', compact('supplierPriceList', 'items', 'categories', 'priceColumns'));
    }

    public function showImportForm()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('supplier-price-lists.import', compact('suppliers'));
    }

    public function analyzeFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,pdf|max:51200',
        ]);

        try {
            $file = $request->file('file');
            $tempPath = $file->store('temp/price-imports');
            $fullPath = Storage::path($tempPath);

            $extension = strtolower($file->getClientOriginalExtension());
            $sheets = [];

            if ($extension === 'pdf') {
                $sheets = $this->parsePdf($fullPath);
            } else {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fullPath);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($fullPath);

                foreach ($spreadsheet->getSheetNames() as $index => $sheetName) {
                    $worksheet = $spreadsheet->getSheet($index);
                    $highestRow = $worksheet->getHighestRow();
                    $highestColumn = $worksheet->getHighestColumn();

                    $previewData = [];
                    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                    $maxColToPreview = min($highestColIndex, 50); // Mở rộng xem trước
                    
                    for ($row = 1; $row <= min(10, $highestRow); $row++) {
                        $rowData = [];
                        for ($colIdx = 1; $colIdx <= $maxColToPreview; $colIdx++) {
                            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                            $rowData[] = $worksheet->getCell($col . $row)->getValue();
                        }
                        $previewData[] = $rowData;
                    }

                    $sheets[] = [
                        'index' => $index,
                        'name' => $sheetName,
                        'rowCount' => $highestRow,
                        'columnCount' => $maxColToPreview,
                        'preview' => $previewData,
                    ];
                }
            }

            // Simple currency detection based on preview data
            $vndScore = 0;
            $usdScore = 0;
            foreach ($sheets as $sheet) {
                foreach ($sheet['preview'] as $row) {
                    foreach ($row as $cell) {
                        if (is_string($cell)) {
                            $lower = strtolower($cell);
                            if (str_contains($lower, 'vnd') || str_contains($lower, 'vnđ') || str_contains($lower, 'đ')) {
                                $vndScore += 10;
                            }
                            if (str_contains($lower, 'usd') || str_contains($cell, '$')) {
                                $usdScore += 10;
                            }
                        } elseif (is_numeric($cell)) {
                            if ($cell > 100000) $vndScore++; // Likely VND
                            if ($cell > 0 && $cell < 5000) $usdScore++; // Likely USD
                        }
                    }
                }
            }
            
            // Override with strict hint if any sheet has it
            foreach ($sheets as $sheet) {
                if (isset($sheet['currency_hint']) && $sheet['currency_hint'] === 'VND') {
                    $vndScore += 1000;
                }
                if (isset($sheet['currency_hint']) && $sheet['currency_hint'] === 'USD') {
                    $usdScore += 1000;
                }
            }
            
            $suggestedCurrency = ($vndScore > $usdScore) ? 'VND' : 'USD';
            // Nếu là VND thì mẫu số chia là 25000 để ra USD. Nếu là USD thì chia 1.
            $suggestedExchangeRate = ($suggestedCurrency === 'VND') ? 1 : 25000;

            return response()->json([
                'success' => true,
                'fileName' => $file->getClientOriginalName(),
                'tempPath' => $tempPath,
                'sheets' => $sheets,
                'suggestedCurrency' => $suggestedCurrency,
                'suggestedExchangeRate' => $suggestedExchangeRate,
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
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            $maxColToProcess = min($highestColIndex, 100); // Giới hạn tối đa 100 cột
            
            // Row-level decision: is headerRow+1 a header continuation or data?
            $isMultiRowHeader = $this->isNextRowHeaderContinuation($worksheet, $headerRow, $maxColToProcess, $highestRow);
            
            for ($colIdx = 1; $colIdx <= $maxColToProcess; $colIdx++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $value = $worksheet->getCell($col . $headerRow)->getValue();
                $trimVal = trim($value ?? '');
                
                // Multi-row header: combine with next row ONLY if row-level check confirmed it
                if ($isMultiRowHeader && $headerRow < $highestRow) {
                    $nextRowVal = $worksheet->getCell($col . ($headerRow + 1))->getValue();
                    $nextVal = trim($nextRowVal ?? '');
                    
                    if ($nextVal !== '') {
                        $trimVal = ($trimVal !== '') ? $trimVal . ' ' . $nextVal : $nextVal;
                    }
                }
                
                // Clean newlines in header values
                $trimVal = preg_replace('/[\r\n]+/', ' ', $trimVal);
                $trimVal = preg_replace('/\s+/', ' ', $trimVal);
                $trimVal = trim($trimVal);
                
                // Chỉ thêm các cột có header không rỗng
                if ($trimVal !== '') {
                    $headers[] = [
                        'column' => $col,
                        'index' => $colIdx - 1,
                        'name' => $trimVal,
                    ];
                }
            }

            $previewData = [];
            for ($row = $headerRow + 1; $row <= min($headerRow + 20, $highestRow); $row++) {
                $rowData = [];
                for ($colIdx = 1; $colIdx <= $maxColToProcess; $colIdx++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
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

    private function getCellValue(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, string $col, int $row)
    {
        $cell = $worksheet->getCell($col . $row);
        
        // Handle merged cells (only when NOT in ReadDataOnly mode)
        if ($cell->isInMergeRange()) {
            $range = $cell->getMergeRange();
            if ($range) {
                $firstCell = explode(':', $range)[0];
                $cell = $worksheet->getCell($firstCell);
            }
        }
        
        $value = $cell->getValue();
        
        // Handle RichText objects (e.g. multi-line headers in QNAP format)
        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $plainText = '';
            foreach ($value->getRichTextElements() as $element) {
                $plainText .= $element->getText();
            }
            return $plainText;
        }
        
        // Handle formulas
        if (is_string($value) && str_starts_with($value, '=')) {
            try {
                return $cell->getCalculatedValue();
            } catch (\Exception $e) {
                return $value;
            }
        }
        
        return $value;
    }

    public function autoDetectMapping(Request $request)
    {
        $request->validate([
            'headers' => 'required|array',
            'supplier_type' => 'nullable|string',
            'temp_path' => 'nullable|string',
            'sheet_index' => 'nullable|integer',
            'header_row' => 'nullable|integer',
        ]);

        $supplierType = $request->supplier_type ?? 'default';
        $preset = $this->supplierPresets[$supplierType] ?? $this->supplierPresets['default'];

        // Convert headers from [{index: 0, name: 'A'}, ...] to [0 => 'A', ...]
        $fileHeaders = [];
        foreach ($request->input('headers', []) as $header) {
            if (isset($header['index'])) {
                $fileHeaders[$header['index']] = $header['name'] ?? '';
            }
        }

        // Read sample data for verification
        $rowSamples = [];
        if ($request->filled('temp_path') && $request->filled('sheet_index')) {
            try {
                $fullPath = Storage::path($request->temp_path);
                if (file_exists($fullPath)) {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fullPath);
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($fullPath);
                    $worksheet = $spreadsheet->getSheet($request->sheet_index);
                    $startRow = ($request->header_row ?? 1) + 1;
                    $highestRow = $worksheet->getHighestRow();
                    
                    for ($r = $startRow; $r < min($startRow + 20, $highestRow); $r++) {
                        $rowVals = [];
                        foreach ($fileHeaders as $colIdx => $headerName) {
                            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                            $val = $worksheet->getCell($col . $r)->getValue();
                            $rowVals[$colIdx] = $val;
                        }
                        $rowSamples[] = $rowVals;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Auto-detect data read failed: ' . $e->getMessage());
            }
        }

        $mapping = $this->autoDetectMappingFromHeaders($fileHeaders, $preset, $rowSamples);

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
            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if ($extension === 'pdf') {
                return $this->importPdf($request, $fullPath);
            }

            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fullPath);
            // DO NOT setReadDataOnly(true) during auto-detection as many files (like QNAP) 
            // use merged cells (vertical) for headers.
            $spreadsheet = $reader->load($fullPath);

            // Lấy supplier để xác định preset
            $supplier = Supplier::find($request->supplier_id);
            $supplierName = strtolower($supplier->name ?? '');
            $supplierType = str_contains($supplierName, 'fortinet') ? 'fortinet' :
                (str_contains($supplierName, 'cisco') ? 'cisco' : 
                (str_contains($supplierName, 'qnap') ? 'qnap' : 
                (str_contains($supplierName, 'zyxel') ? 'zyxel' : 
                ((str_contains($supplierName, 'sonicwall') || str_contains($supplierName, 'dell')) ? 'sonicwall' : 'default'))));
            $preset = $this->supplierPresets[$supplierType] ?? $this->supplierPresets['default'];

            // Tự động phát hiện sheets và mapping nếu cần
            $sheets = $request->sheets;
            if (empty($sheets) || $this->needsAutoMapping($sheets)) {
                Log::info('Auto-detecting sheets and mapping for import');
                $sheets = $this->autoDetectSheetsAndMapping($spreadsheet, $preset);
                
                if (empty($sheets)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Không tìm thấy sheet hợp lệ nào trong file. File có thể có cấu trúc không chuẩn hoặc thiếu các cột bắt buộc (SKU, giá).'
                    ]);
                }
                
                Log::info('Auto-detected ' . count($sheets) . ' valid sheets');
            }

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

            // Lưu custom columns từ request vào price list
            $customColumns = [];
            $realHeaders = []; // Ensure it is defined for custom column labels if needed
            
            // Collect all custom keys from all sheets to save global definition
            foreach ($sheets as $sheetConfig) {
                if (isset($sheetConfig['mapping'])) {
                    foreach ($sheetConfig['mapping'] as $key => $colIndex) {
                        if (str_starts_with($key, 'custom_') || str_starts_with($key, 'meta_')) {
                            // Extract label: either from header (if available) or from key
                            $label = (isset($realHeaders[$colIndex]) && $realHeaders[$colIndex] !== '') 
                                     ? $realHeaders[$colIndex] 
                                     : str_replace(['custom_', 'meta_'], '', $key);
                                     
                            $customColumns[$key] = [
                                'key' => $key,
                                'label' => $label,
                                'type' => str_starts_with($key, 'custom_') ? 'price' : 'text'
                            ];
                        }
                    }
                }
            }
            
            if (!empty($customColumns)) {
                $priceList->update(['custom_columns' => array_values($customColumns)]);
            }

            // Lưu definition các cột (cả custom và standard)
            $columnDefinitions = []; // format: key => label

            foreach ($sheets as $sheetConfig) {
                // Chúng ta sẽ lấy lại headers từ file trong vòng lặp xử lý sheet bên dưới
                // Tuy nhiên, để map label chính xác cho toàn bộ Price List (dùng chung cho các sheet), 
                // ta nên ưu tiên label từ sheet đầu tiên hoặc merge lại.
                // Ở đây ta xử lý LOGIC CAPTURE LABEL bên trong vòng lặp xử lý từng sheet phía dưới 
                // và cập nhật vào biến $columnDefinitions
            }

            foreach ($sheets as $sheetConfig) {
                // ... (logic skip sheet giữ nguyên) ...
                $sheetName = $sheetConfig['name'];
                
                // Kiểm tra skip
                $shouldSkip = false;
                if (!empty($preset['skipSheets'])) {
                    foreach ($preset['skipSheets'] as $skipPattern) {
                        if (stripos($sheetName, $skipPattern) !== false) {
                            $shouldSkip = true;
                            break;
                        }
                    }
                }
                if ($shouldSkip) {
                    // ... log skip ...
                    Log::info("Skipping sheet '{$sheetName}' - in skip list");
                    $importLog['sheets'][] = [
                        'name' => $sheetName, 
                        'rows_processed' => 0, 'items_created' => 0, 'items_updated' => 0, 'items_skipped' => 0, 
                        'skipped_reason' => 'Sheet trong danh sách bỏ qua'
                    ];
                    continue;
                }

                $worksheet = $spreadsheet->getSheet($sheetConfig['index']);
                $configHeaderRow = $sheetConfig['header_row'];
                $highestColumn = $worksheet->getHighestColumn();
                $highestRow = $worksheet->getHighestRow();

                // Find header row...
                $headerRow = $this->findHeaderRow($worksheet, $configHeaderRow, $highestColumn, $preset);
                
                if ($headerRow === null) {
                   // ... log error ...
                   continue;
                }

                // Get File Headers (Real names) - support multi-row headers
                $fileHeaders = [];
                $realHeaders = []; // Store Original Case headers for Labeling
                $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                $maxColToProcess = min($highestColIndex, 100);
                
                // Row-level decision: is headerRow+1 a header continuation or data?
                $isMultiRowHeader = $this->isNextRowHeaderContinuation($worksheet, $headerRow, $maxColToProcess, $highestRow);
                
                for ($colIdx = 1; $colIdx <= $maxColToProcess; $colIdx++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                    $value = $this->getCellValue($worksheet, $col, $headerRow);
                    $trimVal = trim($value ?? '');
                    
                    // Multi-row header: combine with next row ONLY if row-level check confirmed it's a header continuation
                    if ($isMultiRowHeader && $headerRow < $highestRow) {
                        $nextRowVal = $this->getCellValue($worksheet, $col, $headerRow + 1);
                        $nextVal = trim($nextRowVal ?? '');
                        
                        if ($nextVal !== '') {
                            $trimVal = ($trimVal !== '') ? $trimVal . ' ' . $nextVal : $nextVal;
                        }
                    }
                    
                    // Clean newlines in header values
                    $trimVal = preg_replace('/[\r\n]+/', ' ', $trimVal);
                    $trimVal = preg_replace('/\s+/', ' ', $trimVal);
                    $trimVal = trim($trimVal);
                    
                    if ($trimVal !== '') {
                        $fileHeaders[$colIdx - 1] = strtolower($trimVal);
                        $realHeaders[$colIdx - 1] = $trimVal;
                    }
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

                // CAPTURE LABELS from this sheet's mapping
                foreach ($mapping as $field => $colIndex) {
                    if ($colIndex !== '' && isset($realHeaders[$colIndex])) {
                        // Skip internal mapping fields (per-tier SKU columns and range column)
                        if (str_starts_with($field, '_') || preg_match('/^sku_\d+yr$/', $field)) continue;
                        
                        $originalName = $realHeaders[$colIndex];
                        if (!isset($columnDefinitions[$field]) || strlen($originalName) > strlen($columnDefinitions[$field])) {
                            $columnDefinitions[$field] = $originalName;
                        }
                    }
                }
                
                // Also capture Custom Columns Labels from key name directly if not found in header (fallback)
                foreach ($mapping as $key => $colIndex) {
                     if (str_starts_with($key, 'custom_')) {
                         $label = substr($key, 7);
                         if (!isset($columnDefinitions[$key])) {
                             $columnDefinitions[$key] = $label;
                         }
                     }
                }

                $sheetLog = [
                    'name' => $sheetConfig['name'],
                    'rows_processed' => 0,
                    'items_created' => 0,
                    'items_updated' => 0,
                    'items_skipped' => 0,
                    'skipped_details' => [],
                ];

                $currentCategory = null; // Track current category from section headers
                $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                // Determine data start row (reuse multi-row header flag from above)
                $dataStartRow = $isMultiRowHeader ? $headerRow + 2 : $headerRow + 1;
                if ($isMultiRowHeader) {
                    Log::debug("Multi-row header detected, data starts at row {$dataStartRow}");
                }

                for ($row = $dataStartRow; $row <= $highestRow; $row++) {
                    $rowData = [];
                    // Chỉ đọc các cột cần thiết dựa trên mapping
                    $colsToRead = array_unique(array_filter(array_values($mapping), fn($v) => $v !== ''));
                    
                    if (empty($colsToRead)) {
                        // Nếu không có mapping, đọc tối đa 100 cột đầu
                        $maxColToProcess = min($highestColIndex, 100);
                        for ($colIdx = 1; $colIdx <= $maxColToProcess; $colIdx++) {
                            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                            $cellValue = $worksheet->getCell($col . $row)->getValue();
                            if (is_string($cellValue) && str_starts_with($cellValue, '=')) {
                                $cellValue = $worksheet->getCell($col . $row)->getCalculatedValue();
                            }
                            $rowData[$colIdx - 1] = $cellValue;
                        }
                    } else {
                        // Chỉ đọc các cột được map
                        foreach ($colsToRead as $colIndex) {
                            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                            $cellValue = $worksheet->getCell($col . $row)->getValue();
                            if (is_string($cellValue) && str_starts_with($cellValue, '=')) {
                                $cellValue = $worksheet->getCell($col . $row)->getCalculatedValue();
                            }
                            $rowData[$colIndex] = $cellValue;
                        }
                    }

                    // ============================================================
                    // MULTI-TIER SPLIT MODE
                    // When per-tier SKU columns are detected (e.g., Bitdefender format with 
                    // repeating [SKU][Price] pairs), split each Excel row into multiple items.
                    // ============================================================
                    $hasMultiTierSku = isset($mapping['sku_2yr']) || isset($mapping['sku_3yr']) || isset($mapping['sku_4yr']) || isset($mapping['sku_5yr']);
                    
                    if ($hasMultiTierSku) {
                        // Build tier definitions: each tier has a SKU column and a price column
                        $tiers = [];
                        $tierDefs = [
                            ['sku_field' => 'sku', 'price_field' => 'price_1yr', 'period' => '1 Year'],
                            ['sku_field' => 'sku_2yr', 'price_field' => 'price_2yr', 'period' => '2 Years'],
                            ['sku_field' => 'sku_3yr', 'price_field' => 'price_3yr', 'period' => '3 Years'],
                            ['sku_field' => 'sku_4yr', 'price_field' => 'price_4yr', 'period' => '4 Years'],
                            ['sku_field' => 'sku_5yr', 'price_field' => 'price_5yr', 'period' => '5 Years'],
                        ];
                        
                        // For tier 1: use main 'sku' column with 'price_1yr' (or 'price' fallback)
                        foreach ($tierDefs as $def) {
                            $skuCol = $mapping[$def['sku_field']] ?? null;
                            $priceCol = $mapping[$def['price_field']] ?? ($def['sku_field'] === 'sku' ? ($mapping['price'] ?? null) : null);
                            
                            if ($skuCol !== null && $priceCol !== null) {
                                $tiers[] = [
                                    'sku_col' => $skuCol,
                                    'price_col' => $priceCol,
                                    'period' => $def['period'],
                                ];
                            }
                        }
                        
                        // Get common fields
                        $productName = isset($mapping['product_name']) && $mapping['product_name'] !== '' 
                            ? trim((string) ($rowData[$mapping['product_name']] ?? '')) : '';
                        $rangeValue = isset($mapping['_range_column']) && $mapping['_range_column'] !== ''
                            ? trim((string) ($rowData[$mapping['_range_column']] ?? '')) : '';
                        $category = isset($mapping['category']) && $mapping['category'] !== ''
                            ? mb_substr(trim((string) ($rowData[$mapping['category']] ?? '')), 0, 255) : null;
                        if (empty($category) && !empty($currentCategory)) {
                            $category = mb_substr($currentCategory, 0, 255);
                        }
                        $description = isset($mapping['description']) && $mapping['description'] !== ''
                            ? mb_substr(trim((string) ($rowData[$mapping['description']] ?? '')), 0, 65000) : null;
                        
                        // Check if this is a category header row (no valid tier data)
                        $anyTierHasData = false;
                        foreach ($tiers as $tier) {
                            $tierSku = trim((string) ($rowData[$tier['sku_col']] ?? ''));
                            $tierPrice = $this->parsePrice($rowData[$tier['price_col']] ?? null);
                            if (!empty($tierSku) && $tierPrice) {
                                $anyTierHasData = true;
                                break;
                            }
                        }
                        
                        if (!$anyTierHasData) {
                            // Possibly a category/section header
                            $candidate = $productName ?: trim((string) ($rowData[$mapping['sku']] ?? ''));
                            if (!empty($candidate) && strlen($candidate) < 100 && 
                                !str_contains(strtolower($candidate), 'price list') && 
                                !str_contains(strtolower($candidate), 'note:')) {
                                $currentCategory = $candidate;
                            }
                            $sheetLog['items_skipped']++;
                            continue;
                        }
                        
                        // Create one item per tier
                        foreach ($tiers as $tier) {
                            $tierSku = trim((string) ($rowData[$tier['sku_col']] ?? ''));
                            $tierPrice = $this->parsePrice($rowData[$tier['price_col']] ?? null);
                            
                            // Skip tiers with no SKU or no price
                            if (empty($tierSku) || !$tierPrice) continue;
                            
                            if (!$this->isValidSku($tierSku)) continue;
                            
                            // Build descriptive product name: "Product Name (Range) - Period"
                            $tierProductName = $productName ?: $tierSku;
                            if (!empty($rangeValue)) {
                                $tierProductName .= ' (' . $rangeValue . ')';
                            }
                            $tierProductName .= ' - ' . $tier['period'];
                            
                            $itemData = [
                                'supplier_price_list_id' => $priceList->id,
                                'sku' => mb_substr($tierSku, 0, 255),
                                'product_name' => mb_substr($tierProductName, 0, 65000),
                                'description' => $description,
                                'category' => $category,
                                'list_price' => $tierPrice,
                                'price_1yr' => null,
                                'price_2yr' => null,
                                'price_3yr' => null,
                                'price_4yr' => null,
                                'price_5yr' => null,
                                'source_sheet' => mb_substr($sheetConfig['name'], 0, 255),
                                'extra_data' => [
                                    'range' => $rangeValue ?: null,
                                    'period' => $tier['period'],
                                ],
                            ];
                            
                            if ($request->import_mode === 'update') {
                                $existing = SupplierPriceListItem::where('supplier_price_list_id', $priceList->id)
                                    ->where('sku', $tierSku)
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
                        
                    } else {
                    // ============================================================
                    // STANDARD MODE (original logic - one item per row)
                    // ============================================================
                    
                    // Lấy giá trị theo mapping
                    $sku = isset($mapping['sku']) && $mapping['sku'] !== '' ? trim((string) ($rowData[$mapping['sku']] ?? '')) : '';
                    $productName = isset($mapping['product_name']) && $mapping['product_name'] !== '' ? trim((string) ($rowData[$mapping['product_name']] ?? '')) : '';
                    
                    // Parse values to check valid item
                    $listPrice = isset($mapping['price']) && $mapping['price'] !== ''
                            ? $this->parsePrice($rowData[$mapping['price']] ?? null)
                            : null;
                    $price1yr = isset($mapping['price_1yr']) && $mapping['price_1yr'] !== ''
                            ? $this->parsePrice($rowData[$mapping['price_1yr']] ?? null)
                            : null;
                    
                    // Capture dynamic prices & meta
                    $extraPrices = [];
                    $metaData = [];
                    foreach ($mapping as $key => $colIndex) {
                        if ($colIndex === '') continue;
                        if (str_starts_with($key, 'custom_')) {
                            $val = $this->parsePrice($rowData[$colIndex] ?? null);
                            if ($val !== null) $extraPrices[$key] = $val;
                        } elseif (str_starts_with($key, 'meta_')) {
                            $metaData[$key] = trim((string)($rowData[$colIndex] ?? ''));
                        }
                    }
                    $hasDynamicPrice = !empty($extraPrices);

                    // Check all possible price columns (Allow 0 price)
                    $hasPrice = ($listPrice !== null || $price1yr !== null || $hasDynamicPrice);
                    
                    if (!$hasPrice) {
                        // Check other standard price columns (price_2yr..5yr)
                        foreach (['price_2yr', 'price_3yr', 'price_4yr', 'price_5yr'] as $priceCol) {
                            if (isset($mapping[$priceCol]) && $mapping[$priceCol] !== '') {
                                $val = $this->parsePrice($rowData[$mapping[$priceCol]] ?? null);
                                if ($val !== null) {
                                    $hasPrice = true;
                                    break;
                                }
                            }
                        }
                    }

                    // Category Logic:
                    // If row has text in SKU/Name col, but NO Price, treat as Section Header -> Category
                    if (empty($sku) || !$hasPrice) {
                        // Log để debug
                        if (!empty($sku) && $row <= $headerRow + 5) {
                            Log::info("Row {$row} skipped - SKU: {$sku}, hasPrice: " . ($hasPrice ? 'YES' : 'NO') . ", listPrice: {$listPrice}, hasDynamicPrice: " . ($hasDynamicPrice ? 'YES' : 'NO'), [
                                'mapping' => $mapping,
                                'rowData_sample' => array_slice($rowData, 0, 10),
                                'extraPrices' => $extraPrices
                            ]);
                        }
                        
                        // Check if potential category
                        $candidate = $sku ?: $productName;
                        // Heuristic: short text, no price keywords, reasonably meaningful
                        if (!empty($candidate) && strlen($candidate) < 100 && 
                            !str_contains(strtolower($candidate), 'price list') && 
                            !str_contains(strtolower($candidate), 'note:')) {
                            
                            $currentCategory = $candidate;
                        }
                        
                        $sheetLog['items_skipped']++;
                         if (count($sheetLog['skipped_details']) < 100) {
                            $sheetLog['skipped_details'][] = [
                                'row' => $row,
                                'sku' => $sku,
                                'reason' => 'Không có giá hoặc SKU (Có thể là tiêu đề nhóm: ' . ($currentCategory === $candidate ? 'YES' : 'NO') . ')'
                            ];
                        }
                        continue;
                    }

                    // Lọc bỏ các dòng không hợp lệ (nếu có giá nhưng SKU lỗi)
                    // Note: "APV 1600" as SKU? If it has price, it's an item. If no price, captured above.
                    if (!$this->isValidSku($sku)) {
                         $sheetLog['items_skipped']++;
                        if (count($sheetLog['skipped_details']) < 100) {
                            $sheetLog['skipped_details'][] = [
                                'row' => $row,
                                'sku' => $sku,
                                'reason' => 'SKU không hợp lệ hoặc bị blacklist'
                            ];
                        }
                        continue;
                    }

                    $category = isset($mapping['category']) && $mapping['category'] !== ''
                            ? mb_substr(trim((string) ($rowData[$mapping['category']] ?? '')), 0, 255)
                            : null;

                    // Fallback to section header category if mapped category is empty
                    if (empty($category) && !empty($currentCategory)) {
                        $category = mb_substr($currentCategory, 0, 255);
                    }

                    $itemData = [
                        'supplier_price_list_id' => $priceList->id,
                        'sku' => mb_substr(trim((string) $sku), 0, 255),
                        'product_name' => mb_substr(trim((string) ($productName ?: $sku)), 0, 65000),
                        'description' => isset($mapping['description']) && $mapping['description'] !== ''
                            ? mb_substr(trim((string) ($rowData[$mapping['description']] ?? '')), 0, 65000)
                            : null,
                        'category' => $category,
                        'list_price' => $listPrice,
                        'price_1yr' => $price1yr,
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
                        'extra_data' => (!empty($extraPrices) || !empty($metaData)) 
                                        ? ['prices' => $extraPrices, 'metadata' => $metaData] 
                                        : null,
                    ];

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
                    
                    } // end if/else hasMultiTierSku
                }

                $importLog['sheets'][] = $sheetLog;
                $importLog['total_items'] += $sheetLog['rows_processed'];
                $importLog['created'] += $sheetLog['items_created'];
                $importLog['updated'] += $sheetLog['items_updated'];
                $importLog['skipped'] += $sheetLog['items_skipped'];
            }

            // Save Collected Column Definitions to price list
            // Transform [key => label] to [{key, label}]
            // Filter out standard hardcoded fields (already shown in show view), internal fields, and unused tiers
            if (!empty($columnDefinitions)) {
                // Standard fields already displayed as hardcoded columns in show.blade.php
                $standardFields = ['sku', 'product_name', 'category', 'description'];
                
                // Check if multi-tier split was used (per-tier SKU columns detected)
                $isMultiTierImport = isset($mapping['sku_2yr']) || isset($mapping['sku_3yr']) 
                    || isset($mapping['sku_4yr']) || isset($mapping['sku_5yr']);
                
                $cols = [];
                foreach ($columnDefinitions as $key => $label) {
                    // Skip standard hardcoded fields
                    if (in_array($key, $standardFields)) continue;
                    
                    // Skip internal mapping fields
                    if (str_starts_with($key, '_') || preg_match('/^sku_\d+yr$/', $key)) continue;
                    
                    // In multi-tier mode, skip price_Xyr (each item only has list_price)
                    if ($isMultiTierImport && preg_match('/^price_\d+yr$/', $key)) continue;
                    
                    $cols[] = ['key' => $key, 'label' => $label];
                }
                $priceList->update(['custom_columns' => !empty($cols) ? $cols : null]);
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
     * Quét từ dòng 1-50 để tìm dòng có chứa các từ khóa header
     * Hỗ trợ multi-row headers (ví dụ QNAP có header trải qua 2 dòng do merged cells)
     */
    private function findHeaderRow($worksheet, int $suggestedRow, string $highestColumn, array $preset): ?int
    {
        $headerKeywords = [
            'sku', 'part number', 'part#', 'part #', 'partnumber', 'part_no', 'part no',
            'model', 'code', 'product', 'description', 'desc', 'price', 'usd', 'msrp',
            'amount', 'cost', 'item', 'unit', 'contract', 'forticare', 'list', 'p/n',
            'segment', 'purchase', 'dealer', 'suggested', 'without vat', 'availability',
            'chassis', 'hdd', 'cpu', 'memory', 'lan'
        ];

        $bestRow = null;
        $maxMatches = 0;
        $bestNonEmpty = 0;
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Helper: read one row into array of cleaned values
        $readRow = function (int $row) use ($worksheet, $highestColIndex): array {
            $vals = [];
            for ($colIdx = 1; $colIdx <= $highestColIndex; $colIdx++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $cellVal = $this->getCellValue($worksheet, $col, $row);
                $val = strtolower(trim((string) $cellVal));
                $val = preg_replace('/_x000d_|\r\n|\r|\n/i', ' ', $val);
                $val = preg_replace('/\s+/', ' ', $val);
                $vals[$colIdx] = trim($val);
            }
            return $vals;
        };

        // Helper: score a set of values against keywords
    $scoreValues = function (array $values) use ($headerKeywords): array {
        $matches = 0;
        $uniqueKeywords = [];
        $hasPriceKw = false;
        $hasSkuKw = false;
        $nonEmptyValues = array_filter($values);
        $totalNonEmpty = count($nonEmptyValues);
        
        if ($totalNonEmpty === 0) return ['matches' => 0, 'nonEmpty' => 0];

        foreach ($nonEmptyValues as $value) {
            $clean = preg_replace('/[^a-z0-9\p{L}]/u', '', mb_strtolower($value));
            
            if (str_contains($clean, 'sku') || str_contains($clean, 'partnumber') || 
                str_contains($clean, 'partno') || str_contains($clean, 'pn')) {
                $hasSkuKw = true;
            }
            if (str_contains($clean, 'price') || str_contains($clean, 'msrp') ||
                str_contains($clean, 'purchase') || str_contains($clean, 'dealer') ||
                str_contains($clean, 'contract') || str_contains($clean, 'usd')) {
                $hasPriceKw = true;
            }
            
            foreach ($headerKeywords as $kw) {
                $ckw = preg_replace('/[^a-z0-9\p{L}]/u', '', mb_strtolower($kw));
                if (str_contains($clean, $ckw)) {
                    if (!isset($uniqueKeywords[$ckw])) {
                        $matches++;
                        $uniqueKeywords[$ckw] = true;
                    }
                    break;
                }
            }
        }

        $uniquenessRatio = count(array_unique($nonEmptyValues)) / $totalNonEmpty;
        
        // Base score = unique matches
        $finalScore = (float)$matches;
        if ($hasSkuKw) $finalScore += 15;
        if ($hasPriceKw) $finalScore += 10;
        
        // Penalize highly repetitive rows (likely titles or decorations)
        // A real header row should have unique labels across many columns
        if ($uniquenessRatio < 0.4 && $totalNonEmpty > 3) {
            $finalScore *= 0.1;
        }

        return ['matches' => $finalScore, 'nonEmpty' => $totalNonEmpty, 'uniqueness' => $uniquenessRatio];
    };

        $rowsToCheck = range(1, 50);
        if ($suggestedRow > 0 && !in_array($suggestedRow, $rowsToCheck)) {
            array_unshift($rowsToCheck, $suggestedRow);
        }

        // Cache rows we read
        $rowCache = [];

        foreach ($rowsToCheck as $row) {
            if (!isset($rowCache[$row])) {
                $rowCache[$row] = $readRow($row);
            }
            $rowValues = $rowCache[$row];

            // Log first 10 rows for debugging
            if ($row <= 10) {
                $debugData = [];
                foreach ($rowValues as $ci => $v) {
                    if (!empty($v)) {
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci);
                        $debugData[$col] = $v;
                    }
                }
                Log::debug("Sheet: " . $worksheet->getTitle() . " - Row {$row}: " . json_encode($debugData, JSON_UNESCAPED_UNICODE));
            }

            // Score single row
            $score = $scoreValues($rowValues);
            if ($score['nonEmpty'] < 2) continue;

            // USE UNMERGED score as primary detection criteria
            $unmergedMatches = $score['matches'];
            $nonEmptyCount = $score['nonEmpty'];

            // SECONDARY: Check if merging with next row improves things
            $effectiveScore = (float)$unmergedMatches;

            $nextRow = $row + 1;
            if ($nextRow <= 50) {
                if (!isset($rowCache[$nextRow])) {
                    $rowCache[$nextRow] = $readRow($nextRow);
                }
                $nextValues = $rowCache[$nextRow];
                $merged = [];
                for ($ci = 1; $ci <= $highestColIndex; $ci++) {
                    $v1 = $rowValues[$ci] ?? '';
                    $v2 = $nextValues[$ci] ?? '';
                    if ($v1 !== '' && $v2 !== '') {
                        $merged[$ci] = $v1 . ' ' . $v2;
                    } else {
                        $merged[$ci] = $v1 !== '' ? $v1 : $v2;
                    }
                }
                $mergedScore = $scoreValues($merged);
                
                // If merging improves keywords, add a small 0.5 bonus to the core row score
                // But don't let a generic group row (Row 2) beat a specific header row (Row 3)
                // just because Row 2+3 combined has more words.
                if ($mergedScore['matches'] > $unmergedMatches) {
                    $effectiveScore += 0.5;
                    $nonEmptyCount = max($nonEmptyCount, $mergedScore['nonEmpty']);
                    Log::debug("Multi-row header potential: row {$row} matches {$unmergedMatches}, +nextRow matches {$mergedScore['matches']}");
                }
            }

            // Update best row based on weighted score
            if ($effectiveScore > $maxMatches || 
                (abs($effectiveScore - $maxMatches) < 0.01 && $nonEmptyCount > $bestNonEmpty)) {
                $maxMatches = $effectiveScore;
                $bestRow = $row;
                $bestNonEmpty = $nonEmptyCount;
            }
        }

        // Return best row if found
        // Use a higher threshold to avoid titles (typically score 1.5-2 after penalty)
        if ($maxMatches >= 5) {
            Log::debug("Found best header at row {$bestRow} with score {$maxMatches}");
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
     * Check if the row after the header is a header continuation (multi-row header).
     * Returns true if row headerRow+1 appears to be header text, false if it's data.
     * Uses row-level analysis: checks all cells and votes header vs data.
     */
    private function isNextRowHeaderContinuation($worksheet, int $headerRow, int $maxColToCheck, int $highestRow): bool
    {
        if ($headerRow >= $highestRow) return false;

        $headerLikeCount = 0;
        $dataLikeCount = 0;
        $checkCols = min($maxColToCheck, 25);

        for ($colIdx = 1; $colIdx <= $checkCols; $colIdx++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $nextVal = trim($this->getCellValue($worksheet, $col, $headerRow + 1) ?? '');

            if ($nextVal === '') continue;

            // SKU-like codes: mix of uppercase letters and digits, 6+ chars (e.g. 2759ZZBSR120ALZZ)
            $isSkuLike = preg_match('/^[A-Z0-9\-\/\.]{6,}$/i', $nextVal)
                         && preg_match('/[A-Z]/i', $nextVal)
                         && preg_match('/[0-9]/', $nextVal);

            // Numeric values: pure numbers, prices with commas, or range patterns like "0003-0014"
            $isNumeric = is_numeric($nextVal) || preg_match('/^[\d,\.\-\s\/]+$/', $nextVal);

            // Long text: product names, descriptions (>25 chars is almost certainly data)
            $isLongText = mb_strlen($nextVal) > 25;

            if ($isSkuLike || $isNumeric || $isLongText) {
                $dataLikeCount++;
            } else {
                // Short non-numeric text could be a header continuation (e.g. "Year", "(USD)", "Ex-work TW")
                $headerLikeCount++;
            }
        }

        $totalNonEmpty = $headerLikeCount + $dataLikeCount;

        // Row is header continuation only if >60% of non-empty cells look like headers
        $isHeader = $totalNonEmpty > 0 && ($headerLikeCount / $totalNonEmpty) > 0.6;

        Log::debug("isNextRowHeaderContinuation: headerRow={$headerRow}, headerLike={$headerLikeCount}, dataLike={$dataLikeCount}, result=" . ($isHeader ? 'true' : 'false'));

        return $isHeader;
    }

    /**
     * Tự động detect mapping từ headers của file
     */
    private function autoDetectMappingFromHeaders(array $fileHeaders, array $preset = [], array $rowSamples = [])
    {
        $mapping = [];
        $headerMap = [];

        // Chuẩn hóa headers để dễ so sánh (dùng mb_strtolower cho tiếng Việt)
        foreach ($fileHeaders as $index => $header) {
            $headerMap[$index] = mb_strtolower(trim($header), 'UTF-8');
        }

        // Helper: Remove Vietnamese accents and other marks for robust comparison
        $removeAccents = function($str) {
            $str = mb_strtolower((string)$str, 'UTF-8');
            $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/u", "a", $str);
            $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/u", "e", $str);
            $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/u", "i", $str);
            $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/u", "o", $str);
            $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/u", "u", $str);
            $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/u", "y", $str);
            $str = preg_replace("/(đ)/u", "d", $str);
            // Handle common corrupted pieces
            $str = str_replace(['a\'', 'a?', 'a ', 'e\'', 'e?'], ['a', 'a', 'a', 'e', 'e'], $str);
            return $str;
        };

        // Helper: Super Clean (strip everything non-alpha for robust keyword matching)
        $superClean = function($str) use ($removeAccents) {
            $s = $removeAccents($str);
            return preg_replace('/[^a-z0-9]/', '', $s);
        };

        // Helper: Check column data validity score (0-100)
        $getColScore = function($colIndex) use ($rowSamples) {
            if (empty($rowSamples)) return 100;
            $count = 0; $numeric = 0;
            foreach ($rowSamples as $row) {
                $val = $row[$colIndex] ?? null;
                if ($val !== null && trim((string)$val) !== '') {
                    $count++;
                    $cleanVal = preg_replace('/[đ\$,\s\.\,]/u', '', (string)$val);
                    if (is_numeric($cleanVal) && $cleanVal !== '') $numeric++;
                }
            }
            return ($count == 0) ? 0 : ($numeric / $count) * 100;
        };

        // Helper: find first header containing any of the given keywords
        $findByContains = function (array $keywords, array $excludeIndices = []) use ($headerMap): ?int {
            foreach ($keywords as $keyword) {
                foreach ($headerMap as $index => $header) {
                    if (in_array($index, $excludeIndices)) continue;
                    if (str_contains($header, $keyword)) {
                        return $index;
                    }
                }
            }
            return null;
        };

        // Detect Common Price List Types (Override FUZZY detection)
        $allHeaderStr = $superClean(implode(' ', $headerMap));
        
        // Zyxel Specific Detect
        if (str_contains($allHeaderStr, 'partnumber') && (str_contains($allHeaderStr, 'silver') || str_contains($allHeaderStr, 'msrp'))) {
            Log::info("Zyxel-style headers detected. Applying specific mapping rules.");
            foreach ($headerMap as $idx => $header) {
                $sc = $superClean($header);
                if (str_contains($sc, 'partnumber')) $mapping['sku'] = $idx;
                if (str_contains($sc, 'informationinenglish')) $mapping['product_name'] = $idx;
                if (str_contains($sc, 'productmodel')) $mapping['meta_Model'] = $idx;
                if (str_contains($sc, 'ghichu') || str_contains($sc, 'note')) $mapping['meta_Note'] = $idx;
                
                if (str_contains($sc, 'silver')) {
                    if (!isset($mapping['price'])) $mapping['price'] = $idx;
                    else $mapping['custom_Silver'] = $idx;
                }
                if (str_contains($sc, 'msrpchuavat')) {
                    if (!isset($mapping['price'])) $mapping['price'] = $idx; 
                    else $mapping['custom_MSRP (chưa VAT)'] = $idx;
                }
                if (str_contains($sc, 'daily')) $mapping['custom_Đại lý'] = $idx;
                if (str_contains($sc, 'msrpcov')) $mapping['custom_MSRP (có V)'] = $idx;
                if (str_contains($sc, 'msrpcov')) $mapping['custom_MSRP (có V)'] = $idx;
                if ($sc === 'vat') $mapping['vat'] = $idx;
            }
        }
        
        // QNAP Specific Detect (Multi-row merged headers)
        if (str_contains($allHeaderStr, 'purchaseprice') && (str_contains($allHeaderStr, 'suggesteddealerprice') || str_contains($allHeaderStr, 'msrpwithoutvat'))) {
            Log::info("QNAP-style headers detected. Applying specific mapping rules.");
            foreach ($headerMap as $idx => $header) {
                $sc = $superClean($header);
                if (str_contains($sc, 'pn') || str_contains($sc, 'partnumber')) $mapping['sku'] = $idx;
                if (str_contains($sc, 'description')) $mapping['product_name'] = $idx;
                if (str_contains($sc, 'segment')) $mapping['category'] = $idx;
                
                if (str_contains($sc, 'purchaseprice')) {
                    if (!isset($mapping['price'])) $mapping['price'] = $idx;
                    else $mapping['custom_Purchase Price'] = $idx;
                }
                if (str_contains($sc, 'suggesteddealerprice')) $mapping['custom_Dealer Price'] = $idx;
                if (str_contains($sc, 'msrpwithoutvat')) $mapping['custom_MSRP'] = $idx;
            }
        }
        
        // SonicWall Specific Detect
        if (str_contains($allHeaderStr, 'isrp') || str_contains($allHeaderStr, 'disti') || str_contains($allHeaderStr, 'sonicwallsku')) {
            Log::info("SonicWall-style headers detected. Applying specific mapping rules.");
            foreach ($headerMap as $idx => $header) {
                $sc = $superClean($header);
                if (str_contains($sc, 'sonicwallsku')) $mapping['sku'] = $idx;
                if (str_contains($sc, 'sonicwallproductdescription') || (str_contains($sc, 'description') && !isset($mapping['product_name']))) {
                    $mapping['product_name'] = $idx;
                }
                
                if (str_contains($sc, 'isrp')) {
                    if (!isset($mapping['price'])) $mapping['price'] = $idx;
                    else $mapping['custom_ISRP'] = $idx;
                }
                if (str_contains($sc, 'disti') || str_contains($sc, 'distributorprice')) {
                    if (!isset($mapping['price']) && !str_contains($allHeaderStr, 'isrp')) $mapping['price'] = $idx;
                    else $mapping['custom_Distributor Price'] = $idx;
                }
                if (str_contains($sc, 'resellerprice') || str_contains($sc, 'dealerprice')) {
                    $mapping['custom_Dealer Price'] = $idx;
                }
                if (str_contains($sc, 'msrp') && !str_contains($sc, 'isrp')) {
                    if (!isset($mapping['price']) && !str_contains($allHeaderStr, 'isrp') && !str_contains($allHeaderStr, 'disti')) $mapping['price'] = $idx;
                    else $mapping['custom_MSRP'] = $idx;
                }
            }
        }

        // Fortinet Specific Detect
        if (str_contains($allHeaderStr, '1yrcontract') || str_contains($allHeaderStr, 'support1yr')) {
            Log::info("Fortinet-style headers detected. Applying specific mapping rules.");
            foreach ($headerMap as $idx => $header) {
                $sc = $superClean($header);
                if ($sc === 'price') $mapping['price'] = $idx;
                if ($sc === 'netprice') $mapping['custom_Net Price'] = $idx;
                if ($sc === 'unit') $mapping['category'] = $idx;
                if (str_contains($sc, 'sku') || $sc === 'partnumber') $mapping['sku'] = $idx;
                if ($sc === 'description') $mapping['product_name'] = $idx;
            }
        }

        // Fortinet Specific Detect
        if (str_contains($allHeaderStr, '1yrcontract') || str_contains($allHeaderStr, 'support1yr') || 
            (str_contains($allHeaderStr, 'fortigate') && str_contains($allHeaderStr, 'sku'))) {
            Log::info("Fortinet-style headers detected. Applying specific mapping rules.");
            foreach ($headerMap as $idx => $header) {
                $sc = $superClean($header);
                if ($sc === 'price' || $sc === 'msrp') $mapping['price'] = $idx;
                if ($sc === 'netprice') $mapping['custom_Net Price'] = $idx;
                if ($sc === 'unit' || $sc === 'identifier') $mapping['category'] = $idx;
                if (str_contains($sc, 'sku') || $sc === 'partnumber') $mapping['sku'] = $idx;
                if ($sc === 'product' || $sc === 'description' || str_contains($sc, 'description1')) {
                    $mapping['product_name'] = $idx;
                }
            }
        }

        // 1. Detect SKU (Quan trọng nhất)
        // First: Try preset columnPatterns for exact match
        $presetPatterns = $preset['columnPatterns'] ?? [];
        
        if (!empty($presetPatterns['sku'])) {
            foreach ($presetPatterns['sku'] as $pattern) {
                $patternLower = mb_strtolower($pattern, 'UTF-8');
                foreach ($headerMap as $index => $header) {
                    if ($header === $patternLower || str_contains($header, $patternLower)) {
                        $mapping['sku'] = $index;
                        break 2;
                    }
                }
            }
        }
        
        // Fallback: generic SKU keywords
        if (!isset($mapping['sku'])) {
        $skuKeywords = ['sku', 'part number', 'part no', 'p/n', 'part#', 'part #',
                        'item no', 'item number', 'product code', 'marketing no',
                        'mã sản phẩm', 'mã sp', 'model no', 'model number', 'mtm'];
        
        foreach ($skuKeywords as $keyword) {
            $index = array_search($keyword, $headerMap);
            if ($index !== false) {
                $mapping['sku'] = $index;
                break;
            }
        }
        
        if (!isset($mapping['sku'])) {
            $skuContains = ['sku', 'p/n', 'part number', 'part no', 'part#', 'item no',
                           'product code', 'marketing no', 'model no'];
            $mapping['sku'] = $findByContains($skuContains, []) ?? null;
            if ($mapping['sku'] === null) unset($mapping['sku']);
        }
        } // end fallback SKU

        // 2. Detect Product Name
        $usedIndices = isset($mapping['sku']) ? [$mapping['sku']] : [];
        if (!isset($mapping['product_name'])) {
            $nameKeywords = [
                'information in english', 'name', 'product name', 'description', 'thong tin san pham',
                'item description', 'model name', 'tên sản phẩm', 'mô tả', 'diễn giải'
            ];
            $nameExclusions = ['segment', 'category', 'group', 'loại', 'nhóm', 'phần khúc', 'note', 'ghi chú'];

            foreach ($nameKeywords as $keyword) {
                $kwClean = $superClean($keyword);
                foreach ($headerMap as $index => $header) {
                    if (in_array($index, $usedIndices)) continue;
                    $hClean = $superClean($header);
                    
                    // Specific check for "product"
                    if ($kwClean === 'product' || $kwClean === 'tensanpham') {
                        foreach ($nameExclusions as $ex) {
                            if (str_contains($hClean, $superClean($ex))) continue 2;
                        }
                    }

                    if (str_contains($hClean, $kwClean)) {
                        $mapping['product_name'] = $index;
                        break 2;
                    }
                }
            }
        }
        // 3. Detect Category
        // First: Try preset columnPatterns
        if (!empty($presetPatterns['category'])) {
            foreach ($presetPatterns['category'] as $pattern) {
                $patternLower = mb_strtolower($pattern, 'UTF-8');
                foreach ($headerMap as $index => $header) {
                    if ($header === $patternLower || str_contains($header, $patternLower)) {
                        $mapping['category'] = $index;
                        break 2;
                    }
                }
            }
        }
        
        // Fallback: generic category keywords
        if (!isset($mapping['category'])) {
            $catKeywords = ['category', 'segment', 'group', 'product group', 'product family',
                           'danh mục', 'nhóm', 'loại', 'hdd type', 'series', 'project', 'high end'];
            foreach ($catKeywords as $keyword) {
                $kwClean = $superClean($keyword);
                foreach ($headerMap as $index => $header) {
                    if (str_contains($superClean($header), $kwClean)) {
                        $mapping['category'] = $index;
                        break 2;
                    }
                }
            }
        } // end fallback category

        // 4. Detect warranty/contract price tiers FIRST (before primary price)
        // This ensures warranty columns are excluded from primary price detection
        $warrantyKeywords = [
            'price_1yr' => ['1yr', '1 yr', '1 year', '1 năm', 'support 1yr', '12 months', '1y support', 'support 1y'],
            'price_2yr' => ['2yr', '2 yr', '2 year', '2 năm', 'support 2yr', '24 months', '2y support', 'support 2y'],
            'price_3yr' => ['3yr', '3 yr', '3 year', '3 năm', 'support 3yr', '36 months', '3y support', 'support 3y'],
            'price_4yr' => ['4yr', '4 yr', '4 year', '4 năm', '48 months', '4y support', 'support 4y'],
            'price_5yr' => ['5yr', '5 yr', '5 year', '5 năm', '60 months', '5y support', 'support 5y'],
        ];

        // First: Try preset columnPatterns for warranty
        foreach (['price_1yr', 'price_2yr', 'price_3yr', 'price_4yr', 'price_5yr'] as $field) {
            if (!empty($presetPatterns[$field])) {
                foreach ($presetPatterns[$field] as $pattern) {
                    $patternLower = mb_strtolower($pattern, 'UTF-8');
                    foreach ($headerMap as $index => $header) {
                        if (str_contains($header, $patternLower)) {
                            $mapping[$field] = $index;
                            break 2;
                        }
                    }
                }
            }
        }

        // Fallback: generic warranty keywords
        foreach ($warrantyKeywords as $field => $keywords) {
            if (isset($mapping[$field])) continue; // Already found via preset
            foreach ($keywords as $keyword) {
                foreach ($headerMap as $index => $header) {
                    if (str_contains($header, $keyword)) {
                        $mapping[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        // Also detect gold/silver/bronze as warranty tiers
        // IMPORTANT: Only treat as warranty if it ALSO looks like a warranty column (contains year/năm/1y/etc.)
        // Otherwise it might be a partner price level (like in Zyxel)
        $tierAliases = [
            'price_1yr' => ['gold'],
            'price_2yr' => ['silver'],
            'price_3yr' => ['bronze'],
        ];
        $warrantyMarkers = ['1yr', '2yr', '3yr', '4yr', '5yr', 'year', 'năm', 'bảo hành', 'warranty', 'support', 'bh', '1y', '2y', '3y'];
        foreach ($tierAliases as $field => $aliases) {
            if (isset($mapping[$field])) continue;
            foreach ($aliases as $alias) {
                foreach ($headerMap as $index => $header) {
                    if (str_contains($header, $alias)) {
                        $hasWarrantyMarker = false;
                        foreach ($warrantyMarkers as $marker) {
                            if (str_contains($header, $marker)) {
                                $hasWarrantyMarker = true;
                                break;
                            }
                        }
                        if ($hasWarrantyMarker) {
                            $mapping[$field] = $index;
                            break 2;
                        }
                    }
                }
            }
        }

        // Collect warranty column indices to exclude from primary price detection
        $warrantyIndices = [];
        foreach (['price_1yr', 'price_2yr', 'price_3yr', 'price_4yr', 'price_5yr'] as $field) {
            if (isset($mapping[$field])) {
                $warrantyIndices[] = $mapping[$field];
            }
        }

        // 4b. Detect per-tier SKU columns (for formats like Bitdefender with repeating SKU+price pairs)
        // Pattern: header has [SKU][Price 1yr][SKU][Price 2yr][SKU][Price 3yr]
        // Each SKU column has the same header text but contains different SKU codes per tier
        if (isset($mapping['sku'])) {
            $skuHeader = $headerMap[$mapping['sku']] ?? null;
            if ($skuHeader) {
                $tierSkuMap = [
                    'price_1yr' => 'sku_1yr',
                    'price_2yr' => 'sku_2yr',
                    'price_3yr' => 'sku_3yr',
                    'price_4yr' => 'sku_4yr',
                    'price_5yr' => 'sku_5yr',
                ];
                
                foreach ($tierSkuMap as $priceField => $skuField) {
                    if (!isset($mapping[$priceField])) continue;
                    $priceIndex = $mapping[$priceField];
                    
                    // Check if the column immediately before this price column has the same header as the main SKU
                    $prevIndex = $priceIndex - 1;
                    if ($prevIndex >= 0 && $prevIndex !== $mapping['sku'] && isset($headerMap[$prevIndex])) {
                        if ($headerMap[$prevIndex] === $skuHeader) {
                            $mapping[$skuField] = $prevIndex;
                            Log::debug("Detected per-tier SKU column: {$skuField} = column {$prevIndex} (adjacent to {$priceField})");
                        }
                    }
                }
            }
        }
        
        // 4c. Detect range/quantity column (e.g. "Users range", "Quantity range")
        $rangeKeywords = ['users range', 'user range', 'quantity range', 'qty range', 'license range', 'band'];
        foreach ($rangeKeywords as $keyword) {
            foreach ($headerMap as $index => $header) {
                if (str_contains($header, $keyword)) {
                    $mapping['_range_column'] = $index;
                    Log::debug("Detected range column: column {$index} (header: {$header})");
                    break 2;
                }
            }
        }

        // 5. Detect Primary Price column (excluding warranty columns)
        // First: Try preset columnPatterns for price
        if (!empty($presetPatterns['price'])) {
            foreach ($presetPatterns['price'] as $pattern) {
                $patternLower = mb_strtolower($pattern, 'UTF-8');
                foreach ($headerMap as $index => $header) {
                    if (in_array($index, $warrantyIndices)) continue;
                    if ($header === $patternLower || str_contains($header, $patternLower)) {
                        $mapping['price'] = $index;
                        break 2;
                    }
                }
            }
        }

        // Fallback: tiered price pattern search
        if (!isset($mapping['price'])) {
            $pricePatterns = [
                ['(chưa vat)', '(ex vat)', 'msrp (chưa vat)', 'ex-vat', 'exc vat', 'exc. vat', 'chua vat', 'chua v', 'cha v', 'chua vat', 'cha vat', 'giá gốc', 'giá nhập', 'giá mua'], // Tier 0
                ['msrp', 'isrp', 'srp', 'list price', 'price', 'net price', 'base price', 'standard price', 'gpl', 'giá niêm yết', 'giá list'], // Tier 1
                ['retail price', 'giá bán lẻ', 'giá lẻ', 'end-user', 'end user', 'giá user'], // Tier 2
                ['purchase price', 'disti', 'distributor price', 'cost price', 'unit cost', 'giá nhập', 'giá vốn'], // Tier 3
                ['reseller price', 'dealer price', 'partner price', 'giá đại lý', 'đại lý', 'dai ly', 'sales price',
                 'platinum', 'ec price', 'e-commerce', 'giá ec', 'silver', 'gold', 'bronze'], // Tier 4
                ['giá'], // Tier 5
            ];
            
            $priceExclusions = ['note', 'ghi chú', 'ghi chu', 'segment', 'category', 'danh mục', 'mô tả', 'description', 'update', 'product information', 'giới thiệu', 'đăng ký', 'cam kết', 'thông tin', 'sheet', 'warranty', 'bảo hành', 'remark'];
        
            $foundPriceIndices = [];
            foreach ($pricePatterns as $tier => $keywords) {
                foreach ($keywords as $keyword) {
                    $keywordClean = $superClean($keyword);
                    foreach ($headerMap as $index => $header) {
                        if (in_array($index, $usedIndices)) continue;
                        if (in_array($index, $warrantyIndices)) continue;
                        
                        $headerClean = $superClean($header);
                        
                        // Strict exclusion check
                        foreach ($priceExclusions as $excluded) {
                            if (str_contains($headerClean, $superClean($excluded))) continue 2;
                        }
                        
                        // Skip SKU/Part columns
                        if (str_contains($headerClean, 'partnumber') || str_contains($headerClean, 'sku')) continue;

                        if (str_contains($headerClean, $keywordClean)) {
                            $targetIndex = $index;
                            $score = $getColScore($targetIndex);
                            
                            if ($score < 30) {
                                $adjScore = $getColScore($targetIndex + 1);
                                if ($adjScore > 50) {
                                    $targetIndex = $targetIndex + 1;
                                } else {
                                    continue; 
                                }
                            }

                            if (!isset($foundPriceIndices[$targetIndex]) || $tier < $foundPriceIndices[$targetIndex]['tier']) {
                                $foundPriceIndices[$targetIndex] = ['tier' => $tier, 'keyword' => $keyword, 'header' => $header];
                            }
                        }
                    }
                }
            }
            
            if (!empty($foundPriceIndices)) {
                uasort($foundPriceIndices, fn($a, $b) => $a['tier'] <=> $b['tier']);
                $firstPrice = array_key_first($foundPriceIndices);
                $mapping['price'] = $firstPrice;
                
                foreach ($foundPriceIndices as $index => $info) {
                    if ($index === $firstPrice) continue;
                    
                    // Prevent standard warranty/year tiers from being added to custom_columns
                    $isTier = false;
                    foreach ($warrantyKeywords as $key => $kwList) {
                        if (in_array($index, $mapping) && array_search($index, $mapping) === $key) {
                            $isTier = true;
                            break;
                        }
                    }
                    if ($isTier) continue;

                    $label = mb_convert_case($info['header'], MB_CASE_TITLE, "UTF-8");
                    $label = preg_replace_callback('/\b(usd|msrp|vat)\b/i', fn($m) => strtoupper($m[0]), $label);
                    $mapping['custom_' . $label] = $index;
                }
            }
        }
        
        // Fallback: generic 'price' keyword search
        if (!isset($mapping['price'])) {
            $usedIndices = array_values($mapping);
            foreach ($headerMap as $index => $header) {
                if (in_array($index, $usedIndices)) continue;
                if (in_array($index, $warrantyIndices)) continue;
                
                $hClean = $superClean($header);
                
                // Strict Note/SKU exclusion for fallback
                $isExcluded = false;
                foreach ($priceExclusions as $ex) {
                    if (str_contains($hClean, $superClean($ex))) { $isExcluded = true; break; }
                }
                if ($isExcluded) continue;
                if (str_contains($hClean, 'sku') || str_contains($hClean, 'partnumber')) continue;

                if (str_contains($hClean, 'price') || str_contains($hClean, 'gia') || str_contains($hClean, 'amount')) {
                    if ($getColScore($index) > 30) {
                        $mapping['price'] = $index;
                        break;
                    }
                }
            }
        }

        // 6. Detect Description (separate from product_name)
        // First: Try preset columnPatterns for description
        if (!empty($presetPatterns['description'])) {
            foreach ($presetPatterns['description'] as $pattern) {
                $patternLower = mb_strtolower($pattern, 'UTF-8');
                foreach ($headerMap as $index => $header) {
                    if ($index === ($mapping['product_name'] ?? -1)) continue;
                    if ($index === ($mapping['sku'] ?? -1)) continue;
                    if ($header === $patternLower || str_contains($header, $patternLower)) {
                        $mapping['description'] = $index;
                        break 2;
                    }
                }
            }
        }
        
        // Fallback: generic description keywords
        if (!isset($mapping['description']) && isset($mapping['product_name'])) {
            $descKeywords = ['description', 'full description', 'long description', 'specification', 'mô tả'];
            foreach ($descKeywords as $keyword) {
                foreach ($headerMap as $index => $header) {
                    if ($index === ($mapping['product_name'] ?? -1)) continue;
                    if ($index === ($mapping['sku'] ?? -1)) continue;
                    if (str_contains($header, $keyword)) {
                        $mapping['description'] = $index;
                        break 2;
                    }
                }
            }
        }
        
        // Final cleanup: remove duplicate column mappings
        // If multiple columns have the same header text and one is already mapped to a standard field,
        // don't also create custom_ entries for the duplicates
        $mappedHeaders = [];
        $mappedIndices = [];
        foreach ($mapping as $field => $index) {
            if (!str_starts_with($field, 'custom_') && $index !== '' && isset($headerMap[$index])) {
                $mappedHeaders[] = $headerMap[$index];
                $mappedIndices[] = $index;
            }
        }
        // Remove custom_ columns that point to a column with a duplicate header of an already-mapped standard field
        foreach ($mapping as $field => $index) {
            if (str_starts_with($field, 'custom_') && $index !== '' && isset($headerMap[$index])) {
                $header = $headerMap[$index];
                // Remove if this column's header matches any standard field's header
                if (in_array($header, $mappedHeaders)) {
                    unset($mapping[$field]);
                    Log::debug("Removed duplicate custom column '{$field}' (header '{$header}' already mapped to standard field)");
                }
                // Also remove if this column's header contains SKU-like keywords
                if (\Illuminate\Support\Str::contains($header, ['sku', 'part number', 'part no', 'part#', 'p/n', 'mã sản phẩm'])) {
                    unset($mapping[$field]);
                    Log::debug("Removed SKU-like custom column '{$field}' (header '{$header}')");
                }
            }
        }
        
        Log::debug("Auto-detected mapping: " . json_encode($mapping) . " | Warranty cols excluded from price: " . json_encode($warrantyIndices));

        return $mapping;
    }

    /**
     * Kiểm tra sheet có cấu trúc hợp lệ để import không
     */
    private function isValidSheetStructure(array $mapping, array $fileHeaders): bool
    {
        // Phải có cột SKU
        if (!isset($mapping['sku'])) {
            Log::debug("Sheet rejected: Missing SKU mapping");
            return false;
        }

        // Header của cột SKU không được là date, number, hoặc các từ không liên quan
        $skuHeader = strtolower($fileHeaders[$mapping['sku']] ?? '');
        $invalidSkuHeaders = ['date', 'ngày', 'added', 'created', 'updated', 'time', 'id', '#'];

        foreach ($invalidSkuHeaders as $invalid) {
            if (str_contains($skuHeader, $invalid) && !str_contains($skuHeader, 'id') && !str_contains($skuHeader, 'no')) { 
                // Careful with 'id' as it might be 'product id' or 'item id' which are valid
                 if ($invalid == 'id' && (str_contains($skuHeader, 'product') || str_contains($skuHeader, 'part') || str_contains($skuHeader, 'item'))) {
                     continue;
                 }
                 // Careful with '#'
                 if ($invalid == '#' && (str_contains($skuHeader, 'part') || str_contains($skuHeader, 'item'))) {
                     continue;
                 }
                 
                Log::debug("Sheet rejected: SKU header '{$skuHeader}' contains invalid keyword '{$invalid}'");
                return false;
            }
        }

        // Must have at least one price column OR a custom price column
        $hasPrice = isset($mapping['price']) || 
                    isset($mapping['price_1yr']) || 
                    isset($mapping['price_2yr']) ||
                    isset($mapping['price_3yr']) ||
                    isset($mapping['price_4yr']) ||
                    isset($mapping['price_5yr']);
        
        if (!$hasPrice) {
            foreach(array_keys($mapping) as $key) {
                if (str_starts_with($key, 'custom_')) {
                    $hasPrice = true; 
                    break;
                }
            }
        }
        
        if (!$hasPrice) {
            Log::debug("Sheet rejected: No price column mapped");
        }

        return $hasPrice;
    }

    private function parsePrice($value): ?float
    {
        if ($value === null || $value === '')
            return null;
        if (is_numeric($value))
            return (float) $value;

        $str = (string) $value;
        
        // Handle "1.234.567" (VN thousands) -> "1234567"
        // Regex: digit groups separated by dots, no comma
        if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $str)) {
             $str = str_replace('.', '', $str);
             return (float) $str;
        }

        // Handle "1.234.567,89" (VN decimal) -> "1234567.89"
        if (preg_match('/^-?\d{1,3}(\.\d{3})*,\d+$/', $str) || str_contains($str, 'đ') || str_contains(strtolower($str), 'vnd')) {
             // If known VN format or contains VN currency symbol
             // Check if it has dots and commas
             if (str_contains($str, '.') && str_contains($str, ',')) {
                 $lastDot = strrpos($str, '.');
                 $lastComma = strrpos($str, ',');
                 
                 // If comma is after dot (VN way: 1.000,00)
                 if ($lastComma > $lastDot) {
                     $str = str_replace('.', '', $str);
                     $str = str_replace(',', '.', $str);
                 }
             } elseif (str_contains($str, '.')) {
                 // Only dots (1.000.000 đ) -> Remove dots
                 // But be careful of 10.5 đ (unlikely for VND but possible)
                 // VND is usually integer. If > 3 chars after dot, or multiple dots, treat as thousands
                 if (substr_count($str, '.') > 1 || preg_match('/\.\d{3}$/', $str)) {
                     $str = str_replace('.', '', $str);
                 }
             } elseif (str_contains($str, ',')) {
                 // Only comma (1000,00 đ or 1,000 đ)
                 // Hard to detect. Assume comma is decimal if using 'đ'?
                 // Actually standard VND doesn't use decimals often.
                 // Let's rely on standard cleaner for the rest.
             }
        }

        // Final cleanup: Remove currency symbols and non-numeric characters (except . and -)
        $cleaned = preg_replace('/[^0-9.-]/', '', $str);
        
        // Safety check: if stripping too much (e.g. "ATP800" -> "800"), it's probably not a pure price
        // If letters were present and digits are small compared to total length, return null
        if (preg_match('/[a-zA-Z]/', $str) && strlen($cleaned) < (strlen($str) * 0.4)) {
            return null;
        }

        if (substr_count($cleaned, '.') > 1) {
            $cleaned = str_replace('.', '', $cleaned);
        }
        
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Parse PDF file (Bitdefender format primarily)
     */
    private function parsePdf(string $path): array
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($path);
            $text = $pdf->getText();
            $lines = explode("\n", $text);

            // Detect Currency from full text
            $currencyHint = null;
            $lowerText = mb_strtolower($text);
            if (str_contains($lowerText, 'vnd') || str_contains($lowerText, 'vnđ') || str_contains($lowerText, 'đ')) {
                $currencyHint = 'VND';
            } elseif (str_contains($lowerText, 'usd') || str_contains($text, '$')) {
                // Warning: $ might be common, check strictly
                if (str_contains($lowerText, 'usd')) {
                     $currencyHint = 'USD';
                }
            }
            
            $previewData = [];
            // Header Row for flattened data
            $previewData[] = ['Product Name', 'Users Range', 'SKU', 'List Price', 'Period'];

            // Regex for Bitdefender Row:
            // Product Name | Range | SKU 1 | Price 1 | SKU 2 | Price 2 | SKU 3 | Price 3
            // Example: GravityZone... | 0003 - 0014 | 2999... | 500,000 | ... | ...
            // Pattern: start with text, then digit range, then Alphanumeric SKU, then Number, etc.
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Attempt to match the Bitdefender Matrix pattern
                // Capture: 1=Name, 2=Range, 3=SKU1, 4=Price1, 5=SKU2, 6=Price2, 7=SKU3, 8=Price3
                if (preg_match('/^(.+?)\s+(\d{4}\s*-\s*\d{4})\s+([A-Z0-9]+)\s+([\d,.]+)\s+([A-Z0-9]+)\s+([\d,.]+)\s+([A-Z0-9]+)\s+([\d,.]+)$/', $line, $matches)) {
                    $name = trim($matches[1]);
                    $range = trim($matches[2]);
                    
                    // 1 Year
                    $previewData[] = [$name, $range, $matches[3], $this->parsePrice($matches[4]), '1 Year'];
                    // 2 Years
                    $previewData[] = [$name, $range, $matches[5], $this->parsePrice($matches[6]), '2 Years'];
                    // 3 Years
                    $previewData[] = [$name, $range, $matches[7], $this->parsePrice($matches[8]), '3 Years'];
                }
            }

            // If no data found via regex, maybe fallback to raw lines?
            // For now, let's assume the regex works for the user's specific file.
            // If previewData has only header, maybe add raw lines for debugging?
            if (count($previewData) <= 1) {
                 $previewData[] = ['Debug: No pattern matched. Raw lines below:'];
                 foreach (array_slice($lines, 0, 20) as $l) {
                     $previewData[] = [$l];
                 }
            }

            return [[
                'index' => 0,
                'name' => 'PDF Data (Flattened)',
                'rowCount' => count($previewData),
                'columnCount' => 5,
                'preview' => $previewData, // Return ALL data for PDF since we parsed it all
                'currency_hint' => $currencyHint
            ]];

        } catch (\Exception $e) {
            Log::error('PDF Parse Error: ' . $e->getMessage());
            return [[
                'index' => 0,
                'name' => 'Error',
                'rowCount' => 0,
                'columnCount' => 0,
                'preview' => [['Error parsing PDF: ' . $e->getMessage()]],
            ]];
        }
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
            'price_field' => 'required|string',
            'update_mode' => 'required|in:all,empty_only',
        ]);

        DB::beginTransaction();
        try {
            $priceField = $request->price_field;
            if ($priceField === 'price') $priceField = 'list_price'; // Fix mapping
            $updateMode = $request->update_mode;
            $exchangeRate = $supplierPriceList->exchange_rate ?: 1;
            $isCustomColumn = str_starts_with($priceField, 'custom_');

            // Get all items from this price list that have the selected price field
            if ($isCustomColumn) {
                $priceListItems = $supplierPriceList->items()
                    ->whereNotNull('extra_data')
                    ->get()
                    ->filter(function ($item) use ($priceField) {
                        $price = $item->extra_data['prices'][$priceField] ?? null;
                        return $price !== null && $price > 0;
                    });
            } else {
                $priceListItems = $supplierPriceList->items()
                    ->whereNotNull($priceField)
                    ->where($priceField, '>', 0)
                    ->get();
            }

            $updated = 0;
            $skipped = 0;
            $notFound = 0;

            foreach ($priceListItems as $priceItem) {
                $cleanSku = $this->cleanSku($priceItem->sku);
                if (empty($cleanSku))
                    continue;

                // Get the price value
                if ($isCustomColumn) {
                    $priceValue = $priceItem->extra_data['prices'][$priceField] ?? null;
                } else {
                    $priceValue = $priceItem->$priceField;
                }

                if (!$priceValue || $priceValue <= 0) {
                    continue;
                }

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
                            'cost_usd' => $priceValue,
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
        if ($priceField === 'price') $priceField = 'list_price'; // Fix mapping
        $updateMode = $request->get('update_mode', 'all');
        $isCustomColumn = str_starts_with($priceField, 'custom_');

        // Get price list items with the selected price
        if ($isCustomColumn) {
            // For custom columns, we need to check extra_data
            $priceListItems = $supplierPriceList->items()
                ->whereNotNull('extra_data')
                ->get()
                ->filter(function ($item) use ($priceField) {
                    $price = $item->extra_data['prices'][$priceField] ?? null;
                    return $price !== null && $price > 0;
                });
        } else {
            // For standard columns
            $priceListItems = $supplierPriceList->items()
                ->whereNotNull($priceField)
                ->where($priceField, '>', 0)
                ->get();
        }

        $preview = [];
        $matchCount = 0;

        foreach ($priceListItems as $priceItem) {
            $cleanSku = $this->cleanSku($priceItem->sku);
            if (empty($cleanSku))
                continue;

            // Get the price value
            if ($isCustomColumn) {
                $priceValue = $priceItem->extra_data['prices'][$priceField] ?? null;
            } else {
                $priceValue = $priceItem->$priceField;
            }

            if (!$priceValue || $priceValue <= 0) {
                continue;
            }

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
                            'new_cost' => $priceValue,
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

    /**
     * Kiểm tra xem có cần auto mapping không
     */
    private function needsAutoMapping(array $sheets): bool
    {
        foreach ($sheets as $sheet) {
            $mapping = $sheet['mapping'] ?? [];
            // Nếu không có SKU hoặc không có cột giá nào, cần auto mapping
            if (empty($mapping['sku'])) {
                return true;
            }
            $hasPriceColumn = isset($mapping['price']) || 
                            isset($mapping['price_1yr']) || 
                            isset($mapping['price_2yr']) ||
                            isset($mapping['price_3yr']) ||
                            isset($mapping['price_4yr']) ||
                            isset($mapping['price_5yr']);
            
            // Kiểm tra custom price columns
            foreach ($mapping as $key => $val) {
                if (str_starts_with($key, 'custom_') && $val !== '') {
                    $hasPriceColumn = true;
                    break;
                }
            }
            
            if (!$hasPriceColumn) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tự động phát hiện sheets và mapping
     */
    private function autoDetectSheetsAndMapping($spreadsheet, array $preset): array
    {
        $validSheets = [];
        
        $allSheets = [];
        foreach ($spreadsheet->getAllSheets() as $worksheet) {
            $allSheets[] = $worksheet->getTitle();
        }
        Log::info("Default AutoDetect found sheets: " . implode(', ', $allSheets));

        foreach ($spreadsheet->getAllSheets() as $index => $worksheet) {
            $sheetName = $worksheet->getTitle();
            Log::info("Processing Sheet [{$index}]: {$sheetName}");
            
            // Bỏ qua các sheet trong skip list
            $shouldSkip = false;
            if (!empty($preset['skipSheets'])) {
                foreach ($preset['skipSheets'] as $skipPattern) {
                    if (stripos($sheetName, $skipPattern) !== false) {
                        $shouldSkip = true;
                        break;
                    }
                }
            }
            
            if ($shouldSkip) {
                Log::info("Skipping sheet: {$sheetName}");
                continue;
            }
            
            $highestColumn = $worksheet->getHighestColumn();
            $highestRow = $worksheet->getHighestRow();
            
            // Với file có merged cells phức tạp, getHighestColumn() có thể không chính xác
            // Hãy scan tối đa 50 cột đầu tiên
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            $maxColToScan = max($highestColIndex, 50); // Ít nhất scan 50 cột
            $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($maxColToScan);
            
            // Tìm header row
            $headerRow = $this->findHeaderRow($worksheet, 1, $highestColumn, $preset);
            
            if ($headerRow === null) {
                Log::info("No valid header found in sheet: {$sheetName}");
                continue;
            }
            
            // Lấy headers - support multi-row headers (e.g. QNAP has headers spanning 2 rows)
            $fileHeaders = [];
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            $maxColToProcess = min($highestColIndex, 100);
            
            // Row-level decision: is headerRow+1 a header continuation or data?
            $isMultiRowHeader = $this->isNextRowHeaderContinuation($worksheet, $headerRow, $maxColToProcess, $highestRow);
            
            for ($colIdx = 1; $colIdx <= $maxColToProcess; $colIdx++) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $value = $this->getCellValue($worksheet, $col, $headerRow);
                $trimVal = trim($value ?? '');
                
                // Multi-row header: combine with next row ONLY if row-level check confirmed it
                if ($isMultiRowHeader && $headerRow < $highestRow) {
                    $nextRowValue = $this->getCellValue($worksheet, $col, $headerRow + 1);
                    $nextVal = trim($nextRowValue ?? '');
                    
                    if ($nextVal !== '') {
                        $trimVal = ($trimVal !== '') ? $trimVal . ' ' . $nextVal : $nextVal;
                    }
                }
                
                // Clean newlines in header values (e.g. "Purchase\nPrice\n(Ex-work TW)" -> "Purchase Price (Ex-work TW)")
                $trimVal = preg_replace('/[\r\n]+/', ' ', $trimVal);
                $trimVal = preg_replace('/\s+/', ' ', $trimVal);
                $trimVal = trim($trimVal);
                
                if ($trimVal !== '') {
                    $fileHeaders[$colIdx - 1] = strtolower($trimVal);
                }
            }
            
            Log::debug("Sheet '{$sheetName}' merged headers: " . json_encode($fileHeaders, JSON_UNESCAPED_UNICODE));
            
            // Read sample data rows for validation scoring
            $rowSamples = [];
            $dataStartRow = $isMultiRowHeader ? $headerRow + 2 : $headerRow + 1;

            for ($r = $dataStartRow; $r < min($dataStartRow + 20, $highestRow + 1); $r++) {
                $rowVals = [];
                foreach ($fileHeaders as $colIdx => $headerName) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                    $val = $this->getCellValue($worksheet, $col, $r);
                    $rowVals[$colIdx] = $val;
                }
                $rowSamples[] = $rowVals;
            }

            // Auto detect mapping with data validation
            $mapping = $this->autoDetectMappingFromHeaders($fileHeaders, $preset, $rowSamples);
            // Kiểm tra xem có mapping hợp lệ không (phải có SKU và ít nhất 1 cột giá)
            // IMPORTANT: Use !isset() instead of empty() because index 0 is a valid column
            // empty(0) returns true in PHP, which would reject SKU in column A
            if (!isset($mapping['sku'])) {
                Log::info("No SKU column found in sheet: {$sheetName}");
                continue;
            }
            
            $hasPriceColumn = false;
            foreach ($mapping as $key => $val) {
                if (str_contains($key, 'price') && $val !== null) {
                    $hasPriceColumn = true;
                    break;
                }
            }
            
            if (!$hasPriceColumn) {
                Log::info("No price column found in sheet: {$sheetName}");
                continue;
            }
            
            // Sheet hợp lệ
            $validSheets[] = [
                'index' => $index,
                'name' => $sheetName,
                'header_row' => $headerRow,
                'mapping' => $mapping
            ];
            
            Log::info("Valid sheet found: {$sheetName} with " . count($mapping) . " mapped columns");
        }
        
        return $validSheets;
    }

    private function importPdf(Request $request, string $fullPath)
    {
        try {
            // Parse PDF
            $pdfSheets = $this->parsePdf($fullPath);
            if (empty($pdfSheets)) {
                throw new \Exception("Không thể đọc dữ liệu từ file PDF");
            }
            
            // Get data from first sheet (flattened)
            $rows = $pdfSheets[0]['preview'];
            // Remove header row
            array_shift($rows);

            // Create/Update Price List
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

            $itemsCreated = 0;
            $itemsUpdated = 0;
            $itemsSkipped = 0;

            foreach ($rows as $row) {
                // Row structure: [Name(0), Range(1), SKU(2), Price(3), Period(4)]
                $sku = trim($row[2] ?? '');
                if (empty($sku)) {
                    $itemsSkipped++;
                    continue;
                }

                $productName = trim($row[0] ?? '');
                $range = trim($row[1] ?? '');
                $listPrice = $row[3]; 
                $period = $row[4] ?? '';

                // Add Period/Range to name/desc
                $fullName = $productName;
                if ($range) $fullName .= " ({$range})";
                if ($period) $fullName .= " - {$period}";

                $itemData = [
                    'supplier_price_list_id' => $priceList->id,
                    'sku' => mb_substr($sku, 0, 255),
                    'product_name' => mb_substr($fullName, 0, 65000),
                    'description' => "Range: {$range}, Period: {$period}",
                    'list_price' => $listPrice,
                    'source_sheet' => 'PDF Data',
                ];

                if ($request->import_mode === 'update') {
                    $existing = SupplierPriceListItem::where('supplier_price_list_id', $priceList->id)
                        ->where('sku', $sku)
                        ->first();

                    if ($existing) {
                        $existing->update($itemData);
                        $itemsUpdated++;
                    } else {
                        SupplierPriceListItem::create($itemData);
                        $itemsCreated++;
                    }
                } else {
                    SupplierPriceListItem::create($itemData);
                    $itemsCreated++;
                }
            }
            
            // Xóa file tạm
            Storage::delete($request->temp_path);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Import PDF thành công!',
                'price_list_id' => $priceList->id,
                'import_log' => [
                    'imported_at' => now()->toISOString(),
                    'sheets' => [['name' => 'PDF Data', 'rows_processed' => count($rows), 'items_created' => $itemsCreated, 'items_updated' => $itemsUpdated, 'items_skipped' => $itemsSkipped]],
                    'total_items' => count($rows),
                    'created' => $itemsCreated,
                    'updated' => $itemsUpdated,
                    'skipped' => $itemsSkipped,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing PDF price list: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi import PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}
