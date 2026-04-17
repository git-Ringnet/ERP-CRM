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

        $products = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get categories for filter dropdown
        $categories = Product::CATEGORIES;

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new product.
     * Requirements: 1.3, 2.1
     */
    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = Product::CATEGORIES;
        return view('products.create', compact('categories'));
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

        return view('products.edit', compact('product', 'categories'));
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

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Sản phẩm đã được xóa thành công.');
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
        $this->authorize('create', Product::class);

        $filepath = ProductsImport::generateTemplate();
        return response()->download($filepath, 'mau-import-san-pham.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Import products from Excel
     */
    public function import(Request $request)
    {
        $this->authorize('create', Product::class);

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

        $products = Product::search($q)
            ->with(['supplierPriceListItems.priceList'])
            ->select('id', 'code', 'name', 'unit', 'warranty_months')
            ->orderBy('code')
            ->limit(30)
            ->get();

        return response()->json($products->map(function ($product) {
            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'unit' => $product->unit,
                'warranty_months' => $product->warranty_months,
                'cost' => $product->calculated_cost,
                'price' => $product->calculated_selling_price,
            ];
        }));
    }

    /**
     * API for searching products (AJAX)
     */
    public function apiSearch(Request $request)
    {
        $q = $request->get('q');

        $query = Product::query();

        if (!empty($q)) {
            $query->search($q);
        }

        $baseProducts = $query->with(['supplierPriceListItems.priceList'])
            ->withCount([
                'items as liquidation_count' => function ($query) {
                    $query->where('status', \App\Models\ProductItem::STATUS_LIQUIDATION);
                }
            ])
            ->limit(20)
            ->get();

        $products = collect();
        foreach ($baseProducts as $product) {
            $suggestedPrice = $product->calculated_selling_price;

            // Add normal product
            $products->push([
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'price' => $suggestedPrice,
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
                    'price' => 0,
                    'warranty_months' => 0,
                    'is_liquidation' => 1,
                    'liquidation_count' => $product->liquidation_count
                ]);
            }
        }

        return response()->json($products);
    }
}
