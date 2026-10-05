<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductItem;
use App\Imports\ProductsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ExportService;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    /**
     * Display a listing of products with search and filter functionality.
     * Requirements: 1.1, 1.2, 2.3, 6.1, 6.2
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query();

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by category (single letter A-Z)
        if ($request->filled('category')) {
            $query->filterByCategory($request->category);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->filterBySupplier($request->supplier_id);
        }

        // Apply Excel table column filters & sorting
        $query = \App\Services\TableColumnFilterService::apply($query, $request, [
            'code' => 'products.code',
            'name' => 'products.name',
            'brand' => 'products.brand',
            'category' => 'products.category',
            'unit' => 'products.unit',
            'description' => 'products.description',
        ]);

        if (!$request->filled('col_sort')) {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->with(['supplierPriceListItems.priceList.supplier'])
            ->paginate(10)
            ->withQueryString();

        // Get suppliers who have price lists for the tab system
        $suppliersWithProducts = \App\Models\Supplier::whereHas('supplierPriceLists')
            ->orderBy('name')
            ->get();
        
        // Calculate dynamic product counts for each supplier tab
        foreach ($suppliersWithProducts as $supplier) {
            $supplier->dynamic_products_count = Product::filterBySupplier($supplier->id)->count();
        }
        
        $currentSupplierId = $request->get('supplier_id');

        // Get categories for filter dropdown
        $categories = Product::CATEGORIES;

        return view('products.index', compact('products', 'categories', 'suppliersWithProducts', 'currentSupplierId'));
    }

    /**
     * Show the form for creating a new product.
     * Requirements: 1.3, 2.1
     */
    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = Product::CATEGORIES;
        $suppliers = \App\Models\Supplier::orderBy('name')->get();
        return view('products.create', compact('categories', 'suppliers'));
    }

    /**
     * Store a newly created product in storage.
     * Requirements: 1.3, 2.2
     */
    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        // Normalize code before validation
        if ($request->has('code')) {
            $request->merge(['code' => strtoupper(trim($request->code))]);
        }

        // Validation - only basic fields
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
            'name' => ['required', 'string', 'max:2000'],
            'brand' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'size:1', 'regex:/^[A-Z]$/'],
            'unit' => ['required', 'string', 'max:50'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'description' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Sản phẩm đã được tạo thành công.');
    }

    /**
     * Display the specified product with its items.
     * Requirements: 6.3, 6.4
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);

        $this->authorize('view', $product);

        $items = $product->items()
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return view('products.show', compact('product', 'items'));
    }

    /**
     * Show the form for editing the specified product.
     * Requirements: 1.3
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $categories = Product::CATEGORIES;
        $suppliers = \App\Models\Supplier::orderBy('name')->get();

        return view('products.edit', compact('product', 'categories', 'suppliers'));
    }

    /**
     * Update the specified product in storage.
     * Requirements: 1.3, 2.2
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        // Normalize code before validation
        if ($request->has('code')) {
            $request->merge(['code' => strtoupper(trim($request->code))]);
        }

        // Validation with unique rule ignoring current record
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('products')->ignore($id)],
            'name' => ['required', 'string', 'max:2000'],
            'brand' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'size:1', 'regex:/^[A-Z]$/'],
            'unit' => ['required', 'string', 'max:50'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'description' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Sản phẩm đã được cập nhật thành công.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('delete', $product);

        $usage = $product->getUsageLocations();
        if (!empty($usage)) {
            $usedIn = implode(', ', array_unique($usage));
            return redirect()->back()
                ->with('error', "Không thể xóa sản phẩm \"{$product->code}\" vì đang được sử dụng trong: {$usedIn}.");
        }

        $code = $product->code;
        $product->delete();

        return redirect()->back()
            ->with('success', "Sản phẩm [{$code}] đã được xóa thành công.");
    }

    /**
     * Remove multiple products from storage after checking usage across all modules.
     */
    public function bulkDelete(Request $request)
    {
        $this->authorize('deleteAny', Product::class);

        $productIds = $request->input('product_ids', []);

        if (is_string($productIds)) {
            $productIds = explode(',', $productIds);
        }

        $productIds = array_values(array_filter(array_map('intval', (array) $productIds)));

        if (empty($productIds)) {
            return redirect()->back()->with('warning', 'Vui lòng chọn ít nhất một sản phẩm để xóa.');
        }

        $products = Product::whereIn('id', $productIds)->get();
        if ($products->isEmpty()) {
            return redirect()->back()->with('error', 'Không tìm thấy sản phẩm nào được chọn.');
        }

        // Check usage in all modules
        $usageMap = Product::checkProductsUsage($products->pluck('id')->toArray());

        $deletedCount = 0;
        $deletedCodes = [];
        $failedProducts = []; // ['code' => ..., 'name' => ..., 'reasons' => [...]]

        DB::beginTransaction();
        try {
            foreach ($products as $product) {
                $usage = $usageMap[$product->id] ?? [];
                if (!empty($usage)) {
                    $failedProducts[] = [
                        'code' => $product->code,
                        'name' => $product->name,
                        'reasons' => array_unique($usage),
                    ];
                } else {
                    $deletedCodes[] = $product->code;
                    $product->delete();
                    $deletedCount++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Có lỗi xảy ra khi xóa sản phẩm: ' . $e->getMessage());
        }

        // Response cases
        if ($deletedCount > 0 && empty($failedProducts)) {
            return redirect()->back()
                ->with('success', "Đã xóa thành công {$deletedCount} sản phẩm.");
        }

        if ($deletedCount === 0 && !empty($failedProducts)) {
            $failedSummary = collect($failedProducts)->take(8)->map(function ($item) {
                return "• <strong>{$item['code']}</strong>: " . implode(', ', $item['reasons']);
            })->implode('<br>');

            if (count($failedProducts) > 8) {
                $failedSummary .= '<br>• ... và ' . (count($failedProducts) - 8) . ' sản phẩm khác.';
            }

            return redirect()->back()
                ->with('error', "Không thể xóa " . count($failedProducts) . " sản phẩm đã chọn vì đang được sử dụng trong các module khác:<br>" . $failedSummary);
        }

        // Partial deletion
        $failedSummary = collect($failedProducts)->take(8)->map(function ($item) {
            return "• <strong>{$item['code']}</strong>: " . implode(', ', $item['reasons']);
        })->implode('<br>');

        if (count($failedProducts) > 8) {
            $failedSummary .= '<br>• ... và ' . (count($failedProducts) - 8) . ' sản phẩm khác.';
        }

        $warningMessage = "Đã xóa thành công <strong>{$deletedCount}</strong> sản phẩm.<br>Không thể xóa <strong>" . count($failedProducts) . "</strong> sản phẩm do đang được sử dụng:<br>" . $failedSummary;

        return redirect()->back()
            ->with('warning', $warningMessage);
    }

    /**
     * Get product items (API endpoint)
     * Requirements: 6.4
     */
    public function items($id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('view', $product);

        $items = $product->items()
            ->with('warehouse')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'product' => $product,
            'items' => $items,
            'total_quantity' => $product->total_quantity,
            'in_stock_quantity' => $product->in_stock_quantity,
        ]);
    }

    /**
     * Export products to Excel
     */
    public function export(Request $request, ExportService $exportService)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query();

        // Apply filters if present
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category')) {
            $query->filterByCategory($request->category);
        }

        $products = $query->get();

        // Generate Excel file
        $filepath = $exportService->exportProducts($products);

        return response()->download($filepath)->deleteFileAfterSend(true);
    }

    /**
     * Download import template
     */
    public function importTemplate()
    {
        $this->authorize('import', Product::class);

        $filepath = ProductsImport::generateTemplate();
        return response()->download($filepath, 'mau-import-san-pham.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Import products from Excel
     */
    public function import(Request $request)
    {
        $this->authorize('import', Product::class);

        ini_set('memory_limit', '1024M'); // Increased further for 25k rows
        set_time_limit(0); // No limit for import

        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240',
        ]);

        $import = new ProductsImport();
        Excel::import($import, $request->file('file'));

        $errors = $import->getErrors();
        if (!empty($errors)) {
            return back()->with('error', implode('<br>', $errors));
        }

        $imported = $import->getImported();
        $updated = $import->getUpdated();

        $message = "Import thành công! Tạo mới: {$imported}, Cập nhật: {$updated}";

        $warnings = $import->getWarnings();
        if (!empty($warnings)) {
            // Limit warnings shown to first 10 to avoid huge messages
            $shown = array_slice($warnings, 0, 10);
            $remaining = count($warnings) - count($shown);
            $warningText = implode('<br>', $shown);
            if ($remaining > 0) {
                $warningText .= "<br>... và {$remaining} cảnh báo khác";
            }
            return back()->with('success', $message)->with('warning', $warningText);
        }

        return back()->with('success', $message);
    }

    public function ajaxSearch(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }

        $productsQuery = Product::search($q)
            ->with(['supplierPriceListItems.priceList'])
            ->select('id', 'code', 'name', 'brand', 'unit', 'warranty_months', 'description');

        if ($request->filled('warehouse_id')) {
            $warehouseId = (int) $request->warehouse_id;
            $productsQuery->whereHas('items', function ($items) use ($warehouseId) {
                $items->where('warehouse_id', $warehouseId)
                    ->where('status', ProductItem::STATUS_IN_STOCK)
                    ->where('quantity', '>', 0);
            });
        }

        $products = $productsQuery->orderBy('code')->limit(30)->get();

        return response()->json($products->map(function ($product) {
            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'brand' => $product->brand,
                'unit' => $product->unit,
                'warranty_months' => $product->warranty_months,
                'cost' => $product->calculated_cost,
                'price' => $product->calculated_selling_price,
                'description' => $product->description,
            ];
        }));
    }

    /**
     * API for searching products (AJAX)
     */
    public function apiSearch(Request $request)
    {
        $q = $request->get('q');
        $page = $request->get('page', 1);
        $limit = !empty($q) ? 20 : 10;
        $offset = ($page - 1) * $limit;

        $query = Product::query();

        if (!empty($q)) {
            $query->search($q);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $baseProducts = $query->with(['supplierPriceListItems.priceList'])
            ->withCount([
                'items as liquidation_count' => function ($query) {
                    $query->where('status', \App\Models\ProductItem::STATUS_LIQUIDATION);
                }
            ])
            ->offset($offset)
            ->limit($limit)
            ->get();

        $products = collect();
        foreach ($baseProducts as $product) {
            $suggestedPrice = $product->calculated_selling_price;

            // Add normal product
            $products->push([
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'brand' => $product->brand,
                'price' => $suggestedPrice,
                'cost' => $product->calculated_cost,
                'warranty_months' => $product->warranty_months,
                'is_liquidation' => 0,
                'liquidation_count' => $product->liquidation_count
            ]);

            // Add liquidation product if available
            if ($product->liquidation_count > 0) {
                $products->push([
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name . ' - Hàng thanh lý',
                    'brand' => $product->brand,
                    'price' => 0,
                    'warranty_months' => 0,
                    'is_liquidation' => 1,
                    'liquidation_count' => $product->liquidation_count
                ]);
            }
        }

        return response()->json($products);
    }

    /**
     * Search products with available inventory or held stock
     */
    /**
     * Search products with available inventory or held stock
     */
    public function apiSearchAvailableStock(Request $request)
    {
        $q = $request->get('q');
        $currentUser = auth()->user();
        $currentUserName = $currentUser ? trim($currentUser->name) : null;

        if (!$currentUser || !$currentUserName) {
            return response()->json([]);
        }

        $query = Product::query();

        if ($q) {
            $query->search($q);
        } else {
            $query->orderBy('name');
        }

        // Only load products that have in_stock items held by the current logged-in user
        $query->whereHas('items', function ($sq) use ($currentUserName) {
            $sq->where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
               ->where('quantity', '>', 0)
               ->where('borrower', $currentUserName);
        });

        $products = $query->with([
            'items' => function ($sq) use ($currentUserName) {
                $sq->where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
                   ->where('quantity', '>', 0)
                   ->where('borrower', $currentUserName)
                   ->with('warehouse');
            },
            'supplierPriceListItems.priceList'
        ])
        ->limit(40)
        ->get()
        ->map(function ($product) {
            $myHeldItems = $product->items;
            $myHeld = (int) $myHeldItems->sum('quantity');

            // Detail string by warehouse
            $whGroups = $myHeldItems->groupBy(fn($it) => $it->warehouse->name ?? 'Kho');
            $whParts = [];
            foreach ($whGroups as $whName => $groupItems) {
                $whParts[] = $whName . ': ' . $groupItems->sum('quantity');
            }
            $whDetail = implode(', ', $whParts);

            $sellingPrice = $product->calculated_selling_price ?: ($product->price ?? 0);
            $costPrice = $product->calculated_cost ?: ($product->cost ?? 0);

            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'unit' => $product->unit ?? 'Cái',
                'price' => (float) $sellingPrice,
                'cost' => (float) $costPrice,
                'warranty_months' => $product->warranty_months ?? 12,
                'in_stock_quantity' => $myHeld,
                'my_held_quantity' => $myHeld,
                'unallocated_quantity' => 0,
                'held_by_others_quantity' => 0,
                'warehouses_detail' => $whDetail ?: 'Kho: ' . $myHeld,
                'holding_summary' => "Bạn đang giữ: {$myHeld}",
                'is_from_stock' => 1,
            ];
        })
        ->filter(fn($p) => $p['in_stock_quantity'] > 0)
        ->values();

        return response()->json($products);
    }
}
