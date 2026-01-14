<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\ExportService;
use App\Imports\SuppliersImport;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers with search functionality.
     * Requirements: 2.1, 2.9
     */
    public function index(Request $request)
    {
        $query = DB::table('suppliers');

        // Search functionality (Requirement 2.9)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new supplier.
     * Requirements: 2.2
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * Store a newly created supplier in storage.
     * Requirements: 2.3, 2.4
     */
    public function store(Request $request)
    {
        // Validation (Requirement 2.4)
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'base_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'volume_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'volume_threshold' => ['nullable', 'integer', 'min:0'],
            'early_payment_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'early_payment_days' => ['nullable', 'integer', 'min:0'],
            'special_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'special_discount_condition' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        DB::table('suppliers')->insert(array_merge($validated, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('suppliers.index')
            ->with('success', 'Nhà cung cấp đã được tạo thành công.');
    }

    /**
     * Display the specified supplier.
     * Requirements: 2.1
     */
    public function show($id)
    {
        $supplier = \App\Models\Supplier::with([
            'purchaseOrders' => fn($q) => $q->latest()->limit(10),
            'supplierQuotations' => fn($q) => $q->latest()->limit(10),
            'supplierPriceLists' => fn($q) => $q->latest()->limit(10),
            'imports' => fn($q) => $q->latest()->limit(10),
        ])->findOrFail($id);

        // Statistics
        $stats = [
            'total_purchase_orders' => $supplier->purchaseOrders()->count(),
            'total_quotations' => $supplier->supplierQuotations()->count(),
            'total_price_lists' => $supplier->supplierPriceLists()->count(),
            'total_imports' => $supplier->imports()->count(),
            'total_purchase_value' => $supplier->purchaseOrders()->sum('total'),
        ];

        return view('suppliers.show', compact('supplier', 'stats'));
    }

    /**
     * Show the form for editing the specified supplier.
     * Requirements: 2.5
     */
    public function edit($id)
    {
        $supplier = DB::table('suppliers')->where('id', $id)->first();

        if (!$supplier) {
            abort(404);
        }

        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified supplier in storage.
     * Requirements: 2.6
     */
    public function update(Request $request, $id)
    {
        $supplier = DB::table('suppliers')->where('id', $id)->first();

        if (!$supplier) {
            abort(404);
        }

        // Validation with unique rule ignoring current record
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('suppliers')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'base_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'volume_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'volume_threshold' => ['nullable', 'integer', 'min:0'],
            'early_payment_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'early_payment_days' => ['nullable', 'integer', 'min:0'],
            'special_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'special_discount_condition' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        DB::table('suppliers')->where('id', $id)->update(array_merge($validated, [
            'updated_at' => now(),
        ]));

        return redirect()->route('suppliers.index')
            ->with('success', 'Nhà cung cấp đã được cập nhật thành công.');
    }

    /**
     * Remove the specified supplier from storage.
     * Requirements: 2.7, 2.8
     */
    public function destroy($id)
    {
        $supplier = DB::table('suppliers')->where('id', $id)->first();

        if (!$supplier) {
            abort(404);
        }

        DB::table('suppliers')->where('id', $id)->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Nhà cung cấp đã được xóa thành công.');
    }

    /**
     * Export suppliers to Excel
     * Requirements: 7.1, 7.3, 7.6, 7.7
     */
    public function export(Request $request, ExportService $exportService)
    {
        $query = DB::table('suppliers');

        // Apply filters if present (Requirement 7.6)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = collect($query->get());

        // Generate Excel file (Requirement 7.7)
        $filepath = $exportService->exportSuppliers($suppliers);

        return response()->download($filepath)->deleteFileAfterSend(true);
    }

    /**
     * Download import template
     */
    public function importTemplate()
    {
        $filepath = SuppliersImport::generateTemplate();
        return response()->download($filepath, 'mau-import-nha-cung-cap.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Import suppliers from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240',
        ]);

        $import = new SuppliersImport();
        Excel::import($import, $request->file('file'));

        $errors = $import->getErrors();
        if (!empty($errors)) {
            return back()->with('error', implode('<br>', $errors));
        }

        $imported = $import->getImported();
        $updated = $import->getUpdated();

        return back()->with('success', "Import thành công! Tạo mới: {$imported}, Cập nhật: {$updated}");
    }
}
