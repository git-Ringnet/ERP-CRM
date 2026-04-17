<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleExpense;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Project;
use App\Models\Warehouse;
use App\Models\Currency;
use App\Exports\SalesExport;
use App\Exports\SaleInvoiceExport;
use App\Services\SaleExportSyncService;
use App\Services\CurrencyService;
use App\Services\ApprovalService;
use App\Models\ApprovalHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class SaleController extends Controller
{
    protected SaleExportSyncService $saleExportSyncService;
    protected CurrencyService $currencyService;
    protected ApprovalService $approvalService;

    public function __construct(SaleExportSyncService $saleExportSyncService, CurrencyService $currencyService, ApprovalService $approvalService)
    {
        $this->saleExportSyncService = $saleExportSyncService;
        $this->currencyService = $currencyService;
        $this->approvalService = $approvalService;
    }
    /**
     * Display a listing of sales with search and filter functionality.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Sale::class);

        $query = Sale::query();

        // Apply data filtering based on permissions
        $user = auth()->user();
        if (!$user->can('view_all_sales') && !$user->can('view_sales')) {
            // User only has view_own_sales permission
            if ($user->can('view_own_sales')) {
                $query->where('user_id', $user->id);
            } else {
                // User has no permission to view sales
                abort(403, 'Unauthorized action.');
            }
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by specific date range
        if ($request->filled('date_from')) {
            try {
                $dateFrom = \Carbon\Carbon::createFromFormat('d/m/Y', $request->date_from)->startOfDay();
                $query->whereDate('date', '>=', $dateFrom);
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        }
        if ($request->filled('date_to')) {
            try {
                $dateTo = \Carbon\Carbon::createFromFormat('d/m/Y', $request->date_to)->endOfDay();
                $query->whereDate('date', '<=', $dateTo);
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        }

        $sales = $query->with(['project', 'user'])->orderBy('created_at', 'desc')->paginate(10);
        $projects = Project::whereIn('status', ['planning', 'in_progress'])->orderBy('name')->get();
        // Optimize: Select only needed columns
        $customers = Customer::select('id', 'name')->orderBy('name')->get();

        return view('sales.index', compact('sales', 'projects', 'customers'));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Sale::class);

        $customers = Customer::orderBy('name')->get();
        
        // Không load sản phẩm nữa - sẽ dùng AJAX search
        $products = collect();
        
        $projects = Project::whereIn('status', ['planning', 'in_progress'])->orderBy('name')->get();

        // Generate sale code
        $code = $this->generateSaleCode();

        // Pre-select project if passed from project detail page
        $selectedProjectId = $request->get('project_id');
        $selectedProject = $selectedProjectId ? Project::find($selectedProjectId) : null;

        // Multi-currency: load active currencies + today's VND base ID
        $currencies = $this->currencyService->getActiveCurrencies();
        $baseCurrencyId = Currency::getBaseCurrencyId();

        return view('sales.create', compact('customers', 'products', 'projects', 'code', 'selectedProject', 'currencies', 'baseCurrencyId'));
    }

    /**
     * Generate unique sale code
     */
    private function generateSaleCode()
    {
        $date = date('Ymd');
        $prefix = 'SO-' . $date . '-';

        // Get the last sale code for today
        $lastSale = Sale::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastSale) {
            // Extract the sequence number and increment
            $lastNumber = intval(substr($lastSale->code, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Store a newly created sale in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Sale::class);

        // Convert price values from string to numeric (remove formatting)
        $products = $request->input('products', []);
        foreach ($products as $key => $product) {
            if (isset($product['price'])) {
                $products[$key]['price'] = is_numeric($product['price']) ? $product['price'] : preg_replace('/[^0-9]/', '', $product['price']);
            }
        }
        $request->merge(['products' => $products]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:sales,code'],
            'type' => ['required', 'in:retail,project'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'date' => ['required', 'date'],
            'delivery_address' => ['nullable', 'string'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
            'products.*.project_id' => ['nullable', 'exists:projects,id'],
            'products.*.warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'expenses' => ['nullable', 'array'],
            'expenses.*.type' => ['nullable', 'string', 'max:100'],
            'expenses.*.input_mode' => ['nullable', 'in:percent,fixed'],
            'expenses.*.percent_value' => ['nullable', 'numeric', 'min:0'],
            'expenses.*.description' => ['nullable', 'string'],
            'expenses.*.amount' => ['nullable', 'numeric'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
        ]);

        DB::beginTransaction();
        try {
            $code = $validated['code'];

            $customer = Customer::find($validated['customer_id']);

            // Calculate totals
            $subtotal = 0;
            foreach ($validated['products'] as $item) {
                $subtotal += round($item['quantity'] * $item['price'], 2);
            }

            $discountAmount = round($subtotal * ($validated['discount'] ?? 0) / 100, 2);
            $afterDiscount = $subtotal - $discountAmount;
            $vatAmount = round($afterDiscount * ($validated['vat'] ?? 10) / 100, 2);
            $total = round($afterDiscount + $vatAmount, 2);

            // Determine currency
            $currencyId = $validated['currency_id'] ?? Currency::getBaseCurrencyId();
            $exchangeRate = $validated['exchange_rate'] ?? 1;
            $isForeign = $this->currencyService->isForeignTransaction($currencyId);

            // If foreign currency: total is in foreign, need to convert to VND
            // If VND: total_foreign = total, exchange_rate = 1
            $totalForeign = $total;
            if ($isForeign && $exchangeRate > 1) {
                // total in the form is calculated in foreign currency
                // Convert to VND base
                $total = $this->currencyService->toBase($totalForeign, $exchangeRate);
            }

            // Create sale
            $sale = Sale::create([
                'code' => $code,
                'type' => $validated['type'],
                'project_id' => $validated['project_id'] ?? null,
                'customer_id' => $validated['customer_id'],
                'customer_name' => $customer->name,
                'user_id' => auth()->id(),
                'date' => $validated['date'],
                'delivery_address' => $validated['delivery_address'],
                'subtotal' => $isForeign ? $this->currencyService->toBase($subtotal, $exchangeRate) : $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'vat' => $validated['vat'] ?? 10,
                'total' => $total,
                'cost' => 0,
                'margin' => 0,
                'margin_percent' => 0,
                'paid_amount' => $validated['paid_amount'] ?? 0,
                'debt_amount' => 0,
                'payment_status' => 'unpaid',
                'status' => 'pending',
                'note' => $validated['note'],
                'currency_id' => $currencyId,
                'exchange_rate' => $exchangeRate,
                'total_foreign' => $totalForeign,
            ]);

            // Create sale items with cost price and project
            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                $costPrice = $product->calculated_cost;
                $quantity = $item['quantity'];

                // Use item-level project_id if set, otherwise use sale-level project_id
                $itemProjectId = $item['project_id'] ?? $validated['project_id'] ?? null;

                // Get warranty: use input value if provided, otherwise use product default
                $warrantyMonths = isset($item['warranty_months']) && $item['warranty_months'] !== ''
                    ? (int) $item['warranty_months']
                    : $product->warranty_months;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'project_id' => $itemProjectId,
                    'quantity' => $quantity,
                    'is_liquidation' => isset($item['is_liquidation']) ? (bool) $item['is_liquidation'] : false,
                    'price' => $item['price'],
                    'cost_price' => $costPrice,
                    'total' => $quantity * $item['price'],
                    'cost_total' => $quantity * $costPrice,
                    'warranty_months' => $warrantyMonths,
                    'warranty_start_date' => $warrantyMonths ? $validated['date'] : null,
                ]);
            }

            // Create sale expenses
            if (!empty($validated['expenses'])) {
                foreach ($validated['expenses'] as $expense) {
                    if (empty($expense['type'])) continue;
                    $amount = floatval($expense['amount'] ?? 0);
                    if ($amount == 0 && empty($expense['percent_value'])) continue;
                    SaleExpense::create([
                        'sale_id' => $sale->id,
                        'type' => $expense['type'],
                        'input_mode' => $expense['input_mode'] ?? 'fixed',
                        'percent_value' => ($expense['input_mode'] ?? 'fixed') === 'percent' ? ($expense['percent_value'] ?? null) : null,
                        'description' => $expense['description'] ?? '',
                        'amount' => round($amount, 2),
                    ]);
                }
            }

            // Calculate margin and debt
            $sale->calculateMargin();
            $sale->updateDebt();
            $sale->save();

            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Đơn hàng đã được tạo thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale)
    {
        // Return 404 instead of 403 if user lacks permission to prevent information disclosure
        if (!auth()->user()->can('view', $sale)) {
            abort(404);
        }

        $sale->load(['items.product', 'customer', 'expenses', 'project']);
        $currencies = $this->currencyService->getActiveCurrencies();
        $baseCurrencyId = Currency::getBaseCurrencyId();

        return view('sales.show', compact('sale', 'currencies', 'baseCurrencyId'));
    }

    /**
     * Show the form for editing the specified sale.
     */
    public function edit(Sale $sale)
    {
        $this->authorize('update', $sale);

        $sale->load(['items.product', 'expenses']);
        $customers = Customer::orderBy('name')->get();
        
        // Không load sản phẩm nữa - sẽ dùng AJAX search như trang create
        $products = collect();
        
        $projects = Project::whereIn('status', ['planning', 'in_progress'])->orderBy('name')->get();

        // Multi-currency
        $currencies = $this->currencyService->getActiveCurrencies();
        $baseCurrencyId = Currency::getBaseCurrencyId();

        return view('sales.edit', compact('sale', 'customers', 'products', 'projects', 'currencies', 'baseCurrencyId'));
    }

    /**
     * Update the specified sale in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        $this->authorize('update', $sale);

        if ($sale->isPendingApproval()) {
            return back()->with('error', 'Không thể sửa đơn hàng khi đang trong quá trình duyệt P&L.');
        }

        // Convert price values from string to numeric (remove formatting)
        $products = $request->input('products', []);
        foreach ($products as $key => $product) {
            if (isset($product['price'])) {
                $products[$key]['price'] = is_numeric($product['price']) ? $product['price'] : preg_replace('/[^0-9]/', '', $product['price']);
            }
        }
        $request->merge(['products' => $products]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('sales')->ignore($sale->id)],
            'type' => ['required', 'in:retail,project'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'date' => ['required', 'date'],
            'delivery_address' => ['nullable', 'string'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,approved,shipping,completed,cancelled'],
            'note' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
            'products.*.project_id' => ['nullable', 'exists:projects,id'],
            'products.*.warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'expenses' => ['nullable', 'array'],
            'expenses.*.type' => ['nullable', 'string', 'max:100'],
            'expenses.*.input_mode' => ['nullable', 'in:percent,fixed'],
            'expenses.*.percent_value' => ['nullable', 'numeric', 'min:0'],
            'expenses.*.description' => ['nullable', 'string'],
            'expenses.*.amount' => ['nullable', 'numeric'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::find($validated['customer_id']);

            // Calculate totals
            $subtotal = 0;
            foreach ($validated['products'] as $item) {
                $subtotal += round($item['quantity'] * $item['price'], 2);
            }

            $discountAmount = round($subtotal * ($validated['discount'] ?? 0) / 100, 2);
            $afterDiscount = $subtotal - $discountAmount;
            $vatAmount = round($afterDiscount * ($validated['vat'] ?? 10) / 100, 2);
            $total = round($afterDiscount + $vatAmount, 2);

            // Determine currency
            $currencyId = $validated['currency_id'] ?? Currency::getBaseCurrencyId();
            $exchangeRate = $validated['exchange_rate'] ?? 1;
            $isForeign = $this->currencyService->isForeignTransaction($currencyId);

            $totalForeign = $total;
            if ($isForeign && $exchangeRate > 1) {
                $total = $this->currencyService->toBase($totalForeign, $exchangeRate);
            }

            // Update sale
            $sale->update([
                'code' => $validated['code'],
                'type' => $validated['type'],
                'project_id' => $validated['project_id'] ?? null,
                'customer_id' => $validated['customer_id'],
                'customer_name' => $customer->name,
                'date' => $validated['date'],
                'delivery_address' => $validated['delivery_address'],
                'subtotal' => $isForeign ? $this->currencyService->toBase($subtotal, $exchangeRate) : $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'vat' => $validated['vat'] ?? 10,
                'total' => $total,
                'cost' => $validated['cost'] ?? 0,
                'paid_amount' => $validated['paid_amount'] ?? 0,
                'status' => $validated['status'],
                'note' => $validated['note'],
                'currency_id' => $currencyId,
                'exchange_rate' => $exchangeRate,
                'total_foreign' => $totalForeign,
            ]);

            // Lưu dữ liệu P&L của items cũ trước khi xóa
            $oldPnlData = [];
            foreach ($sale->items as $oldItem) {
                $oldPnlData[$oldItem->product_id] = [
                    'usd_price' => $oldItem->usd_price,
                    'exchange_rate' => $oldItem->exchange_rate,
                    'discount_rate' => $oldItem->discount_rate,
                    'import_cost_rate' => $oldItem->import_cost_rate,
                    'estimated_cost_usd' => $oldItem->estimated_cost_usd,
                    'finance_cost_percent' => $oldItem->finance_cost_percent,
                    'overdue_interest_cost' => $oldItem->overdue_interest_cost,
                    'overdue_interest_percent' => $oldItem->overdue_interest_percent,
                    'management_cost_percent' => $oldItem->management_cost_percent,
                    'support_247_cost_percent' => $oldItem->support_247_cost_percent,
                    'other_support_cost' => $oldItem->other_support_cost,
                    'technical_poc_cost' => $oldItem->technical_poc_cost,
                    'implementation_cost' => $oldItem->implementation_cost,
                    'contractor_tax' => $oldItem->contractor_tax,
                ];
            }
            
            // Delete old items and create new ones with cost price and project
            $sale->items()->delete();

            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                $costPrice = $product->calculated_cost;
                $quantity = $item['quantity'];

                // Use item-level project_id if set, otherwise use sale-level project_id
                $itemProjectId = $item['project_id'] ?? $validated['project_id'] ?? null;

                // Get warranty: use input value if provided, otherwise use product default
                $warrantyMonths = isset($item['warranty_months']) && $item['warranty_months'] !== ''
                    ? (int) $item['warranty_months']
                    : $product->warranty_months;

                // Khôi phục dữ liệu P&L nếu sản phẩm đã tồn tại trước đó
                $pnlData = $oldPnlData[$item['product_id']] ?? [];
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'project_id' => $itemProjectId,
                    'quantity' => $quantity,
                    'is_liquidation' => isset($item['is_liquidation']) ? (bool) $item['is_liquidation'] : false,
                    'price' => $item['price'],
                    'cost_price' => $costPrice,
                    'total' => $quantity * $item['price'],
                    'cost_total' => $quantity * $costPrice,
                    'warranty_months' => $warrantyMonths,
                    'warranty_start_date' => $warrantyMonths ? $validated['date'] : null,
                    // Khôi phục dữ liệu P&L
                    'usd_price' => $pnlData['usd_price'] ?? 0,
                    'exchange_rate' => $pnlData['exchange_rate'] ?? ($exchangeRate ?: 1),
                    'discount_rate' => $pnlData['discount_rate'] ?? 0,
                    'import_cost_rate' => $pnlData['import_cost_rate'] ?? 0,
                    'estimated_cost_usd' => $pnlData['estimated_cost_usd'] ?? 0,
                    'finance_cost_percent' => $pnlData['finance_cost_percent'] ?? null,
                    'overdue_interest_cost' => $pnlData['overdue_interest_cost'] ?? 0,
                    'overdue_interest_percent' => $pnlData['overdue_interest_percent'] ?? null,
                    'management_cost_percent' => $pnlData['management_cost_percent'] ?? null,
                    'support_247_cost_percent' => $pnlData['support_247_cost_percent'] ?? null,
                    'other_support_cost' => $pnlData['other_support_cost'] ?? 0,
                    'technical_poc_cost' => $pnlData['technical_poc_cost'] ?? 0,
                    'implementation_cost' => $pnlData['implementation_cost'] ?? 0,
                    'contractor_tax' => $pnlData['contractor_tax'] ?? 0,
                ]);
            }

            // Update sale expenses
            $sale->expenses()->delete();
            if (!empty($validated['expenses'])) {
                foreach ($validated['expenses'] as $expense) {
                    if (empty($expense['type'])) continue;
                    $amount = floatval($expense['amount'] ?? 0);
                    if ($amount == 0 && empty($expense['percent_value'])) continue;
                    SaleExpense::create([
                        'sale_id' => $sale->id,
                        'type' => $expense['type'],
                        'input_mode' => $expense['input_mode'] ?? 'fixed',
                        'percent_value' => ($expense['input_mode'] ?? 'fixed') === 'percent' ? ($expense['percent_value'] ?? null) : null,
                        'description' => $expense['description'] ?? '',
                        'amount' => round($amount, 2),
                    ]);
                }
            }

            // Calculate margin and debt AFTER creating new items
            $sale->calculateMargin();
            $sale->updateDebt();
            $sale->save();

            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Đơn hàng đã được cập nhật thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified sale from storage.
     */
    public function destroy(Sale $sale)
    {
        $this->authorize('delete', $sale);

        DB::beginTransaction();
        try {
            $sale->items()->delete();
            $sale->delete();
            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Đơn hàng đã được xóa thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Export sales to Excel
     */
    public function export(Request $request)
    {
        $this->authorize('export', Sale::class);

        $filters = $request->only(['search', 'status', 'type', 'project_id']);
        $filename = 'don-hang-ban-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new SalesExport($filters), $filename);
    }

    /**
     * Generate PDF/print invoice
     */
    public function generatePdf(Sale $sale)
    {
        // Return 404 instead of 403 if user lacks permission
        if (!auth()->user()->can('view', $sale)) {
            abort(404);
        }

        $sale->load('items.product', 'customer', 'expenses', 'project', 'currency');

        return view('sales.invoice', compact('sale'));
    }

    /**
     * Export invoice to Excel (GTGT format)
     */
    public function exportInvoiceExcel(Sale $sale)
    {
        if (!auth()->user()->can('view', $sale)) {
            abort(404);
        }

        $sale->load(['items.product', 'customer', 'expenses', 'project', 'currency']);

        return \Excel::download(
            new SaleInvoiceExport($sale),
            'hoa-don-' . $sale->code . '.xlsx'
        );
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Sale $sale)
    {
        // Return 404 instead of 403 if user lacks permission
        if (!auth()->user()->can('view', $sale)) {
            abort(404);
        }

        $sale->load('items', 'customer');

        if (!$sale->customer || !$sale->customer->email) {
            return back()->with('error', 'Khách hàng không có email.');
        }

        try {
            Mail::to($sale->customer->email)->send(new \App\Mail\SaleInvoiceMail($sale));

            return back()->with('success', 'Đã gửi hóa đơn qua email đến ' . $sale->customer->email);
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể gửi email: ' . $e->getMessage());
        }
    }

    /**
     * Send multiple invoices via email
     */
    public function sendBulkEmail(Request $request)
    {
        $this->authorize('export', Sale::class);

        $validated = $request->validate([
            'sale_ids' => ['required', 'array', 'min:1'],
            'sale_ids.*' => ['exists:sales,id'],
        ]);

        $sales = Sale::with(['items', 'customer'])->whereIn('id', $validated['sale_ids'])->get();

        $sent = 0;
        $failed = 0;
        $noEmail = 0;
        $errors = [];

        foreach ($sales as $sale) {
            if (!$sale->customer || !$sale->customer->email) {
                $noEmail++;
                $errors[] = "{$sale->code}: Khách hàng không có email";
                continue;
            }

            try {
                Mail::to($sale->customer->email)->send(new \App\Mail\SaleInvoiceMail($sale));
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "{$sale->code}: " . $e->getMessage();
            }
        }

        $message = "Đã gửi {$sent} hóa đơn thành công.";
        if ($noEmail > 0) {
            $message .= " {$noEmail} đơn không có email.";
        }
        if ($failed > 0) {
            $message .= " {$failed} đơn gửi thất bại.";
        }

        if ($failed > 0 || $noEmail > 0) {
            return back()->with('warning', $message)->with('bulk_errors', $errors);
        }

        return back()->with('success', $message);
    }

    /**
     * Record payment
     */
    public function recordPayment(Request $request, Sale $sale)
    {
        $this->authorize('update', $sale);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $amountVnd = $validated['amount'];
            $paymentCurrencyId = $validated['currency_id'] ?? $sale->currency_id;
            $paymentRate = $validated['exchange_rate'] ?? 1;

            // Nếu thanh toán bằng ngoại tệ, cần tính số VND thực tế (nếu amount truyền vào là ngoại tệ)
            // Tuy nhiên, logic hiện tại trong form là user nhập amount theo loại tiền đã chọn.
            // Để thống nhất với recordPayment cũ, ta giả định $validated['amount'] là số tiền theo $paymentCurrencyId.
            
            $isBase = $paymentCurrencyId == \App\Models\Currency::getBaseCurrencyId();
            $actualAmountVnd = $isBase ? $amountVnd : round($amountVnd * $paymentRate, 0);
            $actualAmountForeign = $amountVnd; // Số tiền theo loại tiền thanh toán

            $newPaidAmountVnd = $sale->paid_amount + $actualAmountVnd;

            if ($newPaidAmountVnd > $sale->total + 100) { // Thêm sai số nhỏ để tránh lỗi làm tròn
                return back()->with('error', 'Số tiền thanh toán vượt quá tổng đơn hàng.');
            }

            $sale->paid_amount = $newPaidAmountVnd;
            
            // Cập nhật paid_amount_foreign
            // Nếu Sale theo USD, ta cần biết số USD đã trả.
            if ($sale->currency_id && $sale->currency_id != \App\Models\Currency::getBaseCurrencyId()) {
                if ($paymentCurrencyId == $sale->currency_id) {
                    $sale->paid_amount_foreign += $actualAmountForeign;
                } else {
                    // Thanh toán bằng loại tiền khác, quy đổi về tiền của đơn hàng
                    // Cách tốt nhất là: $actualAmountVnd / $sale->exchange_rate (tỷ giá gốc) 
                    // HOẶC dùng tỷ giá thanh toán? Thường là dùng tỷ giá thanh toán để quy đổi.
                    $sale->paid_amount_foreign += ($actualAmountVnd / ($sale->exchange_rate ?: 1));
                }
            }

            $sale->updateDebt();
            $sale->save();

            // Tạo giao dịch thu vào financial_transactions
            $financialService = app(\App\Services\FinancialTransactionService::class);
            $financialService->createFromSale(
                $sale,
                $actualAmountForeign,
                $validated['payment_method'],
                $validated['note'],
                $paymentCurrencyId,
                $paymentRate
            );

            DB::commit();
            return back()->with('success', 'Đã ghi nhận thanh toán ' . number_format($validated['amount']) . ' đ và tạo phiếu thu');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Update sale status
     * Auto-creates export when status changes to 'approved'
     */
    public function updateStatus(Request $request, Sale $sale)
    {
        $this->authorize('approve', $sale);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,shipping,completed,cancelled'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        $oldStatus = $sale->status;
        $newStatus = $validated['status'];

        // Validate status transitions
        $allowedTransitions = [
            'pending' => ['approved', 'cancelled'],
            'approved' => ['shipping', 'cancelled'],
            'shipping' => ['completed'],
            'completed' => [],
            'cancelled' => [],
        ];

        if (!in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
            return back()->with('error', 'Không thể chuyển trạng thái từ "' . $sale->status_label . '" sang trạng thái này.');
        }

        // Logic Check: Prevent Shipping/Completed if Export is not completed
        if (in_array($newStatus, ['shipping', 'completed'])) {
            $export = $this->saleExportSyncService->getExport($sale);
            
            // Nếu chưa có phiếu xuất kho, thử tạo mới nếu đang chuyển sang Shipping
            if (!$export && $newStatus === 'shipping') {
                try {
                    $export = $this->saleExportSyncService->createExportFromSale($sale);
                } catch (\Exception $e) {
                    return back()->with('error', "Không thể tạo phiếu xuất kho tự động: " . $e->getMessage());
                }
            }

            if ($export && $export->status !== 'completed') {
                return back()->with('error', "Không thể giao hàng. Vui lòng duyệt phiếu xuất kho {$export->code} để xác nhận trừ tồn kho trước.");
            }
            
            if (!$export && $newStatus === 'completed') {
                return back()->with('error', "Đơn hàng chưa có phiếu xuất kho liên kết. Vui lòng tạo phiếu xuất kho trước.");
            }
        }

        DB::beginTransaction();
        try {
            $sale->status = $newStatus;
            $sale->save();

            // Auto-create export when sale is approved
            if ($newStatus === 'approved' && $oldStatus === 'pending') {
                $sale->load('items');
                $warehouseId = $validated['warehouse_id'] ?? null;

                try {
                    $export = $this->saleExportSyncService->createExportFromSale($sale, $warehouseId, false);
                    if ($export) {
                        DB::commit();
                        return back()->with('success', 'Đã duyệt đơn hàng và tạo phiếu xuất kho ' . $export->code . '. Vui lòng duyệt phiếu xuất kho để trừ tồn kho.');
                    }
                } catch (\Exception $e) {
                    // Log error but don't fail the status update
                    Log::warning("Could not create export for Sale #{$sale->id}: " . $e->getMessage());
                }
            }

            // Auto-cancel export/restore stock when sale is cancelled
            if ($newStatus === 'cancelled') {
                try {
                    $this->saleExportSyncService->cancelExportFromSale($sale);
                } catch (\Exception $e) {
                    // Log error but generally we might want to fail the cancellation if stock return fails?
                    // For now, let's just log it to avoid blocking user flow if something minor breaks.
                    // But technically, if stock return fails, we should probably rollback and tell user.
                    // Let's re-throw to trigger rollback in the main catch block.
                    throw new \Exception("Không thể hủy phiếu xuất kho liên quan: " . $e->getMessage());
                }
            }

            DB::commit();

            $statusLabels = [
                'pending' => 'Chờ duyệt',
                'approved' => 'Đã duyệt',
                'shipping' => 'Đang giao hàng',
                'completed' => 'Hoàn thành',
                'cancelled' => 'Đã hủy',
            ];

            return back()->with('success', 'Đã cập nhật trạng thái đơn hàng thành "' . $statusLabels[$newStatus] . '"');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Get linked export for a sale
     */
    /**
     * Get linked export for a sale
     */
    public function getExport(Sale $sale)
    {
        // Return 404 instead of 403 if user lacks permission
        if (!auth()->user()->can('view', $sale)) {
            abort(404);
        }

        $export = $this->saleExportSyncService->getExport($sale);

        if (!$export) {
            return back()->with('error', 'Chưa có phiếu xuất kho liên kết với đơn hàng này.');
        }

        return redirect()->route('exports.show', $export);
    }

    /**
     * Update P&L details for the sale
     */
    public function updatePnL(Request $request, Sale $sale)
    {
        $this->authorize('update', $sale);

        if (!$sale->isPlEditable() && !auth()->user()->hasRole('admin')) {
            return back()->with('error', 'P&L hiện không thể chỉnh sửa (đang chờ duyệt hoặc đã duyệt).');
        }

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:sale_items,id'],
            'items.*.finance_cost_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.overdue_interest_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.overdue_interest_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.management_cost_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.support_247_cost_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.other_support_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.technical_poc_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.implementation_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.contractor_tax' => ['nullable', 'numeric', 'min:0'],
            'items.*.usd_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.import_cost_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.cost_total' => ['nullable', 'numeric', 'min:0'],
            'expenses' => ['nullable', 'array'],
            'expenses.*.id' => ['nullable', 'exists:sale_expenses,id'],
            'expenses.*.type' => ['nullable', 'string'],
            'expenses.*.amount' => ['nullable', 'numeric', 'min:0'],
            'expenses.*.description' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemData) {
                $item = SaleItem::where('id', $itemData['id'])->where('sale_id', $sale->id)->firstOrFail();
                $item->update($itemData);
            }

            // Simple update for general expenses if provided
            if (isset($validated['expenses'])) {
                foreach ($validated['expenses'] as $expenseData) {
                    if (!empty($expenseData['id'])) {
                        $expense = SaleExpense::where('id', $expenseData['id'])->where('sale_id', $sale->id)->firstOrFail();
                        $expense->update($expenseData);
                    } elseif (!empty($expenseData['type']) && !empty($expenseData['amount'])) {
                        $sale->expenses()->create($expenseData);
                    }
                }
            }

            $sale->pl_status = 'draft';
            $sale->calculateMargin();
            $sale->save();

            DB::commit();
            
            // Redirect to same page with hash to stay on P&L tab
            return redirect()->route('sales.show', $sale->id)->with('success', 'Đã cập nhật chi tiết P&L. Vui lòng nhấn "Gửi duyệt" để hoàn tất.')->withFragment('pnl');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi cập nhật P&L: ' . $e->getMessage());
        }
    }

    /**
     * Submit P&L for approval – tích hợp ApprovalWorkflow
     */
    public function submitPnL(Sale $sale)
    {
        $this->authorize('update', $sale);

        // Allow re-submission if pending (to fix stuck cases) or draft/rejected
        if (!in_array($sale->pl_status, [null, 'draft', 'rejected', 'pending'])) {
            return back()->with('error', 'P&L đã được duyệt.');
        }

        // Xóa lịch sử duyệt cũ (nếu đang resubmit)
        ApprovalHistory::where('document_type', 'sale_pnl')
            ->where('document_id', $sale->id)
            ->delete();

        // Thử dùng ApprovalWorkflow nếu đã cấu hình
        $result = $this->approvalService->submit($sale, 'sale_pnl');

        if (!$result['success']) {
            $isSystemError = str_contains($result['message'] ?? '', 'Lỗi hệ thống');
            
            // Nếu là lỗi cấu hình thì mới chuyển sang thủ công
            if (!$isSystemError) {
                $sale->update(['pl_status' => 'pending']);
                return redirect()->route('sales.show', $sale->id)
                    ->with('warning', 'Chưa cấu hình quy trình duyệt P&L. Đã chuyển sang chờ duyệt thủ công.')
                    ->withFragment('pnl');
            }

            return back()->with('error', 'Lỗi khi gửi duyệt P&L: ' . $result['message']);
        }

        // Đồng bộ pl_status theo kết quả
        $sale->refresh();
        if (isset($result['auto_approved']) && $result['auto_approved']) {
            $sale->update([
                'pl_status'      => 'approved',
                'pl_approved_at' => now(),
                'pl_approved_by' => auth()->id(),
            ]);
        } else {
            $sale->update(['pl_status' => 'pending']);
        }

        return redirect()->route('sales.show', $sale->id)
            ->with('success', $result['message'])
            ->withFragment('pnl');
    }

    /**
     * Approve P&L via ApprovalService
     */
    public function approvePnL(Request $request, Sale $sale)
    {
        $this->authorize('approvePnl', $sale);

        $request->validate(['comment' => 'nullable|string|max:500']);

        $result = $this->approvalService->approve($sale, 'sale_pnl', $request->comment);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        // Đồng bộ pl_status (sửa bug: dùng $sale->pl_status thay vì $sale->status)
        $sale->refresh();
        if ($sale->pl_status !== 'approved') {
            $checkDone = !\App\Models\ApprovalHistory::where('document_type', 'sale_pnl')
                ->where('document_id', $sale->id)
                ->where('action', 'pending')
                ->exists();
            if ($checkDone) {
                $sale->update([
                    'pl_status'      => 'approved',
                    'pl_approved_at' => now(),
                    'pl_approved_by' => auth()->id(),
                    'status'         => 'approved', // Đồng bộ trạng thái đơn hàng chính
                ]);
            }
        }

        return redirect()->route('sales.show', $sale->id)
            ->with('success', $result['message'])
            ->withFragment('pnl');
    }

    /**
     * Reject P&L via ApprovalService
     */
    public function rejectPnL(Request $request, Sale $sale)
    {
        $this->authorize('approvePnl', $sale);

        $request->validate(['comment' => 'required|string|min:3|max:500']);

        $result = $this->approvalService->reject($sale, 'sale_pnl', $request->comment);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        $sale->update(['pl_status' => 'rejected']);

        return redirect()->route('sales.show', $sale->id)
            ->with('success', 'P&L đã bị từ chối.')
            ->withFragment('pnl');
    }
}

