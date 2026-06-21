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
        $this->authorize('viewAny', \App\Models\Supplier::class);

        $query = \App\Models\Supplier::query();

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

        // Date constraints
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $suppliers = $query->with('contacts')->orderBy('created_at', 'desc')->paginate(10);

        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new supplier.
     * Requirements: 2.2
     */
    public function create()
    {
        $this->authorize('create', \App\Models\Supplier::class);

        // Auto-generate next supplier code
        $lastSupplier = \App\Models\Supplier::where('code', 'regexp', '^NCC[0-9]{4}$')
            ->orderByRaw('CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC')
            ->first();
            
        if ($lastSupplier) {
            $lastNumber = intval(substr($lastSupplier->code, 3));
            $nextCode = 'NCC' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // Check if any NCC% exists regardless of format to be safer
            $anyNCC = \App\Models\Supplier::where('code', 'like', 'NCC%')->count();
            if ($anyNCC > 0) {
                // If there are other NCC codes, just use total count + 1 to keep it sequential
                $nextCode = 'NCC' . str_pad($anyNCC + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $nextCode = 'NCC0001';
            }
        }

        return view('suppliers.create', compact('nextCode'));
    }

    /**
     * Store a newly created supplier in storage.
     * Requirements: 2.3, 2.4
     */
    public function store(Request $request)
    {
        $this->authorize('create', \App\Models\Supplier::class);

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
            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.first_name' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.last_name' => ['nullable', 'string', 'max:255'],
            'contacts.*.title' => ['nullable', 'string', 'max:50'],
            'contacts.*.position' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.phone' => ['required_with:contacts', 'string', 'max:20'],
            'contacts.*.email' => ['required_with:contacts', 'email', 'max:255'],
            'contacts.*.is_primary' => ['boolean'],
            'contacts.*.note' => ['nullable', 'string'],
        ], [
            'code.required' => 'Mã nhà cung cấp là bắt buộc.',
            'code.string' => 'Mã nhà cung cấp phải là chuỗi ký tự.',
            'code.max' => 'Mã nhà cung cấp không được vượt quá 50 ký tự.',
            'code.unique' => 'Mã nhà cung cấp này đã tồn tại trong hệ thống.',
            'name.required' => 'Tên nhà cung cấp là bắt buộc.',
            'name.string' => 'Tên nhà cung cấp phải là chuỗi ký tự.',
            'name.max' => 'Tên nhà cung cấp không được vượt quá 255 ký tự.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',
            'tax_code.string' => 'Mã số thuế phải là chuỗi ký tự.',
            'tax_code.max' => 'Mã số thuế không được vượt quá 50 ký tự.',
            'website.url' => 'Website không hợp lệ.',
            'website.max' => 'Website không được vượt quá 255 ký tự.',
            'contact_person.string' => 'Người liên hệ phải là chuỗi ký tự.',
            'contact_person.max' => 'Người liên hệ không được vượt quá 255 ký tự.',
            'payment_terms.integer' => 'Điều khoản thanh toán (số ngày) phải là số nguyên.',
            'payment_terms.min' => 'Điều khoản thanh toán không được nhỏ hơn 0.',
            'product_type.string' => 'Loại sản phẩm cung cấp phải là chuỗi ký tự.',
            'product_type.max' => 'Loại sản phẩm cung cấp không được vượt quá 255 ký tự.',
            'base_discount.numeric' => 'Chiết khấu cơ bản phải là một số.',
            'base_discount.min' => 'Chiết khấu cơ bản không được nhỏ hơn 0.',
            'base_discount.max' => 'Chiết khấu cơ bản không được vượt quá 100.',
            'volume_discount.numeric' => 'Chiết khấu sản lượng phải là một số.',
            'volume_discount.min' => 'Chiết khấu sản lượng không được nhỏ hơn 0.',
            'volume_discount.max' => 'Chiết khấu sản lượng không được vượt quá 100.',
            'volume_threshold.integer' => 'Ngưỡng sản lượng phải là số nguyên.',
            'volume_threshold.min' => 'Ngưỡng sản lượng không được nhỏ hơn 0.',
            'early_payment_discount.numeric' => 'Chiết khấu thanh toán sớm phải là một số.',
            'early_payment_discount.min' => 'Chiết khấu thanh toán sớm không được nhỏ hơn 0.',
            'early_payment_discount.max' => 'Chiết khấu thanh toán sớm không được vượt quá 100.',
            'early_payment_days.integer' => 'Số ngày thanh toán sớm phải là số nguyên.',
            'early_payment_days.min' => 'Số ngày thanh toán sớm không được nhỏ hơn 0.',
            'special_discount.numeric' => 'Chiết khấu đặc biệt phải là một số.',
            'special_discount.min' => 'Chiết khấu đặc biệt không được nhỏ hơn 0.',
            'special_discount.max' => 'Chiết khấu đặc biệt không được vượt quá 100.',
            'special_discount_condition.string' => 'Điều kiện chiết khấu đặc biệt phải là chuỗi ký tự.',
            'note.string' => 'Ghi chú phải là chuỗi ký tự.',
            'contacts.*.first_name.required_with' => 'Tên người liên hệ là bắt buộc.',
            'contacts.*.position.required_with' => 'Chức vụ người liên hệ là bắt buộc.',
            'contacts.*.phone.required_with' => 'Số điện thoại người liên hệ là bắt buộc.',
            'contacts.*.email.required_with' => 'Email người liên hệ là bắt buộc.',
            'contacts.*.email.email' => 'Email người liên hệ không hợp lệ.',
        ]);

        $numericFields = [
            'payment_terms', 'base_discount', 'volume_discount', 'volume_threshold',
            'early_payment_discount', 'early_payment_days', 'special_discount'
        ];
        foreach ($numericFields as $field) {
            if (!isset($validated[$field]) || $validated[$field] === '') {
                $validated[$field] = 0;
            }
        }

        DB::transaction(function() use ($validated) {
            $supplier = \App\Models\Supplier::create($validated);
            
            if (!empty($validated['contacts'])) {
                foreach ($validated['contacts'] as $contactData) {
                    $supplier->contacts()->create($contactData);
                }
            }
        });

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

        $this->authorize('view', $supplier);

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
        $supplier = \App\Models\Supplier::with('contacts')->findOrFail($id);
        $this->authorize('update', $supplier);

        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified supplier in storage.
     * Requirements: 2.6
     */
    public function update(Request $request, $id)
    {
        $supplier = \App\Models\Supplier::findOrFail($id);
        $this->authorize('update', $supplier);

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
            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.first_name' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.last_name' => ['nullable', 'string', 'max:255'],
            'contacts.*.title' => ['nullable', 'string', 'max:50'],
            'contacts.*.position' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.phone' => ['required_with:contacts', 'string', 'max:20'],
            'contacts.*.email' => ['required_with:contacts', 'email', 'max:255'],
            'contacts.*.is_primary' => ['boolean'],
            'contacts.*.note' => ['nullable', 'string'],
        ], [
            'code.required' => 'Mã nhà cung cấp là bắt buộc.',
            'code.string' => 'Mã nhà cung cấp phải là chuỗi ký tự.',
            'code.max' => 'Mã nhà cung cấp không được vượt quá 50 ký tự.',
            'code.unique' => 'Mã nhà cung cấp này đã tồn tại trong hệ thống.',
            'name.required' => 'Tên nhà cung cấp là bắt buộc.',
            'name.string' => 'Tên nhà cung cấp phải là chuỗi ký tự.',
            'name.max' => 'Tên nhà cung cấp không được vượt quá 255 ký tự.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',
            'tax_code.string' => 'Mã số thuế phải là chuỗi ký tự.',
            'tax_code.max' => 'Mã số thuế không được vượt quá 50 ký tự.',
            'website.url' => 'Website không hợp lệ.',
            'website.max' => 'Website không được vượt quá 255 ký tự.',
            'contact_person.string' => 'Người liên hệ phải là chuỗi ký tự.',
            'contact_person.max' => 'Người liên hệ không được vượt quá 255 ký tự.',
            'payment_terms.integer' => 'Điều khoản thanh toán (số ngày) phải là số nguyên.',
            'payment_terms.min' => 'Điều khoản thanh toán không được nhỏ hơn 0.',
            'product_type.string' => 'Loại sản phẩm cung cấp phải là chuỗi ký tự.',
            'product_type.max' => 'Loại sản phẩm cung cấp không được vượt quá 255 ký tự.',
            'base_discount.numeric' => 'Chiết khấu cơ bản phải là một số.',
            'base_discount.min' => 'Chiết khấu cơ bản không được nhỏ hơn 0.',
            'base_discount.max' => 'Chiết khấu cơ bản không được vượt quá 100.',
            'volume_discount.numeric' => 'Chiết khấu sản lượng phải là một số.',
            'volume_discount.min' => 'Chiết khấu sản lượng không được nhỏ hơn 0.',
            'volume_discount.max' => 'Chiết khấu sản lượng không được vượt quá 100.',
            'volume_threshold.integer' => 'Ngưỡng sản lượng phải là số nguyên.',
            'volume_threshold.min' => 'Ngưỡng sản lượng không được nhỏ hơn 0.',
            'early_payment_discount.numeric' => 'Chiết khấu thanh toán sớm phải là một số.',
            'early_payment_discount.min' => 'Chiết khấu thanh toán sớm không được nhỏ hơn 0.',
            'early_payment_discount.max' => 'Chiết khấu thanh toán sớm không được vượt quá 100.',
            'early_payment_days.integer' => 'Số ngày thanh toán sớm phải là số nguyên.',
            'early_payment_days.min' => 'Số ngày thanh toán sớm không được nhỏ hơn 0.',
            'special_discount.numeric' => 'Chiết khấu đặc biệt phải là một số.',
            'special_discount.min' => 'Chiết khấu đặc biệt không được nhỏ hơn 0.',
            'special_discount.max' => 'Chiết khấu đặc biệt không được vượt quá 100.',
            'special_discount_condition.string' => 'Điều kiện chiết khấu đặc biệt phải là chuỗi ký tự.',
            'note.string' => 'Ghi chú phải là chuỗi ký tự.',
            'contacts.*.first_name.required_with' => 'Tên người liên hệ là bắt buộc.',
            'contacts.*.position.required_with' => 'Chức vụ người liên hệ là bắt buộc.',
            'contacts.*.phone.required_with' => 'Số điện thoại người liên hệ là bắt buộc.',
            'contacts.*.email.required_with' => 'Email người liên hệ là bắt buộc.',
            'contacts.*.email.email' => 'Email người liên hệ không hợp lệ.',
        ]);

        $numericFields = [
            'payment_terms', 'base_discount', 'volume_discount', 'volume_threshold',
            'early_payment_discount', 'early_payment_days', 'special_discount'
        ];
        foreach ($numericFields as $field) {
            if (!isset($validated[$field]) || $validated[$field] === '') {
                $validated[$field] = 0;
            }
        }

        DB::transaction(function() use ($validated, $supplier) {
            $supplier->update($validated);
            
            if (isset($validated['contacts'])) {
                $supplier->contacts()->delete();
                foreach ($validated['contacts'] as $contactData) {
                    $supplier->contacts()->create($contactData);
                }
            }
        });

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

        // Convert stdClass to Supplier model for authorization
        $supplierModel = \App\Models\Supplier::findOrFail($id);
        $this->authorize('delete', $supplierModel);

        // Check for related records before deleting
        $hasPurchaseOrders = DB::table('purchase_orders')->where('supplier_id', $id)->exists();
        if ($hasPurchaseOrders) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Không thể xóa nhà cung cấp này vì đang có Đơn đặt hàng liên quan.');
        }

        $hasQuotations = DB::table('supplier_quotations')->where('supplier_id', $id)->exists();
        if ($hasQuotations) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Không thể xóa nhà cung cấp này vì đang có Báo giá liên quan.');
        }

        $hasImports = DB::table('imports')->where('supplier_id', $id)->exists();
        if ($hasImports) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Không thể xóa nhà cung cấp này vì đang có Đơn nhập hàng liên quan.');
        }

        $supplierModel->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Nhà cung cấp đã được xóa thành công.');
    }

    /**
     * Export suppliers to Excel
     * Requirements: 7.1, 7.3, 7.6, 7.7
     */
    public function export(Request $request, ExportService $exportService)
    {
        $this->authorize('viewAny', \App\Models\Supplier::class);

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

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $suppliers = $query->with('contacts')->get();

        $contactsToExport = collect();
        foreach ($suppliers as $supplier) {
            if ($supplier->contacts->isEmpty()) {
                $contactsToExport->push($supplier);
            } else {
                foreach ($supplier->contacts as $contact) {
                    $contactsToExport->push($contact);
                }
            }
        }

        // Generate Excel file (Requirement 7.7)
        $filepath = $exportService->exportSuppliers($contactsToExport);

        return response()->download($filepath)->deleteFileAfterSend(true);
    }

    /**
     * Download import template
     */
    public function importTemplate()
    {
        $this->authorize('create', \App\Models\Supplier::class);

        $filepath = SuppliersImport::generateTemplate();
        return response()->download($filepath, 'mau-import-nha-cung-cap.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Import suppliers from Excel
     */
    public function import(Request $request)
    {
        $this->authorize('create', \App\Models\Supplier::class);

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

    /**
     * Show PO template configuration for a supplier.
     */
    public function poConfig($supplierId)
    {
        $supplier = \App\Models\Supplier::findOrFail($supplierId);
        $this->authorize('update', $supplier);

        $config = $supplier->poConfig ?: new \App\Models\SupplierPoConfig();

        return view('suppliers.po-config', compact('supplier', 'config'));
    }

    /**
     * Update PO template configuration for a supplier.
     */
    public function updatePoConfig(Request $request, $supplierId)
    {
        $supplier = \App\Models\Supplier::findOrFail($supplierId);
        $this->authorize('update', $supplier);

        $data = $request->validate([
            'template_type' => 'required|in:fortinet,sale_contract',
            'seller_name' => 'nullable|string|max:255',
            'seller_address_line1' => 'nullable|string|max:255',
            'seller_address_line2' => 'nullable|string|max:255',
            'seller_tel' => 'nullable|string|max:255',
            'seller_fax' => 'nullable|string|max:255',
            'seller_contact' => 'nullable|string|max:1000',
            'seller_beneficiary' => 'nullable|string|max:255',
            'seller_beneficiary_address' => 'nullable|string|max:500',
            'seller_bank_name' => 'nullable|string|max:255',
            'seller_bank_account' => 'nullable|string|max:255',
            'seller_bank_address_line1' => 'nullable|string|max:255',
            'seller_bank_address_line2' => 'nullable|string|max:255',
            'seller_bank_aba' => 'nullable|string|max:255',
            'seller_swift_code' => 'nullable|string|max:255',
            'port_loading' => 'nullable|string|max:255',
            'port_discharge' => 'nullable|string|max:255',
        ]);

        $supplier->poConfig()->updateOrCreate(['supplier_id' => $supplier->id], $data);

        // Redirect back to supplier show
        return redirect()->route('suppliers.show', $supplier->id)->with('success', 'Cập nhật cấu hình biểu mẫu PO thành công!');
    }

    /**
     * Show PO company configuration (Buyer).
     */
    public function companyConfig()
    {
        $this->authorize('update', \App\Models\Setting::class);

        $config = \App\Models\PoCompanyConfig::getConfig();

        return view('settings.po-company', compact('config'));
    }

    /**
     * Update PO company configuration (Buyer).
     */
    public function updateCompanyConfig(Request $request)
    {
        $this->authorize('update', \App\Models\Setting::class);

        $data = $request->validate([
            'buyer_name' => 'nullable|string|max:255',
            'buyer_address_line1' => 'nullable|string|max:255',
            'buyer_address_line2' => 'nullable|string|max:255',
            'buyer_tel' => 'nullable|string|max:255',
            'buyer_fax' => 'nullable|string|max:255',
            'buyer_contact' => 'nullable|string|max:1000',
            'buyer_bank_account' => 'nullable|string|max:255',
            'buyer_bank_name' => 'nullable|string|max:255',
            'buyer_bank_address_line1' => 'nullable|string|max:255',
            'buyer_bank_address_line2' => 'nullable|string|max:255',
            'buyer_swift_code' => 'nullable|string|max:255',
            
            'ship_to_name' => 'nullable|string|max:255',
            'ship_to_address_line1' => 'nullable|string|max:255',
            'ship_to_address_line2' => 'nullable|string|max:255',
            'ship_to_attn' => 'nullable|string|max:255',
            
            'invoice_to_name' => 'nullable|string|max:255',
            'invoice_to_address_line1' => 'nullable|string|max:255',
            'invoice_to_address_line2' => 'nullable|string|max:255',
            'invoice_to_attn' => 'nullable|string|max:255',
            
            'company_full_name' => 'nullable|string|max:255',
            'hcmc_address' => 'nullable|string|max:500',
            'hanoi_address' => 'nullable|string|max:500',
            'website' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'header_logo' => 'nullable|image|max:2048',
            'header_banner' => 'nullable|image|max:4096',
            
            'signer_name' => 'nullable|string|max:255',
            'signer_title' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('header_logo') && $request->file('header_logo')->isValid()) {
            $logoFile = $request->file('header_logo');
            $logoName = 'po_logo_' . time() . '.' . $logoFile->getClientOriginalExtension();

            if (!is_dir(public_path('uploads/po-logos'))) {
                mkdir(public_path('uploads/po-logos'), 0755, true);
            }

            $logoFile->move(public_path('uploads/po-logos'), $logoName);
            $data['header_logo_path'] = 'uploads/po-logos/' . $logoName;
        }
        unset($data['header_logo']);

        if ($request->hasFile('header_banner') && $request->file('header_banner')->isValid()) {
            $bannerFile = $request->file('header_banner');
            $bannerName = 'po_banner_' . time() . '.' . $bannerFile->getClientOriginalExtension();

            if (!is_dir(public_path('uploads/po-logos'))) {
                mkdir(public_path('uploads/po-logos'), 0755, true);
            }

            $bannerFile->move(public_path('uploads/po-logos'), $bannerName);
            $data['header_banner_path'] = 'uploads/po-logos/' . $bannerName;
        }
        unset($data['header_banner']);

        $config = \App\Models\PoCompanyConfig::getConfig();
        $config->update($data);

        return back()->with('success', 'Cập nhật cấu hình công ty PO thành công!');
    }
}
