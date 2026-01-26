<?php

namespace App\Http\Controllers;

use App\Models\SupplierQuotation;
use App\Models\SupplierQuotationItem;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupplierQuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierQuotation::with(['supplier', 'purchaseRequest', 'items']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $quotations = $query->orderBy('created_at', 'desc')->paginate(15);
        $suppliers = Supplier::orderBy('name')->get();
        $purchaseRequests = PurchaseRequest::whereIn('status', ['sent', 'received'])->get();

        return view('supplier-quotations.index', compact('quotations', 'suppliers', 'purchaseRequests'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $purchaseRequests = PurchaseRequest::whereIn('status', ['sent', 'received'])->with('items')->get();
        $code = SupplierQuotation::generateCode();

        $selectedRequest = null;
        if ($request->filled('purchase_request_id')) {
            $selectedRequest = PurchaseRequest::with('items')->find($request->purchase_request_id);
        }

        return view('supplier-quotations.create', compact('suppliers', 'products', 'purchaseRequests', 'code', 'selectedRequest'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:supplier_quotations,code',
            'supplier_id' => 'required|exists:suppliers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after:quotation_date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $quotation = SupplierQuotation::create([
                'code' => $validated['code'],
                'purchase_request_id' => $request->purchase_request_id,
                'supplier_id' => $validated['supplier_id'],
                'quotation_date' => $validated['quotation_date'],
                'valid_until' => $validated['valid_until'],
                'delivery_days' => $request->delivery_days,
                'payment_terms' => $request->payment_terms,
                'warranty' => $request->warranty,
                'discount_percent' => $request->discount_percent ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'vat_percent' => $request->vat_percent ?? 10,
                'note' => $request->note,
                'status' => 'pending',
            ]);

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $total = $item['quantity'] * $item['unit_price'];
                $subtotal += $total;

                $quotation->items()->create([
                    'product_name' => $item['product_name'],
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Cái',
                    'unit_price' => $item['unit_price'],
                    'total' => $total,
                    'note' => $item['note'] ?? null,
                ]);
            }

            // Tính tổng
            $discountAmount = $subtotal * ($quotation->discount_percent / 100);
            $afterDiscount = $subtotal - $discountAmount;
            $beforeVat = $afterDiscount + $quotation->shipping_cost;
            $vatAmount = $beforeVat * ($quotation->vat_percent / 100);

            $quotation->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total' => $beforeVat + $vatAmount,
            ]);

            // Cập nhật trạng thái yêu cầu báo giá
            if ($request->purchase_request_id) {
                $purchaseRequest = PurchaseRequest::find($request->purchase_request_id);
                if ($purchaseRequest && $purchaseRequest->status === 'sent') {
                    $purchaseRequest->update(['status' => 'received']);
                }
            }

            DB::commit();
            return redirect()->route('supplier-quotations.index')
                ->with('success', 'Đã nhập báo giá từ NCC thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }


    public function show(SupplierQuotation $supplierQuotation)
    {
        $supplierQuotation->load(['supplier', 'purchaseRequest', 'items.product']);

        // Lấy các báo giá khác cùng yêu cầu để so sánh
        $compareQuotations = [];
        if ($supplierQuotation->purchase_request_id) {
            $compareQuotations = SupplierQuotation::where('purchase_request_id', $supplierQuotation->purchase_request_id)
                ->where('id', '!=', $supplierQuotation->id)
                ->with('supplier')
                ->get();
        }

        return view('supplier-quotations.show', compact('supplierQuotation', 'compareQuotations'));
    }

    public function edit(SupplierQuotation $supplierQuotation)
    {
        if ($supplierQuotation->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể sửa báo giá đang chờ xử lý!');
        }

        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $supplierQuotation->load(['items']);

        return view('supplier-quotations.edit', compact('supplierQuotation', 'suppliers', 'products'));
    }

    public function update(Request $request, SupplierQuotation $supplierQuotation)
    {
        if ($supplierQuotation->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể sửa báo giá đang chờ xử lý!');
        }

        $validated = $request->validate([
            'valid_until' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $supplierQuotation->update([
                'valid_until' => $validated['valid_until'],
                'delivery_days' => $request->delivery_days,
                'payment_terms' => $request->payment_terms,
                'warranty' => $request->warranty,
                'discount_percent' => $request->discount_percent ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'vat_percent' => $request->vat_percent ?? 10,
                'note' => $request->note,
            ]);

            $supplierQuotation->items()->delete();
            $subtotal = 0;

            foreach ($validated['items'] as $item) {
                $total = $item['quantity'] * $item['unit_price'];
                $subtotal += $total;

                $supplierQuotation->items()->create([
                    'product_name' => $item['product_name'],
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Cái',
                    'unit_price' => $item['unit_price'],
                    'total' => $total,
                ]);
            }

            $discountAmount = $subtotal * ($supplierQuotation->discount_percent / 100);
            $afterDiscount = $subtotal - $discountAmount;
            $beforeVat = $afterDiscount + $supplierQuotation->shipping_cost;
            $vatAmount = $beforeVat * ($supplierQuotation->vat_percent / 100);

            $supplierQuotation->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total' => $beforeVat + $vatAmount,
            ]);

            DB::commit();
            return redirect()->route('supplier-quotations.index')
                ->with('success', 'Đã cập nhật báo giá!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(SupplierQuotation $supplierQuotation)
    {
        if ($supplierQuotation->status === 'selected') {
            return back()->with('error', 'Không thể xóa báo giá đã được chọn!');
        }

        $supplierQuotation->delete();
        return redirect()->route('supplier-quotations.index')
            ->with('success', 'Đã xóa báo giá!');
    }

    public function select(SupplierQuotation $supplierQuotation)
    {
        if ($supplierQuotation->status !== 'pending') {
            return back()->with('error', 'Báo giá này không thể chọn!');
        }

        DB::beginTransaction();
        try {
            // Từ chối các báo giá khác cùng yêu cầu
            if ($supplierQuotation->purchase_request_id) {
                SupplierQuotation::where('purchase_request_id', $supplierQuotation->purchase_request_id)
                    ->where('id', '!=', $supplierQuotation->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'rejected']);
            }

            $supplierQuotation->update(['status' => 'selected']);

            DB::commit();
            return redirect()->route('purchase-orders.create', ['quotation_id' => $supplierQuotation->id])
                ->with('success', 'Đã chọn báo giá! Tiếp tục tạo đơn mua hàng.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function reject(SupplierQuotation $supplierQuotation)
    {
        if ($supplierQuotation->status !== 'pending') {
            return back()->with('error', 'Báo giá này không thể từ chối!');
        }

        $supplierQuotation->update(['status' => 'rejected']);
        return back()->with('success', 'Đã từ chối báo giá!');
    }

    public function compare(Request $request)
    {
        $ids = $request->input('ids', []);
        if (count($ids) < 2) {
            return back()->with('error', 'Vui lòng chọn ít nhất 2 báo giá để so sánh!');
        }

        $quotations = SupplierQuotation::whereIn('id', $ids)
            ->with(['supplier', 'items'])
            ->get();

        return view('supplier-quotations.compare', compact('quotations'));
    }

    // --- Import Excel Methods ---

    public function showImportForm()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $purchaseRequests = PurchaseRequest::whereIn('status', ['sent', 'received'])->get();
        $code = SupplierQuotation::generateCode();
        return view('supplier-quotations.import', compact('suppliers', 'purchaseRequests', 'code'));
    }

    public function analyzeFile(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:10240']);
        try {
            $file = $request->file('file');
            $tempPath = $file->store('temp/quotation-imports');
            $fullPath = Storage::path($tempPath);

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $sheets = [];
            foreach ($spreadsheet->getSheetNames() as $index => $sheetName) {
                $worksheet = $spreadsheet->getSheet($index);
                $sheets[] = [
                    'index' => $index,
                    'name' => $sheetName,
                    'rowCount' => $worksheet->getHighestRow(),
                ];
            }

            // Find sheet with max rows to suggest
            $maxRows = 0;
            $suggestedSheetIndex = 0;
            foreach ($sheets as $fileSheet) {
                if ($fileSheet['rowCount'] > $maxRows) {
                    $maxRows = $fileSheet['rowCount'];
                    $suggestedSheetIndex = $fileSheet['index'];
                }
            }

            return response()->json([
                'success' => true,
                'fileName' => $file->getClientOriginalName(),
                'tempPath' => $tempPath,
                'sheets' => $sheets,
                'suggestedSheetIndex' => $suggestedSheetIndex
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function getSheetData(Request $request)
    {
        try {
            $fullPath = Storage::path($request->temp_path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getSheet($request->sheet_index);

            $headerRow = $request->header_row;

            // Auto-detect Header Row if requested or if current looks empty
            if ($request->boolean('auto_detect_header')) {
                $scores = [];
                $keywords = ['sku', 'code', 'mã', 'product', 'name', 'tên', 'qty', 'quantity', 'sl', 'price', 'giá', 'amount', 'total'];

                // Scan first 100 rows
                for ($r = 1; $r <= min(100, $worksheet->getHighestRow()); $r++) {
                    $score = 0;
                    $matchedTypes = [];

                    // robust loop over columns 1 to max 50
                    $maxColIndex = min(50, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($worksheet->getHighestColumn()));

                    for ($colIdx = 1; $colIdx <= $maxColIndex; $colIdx++) {
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                        $cell = $worksheet->getCell($col . $r);

                        // Get value robustly
                        try {
                            $val = $cell->getCalculatedValue();
                        } catch (\Exception $e) {
                            $val = $cell->getValue();
                        }

                        // Handle Merge: if empty, look up master
                        if (($val === null || $val === '') && $cell->isInMergeRange()) {
                            try {
                                $range = $cell->getMergeRange();
                                $firstCell = explode(':', $range)[0];
                                $val = $worksheet->getCell($firstCell)->getValue(); // fallback to raw value for master
                            } catch (\Exception $e) {
                            }
                        }

                        // Convert to simple string
                        if (is_object($val))
                            $val = (string) $val;
                        $val = mb_strtolower(trim($val), 'UTF-8');

                        if (empty($val))
                            continue;

                        // --- Keyword Matching ---
                        // SKU
                        if (str_contains($val, 'sku') || str_contains($val, 'mã') || str_contains($val, 'code') || str_contains($val, 'part') || str_contains($val, 'model'))
                            $matchedTypes['sku'] = true;

                        // NAME
                        if (str_contains($val, 'tên') || str_contains($val, 'name') || str_contains($val, 'product') || str_contains($val, 'mô tả') || str_contains($val, 'diễn giải') || str_contains($val, 'kích thước') || str_contains($val, 'ghi chú'))
                            $matchedTypes['name'] = true;

                        // QTY
                        if (str_contains($val, 'sl') || str_contains($val, 'số lượng') || str_contains($val, 'qty') || str_contains($val, 'vol'))
                            $matchedTypes['qty'] = true;

                        // PRICE
                        // "báo giá" usually in title, ignore it. But "đơn giá" is good.
                        if ((str_contains($val, 'giá') && !str_contains($val, 'báo giá')) || str_contains($val, 'price') || str_contains($val, 'amount') || str_contains($val, 'việt nam đồng') || str_contains($val, 'vnd') || str_contains($val, 'thành tiền'))
                            $matchedTypes['price'] = true;

                        // OTHER (STT, Unit)
                        if (str_contains($val, 'stt') || str_contains($val, 'no.') || str_contains($val, 'unit') || str_contains($val, 'đvt') || str_contains($val, 'đơn vị'))
                            $matchedTypes['other'] = true;
                    }

                    // Score is number of distinct types found
                    $score = count($matchedTypes);
                    $scores[$r] = $score;
                }

                // Pick row with highest score
                arsort($scores);
                $bestRow = array_key_first($scores);

                // Must find at least 2 relevant columns to be considered a header
                if ($scores[$bestRow] >= 2) {
                    $headerRow = $bestRow;
                }
            }

            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            $highestRow = min($headerRow + 10, $worksheet->getHighestRow());

            $headers = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $cell = $worksheet->getCell($colLetter . $headerRow);
                $val = $cell->getValue();

                // Handle Merged Cells for Header
                if (($val === null || $val === '') && $cell->isInMergeRange()) {
                    try {
                        $range = $cell->getMergeRange();
                        $firstCell = explode(':', $range)[0];
                        $val = $worksheet->getCell($firstCell)->getValue();
                    } catch (\Exception $e) {
                    }
                }

                $headers[] = ['index' => $col - 1, 'column' => $colLetter, 'name' => $val];
            }

            $preview = [];
            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $cell = $worksheet->getCell($colLetter . $row);

                    // Handle Merged Cells for Data
                    if ($cell->isInMergeRange()) {
                        try {
                            $range = $cell->getMergeRange();
                            $firstCell = explode(':', $range)[0];
                            $cell = $worksheet->getCell($firstCell);
                        } catch (\Exception $e) {
                        }
                    }

                    // Use getFormattedValue for Preview to ensure we see exactly what is in Excel
                    $val = $cell->getFormattedValue();
                    $rowData[] = $val;
                }
                $preview[] = $rowData;
            }



            return response()->json([
                'success' => true,
                'headers' => $headers,
                'preview' => $preview,
                'detectedHeaderRow' => $headerRow
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function autoDetectMapping(Request $request)
    {
        $headers = $request->input('headers', []);
        $mapping = [];

        $patterns = [

            'sku' => ['sku', 'part number', 'part no', 'part #', 'code', 'mã', 'ma ', 'ma.', 'model', 'ký hiệu', 'ky hieu', 'ref', 'item no', 'article'],
            'product_name' => ['product', 'name', 'tên', 'ten ', 'ten.', 'description', 'mô tả', 'mo ta', 'diễn giải', 'dien giai', 'hạng mục', 'hang muc', 'chi tiết', 'chi tiet', 'spec', 'hàng', 'hang ', 'vật tư', 'vat tu', 'item'],
            'quantity' => ['qty', 'quantity', 'số lượng', 'so luong', 'sl', 'vol', 'khối lượng', 'khoi luong'],
            'unit_price' => ['unit price', 'đơn giá', 'don gia', 'giá', 'gia ', 'gia.', 'price', 'rate'],
            'unit' => ['unit', 'uom', 'đvt', 'dvt', 'đơn vị', 'don vi', 'tính'],
        ];

        foreach ($patterns as $field => $keywords) {
            foreach ($headers as $header) {
                $headerName = mb_strtolower(trim($header['name'] ?? ''));

                // Skip common false positives
                if ($field === 'unit_price' && (str_contains($headerName, 'báo giá') || str_contains($headerName, 'list') || $headerName === 'giá trị'))
                    continue;
                if ($field === 'product_name' && (str_contains($headerName, 'người') || str_contains($headerName, 'công ty')))
                    continue;

                foreach ($keywords as $keyword) {
                    // Use word boundary check for short keywords to avoid subsquence matching failures? 
                    // Simple str_contains is okay but "báo giá" contains "giá". Handled above.
                    if (str_contains($headerName, $keyword)) {
                        $mapping[$field] = $header['index'];
                        break 2; // Move to next field
                    }
                }
            }
        }

        return response()->json(['success' => true, 'mapping' => $mapping]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required',
            'temp_path' => 'required',
            'mapping' => 'required|array',
            'mapping.unit_price' => 'required',
            'sheet_indices' => 'required|array', // Validate array of sheets
        ]);

        DB::beginTransaction();
        try {
            $fullPath = Storage::path($request->temp_path);
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);

            // Create Quotation
            $quotation = SupplierQuotation::create([
                'code' => $request->code,
                'supplier_id' => $request->supplier_id,
                'purchase_request_id' => $request->purchase_request_id,
                'quotation_date' => $request->quotation_date,
                'valid_until' => $request->valid_until,
                'discount_percent' => $request->discount_percent ?? 0,
                'vat_percent' => $request->vat_percent ?? 10,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'status' => 'pending',
            ]);

            $subtotal = 0;
            $itemsCount = 0;
            $headerRow = $request->header_row;
            $mapping = $request->mapping;

            // Process each selected sheet
            foreach ($request->sheet_indices as $sheetIndex) {
                $worksheet = $spreadsheet->getSheet($sheetIndex);
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                    $rowData = [];
                    for ($col = 1; $col <= $highestColumnIndex; $col++) {
                        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                        $cell = $worksheet->getCell($colLetter . $row);

                        // Handle Merged Cells
                        if ($cell->isInMergeRange()) {
                            try {
                                $range = $cell->getMergeRange();
                                $firstCell = explode(':', $range)[0];
                                $cell = $worksheet->getCell($firstCell);
                            } catch (\Exception $e) {
                            }
                        }

                        try {
                            $val = $cell->getCalculatedValue();
                        } catch (\Exception $e) {
                            $val = $cell->getFormattedValue();
                        }
                        if ($val === null)
                            $val = '';
                        $rowData[] = $val;
                    }

                    $sku = isset($mapping['sku']) ? ($rowData[$mapping['sku']] ?? null) : null;
                    $name = isset($mapping['product_name']) ? ($rowData[$mapping['product_name']] ?? null) : null;

                    // Auto-generate if missing
                    if (empty($name) && empty($sku)) {
                        if ((isset($mapping['unit_price']) && floatval(preg_replace('/[^0-9.]/', '', $rowData[$mapping['unit_price']] ?? 0)) > 0)) {
                            $name = 'Sản phẩm ' . Str::upper(Str::random(6));
                        } else {
                            continue;
                        }
                    }

                    if (empty($sku)) {
                        $sku = 'GEN-' . Str::upper(Str::random(8));
                    }

                    $productName = !empty($name) ? $name : $sku;

                    $price = isset($mapping['unit_price']) ? floatval(preg_replace('/[^0-9.]/', '', $rowData[$mapping['unit_price']] ?? 0)) : 0;

                    if ($price <= 0)
                        continue;

                    // Skip junk rows defined by keywords
                    $junkKeywords = ['tổng cộng', 'total', 'cộng', 'ghi chú', 'lưu ý', 'bao gồm', 'giao hàng', 'thanh toán', 'hiệu lực', 'ký tên', 'xác nhận'];
                    $lowerName = mb_strtolower($productName, 'UTF-8');
                    foreach ($junkKeywords as $junk) {
                        if (str_starts_with($lowerName, $junk)) {
                            continue 2;
                        }
                    }

                    $qty = isset($mapping['quantity']) ? floatval(preg_replace('/[^0-9.]/', '', $rowData[$mapping['quantity']] ?? 1)) : 1;
                    if ($qty <= 0)
                        $qty = 1;

                    $itemTotal = $qty * $price;
                    $subtotal += $itemTotal;

                    $quotation->items()->create([
                        'product_name' => $productName,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'unit' => isset($mapping['unit']) ? ($rowData[$mapping['unit']] ?? 'Cái') : 'Cái',
                        'total' => $itemTotal,
                        'note' => isset($mapping['note']) ? ($rowData[$mapping['note']] ?? '') : null,
                    ]);
                    $itemsCount++;
                }
            }


            // Calculate totals
            $discountAmount = $subtotal * ($quotation->discount_percent / 100);
            $afterDiscount = $subtotal - $discountAmount;
            $beforeVat = $afterDiscount + $quotation->shipping_cost;
            $vatAmount = $beforeVat * ($quotation->vat_percent / 100);

            $quotation->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'vat_amount' => $vatAmount,
                'total' => $beforeVat + $vatAmount,
            ]);

            // Update Purchase Request status if linked
            if ($request->purchase_request_id) {
                $pr = PurchaseRequest::find($request->purchase_request_id);
                if ($pr && $pr->status === 'sent') {
                    $pr->update(['status' => 'received']);
                }
            }

            // Cleanup
            Storage::delete($request->temp_path);

            DB::commit();
            return response()->json([
                'success' => true,
                'redirect' => route('supplier-quotations.show', $quotation)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
