<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ExportService;

class ProductController extends Controller
{
    /**
     * Display a listing of products with search and filter functionality.
     * Requirements: 1.1, 1.2, 2.3, 6.1, 6.2
     */
    public function index(Request $request)
    {
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
        $categories = Product::CATEGORIES;
        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created product in storage.
     * Requirements: 1.3, 2.2
     */
    public function store(Request $request)
    {
        // Validation - only basic fields
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'size:1', 'regex:/^[A-Z]$/'],
            'unit' => ['required', 'string', 'max:50'],
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
        $product = Product::with(['items' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     * Requirements: 1.3
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
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

        // Validation with unique rule ignoring current record
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('products')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'size:1', 'regex:/^[A-Z]$/'],
            'unit' => ['required', 'string', 'max:50'],
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
}
