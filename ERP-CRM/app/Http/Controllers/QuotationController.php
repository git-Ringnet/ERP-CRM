<?php

namespace App\Http\Controllers;

use App\Exports\QuotationsExport;
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

        $query = Quotation::with(['customer', 'creator']);

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

        return view('quotations.create', compact('customers', 'products', 'code', 'prefill', 'currencies', 'baseCurrencyId'));
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

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:quotations,code'],
            'customer_id' => ['required', 'exists:customers,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:date'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'delivery_time' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_name' => ['required', 'string'],
            'products.*.product_id' => ['nullable', 'string'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
        ], [
            'valid_until.after_or_equal' => 'Hạn báo giá phải sau hoặc bằng ngày tạo.',
            'code.unique' => 'Mã báo giá đã tồn tại.',
            'code.required' => 'Vui lòng nhập mã báo giá.',
            'customer_id.required' => 'Vui lòng chọn khách hàng.',
            'title.required' => 'Vui lòng nhập tiêu đề.',
            'products.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'products.*.product_name.required' => 'Vui lòng nhập tên sản phẩm.',
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::find($validated['customer_id']);

            $subtotal = 0;
            foreach ($validated['products'] as $item) {
                $subtotal += round($item['quantity'] * $item['price'], 2);
            }

            $discountAmount = round($subtotal * ($validated['discount'] ?? 0) / 100, 2);
            $afterDiscount = $subtotal - $discountAmount;
            $vatAmount = round($afterDiscount * ($validated['vat'] ?? 10) / 100, 2);
            $total = round($afterDiscount + $vatAmount, 2);

            $quotation = Quotation::create([
                'code' => $validated['code'],
                'customer_id' => $validated['customer_id'],
                'customer_name' => $customer->name,
                'title' => $validated['title'],
                'date' => $validated['date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $this->currencyService->toBase($subtotal, $validated['exchange_rate'] ?? 1)
                    : $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'vat' => $validated['vat'] ?? 10,
                'total' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $this->currencyService->toBase($total, $validated['exchange_rate'] ?? 1)
                    : $total,
                'total_foreign' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $total
                    : null,
                'currency_id' => $validated['currency_id'] ?? Currency::getBaseCurrencyId(),
                'exchange_rate' => $validated['exchange_rate'] ?? 1,
                'payment_terms' => $validated['payment_terms'],
                'delivery_time' => $validated['delivery_time'],
                'note' => $validated['note'],
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['products'] as $item) {
                $productId = null;
                $productName = $item['product_name'];
                $productCode = null;

                if (!empty($item['product_id'])) {
                    $product = $this->getOrSyncProduct($item['product_id'], $item['product_name']);
                    if ($product) {
                        $productId = $product->id;
                        $productName = $product->name;
                        $productCode = $product->code;
                    }
                }

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'product_code' => $productCode,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
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

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('quotations')->ignore($quotation->id)],
            'customer_id' => ['required', 'exists:customers,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:date'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'string'],
            'delivery_time' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_name' => ['required', 'string'],
            'products.*.product_id' => ['nullable', 'string'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
        ], [
            'valid_until.after_or_equal' => 'Hạn báo giá phải sau hoặc bằng ngày tạo.',
            'code.unique' => 'Mã báo giá đã tồn tại.',
            'code.required' => 'Vui lòng nhập mã báo giá.',
            'customer_id.required' => 'Vui lòng chọn khách hàng.',
            'title.required' => 'Vui lòng nhập tiêu đề.',
            'products.required' => 'Vui lòng thêm ít nhất một sản phẩm.',
            'products.*.product_name.required' => 'Vui lòng nhập tên sản phẩm.',
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::find($validated['customer_id']);

            $subtotal = 0;
            foreach ($validated['products'] as $item) {
                $subtotal += round($item['quantity'] * $item['price'], 2);
            }

            $discountAmount = round($subtotal * ($validated['discount'] ?? 0) / 100, 2);
            $afterDiscount = $subtotal - $discountAmount;
            $vatAmount = round($afterDiscount * ($validated['vat'] ?? 10) / 100, 2);
            $total = round($afterDiscount + $vatAmount, 2);

            $quotation->update([
                'code' => $validated['code'],
                'customer_id' => $validated['customer_id'],
                'customer_name' => $customer->name,
                'title' => $validated['title'],
                'date' => $validated['date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $this->currencyService->toBase($subtotal, $validated['exchange_rate'] ?? 1)
                    : $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'vat' => $validated['vat'] ?? 10,
                'total' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $this->currencyService->toBase($total, $validated['exchange_rate'] ?? 1)
                    : $total,
                'total_foreign' => $this->currencyService->isForeignTransaction($validated['currency_id'] ?? null)
                    ? $total
                    : null,
                'currency_id' => $validated['currency_id'] ?? Currency::getBaseCurrencyId(),
                'exchange_rate' => $validated['exchange_rate'] ?? 1,
                'payment_terms' => $validated['payment_terms'],
                'delivery_time' => $validated['delivery_time'],
                'note' => $validated['note'],
                'status' => 'draft',
            ]);

            $quotation->items()->delete();

            foreach ($validated['products'] as $item) {
                $productId = null;
                $productName = $item['product_name'];
                $productCode = null;

                if (!empty($item['product_id'])) {
                    $product = $this->getOrSyncProduct($item['product_id'], $item['product_name']);
                    if ($product) {
                        $productId = $product->id;
                        $productName = $product->name;
                        $productCode = $product->code;
                    }
                }

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'product_code' => $productCode,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
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
                    'text' => "[KHO] {$p->code} - {$p->name}",
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
                    'text' => "[HÃNG] {$item->sku} - {$item->product_name}",
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
                'customer_name' => $quotation->customer_name,
                'date' => now(),
                'subtotal' => $quotation->subtotal,
                'discount' => $quotation->discount,
                'vat' => $quotation->vat,
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

            // Create sale items
            foreach ($quotation->items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
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
            return Product::create([
                'code' => 'M-' . strtoupper(Str::random(6)),
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
