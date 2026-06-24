<?php

namespace App\Http\Controllers;

use App\Exports\QuotationsExport;
use App\Exports\SingleQuotationExport;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SupplierPriceList;
use App\Models\SupplierPriceListItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Currency;
use App\Services\CurrencyService;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    protected CurrencyService $currencyService;
    protected \App\Services\ApprovalService $approvalService;

    public function __construct(CurrencyService $currencyService, \App\Services\ApprovalService $approvalService)
    {
        $this->currencyService = $currencyService;
        $this->approvalService = $approvalService;
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', Quotation::class);

        $query = Quotation::with(['customer', 'creator', 'convertedSale']);

        // Apply data filtering based on permissions
        $user = auth()->user();
        if (!$user->can('view_all_quotations') && !$user->can('view_quotations')) {
            // User only has view_own_quotations permission
            if ($user->can('view_own_quotations')) {
                $query->where('created_by', $user->id);
            } else {
                // User has no permission to view quotations
                abort(403, 'Unauthorized action.');
            }
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $quotations = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('quotations.index', compact('quotations'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Quotation::class);

        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $code = $this->generateCode();

        $prefill = [
            'customer_id' => $request->get('customer_id'),
            'title' => $request->get('title'),
        ];

        $currencies = $this->currencyService->getActiveCurrencies();
        $baseCurrencyId = Currency::getBaseCurrencyId();

        $defaultDisclaimer = Quotation::defaultDisclaimer();

        return view('quotations.create', compact('customers', 'products', 'code', 'prefill', 'currencies', 'baseCurrencyId', 'defaultDisclaimer'));
    }

    private function generateCode(): string
    {
        $date = date('Ymd');
        $prefix = 'QT-' . $date . '-';

        $last = Quotation::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        $number = $last ? intval(substr($last->code, -4)) + 1 : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Quotation::class);

        // Sanitize formatted prices (strip comma separators) before validation
        if ($request->has('products')) {
            $products = $request->input('products');
            foreach ($products as $index => $item) {
                if (isset($item['price'])) {
                    $products[$index]['price'] = str_replace(',', '', $item['price']);
                }
            }
            $request->merge(['products' => $products]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:quotations,code'],
            'customer_id' => ['required', 'exists:customers,id'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:date'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'delivery_time' => ['nullable', 'string'],
            'note' => ['nullable', 'array'],
            'note.*' => ['nullable', 'string', 'max:2000'],
            'disclaimer' => ['nullable', 'array'],
            'disclaimer.*' => ['nullable', 'string', 'max:2000'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_name' => ['nullable', 'string'],
            'products.*.description' => ['nullable', 'string'],
            'products.*.product_id' => ['nullable', 'string'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
            'products.*.vat' => ['nullable', 'numeric', 'min:-1'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'custom_columns' => ['nullable', 'array'],
            'custom_columns.*' => ['required', 'string', 'max:255'],
            'products.*.custom_fields' => ['nullable', 'array'],
            'products.*.custom_fields.*' => ['nullable', 'string', 'max:2000'],
        ], [
            'valid_until.after_or_equal' => 'Hạn báo giá phải sau hoặc bằng ngày tạo.',
            'code.unique' => 'Mã báo giá đã tồn tại.',
            'code.required' => 'Vui lòng nhập mã báo giá.',
            'customer_id.required' => 'Vui lòng chọn khách hàng.',
            'contact_id.required' => 'Vui lòng chọn người phụ trách (P.I.C).',
            'title.required' => 'Vui lòng nhập tiêu đề.',
            'products.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'products.*.product_name.required' => 'Vui lòng nhập tên sản phẩm.',
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::find($validated['customer_id']);

            $subtotal = 0;
            $totalVatAmount = 0;
            foreach ($validated['products'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['price'];
                $subtotal += $itemSubtotal;
                
                $itemVat = $item['vat'] ?? 0;
                $effectiveVat = $itemVat < 0 ? 0 : $itemVat;
                $itemDiscount = $itemSubtotal * ($validated['discount'] ?? 0) / 100;
                $itemBaseForVat = $itemSubtotal - $itemDiscount;
                $itemVatAmount = round($itemBaseForVat * $effectiveVat / 100, 2);
                $totalVatAmount += $itemVatAmount;
            }

            $discountAmount = round($subtotal * ($validated['discount'] ?? 0) / 100, 2);
            $total = round($subtotal - $discountAmount + $totalVatAmount, 2);

            $quotation = Quotation::create([
                'code' => $validated['code'],
                'customer_id' => $validated['customer_id'],
                'contact_id' => $validated['contact_id'],
                'customer_name' => $customer->name,
                'title' => $validated['title'],
                'date' => $validated['date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $this->currencyService->toBase($subtotal, $validated['exchange_rate'] ?? 1)
                    : $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'vat' => 0,
                'vat_amount' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $this->currencyService->toBase($totalVatAmount, $validated['exchange_rate'] ?? 1)
                    : $totalVatAmount,
                'total' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $this->currencyService->toBase($total, $validated['exchange_rate'] ?? 1)
                    : $total,
                'total_foreign' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $total
                    : null,
                'currency_id' => $validated['currency_id'] ?? Currency::getBaseCurrencyId(),
                'payment_terms' => $validated['payment_terms'] ?? null,
                'delivery_time' => $validated['delivery_time'] ?? null,
                'note' => !empty($validated['note']) ? json_encode(array_values(array_filter($validated['note'], fn($v) => trim($v) !== ''))) : null,
                'disclaimer' => !empty($validated['disclaimer']) ? json_encode(array_values(array_filter($validated['disclaimer'], fn($v) => trim($v) !== ''))) : null,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'custom_columns' => $validated['custom_columns'] ?? null,
            ]);

            foreach ($validated['products'] as $item) {
                $productId = null;
                $productName = $item['product_name'] ?? '';
                $productCode = null;

                $syncKey = !empty($item['product_id']) ? $item['product_id'] : $item['product_name'];
                if (!empty($syncKey)) {
                    $product = $this->getOrSyncProduct($syncKey, $item['product_name']);
                    if ($product) {
                        $productId = $product->id;
                        $productCode = $product->code;
                        // For products from catalog: product_name = product name, auto-fill if empty
                        if (empty($productName)) {
                            $productName = $product->name;
                        }
                    }
                }

                $itemSubtotal = $item['quantity'] * $item['price'];
                $itemDiscount = $itemSubtotal * ($validated['discount'] ?? 0) / 100;
                $itemBaseForVat = $itemSubtotal - $itemDiscount;
                $itemVat = $item['vat'] ?? 0;
                $effectiveVat = $itemVat < 0 ? 0 : $itemVat;
                $itemVatAmount = round($itemBaseForVat * $effectiveVat / 100, 2);

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'description' => $item['description'] ?? null,
                    'product_code' => $productCode,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'vat' => $item['vat'] ?? 0,
                    'vat_amount' => $itemVatAmount,
                    'total' => $itemSubtotal,
                    'custom_fields' => $item['custom_fields'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('quotations.index')
                ->with('success', 'Báo giá đã được tạo thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Quotation $quotation)
    {
        // Return 404 instead of 403 if user lacks permission to prevent information disclosure
        if (!auth()->user()->can('view', $quotation)) {
            abort(404);
        }

        $quotation->load('items', 'customer');
        $companySettings = Setting::where('group', 'company')->pluck('value', 'key');
        
        return view('quotations.show', compact('quotation', 'companySettings'));
    }

    public function edit(Quotation $quotation)
    {
        $this->authorize('update', $quotation);

        if (!in_array($quotation->status, ['draft', 'rejected'])) {
            return back()->with('error', 'Chỉ có thể sửa báo giá ở trạng thái Nháp hoặc Từ chối.');
        }

        $quotation->load('items');
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $currencies = $this->currencyService->getActiveCurrencies();
        $baseCurrencyId = Currency::getBaseCurrencyId();

        return view('quotations.edit', compact('quotation', 'customers', 'products', 'currencies', 'baseCurrencyId'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        $this->authorize('update', $quotation);

        if (!in_array($quotation->status, ['draft', 'rejected'])) {
            return back()->with('error', 'Chỉ có thể sửa báo giá ở trạng thái Nháp hoặc Từ chối.');
        }

        // Sanitize formatted prices (strip comma separators) before validation
        if ($request->has('products')) {
            $products = $request->input('products');
            foreach ($products as $index => $item) {
                if (isset($item['price'])) {
                    $products[$index]['price'] = str_replace(',', '', $item['price']);
                }
            }
            $request->merge(['products' => $products]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('quotations')->ignore($quotation->id)],
            'customer_id' => ['required', 'exists:customers,id'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:date'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'delivery_time' => ['nullable', 'string'],
            'note' => ['nullable', 'array'],
            'note.*' => ['nullable', 'string', 'max:2000'],
            'disclaimer' => ['nullable', 'array'],
            'disclaimer.*' => ['nullable', 'string', 'max:2000'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_name' => ['nullable', 'string'],
            'products.*.description' => ['nullable', 'string'],
            'products.*.product_id' => ['nullable', 'string'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
            'products.*.vat' => ['nullable', 'numeric', 'min:-1'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'custom_columns' => ['nullable', 'array'],
            'custom_columns.*' => ['required', 'string', 'max:255'],
            'products.*.custom_fields' => ['nullable', 'array'],
            'products.*.custom_fields.*' => ['nullable', 'string', 'max:2000'],
        ], [
            'valid_until.after_or_equal' => 'Hạn báo giá phải sau hoặc bằng ngày tạo.',
            'code.unique' => 'Mã báo giá đã tồn tại.',
            'code.required' => 'Vui lòng nhập mã báo giá.',
            'customer_id.required' => 'Vui lòng chọn khách hàng.',
            'contact_id.required' => 'Vui lòng chọn người phụ trách (P.I.C).',
            'title.required' => 'Vui lòng nhập tiêu đề.',
            'products.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::find($validated['customer_id']);

            $subtotal = 0;
            $totalVatAmount = 0;
            foreach ($validated['products'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['price'];
                $subtotal += $itemSubtotal;
                
                $itemVat = $item['vat'] ?? 0;
                $effectiveVat = $itemVat < 0 ? 0 : $itemVat;
                $itemDiscount = $itemSubtotal * ($validated['discount'] ?? 0) / 100;
                $itemBaseForVat = $itemSubtotal - $itemDiscount;
                $itemVatAmount = round($itemBaseForVat * $effectiveVat / 100, 2);
                $totalVatAmount += $itemVatAmount;
            }

            $discountAmount = round($subtotal * ($validated['discount'] ?? 0) / 100, 2);
            $total = round($subtotal - $discountAmount + $totalVatAmount, 2);

            $quotation->update([
                'code' => $validated['code'],
                'customer_id' => $validated['customer_id'],
                'contact_id' => $validated['contact_id'],
                'customer_name' => $customer->name,
                'title' => $validated['title'],
                'date' => $validated['date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $this->currencyService->toBase($subtotal, $validated['exchange_rate'] ?? 1)
                    : $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'vat' => 0,
                'vat_amount' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $this->currencyService->toBase($totalVatAmount, $validated['exchange_rate'] ?? 1)
                    : $totalVatAmount,
                'total' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $this->currencyService->toBase($total, $validated['exchange_rate'] ?? 1)
                    : $total,
                'total_foreign' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $total
                    : null,
                'currency_id' => $validated['currency_id'] ?? Currency::getBaseCurrencyId(),
                'exchange_rate' => $validated['exchange_rate'] ?? 1,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'delivery_time' => $validated['delivery_time'] ?? null,
                'note' => !empty($validated['note']) ? json_encode(array_values(array_filter($validated['note'], fn($v) => trim($v) !== ''))) : null,
                'disclaimer' => !empty($validated['disclaimer']) ? json_encode(array_values(array_filter($validated['disclaimer'], fn($v) => trim($v) !== ''))) : null,
                'status' => 'draft',
                'custom_columns' => $validated['custom_columns'] ?? null,
            ]);

            $quotation->items()->delete();

            foreach ($validated['products'] as $item) {
                $productId = null;
                $productName = $item['product_name'] ?? '';
                $productCode = null;

                $syncKey = !empty($item['product_id']) ? $item['product_id'] : $item['product_name'];
                if (!empty($syncKey)) {
                    $product = $this->getOrSyncProduct($syncKey, $item['product_name']);
                    if ($product) {
                        $productId = $product->id;
                        $productCode = $product->code;
                        if (empty($productName)) {
                            $productName = $product->name;
                        }
                    }
                }

                $itemSubtotal = $item['quantity'] * $item['price'];
                $itemDiscount = $itemSubtotal * ($validated['discount'] ?? 0) / 100;
                $itemBaseForVat = $itemSubtotal - $itemDiscount;
                $itemVat = $item['vat'] ?? 0;
                $effectiveVat = $itemVat < 0 ? 0 : $itemVat;
                $itemVatAmount = round($itemBaseForVat * $effectiveVat / 100, 2);

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'description' => $item['description'] ?? null,
                    'product_code' => $productCode,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'vat' => $item['vat'] ?? 0,
                    'vat_amount' => $itemVatAmount,
                    'total' => $itemSubtotal,
                    'custom_fields' => $item['custom_fields'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('quotations.index')
                ->with('success', 'Báo giá đã được cập nhật.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Quotation $quotation)
    {
        $this->authorize('delete', $quotation);

        if (!$quotation->canBeDeleted()) {
            return back()->with('error', "Không thể xóa báo giá ở trạng thái {$quotation->status_label}.");
        }

        DB::beginTransaction();
        try {
            $quotation->items()->delete();
            $quotation->delete();
            DB::commit();

            return redirect()->route('quotations.index')
                ->with('success', 'Báo giá đã được xóa.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Enhanced search for Quotation - Search both Products and Master Catalog
     */
    public function searchCatalog(Request $request)
    {
        $search = $request->get('search') ?? $request->get('q');

        // 1. Search in local Products
        $productQuery = Product::query();
        if (!empty($search)) {
            $productQuery->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        $products = $productQuery->limit(15)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => 'p-' . $p->id,
                    'text' => $p->code,
                    'sku' => $p->code,
                    'name' => $p->name,
                    'description' => $p->description,
                    'price' => $p->calculated_selling_price ?: 0,
                    'unit' => $p->unit,
                    'type' => 'product',
                    'original_id' => $p->id
                ];
            });

        // 2. Search in Active Price Lists (Catalog)
        // Only include items that don't match existing product codes to avoid duplicates
        $productCodes = $products->pluck('sku')->toArray();

        $catalogQuery = SupplierPriceListItem::whereHas('priceList', function ($q) {
                $q->where('is_active', true);
            });
        if (!empty($search)) {
            $catalogQuery->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%");
            });
        }
        $catalogItems = $catalogQuery->whereNotIn('sku', $productCodes) 
            ->limit(15)
            ->get()
            ->map(function ($item) {
                $pl = $item->priceList;
                // Use primary price column instead of best_price
                $primaryPrice = $pl->getPrimaryPriceForItem($item);
                $calculated = $primaryPrice > 0 ? $pl->calculateFinalPrice($primaryPrice) : null;
                return [
                    'id' => 'c-' . $item->id,
                    'text' => $item->sku,
                    'sku' => $item->sku,
                    'name' => $item->product_name,
                    'description' => $item->description,
                    'price' => $calculated ? $calculated['final_price_vnd'] : 0,
                    'unit' => $item->unit ?: 'Bộ',
                    'type' => 'catalog',
                    'original_id' => $item->id,
                    'is_catalog' => true
                ];
            });

        return response()->json($products->concat($catalogItems));
    }


    /**
     * Convert quotation to sale order
     */
    public function convertToSale(Quotation $quotation)
    {
        $this->authorize('update', $quotation);

        if (!$quotation->canConvertToSale()) {
            return back()->with('error', 'Không thể chuyển báo giá này thành đơn hàng.');
        }

        DB::beginTransaction();
        try {
            // Generate sale code
            $date = date('Ymd');
            $prefix = 'SO-' . $date . '-';
            $lastSale = Sale::where('code', 'like', $prefix . '%')
                ->orderBy('code', 'desc')
                ->first();
            $number = $lastSale ? intval(substr($lastSale->code, -4)) + 1 : 1;
            $saleCode = $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);

            // Create sale
            $sale = Sale::create([
                'code' => $saleCode,
                'type' => 'retail',
                'customer_id' => $quotation->customer_id,
                'contact_id' => $quotation->contact_id,
                'customer_name' => $quotation->customer_name,
                'date' => now(),
                'subtotal' => $quotation->subtotal,
                'discount' => $quotation->discount,
                'vat' => $quotation->vat,
                'vat_amount' => $quotation->vat_amount,
                'total' => $quotation->total,
                'total_foreign' => $quotation->total_foreign,
                'currency_id' => $quotation->currency_id,
                'exchange_rate' => $quotation->exchange_rate,
                'cost' => 0,
                'margin' => 0,
                'margin_percent' => 0,
                'paid_amount' => 0,
                'debt_amount' => $quotation->total,
                'payment_status' => 'unpaid',
                'status' => 'pending',
                'pl_status' => 'draft',
                'user_id' => auth()->id(),
                'note' => 'Chuyển từ báo giá: ' . $quotation->code,
            ]);

            // Create sale items with cost auto-setup
            foreach ($quotation->items as $item) {
                $product = $item->product_id ? Product::find($item->product_id) : null;
                $costPrice = $product ? ($product->calculated_cost ?? 0) : 0;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                    'cost_price' => $costPrice,
                    'cost_total' => $item->quantity * $costPrice,
                    'vat' => $item->vat,
                    'vat_amount' => $item->vat_amount,
                ]);
            }

            // Update quotation
            $quotation->update([
                'status' => 'converted',
                'converted_to_sale_id' => $sale->id,
            ]);

            // P&L status khởi tạo là 'draft' (nháp) để Sales Team kiểm tra và chỉnh sửa trước khi gửi duyệt
            $sale->update(['pl_status' => 'draft']);

            DB::commit();

            return redirect()->route('sales.show', $sale)
                ->with('success', 'Đã chuyển báo giá thành đơn hàng ' . $sale->code);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate quotation
     */
    public function duplicate(Quotation $quotation)
    {
        $this->authorize('create', Quotation::class);

        DB::beginTransaction();
        try {
            // Generate a new unique code
            $code = $this->generateCode();

            // Create duplicated quotation
            $newQuotation = Quotation::create([
                'code' => $code,
                'customer_id' => $quotation->customer_id,
                'contact_id' => $quotation->contact_id,
                'customer_name' => $quotation->customer_name,
                'title' => $quotation->title . ' (Bản sao)',
                'date' => now(),
                'valid_until' => now()->addDays(30),
                'subtotal' => $quotation->subtotal,
                'discount' => $quotation->discount,
                'vat' => $quotation->vat,
                'vat_amount' => $quotation->vat_amount,
                'total' => $quotation->total,
                'total_foreign' => $quotation->total_foreign,
                'currency_id' => $quotation->currency_id,
                'exchange_rate' => $quotation->exchange_rate,
                'payment_terms' => $quotation->payment_terms,
                'delivery_time' => $quotation->delivery_time,
                'note' => $quotation->note,
                'disclaimer' => $quotation->disclaimer,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'custom_columns' => $quotation->custom_columns,
            ]);

            // Duplicate items
            foreach ($quotation->items as $item) {
                QuotationItem::create([
                    'quotation_id' => $newQuotation->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_code' => $item->product_code,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'vat' => $item->vat,
                    'vat_amount' => $item->vat_amount,
                    'total' => $item->total,
                    'custom_fields' => $item->custom_fields,
                ]);
            }

            DB::commit();

            $redirectTo = request('redirect_to', 'edit');

            if ($redirectTo === 'index') {
                return redirect()->route('quotations.index')
                    ->with('success', 'Nhân bản báo giá ' . $newQuotation->code . ' thành công.');
            } elseif ($redirectTo === 'show') {
                return redirect()->route('quotations.show', $newQuotation)
                    ->with('success', 'Nhân bản báo giá thành công. Bạn đang xem chi tiết bản sao.');
            }

            return redirect()->route('quotations.edit', $newQuotation)
                ->with('success', 'Nhân bản báo giá thành công. Bạn đang chỉnh sửa bản sao.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra khi nhân bản báo giá: ' . $e->getMessage());
        }
    }

    /**
     * Print quotation
     */
    public function print(Quotation $quotation)
    {
        // Return 404 instead of 403 if user lacks permission to prevent information disclosure
        if (!auth()->user()->can('view', $quotation)) {
            abort(404);
        }

        $quotation->load('items', 'customer');
        $companySettings = Setting::where('group', 'company')->pluck('value', 'key');

        return view('quotations.print', compact('quotation', 'companySettings'));
    }

    /**
     * Export quotations to Excel
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Quotation::class);

        $filters = $request->only(['search', 'status']);
        $filename = 'bao-gia-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new QuotationsExport($filters), $filename);
    }

    /**
     * Export single quotation to Excel
     */
    public function exportSingle(Quotation $quotation)
    {
        if (!auth()->user()->can('view', $quotation)) {
            abort(404);
        }

        $quotation->load('items', 'customer', 'currency', 'contact');
        $safeCode = str_replace(['/', '\\'], '-', $quotation->code);
        $filename = 'bao-gia-' . $safeCode . '.xlsx';

        libxml_use_internal_errors(true);
        try {
            $download = Excel::download(new SingleQuotationExport($quotation), $filename);
            libxml_clear_errors();
            return $download;
        } catch (\Exception $e) {
            libxml_clear_errors();
            throw $e;
        }
    }

    /**
     * Helper to get local product or create from catalog
     */
    private function getOrSyncProduct($productIdRaw, $fallbackName = null)
    {
        if (empty($productIdRaw) && empty($fallbackName)) return null;

        // 1. Catalog item sync
        if (str_starts_with($productIdRaw, 'c-')) {
            $catalogId = substr($productIdRaw, 2);
            $catalogItem = SupplierPriceListItem::find($catalogId);
            
            if (!$catalogItem) return null;

            $sku = $this->cleanSku($catalogItem->sku);
            $product = Product::where('code', $sku)->first();

            if (!$product) {
                $product = Product::create([
                    'code' => $sku,
                    'name' => $catalogItem->product_name,
                    'description' => $catalogItem->description,
                    'unit' => $catalogItem->unit ?: 'Bộ',
                    'category' => 'Z',
                ]);
            }
            return $product;
        }

        // 2. Local product
        if (str_starts_with($productIdRaw, 'p-')) {
            $id = substr($productIdRaw, 2);
            return Product::find($id);
        }

        // 3. Raw numeric ID
        if (is_numeric($productIdRaw)) {
            return Product::find($productIdRaw);
        }

        // 4. Manual text entry - Check if exists by name first, then create
        $name = !empty($fallbackName) ? $fallbackName : $productIdRaw;
        
        // Search for existing product by name (exact match)
        $existingProduct = Product::where('name', $name)->first();
        if ($existingProduct) {
            return $existingProduct;
        }

        // Search for existing product by code (if the manual entry looks like a code)
        if (!empty($productIdRaw) && strlen($productIdRaw) < 50) {
            $existingByCode = Product::where('code', strtoupper($productIdRaw))->first();
            if ($existingByCode) {
                return $existingByCode;
            }
        }

        // Create new product if not found
        try {
            $baseSlug = strtoupper(Str::slug($name));
            $baseSlug = substr($baseSlug, 0, 45);
            $code = $baseSlug;

            // Ensure unique code
            $count = 1;
            while (Product::where('code', $code)->exists()) {
                $suffix = '-' . $count;
                $maxBaseLength = 50 - strlen($suffix);
                $code = substr($baseSlug, 0, $maxBaseLength) . $suffix;
                $count++;
            }

            return Product::create([
                'code' => $code,
                'name' => $name,
                'unit' => 'Bộ',
                'category' => 'Z',
                'description' => 'Sản phẩm tự động tạo từ báo giá',
            ]);
        } catch (\Exception $e) {
            // If creation fails (e.g. code collision), return null to fallback to transient data
            return null;
        }
    }

    /**
     * Helper to clean SKU
     */
    private function cleanSku($sku)
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $sku);
    }

}
