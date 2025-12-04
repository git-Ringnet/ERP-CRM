<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ExportService;

class ProductController extends Controller
{
    /**
     * Display a listing of products with search and filter functionality.
     * Requirements: 4.1, 4.11, 4.12
     */
    public function index(Request $request)
    {
        $query = DB::table('products');

        // Search functionality (Requirement 4.11)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Filter by management_type (Requirement 4.12)
        if ($request->filled('management_type')) {
            $query->where('management_type', $request->management_type);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product.
     * Requirements: 4.2
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created product in storage.
     * Requirements: 4.3, 4.4, 4.5, 4.6
     */
    public function store(Request $request)
    {
        // Validation (Requirement 4.6)
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
            'management_type' => ['required', 'in:normal,serial,lot'],
            'auto_generate_serial' => ['nullable', 'boolean'],
            'serial_prefix' => ['nullable', 'string', 'max:20'],
            'expiry_months' => ['nullable', 'integer', 'min:0'],
            'track_expiry' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        // Convert boolean fields
        $validated['auto_generate_serial'] = $request->has('auto_generate_serial') ? 1 : 0;
        $validated['track_expiry'] = $request->has('track_expiry') ? 1 : 0;

        DB::table('products')->insert(array_merge($validated, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('products.index')
            ->with('success', 'Sản phẩm đã được tạo thành công.');
    }

    /**
     * Display the specified product.
     * Requirements: 4.1
     */
    public function show($id)
    {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            abort(404);
        }

        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     * Requirements: 4.7
     */
    public function edit($id)
    {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            abort(404);
        }

        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified product in storage.
     * Requirements: 4.8
     */
    public function update(Request $request, $id)
    {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            abort(404);
        }

        // Validation with unique rule ignoring current record
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('products')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
            'management_type' => ['required', 'in:normal,serial,lot'],
            'auto_generate_serial' => ['nullable', 'boolean'],
            'serial_prefix' => ['nullable', 'string', 'max:20'],
            'expiry_months' => ['nullable', 'integer', 'min:0'],
            'track_expiry' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        // Convert boolean fields
        $validated['auto_generate_serial'] = $request->has('auto_generate_serial') ? 1 : 0;
        $validated['track_expiry'] = $request->has('track_expiry') ? 1 : 0;

        DB::table('products')->where('id', $id)->update(array_merge($validated, [
            'updated_at' => now(),
        ]));

        return redirect()->route('products.index')
            ->with('success', 'Sản phẩm đã được cập nhật thành công.');
    }

    /**
     * Remove the specified product from storage.
     * Requirements: 4.9, 4.10
     */
    public function destroy($id)
    {
        $product = DB::table('products')->where('id', $id)->first();

        if (!$product) {
            abort(404);
        }

        DB::table('products')->where('id', $id)->delete();

        return redirect()->route('products.index')
            ->with('success', 'Sản phẩm đã được xóa thành công.');
    }

    /**
     * Export products to Excel
     * Requirements: 7.1, 7.5, 7.6, 7.7
     */
    public function export(Request $request, ExportService $exportService)
    {
        $query = DB::table('products');

        // Apply filters if present (Requirement 7.6)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('management_type')) {
            $query->where('management_type', $request->management_type);
        }

        $products = collect($query->get());

        // Generate Excel file (Requirement 7.7)
        $filepath = $exportService->exportProducts($products);

        return response()->download($filepath)->deleteFileAfterSend(true);
    }
}
