<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleExpense;
use App\Models\SaleAttachment;
use App\Models\SaleOrderRequestItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Project;
use App\Models\Warehouse;
use App\Models\Currency;
use App\Models\Notification;
use App\Models\User;
use App\Models\Setting;
use App\Exports\SalesExport;
use App\Exports\SaleInvoiceExport;
use App\Services\SaleExportSyncService;
use App\Services\CurrencyService;
use App\Services\ApprovalService;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Services\SalePurchaseSyncService;
use App\Services\BomParserService;
use App\Models\ApprovalHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PnlApprovalAttachment;

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

        session(['sales_list_url' => $request->fullUrl()]);

        $query = Sale::query();

        // Apply data filtering based on permissions
        $user = auth()->user();
        $isPoTeam = $user->hasAnyRole(['purchase_manager', 'purchase_staff']);
        if ($isPoTeam && !$user->hasAnyRole(['super_admin', 'admin', 'director'])) {
            // PO receives a sale only after its purchase request has left the
            // Sales draft/approval stage.  This keeps quotations and drafts
            // private while retaining the order information PO must execute.
            $query->whereHas('orderRequests', function ($requests) {
                $requests->whereIn('status', [
                    \App\Models\SaleOrderRequest::STATUS_SUBMITTED,
                    \App\Models\SaleOrderRequest::STATUS_PROCESSING,
                    \App\Models\SaleOrderRequest::STATUS_COMPLETED,
                ]);
            });
        } elseif (!$user->can('view_all_sales')) {
            if ($user->can('view_own_sales') || $user->can('view_sales')) {
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
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhereHas('orderRequests', function ($q2) use ($search) {
                        $q2->where('code', 'like', "%{$search}%")
                           ->orWhere('note', 'like', "%{$search}%");
                    });
            });
        }

        // The filter uses the business/dashboard status shown to users, not
        // the raw `sales.status` field. Raw status alone cannot represent P&L,
        // PO, invoice and export stages, which previously caused empty lists.
        $dashboardStatusFilter = $request->input('status');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->boolean('license_vnet')) {
            $query->where('is_license_vnet', true);
        }
        if ($request->boolean('trade_up')) {
            $query->whereIn('trade_up_matrix', ['correct', 'incorrect']);
        }

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by salesperson
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Search by note
        if ($request->filled('note_search')) {
            $noteSearch = $request->note_search;
            $query->where(function($q) use ($noteSearch) {
                $q->where('note', 'like', "%{$noteSearch}%")
                  ->orWhereHas('orderRequests', function ($q2) use ($noteSearch) {
                      $q2->where('note', 'like', "%{$noteSearch}%");
                  });
            });
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

        $relations = ['project', 'user', 'customer', 'quotation', 'items.product', 'paymentSchedules'];
        $query->with($relations)->orderBy('created_at', 'desc');

        // Some filters are action queues rather than a single dashboard state.
        // Keep those in SQL via the model scope; comparing dashboard_status would
        // otherwise make the "waiting for Finance" / "waiting for export" lists
        // appear empty.
        if (in_array($dashboardStatusFilter, ['pending_payment', 'pending_export', 'pending_export_approval'], true)) {
            $sales = $query->filterByStatus($dashboardStatusFilter)->paginate(10);
        } elseif ($dashboardStatusFilter) {
            $allSales = $query->get()->filter(fn (Sale $sale) => $sale->dashboard_status === $dashboardStatusFilter)->values();
            $page = max(1, (int) $request->input('page', 1));
            $perPage = 10;
            $sales = new \Illuminate\Pagination\LengthAwarePaginator(
                $allSales->forPage($page, $perPage)->values(),
                $allSales->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $sales = $query->paginate(10);
        }

        // Load payment transactions cho từng sale (để hiển thị % cọc/thanh toán)
        $saleCodes = $sales->pluck('code')->toArray();
        $paymentTransactions = \App\Models\FinancialTransaction::whereIn('reference_number', $saleCodes)
            ->where('type', 'income')
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy('reference_number');

        // Gắn vào từng sale
        foreach ($sales as $sale) {
            $sale->payment_history = $paymentTransactions->get($sale->code, collect());
        }

        // Keep the project dropdown aligned with the selected Sales PIC.
        // Showing every project made the filter misleading and slow to use.
        $projectOwnerId = $request->input('user_id');
        $projects = Project::whereIn('status', ['planning', 'in_progress'])
            ->when($projectOwnerId, fn ($projects) => $projects->where('manager_id', $projectOwnerId))
            ->orderBy('name')
            ->get();
        // Optimize: Select only needed columns
        $customers = Customer::select('id', 'name')->orderBy('name')->get();
        
        $salespersons = \App\Models\User::where('status', 'active')
            ->orWhereHas('sales')
            ->orderBy('name')
            ->get();

        return view('sales.index', compact('sales', 'projects', 'customers', 'salespersons'));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Sale::class);

        $customers = Customer::select('id', 'name', 'tax_code', 'abv_name', 'debt_days', 'payment_terms')->orderBy('name')->get();
        
        // Không load sản phẩm nữa - sẽ dùng AJAX search
        $products = collect();
        
        $projects = Project::select('id', 'code', 'name', 'customer_id', 'status')->with(['customer:id,name,tax_code,abv_name'])->whereIn('status', ['planning', 'in_progress'])->orderBy('name')->get();

        // Generate sale code
        $code = $this->generateSaleCode();

        // Pre-select project or multiple projects if passed
        $rawProjectIds = $request->input('project_ids', []);
        if (empty($rawProjectIds) && $request->filled('project_id')) {
            $rawProjectIds = [(int)$request->input('project_id')];
        } elseif (is_string($rawProjectIds)) {
            $rawProjectIds = explode(',', $rawProjectIds);
        }
        $projectIds = array_filter(array_map('intval', (array)$rawProjectIds));

        $selectedProjects = !empty($projectIds)
            ? Project::with(['customer', 'vendor', 'collaborateCustomer'])->whereIn('id', $projectIds)->get()
            : collect();

        $clearPartnerEu = $request->boolean('clear_partner_eu');
        $selectedProject = $selectedProjects->first();
        $selectedCustomerId = null;

        if (!$clearPartnerEu && $selectedProject) {
            $effectiveCust = $selectedProject->findOrCreateCustomerFromProject();
            $selectedCustomerId = $effectiveCust?->id;
            // Ensure newly created customer is in the list
            if ($effectiveCust && !$customers->contains('id', $effectiveCust->id)) {
                $customers = Customer::select('id', 'name', 'tax_code', 'abv_name', 'debt_days', 'payment_terms')->orderBy('name')->get();
            }
        }

        // Multi-currency: load active currencies + today's VND base ID
        $currencies = $this->currencyService->getActiveCurrencies();
        $baseCurrencyId = Currency::getBaseCurrencyId();
        $suppliers = Supplier::select('id', 'code', 'name')->orderByRaw("CASE WHEN name = 'Other' THEN 1 ELSE 0 END, name")->get();
        $paymentTemplates = \App\Models\PaymentTemplate::with('items')->where('is_active', true)->get();

        // Parse BOM data from selected projects if available
        $bomParser = app(BomParserService::class);
        $prefilledProducts = $bomParser->parseFromProjects($selectedProjects);

        return view('sales.create', compact(
            'customers', 'products', 'projects', 'code', 'selectedProject',
            'selectedProjects', 'clearPartnerEu', 'selectedCustomerId',
            'currencies', 'baseCurrencyId', 'suppliers', 'paymentTemplates',
            'prefilledProducts'
        ));
    }

    /**
     * API/AJAX endpoint to parse BOM text or uploaded Excel file into structured products
     */
    public function parseBom(Request $request)
    {
        $projectId = $request->input('project_id');
        $bomParser = app(BomParserService::class);
        $fileName = null;

        if ($request->hasFile('file') || $request->hasFile('bom_file')) {
            $file = $request->file('file') ?? $request->file('bom_file');
            $request->validate([
                'file' => 'nullable|file|mimes:xlsx,xls,csv,txt|max:10240',
                'bom_file' => 'nullable|file|mimes:xlsx,xls,csv,txt|max:10240',
            ]);
            $items = $bomParser->parseSpreadsheetFile($file, $projectId ? (int)$projectId : null);
            $fileName = $file->getClientOriginalName();
        } else {
            $bomText = $request->input('bom_data', '');
            $items = $bomParser->parse($bomText, $projectId ? (int)$projectId : null);
        }

        return response()->json([
            'success' => true,
            'count' => count($items),
            'items' => $items,
            'filename' => $fileName,
        ]);
    }

    /**
     * Download Excel template for quick BOM import
     */
    public function downloadBomTemplate()
    {
        $filename = 'mau_nhap_nhanh_bom_' . date('Y-m-d') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\BomTemplateExport(), $filename);
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

        // Custom validation check for new products and existing products
        foreach ($products as $key => $prod) {
            if (isset($prod['product_id'])) {
                if ($prod['product_id'] === 'new') {
                    if (empty($prod['new_name'])) {
                        return back()->withInput()->withErrors(["products.{$key}.new_name" => "Tên sản phẩm mới không được để trống."]);
                    }
                    if (empty($prod['new_code'])) {
                        return back()->withInput()->withErrors(["products.{$key}.new_code" => "Mã sản phẩm mới không được để trống."]);
                    }
                } else {
                    if (!\App\Models\Product::where('id', $prod['product_id'])->exists()) {
                        return back()->withInput()->withErrors(["products.{$key}.product_id" => "Sản phẩm không hợp lệ hoặc không tồn tại."]);
                    }
                }
            }
        }

        $saleCode = $request->input('code');
        if (empty($saleCode) || Sale::where('code', $saleCode)->exists()) {
            $saleCode = $this->generateSaleCode();
        }
        $request->merge(['code' => $saleCode]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:sales,code'],
            'type' => ['required', 'in:retail,project'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'is_license_vnet' => ['nullable', 'boolean'],
            'trade_up_matrix' => ['required', 'in:none,correct,incorrect'],
            'ohf_cost_added' => ['nullable', 'boolean'],
            'customer_id' => ['required', 'exists:customers,id'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'date' => ['required', 'date'],
            'delivery_address' => ['nullable', 'string'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'string'],
            'products.*.new_name' => ['nullable', 'string', 'max:2000'],
            'products.*.new_code' => ['nullable', 'string', 'max:50'],
            'products.*.new_unit' => ['nullable', 'string', 'max:50'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
            'products.*.vat' => ['nullable', 'numeric', 'min:-1'],
            'products.*.project_id' => ['nullable', 'exists:projects,id'],
            'multi_project_confirmed' => ['nullable', 'boolean'],
            'products.*.warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'products.*.contractor_tax_enabled' => ['nullable', 'boolean'],
            'products.*.is_from_stock' => ['nullable', 'boolean'],
            'expenses' => ['nullable', 'array'],
            'expenses.*.type' => ['nullable', 'string', 'max:100'],
            'expenses.*.input_mode' => ['nullable', 'in:percent,fixed'],
            'expenses.*.percent_value' => ['nullable', 'numeric', 'min:0'],
            'expenses.*.description' => ['nullable', 'string'],
            'expenses.*.amount' => ['nullable', 'numeric'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'payment_terms' => ['nullable', 'array'],
            'payment_terms.*.milestone_name' => ['nullable', 'string', 'max:255'],
            'payment_terms.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_term' => ['nullable', 'string', 'max:1000'],
            'payment_term_type' => ['nullable', 'string', 'max:100'],
            'has_bank_guarantee' => ['nullable', 'boolean'],
            'bank_guarantee_note' => ['nullable', 'string', 'max:1000'],
            'payment_due_date' => ['nullable', 'date'],
            'payment_exception_file' => ['nullable', 'file', 'max:20480'],
        ]);

        DB::beginTransaction();
        try {
            $dealFlags = $this->resolveDealFlags($validated, $request);
            $processedProducts = [];
            foreach ($validated['products'] as $item) {
                if ($item['product_id'] === 'new') {
                    $newCode = strtoupper(trim($item['new_code']));
                    $product = Product::where('code', $newCode)->first();
                    if (!$product) {
                        $product = Product::create([
                            'code' => $newCode,
                            'name' => $item['new_name'],
                            'unit' => $item['new_unit'] ?: 'Cái',
                            'category' => 'A',
                        ]);
                    }
                    $item['product_id'] = $product->id;
                }
                $processedProducts[] = $item;
            }
            $validated['products'] = $processedProducts;

            $code = $validated['code'];

            $customer = Customer::find($validated['customer_id']);

            // Calculate totals
            $subtotal = 0;
            $totalVatAmount = 0;
            $firstVat = null;
            $allSameVat = true;

            foreach ($validated['products'] as $item) {
                $itemSubtotal = round($item['quantity'] * $item['price'], 2);
                $subtotal += $itemSubtotal;

                // Calculate item VAT amount based on discount
                $itemDiscount = round($itemSubtotal * ($validated['discount'] ?? 0) / 100, 2);
                $itemVat = isset($item['vat']) ? (float)$item['vat'] : 8.0;
                $effectiveVat = $itemVat < 0 ? 0 : $itemVat;
                $itemBaseForVat = $itemSubtotal - $itemDiscount;
                $itemVatAmount = round($itemBaseForVat * $effectiveVat / 100, 2);
                $totalVatAmount += $itemVatAmount;

                if ($firstVat === null) {
                    $firstVat = $itemVat;
                } elseif ($firstVat !== $itemVat) {
                    $allSameVat = false;
                }
            }

            $discountAmount = round($subtotal * ($validated['discount'] ?? 0) / 100, 2);
            $total = round($subtotal - $discountAmount + $totalVatAmount, 2);

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

            $representativeVat = $allSameVat ? ($firstVat ?? 0) : ($firstVat ?? 0);

            if ($paymentTermError = $this->validatePaymentTermPercentages($request->input('payment_terms', []))) {
                return back()->withInput()->with('error', $paymentTermError);
            }

            if ($paymentTermError = $this->validateLargeOrderPaymentTerms($request->input('payment_terms', []), $total)) {
                return back()->withInput()->with('error', $paymentTermError);
            }

            $user = auth()->user();
            $canCustomizePaymentTerms = $user && (
                $user->hasRole('super_admin') || 
                $user->hasRole('admin') || 
                $user->hasRole('director') || 
                $user->hasRole('accountant')
            );

            if (false && !$canCustomizePaymentTerms && (($validated['payment_term_type'] ?? null) === 'custom' || $request->input('milestone_preset') === 'custom')) {
                return back()->withInput()->with('error', 'Tài khoản Sales không có quyền tự tùy chỉnh cấu hình điều khoản thanh toán. Vui lòng chọn điều khoản mẫu cố định.');
            }

            $exceptionFilePath = null;
            $isPaymentException = false;
            if (($validated['payment_term_type'] ?? null) === 'bod_exception' && $request->hasFile('payment_exception_file')) {
                $exceptionFilePath = $request->file('payment_exception_file')->store('payment_exceptions', 'public');
                $isPaymentException = true;
            }

            // Create sale
            $sale = Sale::create([
                'code' => $code,
                'type' => $validated['type'],
                'project_id' => $validated['project_id'] ?? null,
                ...$dealFlags,
                'customer_id' => $validated['customer_id'],
                'contact_id' => $validated['contact_id'],
                'customer_name' => $customer->name,
                'user_id' => auth()->id(),
                'date' => $validated['date'],
                'delivery_address' => $validated['delivery_address'],
                'subtotal' => $isForeign ? $this->currencyService->toBase($subtotal, $exchangeRate) : $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'vat' => $representativeVat,
                'vat_amount' => $isForeign ? $this->currencyService->toBase($totalVatAmount, $exchangeRate) : $totalVatAmount,
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
                'payment_terms' => $request->input('payment_terms'),
                'payment_term' => $validated['payment_term'] ?? null,
                'payment_term_type' => $validated['payment_term_type'] ?? null,
                'has_bank_guarantee' => $request->boolean('has_bank_guarantee'),
                'bank_guarantee_note' => $request->boolean('has_bank_guarantee') ? ($validated['bank_guarantee_note'] ?? null) : null,
                'payment_due_date' => $validated['payment_due_date'] ?? null,
                'is_payment_exception' => $isPaymentException,
                'payment_exception_file' => $exceptionFilePath,
            ]);

            // Create sale items with cost price and project
            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                $costPrice = $product->calculated_cost;
                $quantity = $item['quantity'];

                // Inherit sale-level project_id
                $itemProjectId = $validated['project_id'] ?? null;

                // Get warranty: use input value if provided, otherwise use product default
                $warrantyMonths = isset($item['warranty_months']) && $item['warranty_months'] !== ''
                    ? (int) $item['warranty_months']
                    : $product->warranty_months;

                $itemSubtotal = round($quantity * $item['price'], 2);
                $itemDiscount = round($itemSubtotal * ($validated['discount'] ?? 0) / 100, 2);
                $itemVat = isset($item['vat']) ? (float)$item['vat'] : 8.0;
                $effectiveVat = $itemVat < 0 ? 0 : $itemVat;
                $itemBaseForVat = $itemSubtotal - $itemDiscount;
                $itemVatAmount = round($itemBaseForVat * $effectiveVat / 100, 2);

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
                    'contractor_tax_enabled' => isset($item['contractor_tax_enabled']) ? (bool) $item['contractor_tax_enabled'] : false,
                    'vat' => $itemVat,
                    'vat_amount' => $itemVatAmount,
                    'is_from_stock' => !empty($item['is_from_stock']),
                ]);
            }

            // Create sale expenses
            if (!empty($validated['expenses'])) {
                $uniqueExpenses = collect($validated['expenses'])
                    ->filter(fn($e) => !empty(trim($e['type'] ?? '')))
                    ->unique(fn($e) => trim($e['type']))
                    ->values();

                foreach ($uniqueExpenses as $expense) {
                    $inputMode = $expense['input_mode'] ?? 'fixed';
                    $amount = $inputMode === 'percent'
                        ? 0
                        : round(floatval($expense['amount'] ?? 0), 2);
                    $percentValue = $inputMode === 'percent'
                        ? round(floatval($expense['percent_value'] ?? 0), 2)
                        : null;

                    SaleExpense::create([
                        'sale_id' => $sale->id,
                        'type' => trim($expense['type']),
                        'input_mode' => $inputMode,
                        'percent_value' => $percentValue,
                        'description' => $expense['description'] ?? '',
                        'amount' => $amount,
                    ]);
                }
            }

            $this->syncOrderExpensesToPnlItems($sale);
            $sale->load(['items', 'expenses']);

            // Calculate margin and debt
            $sale->calculateMargin();
            $sale->updateDebt();
            $sale->save();

            // Xử lý Yêu cầu đặt hàng nếu có
            if ($request->has('order_request_items')) {
                $hasValidItems = collect($request->input('order_request_items'))
                    ->filter(fn($item) => !empty($item['vendor']) && !empty($item['part_number']))
                    ->isNotEmpty();

                if ($hasValidItems) {
                    $orderRequest = \App\Models\SaleOrderRequest::create([
                        'code' => \App\Models\SaleOrderRequest::generateCode(),
                        'sale_id' => $sale->id,
                        'created_by' => auth()->id(),
                        'note' => $request->input('order_request_note'),
                        'sent_at' => now(),
                        'status' => \App\Models\SaleOrderRequest::STATUS_PENDING_ADMIN,
                    ]);

                    foreach ($request->input('order_request_items') as $item) {
                        if (empty($item['vendor']) || empty($item['part_number'])) continue;
                        \App\Models\SaleOrderRequestItem::create([
                            'sale_order_request_id' => $orderRequest->id,
                            'vendor' => $item['vendor'],
                            'type' => $item['type'],
                            'part_number' => $item['part_number'],
                            'serial_number' => $item['serial_number'] ?? null,
                            'exp_date' => $item['exp_date'] ?? null,
                            'si_name' => $item['si_name'] ?? '',
                            'pos_id' => $item['pos_id'] ?? null,
                            'eu_name_mst' => !empty($item['eu_name']) && !empty($item['mst'])
                                ? trim($item['eu_name']) . ' - ' . trim($item['mst'])
                                : (trim($item['eu_name'] ?? '') ?: trim($item['eu_name_mst'] ?? '')),
                            'address' => $item['address'] ?? null,
                        ]);
                    }

                    // Upload files for order request
                    if ($request->hasFile('order_request_files')) {
                        foreach ($request->file('order_request_files') as $file) {
                            $path = $file->store('sale-order-requests/' . $orderRequest->id, 'public');
                            \App\Models\SaleOrderRequestAttachment::create([
                                'sale_order_request_id' => $orderRequest->id,
                                'file_name' => $file->getClientOriginalName(),
                                'file_path' => $path,
                                'mime_type' => $file->getMimeType(),
                                'file_size' => $file->getSize(),
                                'uploaded_by' => auth()->id(),
                            ]);
                        }
                    }

                    // Notify PO team
                    $this->notifyPoTeam($sale, $orderRequest);
                }
            }

            // Link all involved projects with this sale order
            $allProjectIds = collect($validated['products'])
                ->pluck('project_id')
                ->push($validated['project_id'] ?? null)
                ->filter()
                ->unique()
                ->values();

            foreach ($allProjectIds as $pId) {
                $p = Project::find($pId);
                if ($p) {
                    if (empty($p->po_code)) {
                        $p->update(['po_code' => $sale->code]);
                    }
                    app(\App\Services\ActivityLogService::class)->log(
                        'updated',
                        $p,
                        ['sale_id' => $sale->id, 'sale_code' => $sale->code, 'changes' => ['po_code' => ['old' => null, 'new' => $sale->code]]],
                        "Đã tạo đơn hàng bán: {$sale->code}"
                    );
                }
            }

            DB::commit();

            return redirect()->route('sales.show', $sale->id)
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

        $user = auth()->user();
        if ($user->hasAnyRole(['purchase_manager', 'purchase_staff'])
            && !$user->hasAnyRole(['super_admin', 'admin', 'director'])
            && !$sale->orderRequests()->whereIn('status', [
                \App\Models\SaleOrderRequest::STATUS_SUBMITTED,
                \App\Models\SaleOrderRequest::STATUS_PROCESSING,
                \App\Models\SaleOrderRequest::STATUS_COMPLETED,
            ])->exists()) {
            abort(404);
        }

        // Auto sync database status if completed
        if ($sale->dashboard_status === 'completed' && $sale->status !== 'completed') {
            $sale->update(['status' => 'completed']);
        } elseif ($sale->dashboard_status === 'invoiced' && $sale->status === 'approved') {
            $sale->update(['status' => 'shipping']);
        }

        $sale->load(['items.product', 'items.project', 'customer', 'expenses', 'project', 'orderRequests.items', 'orderRequests.attachments']);
        $currencies = $this->currencyService->getActiveCurrencies();
        $baseCurrencyId = Currency::getBaseCurrencyId();
        $suppliers = Supplier::select('id', 'code', 'name')->orderByRaw("CASE WHEN name = 'Other' THEN 1 ELSE 0 END, name")->get();
        $paymentTemplates = \App\Models\PaymentTemplate::with('items')->where('is_active', true)->get();

        return view('sales.show', compact('sale', 'currencies', 'baseCurrencyId', 'suppliers', 'paymentTemplates'));
    }

    /**
     * Show the form for editing the specified sale.
     */
    public function edit(Sale $sale)
    {
        if ($sale->status === 'cancelled') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Đơn hàng đã hủy, không thể chỉnh sửa.');
        }

        if ($sale->hasPayment()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Đơn hàng đã có phát sinh thanh toán, không được phép chỉnh sửa thông tin đơn hàng và BOM.');
        }

        $this->authorize('update', $sale);

        $sale->load([
            'items.product:id,code,name,unit,warranty_months',
            'expenses',
            'customer:id,name,tax_code,abv_name,debt_days,payment_terms',
            'project:id,code,name,customer_id',
        ]);
        $customers = Customer::select('id', 'name', 'tax_code', 'abv_name', 'debt_days', 'payment_terms')->orderBy('name')->get();
        
        // Không load sản phẩm nữa - sẽ dùng AJAX search như trang create
        $products = collect();
        
        $projects = Project::select('id', 'code', 'name', 'customer_id', 'status')->with(['customer:id,name,tax_code,abv_name'])->whereIn('status', ['planning', 'in_progress'])->orderBy('name')->get();

        $currencies = $this->currencyService->getActiveCurrencies();
        $baseCurrencyId = Currency::getBaseCurrencyId();
        $suppliers = Supplier::select('id', 'code', 'name')->orderByRaw("CASE WHEN name = 'Other' THEN 1 ELSE 0 END, name")->get();
        $paymentTemplates = \App\Models\PaymentTemplate::with('items')->where('is_active', true)->get();

        return view('sales.edit', compact('sale', 'customers', 'products', 'projects', 'currencies', 'baseCurrencyId', 'suppliers', 'paymentTemplates'));
    }

    /**
     * Update the specified sale in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        if ($sale->status === 'cancelled') {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Đơn hàng đã hủy, không thể chỉnh sửa.');
        }

        if ($sale->hasPayment()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Đơn hàng đã có phát sinh thanh toán, không được phép chỉnh sửa thông tin đơn hàng và BOM.');
        }

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

        // Custom validation check for new products and existing products
        foreach ($products as $key => $prod) {
            if (isset($prod['product_id'])) {
                if ($prod['product_id'] === 'new') {
                    if (empty($prod['new_name'])) {
                        return back()->withInput()->withErrors(["products.{$key}.new_name" => "Tên sản phẩm mới không được để trống."]);
                    }
                    if (empty($prod['new_code'])) {
                        return back()->withInput()->withErrors(["products.{$key}.new_code" => "Mã sản phẩm mới không được để trống."]);
                    }
                } else {
                    if (!\App\Models\Product::where('id', $prod['product_id'])->exists()) {
                        return back()->withInput()->withErrors(["products.{$key}.product_id" => "Sản phẩm không hợp lệ hoặc không tồn tại."]);
                    }
                }
            }
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('sales')->ignore($sale->id)],
            'type' => ['required', 'in:retail,project'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'is_license_vnet' => ['nullable', 'boolean'],
            'trade_up_matrix' => ['required', 'in:none,correct,incorrect'],
            'ohf_cost_added' => ['nullable', 'boolean'],
            'customer_id' => ['required', 'exists:customers,id'],
            'contact_id' => ['required', 'exists:contacts,id'],
            'date' => ['required', 'date'],
            'delivery_address' => ['nullable', 'string'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,approved,shipping,completed,cancelled'],
            'note' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'string'],
            'products.*.new_name' => ['nullable', 'string', 'max:2000'],
            'products.*.new_code' => ['nullable', 'string', 'max:50'],
            'products.*.new_unit' => ['nullable', 'string', 'max:50'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.price' => ['required', 'numeric', 'min:0'],
            'products.*.vat' => ['nullable', 'numeric', 'min:-1'],
            'products.*.project_id' => ['nullable', 'exists:projects,id'],
            'multi_project_confirmed' => ['nullable', 'boolean'],
            'products.*.warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'products.*.contractor_tax_enabled' => ['nullable', 'boolean'],
            'products.*.is_from_stock' => ['nullable', 'boolean'],
            'expenses' => ['nullable', 'array'],
            'expenses.*.type' => ['nullable', 'string', 'max:100'],
            'expenses.*.input_mode' => ['nullable', 'in:percent,fixed'],
            'expenses.*.percent_value' => ['nullable', 'numeric', 'min:0'],
            'expenses.*.description' => ['nullable', 'string'],
            'expenses.*.amount' => ['nullable', 'numeric'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'payment_terms' => ['nullable', 'array'],
            'payment_terms.*.milestone_name' => ['nullable', 'string', 'max:255'],
            'payment_terms.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_term' => ['nullable', 'string', 'max:1000'],
            'payment_term_type' => ['nullable', 'string', 'max:100'],
            'has_bank_guarantee' => ['nullable', 'boolean'],
            'bank_guarantee_note' => ['nullable', 'string', 'max:1000'],
            'payment_due_date' => ['nullable', 'date'],
            'payment_exception_file' => ['nullable', 'file', 'max:20480'],
        ]);

        DB::beginTransaction();
        try {
            $dealFlags = $this->resolveDealFlags($validated, $request);
            $processedProducts = [];
            foreach ($validated['products'] as $item) {
                if ($item['product_id'] === 'new') {
                    $newCode = strtoupper(trim($item['new_code']));
                    $product = Product::where('code', $newCode)->first();
                    if (!$product) {
                        $product = Product::create([
                            'code' => $newCode,
                            'name' => $item['new_name'],
                            'unit' => $item['new_unit'] ?: 'Cái',
                            'category' => 'A',
                        ]);
                    }
                    $item['product_id'] = $product->id;
                }
                $processedProducts[] = $item;
            }
            $validated['products'] = $processedProducts;

            $customer = Customer::find($validated['customer_id']);

            // Calculate totals
            $subtotal = 0;
            $totalVatAmount = 0;
            $firstVat = null;
            $allSameVat = true;

            foreach ($validated['products'] as $item) {
                $itemSubtotal = round($item['quantity'] * $item['price'], 2);
                $subtotal += $itemSubtotal;

                $itemDiscount = round($itemSubtotal * ($validated['discount'] ?? 0) / 100, 2);
                $itemVat = isset($item['vat']) ? (float)$item['vat'] : 8.0;
                $effectiveVat = $itemVat < 0 ? 0 : $itemVat;
                $itemBaseForVat = $itemSubtotal - $itemDiscount;
                $itemVatAmount = round($itemBaseForVat * $effectiveVat / 100, 2);
                $totalVatAmount += $itemVatAmount;

                if ($firstVat === null) {
                    $firstVat = $itemVat;
                } elseif ($firstVat !== $itemVat) {
                    $allSameVat = false;
                }
            }

            $discountAmount = round($subtotal * ($validated['discount'] ?? 0) / 100, 2);
            $total = round($subtotal - $discountAmount + $totalVatAmount, 2);

            // Determine currency
            $currencyId = $validated['currency_id'] ?? Currency::getBaseCurrencyId();
            $exchangeRate = $validated['exchange_rate'] ?? 1;
            $isForeign = $this->currencyService->isForeignTransaction($currencyId);

            $totalForeign = $total;
            if ($isForeign && $exchangeRate > 1) {
                $total = $this->currencyService->toBase($totalForeign, $exchangeRate);
            }

            $representativeVat = $allSameVat ? ($firstVat ?? 0) : ($firstVat ?? 0);

            if ($paymentTermError = $this->validatePaymentTermPercentages($request->input('payment_terms', []))) {
                return back()->withInput()->with('error', $paymentTermError);
            }

            if ($paymentTermError = $this->validateLargeOrderPaymentTerms($request->input('payment_terms', []), $total)) {
                return back()->withInput()->with('error', $paymentTermError);
            }

            $user = auth()->user();
            $canCustomizePaymentTerms = $user && (
                $user->hasRole('super_admin') || 
                $user->hasRole('admin') || 
                $user->hasRole('director') || 
                $user->hasRole('accountant')
            );

            if (false && !$canCustomizePaymentTerms && (($validated['payment_term_type'] ?? null) === 'custom' || $request->input('milestone_preset') === 'custom')) {
                return back()->withInput()->with('error', 'Tài khoản Sales không có quyền tự tùy chỉnh cấu hình điều khoản thanh toán. Vui lòng chọn điều khoản mẫu cố định.');
            }

            $isPaymentException = $sale->is_payment_exception;
            $exceptionFilePath = $sale->payment_exception_file;
            if ($request->hasFile('payment_exception_file')) {
                $exceptionFilePath = $request->file('payment_exception_file')->store('payment_exceptions', 'public');
                $isPaymentException = true;
            } elseif (($validated['payment_term_type'] ?? null) === 'bod_exception') {
                $isPaymentException = true;
            } else {
                $isPaymentException = false;
                $exceptionFilePath = null;
            }

            // Update sale
            $sale->update([
                'code' => $validated['code'],
                'type' => $validated['type'],
                'project_id' => $validated['project_id'] ?? null,
                ...$dealFlags,
                'customer_id' => $validated['customer_id'],
                'contact_id' => $validated['contact_id'],
                'customer_name' => $customer->name,
                'date' => $validated['date'],
                'delivery_address' => $validated['delivery_address'],
                'subtotal' => $isForeign ? $this->currencyService->toBase($subtotal, $exchangeRate) : $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'vat' => $representativeVat,
                'vat_amount' => $isForeign ? $this->currencyService->toBase($totalVatAmount, $exchangeRate) : $totalVatAmount,
                'total' => $total,
                'cost' => $validated['cost'] ?? 0,
                'paid_amount' => $validated['paid_amount'] ?? 0,
                'status' => $validated['status'] ?? $sale->status,
                'note' => $validated['note'],
                'currency_id' => $currencyId,
                'exchange_rate' => $exchangeRate,
                'total_foreign' => $totalForeign,
                'payment_terms' => $request->input('payment_terms'),
                'payment_term' => $validated['payment_term'] ?? null,
                'payment_term_type' => $validated['payment_term_type'] ?? null,
                'has_bank_guarantee' => $request->boolean('has_bank_guarantee'),
                'bank_guarantee_note' => $request->boolean('has_bank_guarantee') ? ($validated['bank_guarantee_note'] ?? null) : null,
                'payment_due_date' => $validated['payment_due_date'] ?? null,
                'is_payment_exception' => $isPaymentException,
                'payment_exception_file' => $exceptionFilePath,
            ]);

            // 1. Thu thập dữ liệu P&L từ request 'items' (đã thêm các input ẩn trong pnl-tab)
            $requestPnlItems = $request->input('items', []);
            $pnlMap = [];
            foreach ($requestPnlItems as $reqPnl) {
                if (isset($reqPnl['product_id'])) {
                    $pid = $reqPnl['product_id'];
                    if (!isset($pnlMap[$pid])) $pnlMap[$pid] = [];
                    $pnlMap[$pid][] = $reqPnl;
                }
            }

            // 2. Lưu dữ liệu P&L của items cũ để dự phòng trường hợp không có trong request (vd: tab P&L chưa load đủ)
            $oldPnlData = [];
            foreach ($sale->items as $oldItem) {
                $pid = $oldItem->product_id;
                if (!isset($oldPnlData[$pid])) $oldPnlData[$pid] = [];
                $oldPnlData[$pid][] = [
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
                    'technical_poc_percent' => $oldItem->technical_poc_percent,
                    'implementation_cost' => $oldItem->implementation_cost,
                    'implementation_cost_percent' => $oldItem->implementation_cost_percent,
                    'contractor_tax' => $oldItem->contractor_tax,
                    'contractor_tax_percent' => $oldItem->contractor_tax_percent,
                    'extra_expenses_data' => $oldItem->extra_expenses_data,
                    'supplier_id' => $oldItem->supplier_id,
                    'is_service' => $oldItem->is_service,
                ];
            }

            // 3. Map chi phí cũ sang ID chi phí mới
            $oldExpenseTypes = $sale->expenses->pluck('type', 'id')->toArray();
            
            // Update sale expenses (Làm trước để có ID mới)
            $sale->expenses()->delete();
            $newExpensesMap = []; // oldId => newId
            
            if (!empty($validated['expenses'])) {
                $uniqueExpenses = collect($validated['expenses'])
                    ->filter(fn($e) => !empty(trim($e['type'] ?? '')))
                    ->unique(fn($e) => trim($e['type']))
                    ->values();

                foreach ($uniqueExpenses as $expense) {
                    $type = trim($expense['type']);
                    $inputMode = $expense['input_mode'] ?? 'fixed';
                    $amount = $inputMode === 'percent'
                        ? 0
                        : round(floatval($expense['amount'] ?? 0), 2);
                    $percentValue = $inputMode === 'percent'
                        ? round(floatval($expense['percent_value'] ?? 0), 2)
                        : null;

                    $newExp = SaleExpense::create([
                        'sale_id' => $sale->id,
                        'type' => $type,
                        'input_mode' => $inputMode,
                        'percent_value' => $percentValue,
                        'description' => $expense['description'] ?? '',
                        'amount' => $amount,
                    ]);
                    
                    $oldId = array_search($type, $oldExpenseTypes);
                    if ($oldId !== false) {
                        $newExpensesMap[$oldId] = $newExp->id;
                        unset($oldExpenseTypes[$oldId]); // Đã map xong thì xóa đi để tránh trùng
                    }
                }
            }

            // 4. Xóa dữ liệu PNL cũ của các chi phí (Standard) đã bị người dùng xóa
            $newExpenseTypes = array_column($validated['expenses'] ?? [], 'type');
            $activeStandardFields = [];
            $standardExpenseMapping = [
                'Chi phí Tài chính' => ['finance_cost_percent'],
                'Lãi vay phát sinh do nợ quá hạn' => ['overdue_interest_cost', 'overdue_interest_percent'],
                'Chi phí Quản lí, Back Office & kỹ thuật' => ['management_cost_percent'],
                '24x7 Support cost' => ['support_247_cost_percent'],
                'Other Support' => ['other_support_cost'],
                'Technical support/POC' => ['technical_poc_cost', 'technical_poc_percent'],
                'Technical support/POC 30%' => ['technical_poc_cost', 'technical_poc_percent'],
                'Chi phí triển khai hợp đồng' => ['implementation_cost', 'implementation_cost_percent'],
                'Thuế nhà thầu' => ['contractor_tax', 'contractor_tax_percent'],
            ];
            foreach ($standardExpenseMapping as $type => $fields) {
                if (in_array($type, $newExpenseTypes)) {
                    foreach ($fields as $field) {
                        $activeStandardFields[$field] = true;
                    }
                }
            }
            foreach ($oldPnlData as &$pnlItemsForProduct) {
                foreach ($pnlItemsForProduct as &$pnlItem) {
                    $allStandardFields = [
                        'finance_cost_percent', 'overdue_interest_cost', 'overdue_interest_percent', 
                        'management_cost_percent', 'support_247_cost_percent', 'other_support_cost', 
                        'technical_poc_cost', 'technical_poc_percent', 'implementation_cost', 
                        'implementation_cost_percent', 'contractor_tax', 'contractor_tax_percent'
                    ];
                    foreach ($allStandardFields as $field) {
                        if (!isset($activeStandardFields[$field])) {
                            $pnlItem[$field] = null;
                        }
                    }
                }
                unset($pnlItem);
            }
            unset($pnlItemsForProduct);

            // Delete old items and create new ones with cost price and project
            $sale->items()->delete();

            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                $costPrice = $product->calculated_cost;
                $quantity = $item['quantity'];

                // Inherit sale-level project_id
                $itemProjectId = $validated['project_id'] ?? null;

                // Get warranty: use input value if provided, otherwise use product default
                $warrantyMonths = isset($item['warranty_months']) && $item['warranty_months'] !== ''
                    ? (int) $item['warranty_months']
                    : $product->warranty_months;

                // Lấy dữ liệu P&L: Ưu tiên dữ liệu từ form P&L (items[]), sau đó mới tới Data cũ, cuối cùng là mặc định
                $reqPnl = [];
                if (!empty($pnlMap[$item['product_id']])) {
                    $reqPnl = array_shift($pnlMap[$item['product_id']]);
                }

                $oldPnl = [];
                if (!empty($oldPnlData[$item['product_id']])) {
                    $oldPnl = array_shift($oldPnlData[$item['product_id']]);
                }

                // Helper xử lý giá trị NULL/0/NA
                $getVal = function($fieldReq, $fieldOld, $fallback = null, $naField = null) use ($reqPnl, $oldPnl) {
                    // Nếu có flag N/A trong request -> ép về 0
                    if ($naField && isset($reqPnl[$naField]) && $reqPnl[$naField] == '1') {
                        return 0;
                    }
                    if (isset($reqPnl[$fieldReq]) && $reqPnl[$fieldReq] !== '') {
                        return $reqPnl[$fieldReq];
                    }
                    return $oldPnl[$fieldOld] ?? $fallback;
                };

                $getPercentVal = function($fieldReq, $fieldOld, $naField = null) use ($reqPnl, $oldPnl) {
                    // Nếu có flag N/A trong request -> ép về 0
                    if ($naField && isset($reqPnl[$naField]) && $reqPnl[$naField] == '1') {
                        return 0;
                    }
                    if (isset($reqPnl[$fieldReq]) && $reqPnl[$fieldReq] !== '') {
                        return $reqPnl[$fieldReq];
                    }
                    return (isset($oldPnl[$fieldOld]) && $oldPnl[$fieldOld] !== '') ? $oldPnl[$fieldOld] : null;
                };

                $oldExtraData = $oldPnl['extra_expenses_data'] ?? [];
                $newExtraData = [];
                if (is_array($oldExtraData)) {
                    foreach ($oldExtraData as $oldId => $val) {
                        if (isset($newExpensesMap[$oldId])) {
                            $newExtraData[(string) $newExpensesMap[$oldId]] = $val;
                        }
                    }
                }

                $itemSubtotal = round($quantity * $item['price'], 2);
                $itemDiscount = round($itemSubtotal * ($validated['discount'] ?? 0) / 100, 2);
                $itemVat = isset($item['vat']) ? (float)$item['vat'] : 8.0;
                $effectiveVat = $itemVat < 0 ? 0 : $itemVat;
                $itemBaseForVat = $itemSubtotal - $itemDiscount;
                $itemVatAmount = round($itemBaseForVat * $effectiveVat / 100, 2);

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
                    'vat' => $itemVat,
                    'vat_amount' => $itemVatAmount,
                    // Khôi phục & Cập nhật dữ liệu P&L
                    'usd_price' => $getVal('usd_price', 'usd_price', 0),
                    'exchange_rate' => $getVal('exchange_rate', 'exchange_rate', ($exchangeRate ?: 1)),
                    'discount_rate' => $getVal('discount_rate', 'discount_rate', 0),
                    'import_cost_rate' => $getVal('import_cost_rate', 'import_cost_rate', 0),
                    'estimated_cost_usd' => $oldPnl['estimated_cost_usd'] ?? 0,
                    'finance_cost_percent' => $getPercentVal('finance_cost_percent', 'finance_cost_percent', 'finance_na'),
                    'overdue_interest_cost' => $getVal('overdue_interest_cost', 'overdue_interest_cost', 0, 'overdue_na'),
                    'overdue_interest_percent' => $getPercentVal('overdue_interest_percent', 'overdue_interest_percent', 'overdue_na'),
                    'management_cost_percent' => $getPercentVal('management_cost_percent', 'management_cost_percent', 'management_na'),
                    'support_247_cost_percent' => $getPercentVal('support_247_cost_percent', 'support_247_cost_percent', 'support_na'),
                    'other_support_cost' => $getPercentVal('other_support_cost', 'other_support_cost', 'other_na'),
                    'technical_poc_cost' => $getVal('technical_poc_cost', 'technical_poc_cost', 0),
                    'technical_poc_percent' => $getPercentVal('technical_poc_percent', 'technical_poc_percent'),
                    'implementation_cost' => $getVal('implementation_cost', 'implementation_cost', 0),
                    'implementation_cost_percent' => $getPercentVal('implementation_cost_percent', 'implementation_cost_percent'),
                    'contractor_tax' => $getVal('contractor_tax', 'contractor_tax', 0),
                    'contractor_tax_percent' => $getPercentVal('contractor_tax_percent', 'contractor_tax_percent'),
                    'extra_expenses_data' => !empty($newExtraData) ? $newExtraData : null,
                    'contractor_tax_enabled' => isset($item['contractor_tax_enabled']) 
                        ? (bool) $item['contractor_tax_enabled'] 
                        : (isset($reqPnl['contractor_tax_enabled']) 
                            ? (bool) $reqPnl['contractor_tax_enabled'] 
                            : (isset($oldPnl['contractor_tax_enabled']) ? (bool) $oldPnl['contractor_tax_enabled'] : false)),
                    'supplier_id' => $getVal('supplier_id', 'supplier_id', null),
                    'is_service' => (bool)$getVal('is_service', 'is_service', false),
                    'is_from_stock' => !empty($item['is_from_stock']) || (bool)$getVal('is_from_stock', 'is_from_stock', false),
                ]);
            }

            $this->syncOrderExpensesToPnlItems($sale);
            $sale->load(['items', 'expenses']);

            // Calculate margin and debt AFTER creating new items
            $sale->calculateMargin();
            $sale->updateDebt();
            $sale->save();

            // Xử lý Yêu cầu đặt hàng nếu có
            if ($request->has('order_request_items')) {
                $hasValidItems = collect($request->input('order_request_items'))
                    ->filter(fn($item) => !empty($item['vendor']) && !empty($item['part_number']))
                    ->isNotEmpty();

                if ($hasValidItems) {
                    // Delete old order request if updating
                    $existingRequest = $sale->orderRequests()->first();
                    if ($existingRequest) {
                        $existingRequest->items()->delete();
                        // Keep old attachments, only add new ones
                    }

                    $orderRequest = $existingRequest ?? \App\Models\SaleOrderRequest::create([
                        'code' => \App\Models\SaleOrderRequest::generateCode(),
                        'sale_id' => $sale->id,
                        'created_by' => auth()->id(),
                        'sent_at' => now(),
                    ]);

                    $orderRequest->update([
                        'note' => $request->input('order_request_note'),
                        'status' => \App\Models\SaleOrderRequest::STATUS_PENDING_ADMIN,
                    ]);

                    // Recreate items
                    $orderRequest->items()->delete();
                    foreach ($request->input('order_request_items') as $item) {
                        if (empty($item['vendor']) || empty($item['part_number'])) continue;
                        \App\Models\SaleOrderRequestItem::create([
                            'sale_order_request_id' => $orderRequest->id,
                            'vendor' => $item['vendor'],
                            'type' => $item['type'],
                            'part_number' => $item['part_number'],
                            'serial_number' => $item['serial_number'] ?? null,
                            'exp_date' => $item['exp_date'] ?? null,
                            'si_name' => $item['si_name'] ?? '',
                            'pos_id' => $item['pos_id'] ?? null,
                            'eu_name_mst' => !empty($item['eu_name']) && !empty($item['mst'])
                                ? trim($item['eu_name']) . ' - ' . trim($item['mst'])
                                : (trim($item['eu_name'] ?? '') ?: trim($item['eu_name_mst'] ?? '')),
                            'address' => $item['address'] ?? null,
                        ]);
                    }

                    // Upload new files
                    if ($request->hasFile('order_request_files')) {
                        foreach ($request->file('order_request_files') as $file) {
                            $path = $file->store('sale-order-requests/' . $orderRequest->id, 'public');
                            \App\Models\SaleOrderRequestAttachment::create([
                                'sale_order_request_id' => $orderRequest->id,
                                'file_name' => $file->getClientOriginalName(),
                                'file_path' => $path,
                                'mime_type' => $file->getMimeType(),
                                'file_size' => $file->getSize(),
                                'uploaded_by' => auth()->id(),
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->route('sales.show', $sale->id)
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

        // Chặn xóa nếu đơn hàng đã được duyệt hoặc ở các trạng thái sau đó
        if ($sale->status !== 'pending') {
            return back()->with('error', 'Không thể xóa đơn hàng đã duyệt hoặc đang trong quá trình thực hiện.');
        }

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

        $filters = $request->only(['search', 'status', 'type', 'project_id', 'customer_id', 'date_from', 'date_to', 'license_vnet', 'trade_up']);
        $filename = 'don-hang-ban-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new SalesExport($filters), $filename);
    }

    /**
     * Generate PDF/print invoice
     */
    public function generatePdf(Sale $sale, Request $request)
    {
        // Return 404 instead of 403 if user lacks permission
        if (!auth()->user()->can('view', $sale)) {
            abort(404);
        }

        $sale->load('items.product', 'customer', 'expenses', 'project', 'currency');
        
        $isDraft = $request->has('is_draft') || $sale->invoiceRequests()->where('status', 'draft_issued')->exists();

        return view('sales.invoice', compact('sale', 'isDraft'));
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
    public function sendEmail(Sale $sale, Request $request)
    {
        // Return 404 instead of 403 if user lacks permission
        if (!auth()->user()->can('view', $sale)) {
            abort(404);
        }

        if ($sale->status === 'cancelled') {
            return back()->with('error', 'Không thể gửi email cho đơn hàng đã hủy.');
        }

        $sale->load('items', 'customer');

        $toEmail = $request->input('to_email') ?: ($sale->customer->email ?? null);
        if (!$toEmail) {
            return back()->with('error', 'Khách hàng không có email.');
        }

        // Find linked official invoice request file
        $attachmentPath = null;
        $invoiceRequest = \App\Models\InvoiceRequest::where('sale_id', $sale->id)
            ->where('status', 'official_issued')
            ->orderBy('id', 'desc')
            ->first();
        if ($invoiceRequest && $invoiceRequest->official_path) {
            $attachmentPath = $invoiceRequest->official_path;
        }

        try {
            \Mail::to($toEmail)->send(new \App\Mail\SaleInvoiceMail($sale, $attachmentPath));

            return back()->with('success', 'Đã gửi hóa đơn qua email đến ' . $toEmail);
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
            'payment_label' => ['nullable', 'string', 'max:100'],
            'payment_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'note' => ['nullable', 'string'],
            'proof_file' => ['required', 'file', 'max:20480'], // Yêu cầu UNC
        ]);

        if (!$request->hasFile('proof_file')) {
            return back()->with('error', 'Vui lòng tải lên chứng từ UNC.');
        }

        DB::beginTransaction();
        try {
            $amountVnd = $validated['amount'];
            $paymentCurrencyId = $validated['currency_id'] ?? $sale->currency_id;
            $paymentRate = $validated['exchange_rate'] ?? 1;

            $isBase = $paymentCurrencyId == \App\Models\Currency::getBaseCurrencyId();
            $actualAmountVnd = $isBase ? $amountVnd : round($amountVnd * $paymentRate, 0);

            // Tải lên file
            $file = $request->file('proof_file');
            $path = $file->store('payment-proofs/' . $sale->id, 'public');

            // Tìm hoặc tạo Milestone tương ứng
            $label = $validated['payment_label'] ?: 'Khác';
            $schedule = $sale->paymentSchedules()->where('milestone_name', $label)->first();

            if (!$schedule) {
                // Tạo một mốc thanh toán ad-hoc nếu không tìm thấy tên phù hợp
                $schedule = $sale->paymentSchedules()->create([
                    'sale_id' => $sale->id,
                    'milestone_name' => $label,
                    'percentage' => $validated['payment_percent'] ?? 0,
                    'amount' => $actualAmountVnd,
                    'trigger_type' => 'MANUAL',
                    'due_base' => 'contract_date',
                    'status' => 'pending_finance',
                    'proof_file_path' => $path,
                ]);
            } else {
                $schedule->status = 'pending_finance';
                $schedule->proof_file_path = $path;
                $schedule->save();
            }

            // Tạo chứng từ PaymentEvidence
            \App\Models\PaymentEvidence::create([
                'schedule_id' => $schedule->id,
                'doc_type' => 'unc',
                'reference_number' => $sale->code,
                'amount' => $actualAmountVnd,
                'file_path' => $path,
                'uploaded_by' => auth()->id(),
                'status' => 'pending',
                'notes' => $validated['note'] ?? null,
            ]);

            // Ghi log phê duyệt thanh toán
            \App\Models\PaymentApprovalLog::create([
                'schedule_id' => $schedule->id,
                'sale_id' => $sale->id,
                'action' => 'proof_uploaded',
                'old_value' => 'pending',
                'new_value' => 'pending_finance',
                'reason' => 'Sales ghi nhận thanh toán kèm UNC qua modal. Ghi chú: ' . ($validated['note'] ?? ''),
                'attachment_path' => $path,
                'performed_by' => auth()->id(),
                'performed_at' => now(),
            ]);

            // Gửi thông báo cho Kế toán / Admin
            $accountants = User::whereHas('roles', function ($q) {
                $q->whereIn('slug', ['accountant', 'admin', 'super_admin']);
            })->get();

            $senderName = auth()->user()->name ?? 'Sales';
            foreach ($accountants as $acc) {
                \App\Models\Notification::create([
                    'user_id' => $acc->id,
                    'type' => 'payment_alert',
                    'title' => 'Yêu cầu xác nhận thanh toán',
                    'message' => "{$senderName} đã upload UNC ghi nhận thanh toán đợt \"" . ($schedule->milestone_name) . "\" của đơn {$sale->code}.",
                    'link' => route('sales.show', $sale->id),
                    'icon' => 'fas fa-file-invoice-dollar',
                    'color' => 'yellow',
                ]);
            }

            DB::commit();
            return back()->with('success', 'Đã tải lên UNC và gửi yêu cầu xác nhận thanh toán đến phòng Tài chính/Kế toán.');
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
            'cancellation_reason' => ['nullable', 'string', 'min:10', 'max:1000'],
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

        // Once any payment or purchased item has been received, cancellation is not allowed directly
        if ($newStatus === 'cancelled') {
            if ($sale->hasPayment()) {
                return back()->with('error', 'Không thể hủy đơn hàng đã có thanh toán. Vui lòng hoàn tất hoặc xử lý hoàn tiền trước.');
            }

            $hasGoodsReceived = $sale->all_purchase_orders
                ->contains(fn ($purchaseOrder) => in_array($purchaseOrder->status, ['partial_received', 'received'], true));
            if ($hasGoodsReceived) {
                return back()->with('error', 'Không thể hủy đơn hàng đã có hàng về. Vui lòng thực hiện quy trình trả hàng/điều chỉnh kho để đảm bảo tồn kho và công nợ đúng.');
            }
            if ($hasGoodsReceived && !auth()->user()->hasAnyRole(['super_admin', 'admin', 'director'])) {
                abort(403, 'Đơn hàng đã có hàng về chỉ BOD mới được phép hủy.');
            }
            if ($hasGoodsReceived && blank($validated['cancellation_reason'] ?? null)) {
                return back()->with('error', 'Cần nhập lý do hủy đơn đã có hàng về để lưu vết xử lý.');
            }
        }

        // Logic Check: Prevent Shipping/Completed if Export is not completed
        if (in_array($newStatus, ['shipping', 'completed'])) {
            // Check if goods are fully received for procurement-dependent items (only for project sales)
            if ($sale->type !== 'retail' && !$sale->isFullyReceived()) {
                return back()->with('error', "Hàng chưa về đủ theo yêu cầu đặt hàng. Không thể thực hiện giao hàng.");
            }

            $export = $this->saleExportSyncService->getExport($sale);
            
            // Bị vô hiệu hóa: quy trình mới yêu cầu tạo xuất kho thủ công/từng phần
            if (!$export) {
                return back()->with('error', "Đơn hàng chưa có phiếu xuất kho liên kết. Vui lòng thực hiện yêu cầu xuất kho trước.");
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
            if ($newStatus === 'cancelled' && filled($validated['cancellation_reason'] ?? null)) {
                $sale->note = trim(($sale->note ? $sale->note . "\n" : '') . '[Hủy đơn - ' . auth()->user()->name . ']: ' . $validated['cancellation_reason']);
            }
            
            // Ngày hóa đơn/công nợ chỉ được Finance ghi khi phát hành hóa đơn
            // chính thức. Không được suy diễn từ trạng thái giao hay hoàn thành.

            $sale->save();

            // Notify purchasing department when sale is approved
            if ($newStatus === 'approved' && $oldStatus === 'pending') {
                $this->notifyPurchasingDepartment($sale);
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
     * Notify purchasing department when a sale is approved
     */
    private function notifyPurchasingDepartment(Sale $sale): void
    {
        try {
            // Find users with purchase order creation permission
            $purchaseUsers = User::withPermission('create_purchase_orders')->get();

            foreach ($purchaseUsers as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'sale_approved',
                    'title' => 'Đơn bán hàng cần đặt mua',
                    'message' => "Đơn hàng {$sale->code} ({$sale->customer_name}) đã được duyệt. Vui lòng tiến hành đặt hàng với NCC.",
                    'link' => route('sales.show', $sale->id),
                    'icon' => 'fas fa-shopping-cart',
                    'color' => 'blue',
                    'data' => ['sale_id' => $sale->id, 'sale_code' => $sale->code],
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to notify purchasing: ' . $e->getMessage());
        }
    }

    /**
     * Upload file attachment for a sale
     */
    public function uploadAttachment(Request $request, Sale $sale)
    {
        $this->authorize('update', $sale);

        $request->validate([
            'file' => ['required', 'file', 'max:20480'], // 20MB max
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('file');
        $path = $file->store('sale-attachments/' . $sale->id, 'public');

        SaleAttachment::create([
            'sale_id' => $sale->id,
            'uploaded_by' => auth()->id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'note' => $request->note,
        ]);

        return back()->with('success', 'Đã tải lên file: ' . $file->getClientOriginalName());
    }

    /**
     * Delete file attachment
     */
    public function deleteAttachment(Sale $sale, SaleAttachment $attachment)
    {
        $this->authorize('update', $sale);

        if ($attachment->sale_id !== $sale->id) {
            abort(404);
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Đã xóa file đính kèm.');
    }

    /**
     * Download file attachment
     */
    public function downloadAttachment(Sale $sale, SaleAttachment $attachment)
    {
        if (!auth()->user()->can('view', $sale)) {
            abort(404);
        }

        if ($attachment->sale_id !== $sale->id) {
            abort(404);
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
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
        // Decode JSON payload if sent from frontend to prevent max_input_vars limit issues
        if ($request->has('items_json') && is_string($request->input('items_json'))) {
            $decoded = json_decode($request->input('items_json'), true);
            if (is_array($decoded)) {
                $request->merge(['items' => $decoded]);
            }
        }
        if ($request->has('expenses_json') && is_string($request->input('expenses_json'))) {
            $decoded = json_decode($request->input('expenses_json'), true);
            if (is_array($decoded)) {
                $request->merge(['expenses' => $decoded]);
            }
        }
        if ($request->has('new_expenses_json') && is_string($request->input('new_expenses_json'))) {
            $decoded = json_decode($request->input('new_expenses_json'), true);
            if (is_array($decoded)) {
                $request->merge(['new_expenses' => $decoded]);
            }
        }
        if ($request->has('pnl_extra_expenses_json') && is_string($request->input('pnl_extra_expenses_json'))) {
            $decoded = json_decode($request->input('pnl_extra_expenses_json'), true);
            if (is_array($decoded)) {
                $request->merge(['pnl_extra_expenses' => $decoded]);
            }
        }

        \Illuminate\Support\Facades\Log::info('updatePnL called', [
            'sale_id' => $sale->id,
            'user_id' => auth()->id(),
            'request' => $request->all(),
        ]);

        $this->authorize('update', $sale);

        if ($sale->pl_status === 'approved' && !auth()->user()->hasAnyRole(['super_admin', 'sales_manager'])) {
            return back()->with('error', 'P&L đã được duyệt, không thể chỉnh sửa.');
        }

        $itemsPayload = $request->input('items', []);
        foreach ($itemsPayload as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach (['technical_poc_percent', 'implementation_cost_percent', 'contractor_tax_percent'] as $pctField) {
                if (array_key_exists($pctField, $row) && $row[$pctField] === '') {
                    $itemsPayload[$i][$pctField] = null;
                }
            }
        }
        $request->merge(['items' => $itemsPayload]);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:sale_items,id'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.supplier_id' => ['nullable', 'exists:suppliers,id'],
            'items.*.is_service' => ['nullable', 'boolean'],
            'items.*.finance_na' => ['nullable', 'boolean'],
            'items.*.overdue_na' => ['nullable', 'boolean'],
            'items.*.management_na' => ['nullable', 'boolean'],
            'items.*.support_na' => ['nullable', 'boolean'],
            'items.*.other_na' => ['nullable', 'boolean'],
            'items.*.finance_cost_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.overdue_interest_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.overdue_interest_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.management_cost_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.support_247_cost_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.other_support_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.technical_poc_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.technical_poc_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.implementation_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.implementation_cost_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.contractor_tax' => ['nullable', 'numeric', 'min:0'],
            'items.*.contractor_tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.contractor_tax_enabled' => ['nullable', 'boolean'],
            'items.*.usd_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.import_cost_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.estimated_cost_usd' => ['nullable', 'numeric', 'min:0'],
            'items.*.exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.cost_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.total' => ['nullable', 'numeric', 'min:0'],
            'expenses' => ['nullable', 'array'],
            'expenses.*.id' => ['nullable', 'exists:sale_expenses,id'],
            'expenses.*.type' => ['nullable', 'string'],
            'expenses.*.input_mode' => ['nullable', 'in:percent,fixed'],
            'expenses.*.percent_value' => ['nullable', 'numeric', 'min:0'],
            'expenses.*.amount' => ['nullable', 'numeric', 'min:0'],
            'expenses.*.description' => ['nullable', 'string'],
            'items.*.extra_expenses_data' => ['nullable', 'array'],
            'items.*.extra_expenses_data.*' => ['nullable', 'numeric', 'min:0'],
            'new_expenses' => ['nullable', 'array'],
            'new_expenses.*.type' => ['nullable', 'string', 'max:200'],
            'new_expenses.*.input_mode' => ['nullable', 'in:percent,fixed'],
            'new_expenses.*.percent_value' => ['nullable', 'numeric', 'min:0'],
            'new_expenses.*.amount' => ['nullable', 'numeric', 'min:0'],
            'new_expenses.*.description' => ['nullable', 'string', 'max:500'],
            'pnl_extra_expenses' => ['nullable', 'array'],
            'pnl_extra_expenses.*.id' => ['required', 'exists:sale_expenses,id'],
            'pnl_extra_expenses.*.input_mode' => ['required', 'in:percent,fixed'],
            'pnl_extra_expenses.*.percent_value' => ['nullable', 'numeric', 'min:0'],
            'pnl_extra_expenses.*.amount' => ['nullable', 'numeric', 'min:0'],
            'pnl_extra_expenses.*.description' => ['nullable', 'string', 'max:500'],
            'pnl_attachments' => ['nullable', 'array'],
            'pnl_attachments.*' => ['file', 'max:20480'],
            'is_license_vnet' => ['nullable', 'boolean'],
            'trade_up_matrix' => ['required', 'in:none,correct,incorrect'],
            'ohf_cost_added' => ['nullable', 'boolean'],
            'payment_term' => ['nullable', 'string', 'max:100'],
            'payment_due_date' => ['nullable', 'date'],
            'has_bank_guarantee' => ['nullable', 'boolean'],
            'bank_guarantee_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            $errorDetails = [];
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errorDetails[] = $field . ': ' . implode(', ', $messages);
            }
            $errorMsg = 'Lỗi xác thực dữ liệu: ' . implode('; ', $errorDetails);
            
            Log::error('Validation failed in updatePnL:', [
                'errors' => $validator->errors()->toArray()
            ]);

            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', $errorMsg)
                ->withFragment('pnl');
        }

        $validated = $validator->validated();

        $submitForApproval = $request->input('_submit_for_approval') == '1';
        if ($submitForApproval) {
            foreach ($validated['items'] as $itemData) {
                if (empty($itemData['supplier_id']) && empty($itemData['is_service'])) {
                    return back()
                        ->withInput()
                        ->with('error', 'Vui lòng chọn Hãng (Vendor) cho tất cả các sản phẩm trong bảng P&L trước khi gửi duyệt.')
                        ->withFragment('pnl');
                }
            }
        }

        DB::beginTransaction();
        try {
            $dealFlags = $this->resolveDealFlags($validated, $request, $sale->project_id);
            $sale->fill($dealFlags);
            if ($request->has('payment_term')) {
                $sale->payment_term = $validated['payment_term'] ?? null;
            }
            if ($request->has('payment_due_date')) {
                $sale->payment_due_date = $validated['payment_due_date'] ?? null;
            }
            if ($request->has('has_bank_guarantee')) {
                $sale->has_bank_guarantee = $request->boolean('has_bank_guarantee');
                $sale->bank_guarantee_note = $sale->has_bank_guarantee ? ($validated['bank_guarantee_note'] ?? null) : null;
            }

            foreach ($validated['items'] as $itemData) {
                $item = SaleItem::where('id', $itemData['id'])->where('sale_id', $sale->id)->firstOrFail();

                // Đảm bảo các trường không được NULL (NOT NULL) trong DB luôn có giá trị mặc định an toàn nếu request gửi lên trống hoặc null
                $nonNullFieldsWithDefaults = [
                    'usd_price' => 0,
                    'exchange_rate' => 1,
                    'discount_rate' => 0,
                    'import_cost_rate' => 0,
                    'estimated_cost_usd' => 0,
                    'cost_price' => 0,
                    'total' => 0,
                    'cost_total' => 0,
                    'contractor_tax_enabled' => 0,
                    'is_service' => 0,
                ];
                foreach ($nonNullFieldsWithDefaults as $field => $defaultVal) {
                    if (!array_key_exists($field, $itemData) || is_null($itemData[$field]) || $itemData[$field] === '') {
                        $itemData[$field] = $defaultVal;
                    }
                }

                // N/A từ UI thường gửi chuỗi rỗng; ép về giá trị an toàn theo schema DB.
                // - Các cột percent có thể null
                // - Các cột amount dạng decimal NOT NULL phải về 0
                $nullablePercentFields = [
                    'finance_cost_percent' => 'finance_na',
                    'overdue_interest_percent' => 'overdue_na',
                    'management_cost_percent' => 'management_na',
                    'support_247_cost_percent' => 'support_na',
                    'other_support_cost' => 'other_na',
                    'technical_poc_percent' => null,
                    'implementation_cost_percent' => null,
                    'contractor_tax_percent' => null,
                ];
                $nonNullAmountFields = [
                    'overdue_interest_cost' => 'overdue_na',
                    'technical_poc_cost' => null,
                    'implementation_cost' => null,
                    'contractor_tax' => null,
                ];

                foreach ($nullablePercentFields as $field => $naField) {
                    if ($naField && isset($itemData[$naField]) && $itemData[$naField] == '1') {
                        $itemData[$field] = 0;
                    } elseif (array_key_exists($field, $itemData) && $itemData[$field] === '') {
                        $itemData[$field] = null;
                    }
                }
                foreach ($nonNullAmountFields as $field => $naField) {
                    if ($naField && isset($itemData[$naField]) && $itemData[$naField] == '1') {
                        $itemData[$field] = 0;
                    } elseif (!array_key_exists($field, $itemData) || $itemData[$field] === '' || is_null($itemData[$field])) {
                        $itemData[$field] = 0;
                    }
                }

                unset($itemData['id']);

                // Process extra_expenses_data: convert nested array to clean JSON
                if (isset($itemData['extra_expenses_data']) && is_array($itemData['extra_expenses_data'])) {
                    $cleanExtra = [];
                    foreach ($itemData['extra_expenses_data'] as $expenseId => $amount) {
                        $amt = (float) ($amount ?? 0);
                        if ($amt > 0) {
                            $cleanExtra[(string) $expenseId] = $amt;
                        }
                    }
                    $itemData['extra_expenses_data'] = !empty($cleanExtra) ? $cleanExtra : null;
                }

                $itemData['contractor_tax_enabled'] = (bool)($itemData['contractor_tax_enabled'] ?? false);

                $item->update($itemData);
            }

            // Xử lý upload file đính kèm P&L
            $this->handlePnlAttachmentsUpload($request, $sale);

            // Simple update for general expenses if provided
            if (isset($validated['expenses'])) {
                foreach ($validated['expenses'] as $expenseData) {
                    if (!empty($expenseData['id'])) {
                        $expense = SaleExpense::where('id', $expenseData['id'])->where('sale_id', $sale->id)->first();
                        if (!$expense) continue;
                        $inputMode = $expenseData['input_mode'] ?? $expense->input_mode ?? 'fixed';
                        $pctVal = (isset($expenseData['percent_value']) && $expenseData['percent_value'] !== '') ? (float) $expenseData['percent_value'] : 0;
                        $amtVal = (isset($expenseData['amount']) && $expenseData['amount'] !== '') ? (float) $expenseData['amount'] : 0;
                        $expense->update([
                            'type' => $expenseData['type'] ?? $expense->type,
                            'input_mode' => $inputMode,
                            'percent_value' => $inputMode === 'percent' ? $pctVal : null,
                            'amount' => $inputMode === 'fixed' ? $amtVal : 0,
                            'description' => $expenseData['description'] ?? ($expense->description ?? ''),
                        ]);
                    }
                }
            }

            // Sync per-item extra expense totals back to sale_expenses.amount
            $sale->load('items');
            if (isset($validated['expenses'])) {
                foreach ($validated['expenses'] as $expenseData) {
                    if (!empty($expenseData['id'])) {
                        $expenseId = $expenseData['id'];
                        $expense = SaleExpense::where('id', $expenseId)->where('sale_id', $sale->id)->first();
                        if ($expense && $expense->input_mode === 'fixed') {
                            // Sum per-item values for this expense
                            $totalFromItems = 0;
                            foreach ($sale->items as $saleItem) {
                                $extraData = $saleItem->extra_expenses_data ?? [];
                                $totalFromItems += (float) ($extraData[(string) $expenseId] ?? 0);
                            }
                            if ($totalFromItems > 0) {
                                $expense->update(['amount' => round($totalFromItems, 2)]);
                            }
                        }
                    }
                }
            }

            $sale->load(['items', 'expenses']);
            $this->syncPnlItemsToOrderExpenses($sale);

            // Xử lý chi phí bổ sung PNL — tạo mới
            $standardTypes = [
                'Chi phí Tài chính',
                'Lãi vay phát sinh do nợ quá hạn',
                'Chi phí Quản lí, Back Office & kỹ thuật',
                '24x7 Support cost',
                'Other Support',
                'Technical support/POC',
                'Technical support/POC 30%',
                'Chi phí triển khai hợp đồng',
                'Thuế nhà thầu',
            ];

            foreach ($validated['new_expenses'] ?? [] as $newExpense) {
                $type = trim($newExpense['type'] ?? '');
                if (empty($type)) continue;

                $inputMode = $newExpense['input_mode'] ?? 'fixed';
                $pctVal = (isset($newExpense['percent_value']) && $newExpense['percent_value'] !== '') ? (float) $newExpense['percent_value'] : 0;
                $amtVal = (isset($newExpense['amount']) && $newExpense['amount'] !== '') ? (float) $newExpense['amount'] : 0;

                $sale->expenses()->create([
                    'type' => $type,
                    'input_mode' => $inputMode,
                    'percent_value' => $inputMode === 'percent' ? $pctVal : null,
                    'amount' => $inputMode === 'fixed' ? $amtVal : 0,
                    'description' => $newExpense['description'] ?? '',
                ]);
            }

            // Xử lý chi phí bổ sung PNL — cập nhật existing
            foreach ($validated['pnl_extra_expenses'] ?? [] as $extraData) {
                $expense = SaleExpense::where('id', $extraData['id'])->where('sale_id', $sale->id)->first();
                if (!$expense) continue;
                // Chỉ cập nhật loại ngoài standard
                if (in_array($expense->type, $standardTypes)) continue;

                $inputMode = $extraData['input_mode'] ?? $expense->input_mode;
                $pctVal = (isset($extraData['percent_value']) && $extraData['percent_value'] !== '') ? (float) $extraData['percent_value'] : 0;
                $amtVal = (isset($extraData['amount']) && $extraData['amount'] !== '') ? (float) $extraData['amount'] : 0;

                $expense->update([
                    'input_mode' => $inputMode,
                    'percent_value' => $inputMode === 'percent' ? $pctVal : null,
                    'amount' => $inputMode === 'fixed' ? $amtVal : 0,
                    'description' => $extraData['description'] ?? ($expense->description ?? ''),
                ]);
            }

            $sale->load('expenses');

            // Nếu có flag gửi duyệt → chuyển thẳng sang quy trình duyệt thay vì lưu nháp
            $submitForApproval = $request->input('_submit_for_approval') == '1';

            if ($submitForApproval && in_array($sale->pl_status, [null, 'draft', 'rejected', 'pending'])) {
                // Tính margin trước khi gửi duyệt
                $sale->calculateMargin();
                $sale->save();

                // Xóa lịch sử duyệt đang chờ cũ để tránh xung đột
                ApprovalHistory::where('document_type', 'sale_pnl')
                    ->where('document_id', $sale->id)
                    ->where('action', 'pending')
                    ->delete();

                $result = $this->approvalService->submit($sale, 'sale_pnl');

                if ($result['success'] || !str_contains($result['message'] ?? '', 'Lỗi hệ thống')) {
                    // File đính kèm giữ nguyên approval_history_id = NULL
                    // → hiển thị trong "Hồ sơ đính kèm" khi đang chờ duyệt
                    // → sẽ được gán vào bản ghi lịch sử khi cấp duyệt phê duyệt/từ chối
                }

                if (!$result['success']) {
                    $isSystemError = str_contains($result['message'] ?? '', 'Lỗi hệ thống');
                    if (!$isSystemError) {
                        $sale->update(['pl_status' => 'pending']);
                        DB::commit();
                        return redirect()->route('sales.show', $sale->id)
                            ->with('warning', 'Đã lưu P&L. Chưa cấu hình quy trình duyệt — đã chuyển sang chờ duyệt thủ công.')
                            ->withFragment('pnl');
                    }
                    DB::commit();
                    return back()->with('error', 'Đã lưu P&L nhưng lỗi khi gửi duyệt: ' . $result['message']);
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

                DB::commit();
                return redirect()->route('sales.show', $sale->id)
                    ->with('success', 'Đã lưu và ' . ($result['message'] ?? 'gửi duyệt P&L thành công.'))
                    ->withFragment('pnl');
            }

            // Lưu nháp thông thường hoặc lưu khi đang chờ duyệt
            if ($sale->pl_status !== 'pending') {
                $sale->pl_status = 'draft';
            }
            $sale->calculateMargin();
            $sale->save();

            DB::commit();
            
            // Redirect to same page with hash to stay on P&L tab
            $successMsg = $sale->pl_status === 'pending'
                ? 'Đã cập nhật chi tiết P&L thành công.'
                : 'Đã cập nhật chi tiết P&L. Vui lòng nhấn "Gửi duyệt" để hoàn tất.';
            return redirect()->route('sales.show', $sale->id)->with('success', $successMsg)->withFragment('pnl');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('QueryException in updatePnL: ' . $e->getMessage(), [
                'sale_id' => $sale->id,
                'trace' => $e->getTraceAsString(),
            ]);
            $msg = $e->getMessage();
            if (str_contains($msg, '22003') || str_contains($msg, 'Out of range')) {
                return back()->with('error', 'Lỗi: Giá trị nhập vào vượt quá giới hạn cho phép của hệ thống. Vui lòng kiểm tra lại các con số (đặc biệt là phần trăm).')->withFragment('pnl');
            }
            return back()->with('error', 'Lỗi cơ sở dữ liệu khi cập nhật P&L: ' . $msg)->withFragment('pnl');
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Exception in updatePnL: ' . $e->getMessage(), [
                'sale_id' => $sale->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Lỗi khi cập nhật P&L: ' . $e->getMessage())->withFragment('pnl');
        }
    }

    /**
     * Delete a PNL extra expense (AJAX) — only non-standard types allowed.
     */
    public function deletePnlExpense(Request $request, Sale $sale, SaleExpense $expense)
    {
        $this->authorize('update', $sale);

        if ($expense->sale_id !== $sale->id) {
            return response()->json(['success' => false, 'message' => 'Chi phí không thuộc đơn hàng này.'], 404);
        }

        if (!$sale->isPlEditable() && !auth()->user()->hasAnyRole(['super_admin', 'sales_manager'])) {
            return response()->json(['success' => false, 'message' => 'P&L hiện không thể chỉnh sửa.'], 403);
        }

        // Cho phép xóa tất cả chi phí (bao gồm cả chi phí tiêu chuẩn) từ PNL

        // Xóa per-item extra_expenses_data cho expense này
        foreach ($sale->items as $item) {
            $extraData = $item->extra_expenses_data ?? [];
            if (isset($extraData[(string) $expense->id])) {
                unset($extraData[(string) $expense->id]);
                $item->update(['extra_expenses_data' => !empty($extraData) ? $extraData : null]);
            }
        }

        $expense->delete();

        // Recalculate margin
        $sale->load(['items', 'expenses']);
        $sale->calculateMargin();
        $sale->save();

        return response()->json(['success' => true, 'message' => 'Đã xóa chi phí.']);
    }

    /**
     * Normalize expense type for robust matching (case/diacritics/spacing insensitive).
     */
    private function normalizeExpenseType(?string $type): string
    {
        $normalized = Str::ascii((string) $type);
        $normalized = Str::lower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/u', '', $normalized) ?? '';

        return $normalized;
    }

    /**
     * Find first sale_expenses row matching any of the given type labels.
     */
    private function findOrderExpenseByTypes(Sale $sale, array $types): ?SaleExpense
    {
        $orderedExpenses = $sale->expenses->sortByDesc('id')->values();

        foreach ($types as $type) {
            $expense = $orderedExpenses->firstWhere('type', $type);
            if ($expense) {
                return $expense;
            }
        }

        $normalizedNeedles = array_map(fn ($type) => $this->normalizeExpenseType($type), $types);
        foreach ($orderedExpenses as $expense) {
            $normalizedExpenseType = $this->normalizeExpenseType($expense->type);
            if (in_array($normalizedExpenseType, $normalizedNeedles, true)) {
                return $expense;
            }
        }

        return null;
    }

    /**
     * Push aggregated P&L item fields into sale_expenses so "Chi phí đơn hàng" stays in sync.
     */
    private function syncPnlItemsToOrderExpenses(Sale $sale): void
    {
        $items = $sale->items;
        if ($items->isEmpty()) {
            return;
        }

        $upsert = function (array $typeKeys, string $defaultType, string $inputMode, ?float $percent, float $amount) use ($sale) {
            $existing = $this->findOrderExpenseByTypes($sale, $typeKeys);
            $type = $existing?->type ?? $defaultType;
            $payload = [
                'input_mode' => $inputMode,
                'percent_value' => $inputMode === 'percent' ? round((float) $percent, 4) : null,
                'amount' => $inputMode === 'fixed' ? round((float) $amount, 2) : 0,
                'description' => $existing?->description ?? '',
            ];
            if ($existing) {
                $existing->update($payload);
            } elseif ($amount > 0 || ($percent !== null && $percent > 0)) {
                SaleExpense::create(array_merge([
                    'sale_id' => $sale->id,
                    'type' => $type,
                ], $payload));
            }
        };

        $syncPercentExpense = function (array $typeKeys, string $defaultType, float $maxPercent) use ($sale, $upsert) {
            $existing = $this->findOrderExpenseByTypes($sale, $typeKeys);

            if ($maxPercent > 0) {
                $upsert($typeKeys, $defaultType, 'percent', $maxPercent, 0);
                return;
            }

            // Không ép fixed -> percent(0) khi lưu nháp P&L.
            if ($existing && ($existing->input_mode ?? 'fixed') === 'fixed') {
                return;
            }

            if (! $existing) {
                return;
            }

            $upsert($typeKeys, $defaultType, 'percent', 0, 0);
        };

        $maxFinance = (float) $items->max(fn ($i) => (float) ($i->finance_cost_percent ?? 0));
        $syncPercentExpense(['Chi phí Tài chính'], 'Chi phí Tài chính', $maxFinance);

        $maxOverdueP = (float) $items->max(fn ($i) => (float) ($i->overdue_interest_percent ?? 0));
        $sumOverdueC = (float) $items->sum(fn ($i) => (float) ($i->overdue_interest_cost ?? 0));
        if ($maxOverdueP > 0) {
            $upsert(['Lãi vay phát sinh do nợ quá hạn'], 'Lãi vay phát sinh do nợ quá hạn', 'percent', $maxOverdueP, 0);
        } else {
            $upsert(['Lãi vay phát sinh do nợ quá hạn'], 'Lãi vay phát sinh do nợ quá hạn', 'fixed', null, $sumOverdueC);
        }

        $maxMgmt = (float) $items->max(fn ($i) => (float) ($i->management_cost_percent ?? 0));
        $syncPercentExpense(['Chi phí Quản lí, Back Office & kỹ thuật'], 'Chi phí Quản lí, Back Office & kỹ thuật', $maxMgmt);

        $max247 = (float) $items->max(fn ($i) => (float) ($i->support_247_cost_percent ?? 0));
        $syncPercentExpense(['24x7 Support cost'], '24x7 Support cost', $max247);

        $maxOther = (float) $items->max(fn ($i) => (float) ($i->other_support_cost ?? 0));
        $syncPercentExpense(['Other Support'], 'Other Support', $maxOther);

        $maxPocP = (float) $items->max(fn ($i) => (float) ($i->technical_poc_percent ?? 0));
        $sumPoc = (float) $items->sum(fn ($i) => (float) ($i->technical_poc_cost ?? 0));
        $pocExisting = $this->findOrderExpenseByTypes($sale, ['Technical support/POC 30%', 'Technical support/POC']);
        $pocType = $pocExisting?->type ?? 'Technical support/POC 30%';
        if ($maxPocP > 0) {
            $upsert(['Technical support/POC 30%', 'Technical support/POC'], $pocType, 'percent', $maxPocP, 0);
        } else {
            $upsert(['Technical support/POC 30%', 'Technical support/POC'], $pocType, 'fixed', null, $sumPoc);
        }

        $maxImplP = (float) $items->max(fn ($i) => (float) ($i->implementation_cost_percent ?? 0));
        $sumImpl = (float) $items->sum(fn ($i) => (float) ($i->implementation_cost ?? 0));
        if ($maxImplP > 0) {
            $upsert(['Chi phí triển khai hợp đồng'], 'Chi phí triển khai hợp đồng', 'percent', $maxImplP, 0);
        } else {
            $upsert(['Chi phí triển khai hợp đồng'], 'Chi phí triển khai hợp đồng', 'fixed', null, $sumImpl);
        }

        $maxTaxP = (float) $items->max(fn ($i) => (float) ($i->contractor_tax_percent ?? 0));
        $sumTax = (float) $items->sum(fn ($i) => (float) ($i->contractor_tax ?? 0));
        if ($maxTaxP > 0) {
            $upsert(['Thuế nhà thầu'], 'Thuế nhà thầu', 'percent', $maxTaxP, 0);
        } else {
            $upsert(['Thuế nhà thầu'], 'Thuế nhà thầu', 'fixed', null, $sumTax);
        }

        $sale->load('expenses');
    }

    /**
     * Sync order-level expenses to item-level P&L fields.
     */
    private function syncOrderExpensesToPnlItems(Sale $sale): void
    {
        // Sau khi items()->delete() + create lại (update đơn), quan hệ items có thể vẫn là collection cũ;
        // loadMissing không nạp lại → đồng bộ chi phí ghi nhầm và item đã xóa, P&L không đổi theo kiểu %/VND.
        $sale->unsetRelation('items');
        $sale->unsetRelation('expenses');
        $sale->load(['items', 'expenses']);
        $items = $sale->items;
        if ($items->isEmpty()) {
            return;
        }

        $totalCostBase = (float) $items->sum(fn ($item) => (float) ($item->cost_total ?? 0));
        $totalRevenueBase = (float) $items->sum(fn ($item) => (float) ($item->total ?? 0));
        $itemCount = $items->count();

        $getExpense = fn (array $types) => $this->findOrderExpenseByTypes($sale, $types);

        $finance = $getExpense(['Chi phí Tài chính']);
        $overdue = $getExpense(['Lãi vay phát sinh do nợ quá hạn']);
        $management = $getExpense(['Chi phí Quản lí, Back Office & kỹ thuật']);
        $support247 = $getExpense(['24x7 Support cost']);
        $otherSupport = $getExpense(['Other Support']);
        $technicalPoc = $getExpense(['Technical support/POC', 'Technical support/POC 30%']);
        $implementation = $getExpense(['Chi phí triển khai hợp đồng']);
        $contractorTax = $getExpense(['Thuế nhà thầu']);

        foreach ($items as $index => $item) {
            $costTotal = (float) ($item->cost_total ?? 0);
            $revenueTotal = (float) ($item->total ?? 0);

            $share = 0.0;
            if ($itemCount === 1) {
                $share = 1.0;
            } elseif ($totalCostBase > 0 && $costTotal > 0) {
                $share = $costTotal / $totalCostBase;
            } elseif ($totalRevenueBase > 0 && $revenueTotal > 0) {
                $share = $revenueTotal / $totalRevenueBase;
            } elseif ($index === 0) {
                $share = 1.0;
            }

            // Finance Cost
            if (is_null($item->finance_cost_percent)) {
                $item->finance_cost_percent = ($finance && $finance->input_mode === 'percent')
                    ? (float) ($finance->percent_value ?? 0)
                    : null;
            }

            // Overdue Interest
            if (is_null($item->overdue_interest_percent) && is_null($item->overdue_interest_cost)) {
                if ($overdue && $overdue->input_mode === 'percent') {
                    $item->overdue_interest_percent = (float) ($overdue->percent_value ?? 0);
                    $item->overdue_interest_cost = 0;
                } elseif ($overdue && $overdue->input_mode === 'fixed') {
                    $item->overdue_interest_percent = null;
                    $item->overdue_interest_cost = round(((float) ($overdue->amount ?? 0)) * $share, 2);
                } else {
                    $item->overdue_interest_percent = null;
                    $item->overdue_interest_cost = 0;
                }
            }

            // Management Cost
            if (is_null($item->management_cost_percent)) {
                $item->management_cost_percent = ($management && $management->input_mode === 'percent')
                    ? (float) ($management->percent_value ?? 0)
                    : null;
            }

            // Support 24/7 Cost
            if (is_null($item->support_247_cost_percent)) {
                $item->support_247_cost_percent = ($support247 && $support247->input_mode === 'percent')
                    ? (float) ($support247->percent_value ?? 0)
                    : null;
            }

            // Other Support Cost
            if (is_null($item->other_support_cost)) {
                $item->other_support_cost = ($otherSupport && $otherSupport->input_mode === 'percent')
                    ? (float) ($otherSupport->percent_value ?? 0)
                    : 0;
            }

            // Technical POC - Respect manual override
            if (is_null($item->technical_poc_percent) && is_null($item->technical_poc_cost)) {
                if ($technicalPoc && $technicalPoc->input_mode === 'percent') {
                     $item->technical_poc_percent = (float) ($technicalPoc->percent_value ?? 0);
                     $item->technical_poc_cost = 0;
                } elseif ($technicalPoc && $technicalPoc->input_mode === 'fixed') {
                     $item->technical_poc_percent = null;
                     $item->technical_poc_cost = round(((float) ($technicalPoc->amount ?? 0)) * $share, 2);
                } else {
                     $item->technical_poc_percent = null;
                     $item->technical_poc_cost = 0;
                }
            }

            // Implementation Cost - Respect manual override
            if (is_null($item->implementation_cost_percent) && is_null($item->implementation_cost)) {
                if ($implementation && $implementation->input_mode === 'percent') {
                    $item->implementation_cost_percent = (float) ($implementation->percent_value ?? 0);
                    $item->implementation_cost = 0;
                } elseif ($implementation && $implementation->input_mode === 'fixed') {
                    $item->implementation_cost_percent = null;
                    $item->implementation_cost = round(((float) ($implementation->amount ?? 0)) * $share, 2);
                } else {
                    $item->implementation_cost_percent = null;
                    $item->implementation_cost = 0;
                }
            }

            // Contractor Tax - Use fixed formula only if contractor_tax_enabled is true
            if ($item->contractor_tax_enabled && $contractorTax) {
                if (is_null($item->contractor_tax) || $item->contractor_tax == 0) {
                    $item->contractor_tax_percent = null;
                    $item->contractor_tax = round($costTotal / 0.9 * 0.1);
                }
            } else {
                $item->contractor_tax_percent = null;
                $item->contractor_tax = 0;
            }

            $item->save();
        }
    }

    /**
     * Submit P&L for approval – tích hợp ApprovalWorkflow
     */
    public function submitPnL(Sale $sale)
    {
        $this->authorize('update', $sale);

        $user = auth()->user();
        $maySubmitPnl = $sale->user_id === $user->id
            || $user->hasAnyRole(['sales_manager', 'admin', 'super_admin', 'director']);
        if (!$maySubmitPnl) {
            abort(403, 'Chỉ Sales phụ trách hoặc quản lý được gửi P&L duyệt.');
        }

        // Allow re-submission if pending (to fix stuck cases) or draft/rejected
        if (!in_array($sale->pl_status, [null, 'draft', 'rejected', 'pending'])) {
            return back()->with('error', 'P&L đã được duyệt.');
        }

        if ($sale->project?->deal_type === 'trade_up' && $sale->trade_up_matrix === 'none') {
            return back()->with('error', 'Đơn từ ĐKDA Trade up phải chọn đúng hoặc không đúng matrix trước khi gửi duyệt P&L.');
        }

        if (request()->has('has_bank_guarantee')) {
            $sale->has_bank_guarantee = request()->boolean('has_bank_guarantee');
            $sale->bank_guarantee_note = $sale->has_bank_guarantee ? (request()->input('bank_guarantee_note') ?? null) : null;
            $sale->save();
        }

        // Validate payment terms before P&L submission
        if (!$sale->payment_term_type) {
            return back()->with('error', 'Vui lòng chọn loại điều khoản thanh toán trước khi gửi duyệt P&L.');
        }

        $milestones = $sale->payment_terms ?? [];
        if (empty($milestones)) {
            return back()->with('error', 'Vui lòng thiết lập ít nhất một mốc thanh toán trước khi gửi duyệt P&L.');
        }

        $totalPercent = collect($milestones)->sum(function($ms) {
            return (float) ($ms['percentage'] ?? ($ms['percent'] ?? 0));
        });

        if (abs($totalPercent - 100) > 0.01) {
            return back()->with('error', 'Tổng tỷ lệ phần trăm các đợt thanh toán phải bằng chính xác 100% (Hiện tại: ' . $totalPercent . '%).');
        }

        // Validate vendor/supplier selection for all items before P&L submission
        $itemsWithoutVendor = $sale->items->filter(function($item) {
            return empty($item->supplier_id) && !$item->is_service;
        });
        if ($itemsWithoutVendor->isNotEmpty()) {
            return back()->with('error', 'Vui lòng chọn Hãng (Vendor) cho tất cả các sản phẩm trong bảng P&L trước khi gửi duyệt.');
        }

        // Xóa lịch sử duyệt đang chờ cũ để tránh xung đột
        ApprovalHistory::where('document_type', 'sale_pnl')
            ->where('document_id', $sale->id)
            ->where('action', 'pending')
            ->delete();

        // Thử dùng ApprovalWorkflow nếu đã cấu hình
        $result = $this->approvalService->submit($sale, 'sale_pnl');

        if ($result['success'] || !str_contains($result['message'] ?? '', 'Lỗi hệ thống')) {
            // Tạo bản ghi history cho sự kiện Gửi duyệt
            $submitHistory = ApprovalHistory::create([
                'document_type' => 'sale_pnl',
                'document_id' => $sale->id,
                'level' => 0,
                'level_name' => 'Yêu cầu duyệt',
                'approver_id' => auth()->id(),
                'approver_name' => auth()->user()->name,
                'action' => 'submitted',
                'comment' => request()->input('comment') ?: 'Gửi duyệt P&L',
                'action_at' => now(),
            ]);

            // Liên kết các file đính kèm nháp hiện tại với bản ghi gửi duyệt này
            PnlApprovalAttachment::where('sale_id', $sale->id)
                ->whereNull('approval_history_id')
                ->update(['approval_history_id' => $submitHistory->id]);
        }

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

        // Liên kết file đính kèm nháp với bản ghi duyệt mới nhất (approved) của cấp này
        $latestApproveHistory = ApprovalHistory::where('document_type', 'sale_pnl')
            ->where('document_id', $sale->id)
            ->where('action', 'approved')
            ->orderByDesc('id')
            ->first();
        if ($latestApproveHistory) {
            PnlApprovalAttachment::where('sale_id', $sale->id)
                ->whereNull('approval_history_id')
                ->update(['approval_history_id' => $latestApproveHistory->id]);
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

                // Tự động tạo PO nháp cho bộ phận mua hàng qua Service - Đã loại bỏ theo yêu cầu
                // app(SalePurchaseSyncService::class)->createPurchaseOrderFromSale($sale);
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

        // Liên kết file đính kèm nháp với bản ghi từ chối mới nhất của cấp này
        $latestRejectHistory = ApprovalHistory::where('document_type', 'sale_pnl')
            ->where('document_id', $sale->id)
            ->where('action', 'rejected')
            ->orderByDesc('id')
            ->first();
        if ($latestRejectHistory) {
            PnlApprovalAttachment::where('sale_id', $sale->id)
                ->whereNull('approval_history_id')
                ->update(['approval_history_id' => $latestRejectHistory->id]);
        }

        $sale->update([
            'pl_status' => 'rejected',
            'status'    => 'pending',  // Đồng bộ: trả trạng thái đơn hàng về "Chờ duyệt" khi PNL bị từ chối
        ]);

        // Gửi thông báo cho người tạo đơn (Sales) biết P&L bị từ chối
        $this->notifyPnlStatusChanged($sale, 'rejected', $request->comment);

        return redirect()->route('sales.show', $sale->id)
            ->with('success', 'P&L đã bị từ chối.')
            ->withFragment('pnl');
    }

    /**
     * Request revision for P&L (Yêu cầu chỉnh sửa - softer than reject)
     */
    public function requestRevisionPnL(Request $request, Sale $sale)
    {
        $this->authorize('approvePnl', $sale);

        $request->validate(['comment' => 'required|string|min:3|max:500']);

        $result = $this->approvalService->reject($sale, 'sale_pnl', $request->comment);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        // Liên kết file đính kèm nháp với bản ghi yêu cầu chỉnh sửa mới nhất
        $latestHistory = ApprovalHistory::where('document_type', 'sale_pnl')
            ->where('document_id', $sale->id)
            ->where('action', 'rejected')
            ->orderByDesc('id')
            ->first();

        if ($latestHistory) {
            // Cập nhật action thành need_revision để phân biệt với rejected
            $latestHistory->update(['action' => 'need_revision']);

            PnlApprovalAttachment::where('sale_id', $sale->id)
                ->whereNull('approval_history_id')
                ->update(['approval_history_id' => $latestHistory->id]);
        }

        $sale->update([
            'pl_status' => 'need_revision',
            'status'    => 'pending',  // Đồng bộ: trả trạng thái đơn hàng về "Chờ duyệt"
        ]);

        // Gửi thông báo cho người tạo đơn (Sales) biết P&L cần chỉnh sửa
        $this->notifyPnlStatusChanged($sale, 'need_revision', $request->comment);

        return redirect()->route('sales.show', $sale->id)
            ->with('success', 'P&L đã được yêu cầu chỉnh sửa.')
            ->withFragment('pnl');
    }

    /**
     * Gửi thông báo cho người tạo đơn khi P&L bị Rejected hoặc Need Revision
     */
    private function notifyPnlStatusChanged(Sale $sale, string $action, ?string $comment): void
    {
        $creatorId = $sale->getCreatorId();
        if (!$creatorId || $creatorId === auth()->id()) {
            return;
        }

        $approverName = auth()->user()->name ?? 'Người duyệt';
        $link = route('sales.show', $sale->id) . '#pnl';

        if ($action === 'rejected') {
            Notification::create([
                'user_id' => $creatorId,
                'type' => 'pnl_rejected',
                'title' => 'P&L bị từ chối',
                'message' => "{$approverName} đã từ chối P&L cho đơn hàng {$sale->code}." . ($comment ? " Lý do: \"{$comment}\"" : ''),
                'link' => $link,
                'icon' => 'fas fa-times-circle',
                'color' => 'red',
            ]);
        } elseif ($action === 'need_revision') {
            Notification::create([
                'user_id' => $creatorId,
                'type' => 'pnl_need_revision',
                'title' => 'P&L cần chỉnh sửa',
                'message' => "{$approverName} yêu cầu chỉnh sửa P&L cho đơn hàng {$sale->code}." . ($comment ? " Ghi chú: \"{$comment}\"" : ''),
                'link' => $link,
                'icon' => 'fas fa-edit',
                'color' => 'orange',
            ]);
        }
    }

    /**
     * Notify PO team about a new order request
     */
    private function notifyPoTeam(Sale $sale, \App\Models\SaleOrderRequest $orderRequest)
    {
        $poUsers = User::whereHas('roles', function ($q) {
            $q->where('slug', 'admin')
              ->orWhere('slug', 'purchase_manager');
        })->get();

        if ($poUsers->isEmpty()) {
            $poUsers = User::whereHas('roles', function ($q) {
                $q->where('slug', 'admin');
            })->get();
        }

        $senderName = auth()->user()->name ?? 'Sales';
        foreach ($poUsers as $user) {
            if ($user->id === auth()->id()) continue;

            Notification::create([
                'user_id' => $user->id,
                'type' => 'order_request',
                'title' => 'Yêu cầu đặt hàng mới',
                'message' => "{$senderName} đã gửi yêu cầu đặt hàng ({$orderRequest->code}) cho đơn {$sale->code}",
                'link' => route('sales.show', $sale->id),
                'icon' => 'fas fa-cart-plus',
                'color' => 'blue',
            ]);
        }
    }

    public function createOrderRequest(Sale $sale)
    {
        if ($sale->status === 'cancelled') {
            return back()->with('error', 'Đơn hàng đã hủy, không thể tạo yêu cầu đặt hàng.');
        }

        if ($sale->pl_status !== 'approved') {
            return back()->with('error', 'Yêu cầu đặt hàng chỉ được tạo sau khi P&L đã được duyệt.');
        }

        $status = $sale->getPaymentConditionStatus();
        if (!$status['eligible_for_order']) {
            $pendingList = implode(', ', $status['pending_order_milestones']);
            return back()->with('error', 'Đơn hàng chưa đủ điều kiện đặt hàng do chưa có UNC hoặc chưa được Finance xác nhận đợt: ' . $pendingList);
        }

        // Kiểm tra xem tất cả các sản phẩm đã được chọn Hãng ở P&L chưa
        $itemsWithoutVendor = $sale->items->filter(function($item) {
            return empty($item->supplier_id) && !$item->is_service;
        });
        if ($itemsWithoutVendor->isNotEmpty()) {
            return back()->with('error', 'Đơn hàng có sản phẩm chưa được chọn Hãng ở bảng P&L. Vui lòng cập nhật bảng P&L trước khi tạo yêu cầu đặt hàng.');
        }

        $sale->load(['items.product', 'items.supplier', 'items.project', 'customer', 'project']);
        $suppliers = \App\Models\Supplier::orderByRaw("CASE WHEN name = 'Other' THEN 1 ELSE 0 END, name")->get();
        $customers = Customer::select('id', 'name', 'tax_code')->orderBy('name')->get();
        
        return view('sales.order-request-create', compact('sale', 'suppliers', 'customers'));
    }

    public function editOrderRequest(Sale $sale, \App\Models\SaleOrderRequest $orderRequest)
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('purchase_manager');

        if (!in_array($orderRequest->status, [\App\Models\SaleOrderRequest::STATUS_DRAFT, \App\Models\SaleOrderRequest::STATUS_NEED_INFO]) && !$isAdmin) {
            return back()->with('error', 'Chỉ có thể chỉnh sửa yêu cầu ở trạng thái "Bản nháp" hoặc "Thiếu thông tin".');
        }

        $sale->load(['items.product', 'items.supplier', 'items.project', 'customer', 'project']);
        $orderRequest->load(['items.vendor', 'attachments']);
        $suppliers = Supplier::orderByRaw("CASE WHEN name = 'Other' THEN 1 ELSE 0 END, name")->get();
        $customers = Customer::orderBy('name')->get();

        return view('sales.order-request-create', compact('sale', 'orderRequest', 'suppliers', 'customers'));
    }

    /**
     * Store a new order request (Yêu cầu đặt hàng) from Sales to PO team
     */
    public function storeOrderRequest(Request $request, Sale $sale)
    {
        if ($sale->status === 'cancelled') {
            return back()->with('error', 'Đơn hàng đã hủy, không thể tạo yêu cầu đặt hàng.');
        }

        if ($sale->pl_status !== 'approved') {
            return back()->with('error', 'Yêu cầu đặt hàng chỉ được tạo sau khi P&L đã được duyệt.');
        }

        $isDraft = $request->input('action_type') === 'draft';

        if (!$isDraft) {
            $status = $sale->getPaymentConditionStatus();
            if (!$status['eligible_for_order']) {
                $pendingList = implode(', ', $status['pending_order_milestones']);
                return back()->with('error', 'Đơn hàng chưa đủ điều kiện đặt hàng do chưa có UNC hoặc chưa được Finance xác nhận đợt: ' . $pendingList);
            }
        }

        \Log::info('storeOrderRequest raw input: ' . json_encode($request->all()));
        try {
            $validated = $request->validate([
                'order_request_items' => 'required|array|min:1',
                'order_request_items.*.vendor_id' => $isDraft ? 'nullable' : 'required|exists:suppliers,id',
                'order_request_items.*.type' => $isDraft ? 'nullable|string' : 'required|string|max:100',
                'order_request_items.*.needs_cq' => 'nullable|boolean',
                'order_request_items.*.part_number' => $isDraft ? 'nullable|string' : 'required|string|max:255',
                'order_request_items.*.product_id' => 'nullable|exists:products,id',
                'order_request_items.*.sale_item_id' => 'nullable|exists:sale_items,id',
                'order_request_items.*.quantity' => 'required|numeric|min:0.01',
                'order_request_items.*.unit' => 'nullable|string|max:50',
                'order_request_items.*.serial_number' => 'nullable',
                'order_request_items.*.serial_expiry_dates' => 'nullable|array',
                'order_request_items.*.serial_expiry_dates.*' => 'nullable|date',
                'order_request_items.*.exp_date' => 'nullable|date',
                'order_request_items.*.si_name' => $isDraft ? 'nullable|string' : 'required|string|max:255',
                'order_request_items.*.pos_id' => 'nullable|string|max:255',
                'order_request_items.*.eu_name' => 'nullable|string|max:255',
                'order_request_items.*.mst' => 'nullable|string|max:255',
                'order_request_items.*.address' => 'nullable|string|max:500',
                'order_request_note' => 'nullable|string|max:2000',
                'is_license_from_other_distributor' => 'nullable|boolean',
                'other_distributor_name' => 'nullable|string|max:255',
                'order_request_files' => $isDraft ? 'nullable|array' : 'required|array|min:1',
                'order_request_files.*' => 'file|max:20480',
            ], [
                'order_request_files.required' => 'Bắt buộc đính kèm ít nhất 1 file khi gửi yêu cầu đặt hàng.',
                'order_request_files.min' => 'Bắt buộc đính kèm ít nhất 1 file khi gửi yêu cầu đặt hàng.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('storeOrderRequest validation failed: ' . json_encode($e->errors()));
            throw $e;
        }

        $this->validateTradeUpOrderRequestSerials($sale, $validated['order_request_items'], $isDraft);

        DB::beginTransaction();
        try {
            $orderRequest = \App\Models\SaleOrderRequest::create([
                'code' => \App\Models\SaleOrderRequest::generateCode(),
                'sale_id' => $sale->id,
                'created_by' => auth()->id(),
                'note' => $request->input('order_request_note'),
                'is_license_from_other_distributor' => $request->boolean('is_license_from_other_distributor'),
                'other_distributor_name' => $request->input('other_distributor_name'),
                'sent_at' => $isDraft ? null : now(),
                'status' => $isDraft ? \App\Models\SaleOrderRequest::STATUS_DRAFT : \App\Models\SaleOrderRequest::STATUS_PENDING_ADMIN,
            ]);

            foreach ($validated['order_request_items'] as $item) {
                $supplier = !empty($item['vendor_id']) ? \App\Models\Supplier::find($item['vendor_id']) : null;
                $isFortinet = $supplier && stripos($supplier->name, 'Fortinet') !== false;
                $isHW = ($item['type'] ?? '') === 'HW';
                $needsCq = ($isFortinet && $isHW) ? !empty($item['needs_cq']) : true;
                
                if (!$isDraft && $needsCq && (empty($item['eu_name']) || empty($item['mst']))) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'order_request_items' => ['EU Name và MST bắt buộc khi cần cấp CQ riêng cho sản phẩm ' . ($item['part_number'] ?? '')]
                    ]);
                }
                
                $euNameMst = '';
                if (!empty($item['eu_name']) && !empty($item['mst'])) {
                    $euNameMst = trim($item['eu_name']) . ' - ' . trim($item['mst']);
                } elseif (!empty($item['eu_name'])) {
                    $euNameMst = trim($item['eu_name']);
                }

                $rawSn = $item['serial_number'] ?? null;
                $snStr = null;
                if (is_array($rawSn)) {
                    $cleanSerials = array_filter(array_map('trim', $rawSn), fn($v) => $v !== '');
                    $snStr = !empty($cleanSerials) ? implode(', ', $cleanSerials) : null;
                } elseif (is_string($rawSn) && trim($rawSn) !== '') {
                    $snStr = trim($rawSn);
                }
                $serialExpiryDates = $this->buildSerialExpiryDates(
                    $rawSn,
                    $item['serial_expiry_dates'] ?? [],
                    $item['exp_date'] ?? null
                );
                
                \App\Models\SaleOrderRequestItem::create([
                    'sale_order_request_id' => $orderRequest->id,
                    'vendor_id' => $item['vendor_id'] ?? null,
                    'vendor' => $supplier?->name ?? '',
                    'type' => $item['type'] ?? 'HW',
                    'needs_cq' => $needsCq,
                    'part_number' => $item['part_number'] ?? '',
                    'product_id' => $item['product_id'] ?? null,
                    'sale_item_id' => $item['sale_item_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'serial_number' => $snStr,
                    'serial_expiry_dates' => $serialExpiryDates,
                    'exp_date' => $item['exp_date'] ?? null,
                    'si_name' => $item['si_name'] ?? '',
                    'pos_id' => $item['pos_id'] ?? null,
                    'eu_name_mst' => $euNameMst,
                    'address' => $item['address'] ?? null,
                ]);
            }

            if ($request->hasFile('order_request_files')) {
                foreach ($request->file('order_request_files') as $file) {
                    $path = $file->store('sale-order-requests/' . $orderRequest->id, 'public');
                    \App\Models\SaleOrderRequestAttachment::create([
                        'sale_order_request_id' => $orderRequest->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }

            if (!$isDraft) {
                $poUsers = User::whereHas('roles', fn($q) => $q->whereIn('slug', ['admin', 'purchase_manager']))->get();
                $senderName = auth()->user()->name ?? 'Sales';
                foreach ($poUsers as $user) {
                    if ($user->id === auth()->id()) continue;
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'order_request',
                        'title' => 'Yêu cầu đặt hàng mới',
                        'message' => "{$senderName} đã gửi yêu cầu đặt hàng ({$orderRequest->code}) cho đơn {$sale->code}",
                        'link' => route('sales.show', $sale->id),
                        'icon' => 'fas fa-cart-plus',
                        'color' => 'blue',
                    ]);
                }
            }

            DB::commit();

            $msg = $isDraft 
                ? "Đã lưu nháp yêu cầu đặt hàng ({$orderRequest->code}) thành công!" 
                : "Đã gửi yêu cầu đặt hàng ({$orderRequest->code}) thành công!";
            return redirect()->route('sales.show', $sale->id)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store order request failed: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Update and resubmit an order request
     */
    public function updateOrderRequest(Request $request, Sale $sale, \App\Models\SaleOrderRequest $orderRequest)
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('purchase_manager');
        
        if (!$isAdmin && auth()->id() !== $orderRequest->created_by && auth()->id() !== $sale->user_id) {
            return back()->with('error', 'Bạn không có quyền chỉnh sửa yêu cầu này.');
        }

        if (!in_array($orderRequest->status, [\App\Models\SaleOrderRequest::STATUS_DRAFT, \App\Models\SaleOrderRequest::STATUS_NEED_INFO]) && !$isAdmin) {
            return back()->with('error', 'Chỉ có thể chỉnh sửa yêu cầu ở trạng thái "Bản nháp" hoặc "Thiếu thông tin".');
        }

        $isDraft = $request->input('action_type') === 'draft';

        if (!$isDraft) {
            $status = $sale->getPaymentConditionStatus();
            if (!$status['eligible_for_order']) {
                $pendingList = implode(', ', $status['pending_order_milestones']);
                return back()->with('error', 'Đơn hàng chưa đủ điều kiện đặt hàng do chưa có UNC hoặc chưa được Finance xác nhận đợt: ' . $pendingList);
            }
        }

        $validated = $request->validate([
            'order_request_items' => 'required|array|min:1',
            'order_request_items.*.vendor_id' => $isDraft ? 'nullable' : 'required|exists:suppliers,id',
            'order_request_items.*.type' => $isDraft ? 'nullable|string' : 'required|string|max:100',
            'order_request_items.*.needs_cq' => 'nullable|boolean',
            'order_request_items.*.part_number' => $isDraft ? 'nullable|string' : 'required|string|max:255',
            'order_request_items.*.product_id' => 'nullable|exists:products,id',
            'order_request_items.*.sale_item_id' => 'nullable|exists:sale_items,id',
            'order_request_items.*.quantity' => 'required|numeric|min:0.01',
            'order_request_items.*.unit' => 'nullable|string|max:50',
                'order_request_items.*.serial_number' => 'nullable',
                'order_request_items.*.serial_expiry_dates' => 'nullable|array',
                'order_request_items.*.serial_expiry_dates.*' => 'nullable|date',
                'order_request_items.*.exp_date' => 'nullable|date',
            'order_request_items.*.si_name' => $isDraft ? 'nullable|string' : 'required|string|max:255',
            'order_request_items.*.pos_id' => 'nullable|string|max:255',
            'order_request_items.*.eu_name' => 'nullable|string|max:255',
            'order_request_items.*.mst' => 'nullable|string|max:255',
            'order_request_items.*.address' => 'nullable|string|max:500',
            'order_request_note' => 'nullable|string|max:2000',
            'is_license_from_other_distributor' => 'nullable|boolean',
            'other_distributor_name' => 'nullable|string|max:255',
            'order_request_files' => ($isDraft || $orderRequest->attachments()->count() > 0) ? 'nullable|array' : 'required|array|min:1',
            'order_request_files.*' => 'file|max:20480',
        ], [
            'order_request_files.required' => 'Bắt buộc đính kèm ít nhất 1 file khi gửi yêu cầu đặt hàng.',
            'order_request_files.min' => 'Bắt buộc đính kèm ít nhất 1 file khi gửi yêu cầu đặt hàng.',
        ]);

        $this->validateTradeUpOrderRequestSerials($sale, $validated['order_request_items'], $isDraft);

        DB::beginTransaction();
        try {
            $newStatus = $isDraft ? \App\Models\SaleOrderRequest::STATUS_DRAFT : \App\Models\SaleOrderRequest::STATUS_PENDING_ADMIN;
            $orderRequest->update([
                'note' => $request->input('order_request_note'),
                'is_license_from_other_distributor' => $request->boolean('is_license_from_other_distributor'),
                'other_distributor_name' => $request->input('other_distributor_name'),
                'status' => $newStatus,
                'sent_at' => $isDraft ? null : now(),
                'rejection_note' => $isDraft ? $orderRequest->rejection_note : null,
            ]);

            $orderRequest->items()->delete();

            foreach ($validated['order_request_items'] as $item) {
                $supplier = !empty($item['vendor_id']) ? \App\Models\Supplier::find($item['vendor_id']) : null;
                $isFortinet = $supplier && stripos($supplier->name, 'Fortinet') !== false;
                $isHW = ($item['type'] ?? '') === 'HW';
                $needsCq = ($isFortinet && $isHW) ? !empty($item['needs_cq']) : true;

                if (!$isDraft && $needsCq && (empty($item['eu_name']) || empty($item['mst']))) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'order_request_items' => ['EU Name và MST bắt buộc khi cần cấp CQ riêng cho sản phẩm ' . ($item['part_number'] ?? '')]
                    ]);
                }

                $euNameMst = '';
                if (!empty($item['eu_name']) && !empty($item['mst'])) {
                    $euNameMst = trim($item['eu_name']) . ' - ' . trim($item['mst']);
                } elseif (!empty($item['eu_name'])) {
                    $euNameMst = trim($item['eu_name']);
                }

                $rawSn = $item['serial_number'] ?? null;
                $snStr = null;
                if (is_array($rawSn)) {
                    $cleanSerials = array_filter(array_map('trim', $rawSn), fn($v) => $v !== '');
                    $snStr = !empty($cleanSerials) ? implode(', ', $cleanSerials) : null;
                } elseif (is_string($rawSn) && trim($rawSn) !== '') {
                    $snStr = trim($rawSn);
                }
                $serialExpiryDates = $this->buildSerialExpiryDates(
                    $rawSn,
                    $item['serial_expiry_dates'] ?? [],
                    $item['exp_date'] ?? null
                );

                \App\Models\SaleOrderRequestItem::create([
                    'sale_order_request_id' => $orderRequest->id,
                    'vendor_id' => $item['vendor_id'] ?? null,
                    'vendor' => $supplier?->name ?? '',
                    'type' => $item['type'] ?? 'HW',
                    'needs_cq' => $needsCq,
                    'part_number' => $item['part_number'] ?? '',
                    'product_id' => $item['product_id'] ?? null,
                    'sale_item_id' => $item['sale_item_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'serial_number' => $snStr,
                    'serial_expiry_dates' => $serialExpiryDates,
                    'exp_date' => $item['exp_date'] ?? null,
                    'si_name' => $item['si_name'] ?? '',
                    'pos_id' => $item['pos_id'] ?? null,
                    'eu_name_mst' => $euNameMst,
                    'address' => $item['address'] ?? null,
                ]);
            }

            if ($request->hasFile('order_request_files')) {
                foreach ($request->file('order_request_files') as $file) {
                    $path = $file->store('sale-order-requests/' . $orderRequest->id, 'public');
                    \App\Models\SaleOrderRequestAttachment::create([
                        'sale_order_request_id' => $orderRequest->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }

            if (!$isDraft) {
                $poUsers = User::whereHas('roles', fn($q) => $q->whereIn('slug', ['admin', 'purchase_manager']))->get();
                $senderName = auth()->user()->name ?? 'Sales';
                foreach ($poUsers as $user) {
                    if ($user->id === auth()->id()) continue;
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'order_request',
                        'title' => 'Yêu cầu đặt hàng đã gửi',
                        'message' => "{$senderName} đã chỉnh sửa và gửi yêu cầu đặt hàng ({$orderRequest->code}) cho đơn {$sale->code}",
                        'link' => route('sales.show', $sale->id),
                        'icon' => 'fas fa-paper-plane',
                        'color' => 'blue',
                    ]);
                }
            }

            DB::commit();

            $msg = $isDraft 
                ? "Đã lưu nháp yêu cầu đặt hàng ({$orderRequest->code}) thành công!" 
                : "Đã gửi yêu cầu đặt hàng ({$orderRequest->code}) thành công!";
            return redirect()->route('sales.show', $sale->id)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update order request failed: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Admin approves a purchase request
     */
    public function approveOrderRequestByAdmin(Request $request, Sale $sale, \App\Models\SaleOrderRequest $orderRequest)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['admin', 'super_admin', 'director', 'purchase_manager', 'sales_manager'])) {
            return back()->with('error', 'Bạn không có quyền thực hiện hành động này.');
        }

        if ($orderRequest->status !== \App\Models\SaleOrderRequest::STATUS_PENDING_ADMIN) {
            return back()->with('error', 'Yêu cầu không ở trạng thái Chờ Admin duyệt.');
        }

        $orderRequest->update([
            'status' => \App\Models\SaleOrderRequest::STATUS_PROCESSING,
            'rejection_note' => null,
        ]);

        // Notify PO Team (Purchase managers / purchase staff)
        $poUsers = User::whereHas('roles', function ($q) {
            $q->whereIn('slug', ['purchase_manager', 'purchase_staff']);
        })->get();

        $adminName = $user->name;
        foreach ($poUsers as $poUser) {
            Notification::create([
                'user_id' => $poUser->id,
                'type' => 'order_request',
                'title' => 'Yêu cầu đặt hàng mới đã được duyệt',
                'message' => "Admin {$adminName} đã duyệt yêu cầu đặt hàng ({$orderRequest->code}) cho đơn {$sale->code}. Sẵn sàng để gom hàng.",
                'link' => route('sales.show', $sale->id),
                'icon' => 'fas fa-cart-plus',
                'color' => 'blue',
            ]);
        }

        // Notify creator (Salesperson)
        if ($orderRequest->created_by) {
            Notification::create([
                'user_id' => $orderRequest->created_by,
                'type' => 'order_request',
                'title' => 'Yêu cầu đặt hàng đã được duyệt',
                'message' => "Yêu cầu đặt hàng ({$orderRequest->code}) của bạn đã được Admin duyệt.",
                'link' => route('sales.show', $sale->id),
                'icon' => 'fas fa-check-circle',
                'color' => 'green',
            ]);
        }

        return back()->with('success', 'Đã duyệt yêu cầu đặt hàng #' . $orderRequest->code);
    }

    /**
     * Admin rejects/requests info for a purchase request
     */
    public function rejectOrderRequestByAdmin(Request $request, Sale $sale, \App\Models\SaleOrderRequest $orderRequest)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['admin', 'super_admin', 'director', 'purchase_manager', 'sales_manager'])) {
            return back()->with('error', 'Bạn không có quyền thực hiện hành động này.');
        }

        if ($orderRequest->status !== \App\Models\SaleOrderRequest::STATUS_PENDING_ADMIN) {
            return back()->with('error', 'Yêu cầu không ở trạng thái Chờ Admin duyệt.');
        }

        $request->validate(['rejection_note' => 'required|string|max:1000']);

        $orderRequest->update([
            'status' => \App\Models\SaleOrderRequest::STATUS_NEED_INFO,
            'rejection_note' => $request->input('rejection_note'),
        ]);

        // Notify creator (Salesperson)
        if ($orderRequest->created_by) {
            Notification::create([
                'user_id' => $orderRequest->created_by,
                'type' => 'order_request',
                'title' => 'Yêu cầu đặt hàng bị trả về',
                'message' => "Yêu cầu đặt hàng ({$orderRequest->code}) của bạn bị Admin trả về vì: \"{$orderRequest->rejection_note}\".",
                'link' => route('sales.show', $sale->id),
                'icon' => 'fas fa-exclamation-triangle',
                'color' => 'orange',
            ]);
        }

        return back()->with('success', 'Đã trả yêu cầu đặt hàng #' . $orderRequest->code . ' về cho bộ phận Sales.');
    }

    /**
     * Download order request attachment
     */
    public function downloadOrderRequestAttachment(Sale $sale, \App\Models\SaleOrderRequestAttachment $attachment)
    {
        if (!auth()->user()->can('view', $sale)) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            return back()->with('error', 'File không tồn tại.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Preview order request attachment
     */
    public function previewOrderRequestAttachment(Sale $sale, \App\Models\SaleOrderRequestAttachment $attachment)
    {
        if (!auth()->user()->can('view', $sale)) {
            abort(404);
        }

        $path = Storage::disk('public')->path($attachment->file_path);

        if (!file_exists($path)) {
            return back()->with('error', 'File không tồn tại.');
        }

        return response()->file($path);
    }

    public function orderTracking(Request $request)
    {
        $this->authorize('viewAny', Sale::class);

        $query = SaleOrderRequestItem::where('is_cancelled', false)
            ->whereHas('saleOrderRequest', function($q) {
                $q->whereIn('status', [
                    \App\Models\SaleOrderRequest::STATUS_SUBMITTED,
                    \App\Models\SaleOrderRequest::STATUS_PROCESSING,
                    \App\Models\SaleOrderRequest::STATUS_COMPLETED
                ]);
            })
            ->with(['saleOrderRequest.sale', 'vendor', 'purchaseOrderItems.purchaseOrder']);

        $user = auth()->user();
        if (!$user->can('view_all_sales')) {
            $query->whereHas('saleOrderRequest.sale', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // Filter by Sales Order Code
        if ($request->filled('sale_code')) {
            $query->whereHas('saleOrderRequest.sale', function($q) use ($request) {
                $q->where('code', 'like', '%' . $request->sale_code . '%');
            });
        }

        // Filter by Part Number
        if ($request->filled('part_number')) {
            $query->where('part_number', 'like', '%' . $request->part_number . '%');
        }

        // Filter by Vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by Status (auto-computed)
        $statusFilter = $request->input('status_filter');

        $allItems = $query->latest()->get();

        // 🔥 Group theo Sale Order + Product (part_number)
        $grouped = [];
        foreach ($allItems as $item) {
            $saleCode = $item->saleOrderRequest->sale->code ?? 'N/A';
            $saleId = $item->saleOrderRequest->sale_id ?? 0;
            $key = $saleId . '-' . ($item->part_number ?? 'no-pn');

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'sale_id' => $saleId,
                    'sale_code' => $saleCode,
                    'part_number' => $item->part_number,
                    'vendor_name' => $item->vendor->name ?? $item->vendor ?? 'N/A',
                    'requested' => 0,
                    'ordered' => 0,
                    'received' => 0,
                    'pr_codes' => [],
                    'po_links' => [], // [{id, code, status_label}]
                    'created_at' => $item->created_at,
                    'po_items_raw' => [],
                    'sale_raw' => $item->saleOrderRequest->sale ?? null,
                    'pr_created_at' => $item->created_at,
                ];
            }

            $ordered = $item->ordered_quantity_total;
            $received = $item->received_quantity_total;

            $grouped[$key]['requested'] += $item->quantity;
            $grouped[$key]['ordered'] += $ordered;
            $grouped[$key]['received'] += $received;

            // Thu thập mã PR (không trùng)
            $prCode = $item->saleOrderRequest->code ?? '';
            if ($prCode && !in_array($prCode, $grouped[$key]['pr_codes'])) {
                $grouped[$key]['pr_codes'][] = $prCode;
            }

            // Thu thập link PO (không trùng) và raw items
            foreach ($item->purchaseOrderItems as $poItem) {
                $poId = $poItem->purchase_order_id;
                $po = $poItem->purchaseOrder;
                if ($po && !isset($grouped[$key]['po_links'][$poId])) {
                    $grouped[$key]['po_links'][$poId] = [
                        'id' => $poId,
                        'code' => $po->code ?? '',
                        'status' => $po->status ?? '',
                        'status_label' => $po->status_label ?? '',
                        'order_date' => $po->order_date ? $po->order_date->format('d/m/Y') : '--',
                        'supplier_name' => $po->supplier->name ?? 'N/A',
                        'cpq_number' => $po->cpq_number ?? '--',
                        'etd' => $po->manufacturer_release_date ? $po->manufacturer_release_date->format('d/m/Y') : '--',
                        'eta' => $po->expected_arrival_date ? $po->expected_arrival_date->format('d/m/Y') : '--',
                        'actual_delivery' => $po->actual_delivery ? $po->actual_delivery->format('d/m/Y') : '--',
                        'total_usd' => number_format($po->total_foreign ?: ($po->total / max(1, $po->exchange_rate ?: 1)), 2),
                        'payment_terms' => $po->payment_terms_label ?? $po->payment_terms ?? 'Net 30',
                        'note' => $po->note ?? '',
                        'hold_reason' => $po->hold_reason ?? '',
                    ];
                }
                $grouped[$key]['po_items_raw'][] = $poItem;
            }

            // Giữ ngày sớm nhất
            if ($item->created_at && $item->created_at->lt($grouped[$key]['created_at'])) {
                $grouped[$key]['created_at'] = $item->created_at;
            }
        }

        // Tính auto status và đếm thống kê cho mỗi nhóm
        $statsCounts = [
            'pr' => 0,
            'po' => 0,
            'vendor_confirm' => 0,
            'production' => 0,
            'mfg_export' => 0,
            'transit' => 0,
            'arrived_vn' => 0,
            'warehouse_received' => 0,
            'delivered_sales' => 0,
        ];

        foreach ($grouped as &$row) {
            $row['po_links'] = array_values($row['po_links']);
            $row['remaining'] = max(0, $row['requested'] - $row['received']);

            $row['completion_percent'] = $row['requested'] > 0 ? min(100, round(($row['received'] / $row['requested']) * 100)) : 0;
            $row['ordered_percent'] = $row['requested'] > 0 ? min(100, round(($row['ordered'] / $row['requested']) * 100)) : 0;

            // Compute 9-step timeline details
            $row['timeline'] = $this->calculateTimeline(
                $row['requested'],
                $row['ordered'],
                $row['received'],
                $row['po_items_raw'],
                $row['sale_raw'],
                $row['pr_created_at']
            );

            // Find current active step in the timeline
            $currentStatusStep = 'pr';
            $stepOrder = ['pr', 'po', 'vendor_confirm', 'production', 'mfg_export', 'transit', 'arrived_vn', 'warehouse_received', 'delivered_sales'];
            foreach ($stepOrder as $stepKey) {
                if ($row['timeline'][$stepKey]['status'] !== 'pending') {
                    $currentStatusStep = $stepKey;
                }
            }

            // Determine the status label based on the active step and PO status
            $statusLabel = 'N/A';
            $statusColor = 'bg-gray-100 text-gray-600';
            $statusIcon = 'fas fa-info-circle';

            $representativePo = null;
            if (!empty($row['po_items_raw'])) {
                $statusWeights = [
                    'draft' => 0,
                    'pending_approval' => 1,
                    'approved' => 2,
                    'sent' => 3,
                    'confirmed' => 4,
                    'shipping' => 5,
                    'partial_received' => 6,
                    'received' => 7,
                ];
                $maxStatusWeight = -1;
                foreach ($row['po_items_raw'] as $poItem) {
                    $po = $poItem instanceof \App\Models\PurchaseOrder ? $poItem : ($poItem->purchaseOrder ?? null);
                    if ($po) {
                        $weight = $statusWeights[$po->status] ?? 0;
                        if ($weight > $maxStatusWeight) {
                            $maxStatusWeight = $weight;
                            $representativePo = $po;
                        }
                    }
                }
            }

            // Timeline mốc thời gian ETA/ETD/Actual Arrival
            $row['etd'] = $representativePo && $representativePo->manufacturer_release_date ? $representativePo->manufacturer_release_date->format('d/m/Y') : null;
            $row['eta'] = $representativePo && $representativePo->expected_arrival_date ? $representativePo->expected_arrival_date->format('d/m/Y') : null;
            $row['actual_arrival'] = $representativePo && $representativePo->actual_delivery ? $representativePo->actual_delivery->format('d/m/Y') : null;

            // Phân tích nguyên nhân thiếu hàng (nếu còn thiếu)
            $shortageReason = null;
            if ($row['remaining'] > 0) {
                if ($row['ordered'] <= 0) {
                    $shortageReason = 'Chưa tạo đơn mua hàng (PO) với nhà cung cấp';
                } elseif ($representativePo) {
                    if ($representativePo->is_hold) {
                        $shortageReason = 'PO đang tạm hoãn: ' . ($representativePo->hold_reason ?: 'Cần kiểm tra lại');
                    } elseif ($representativePo->status === 'draft') {
                        $shortageReason = 'PO mới ở dạng nháp, chưa gửi duyệt';
                    } elseif ($representativePo->status === 'pending_approval') {
                        $shortageReason = 'PO đang chờ cấp quản lý phê duyệt';
                    } elseif (in_array($representativePo->status, ['approved', 'sent'])) {
                        $shortageReason = 'Đã gửi PO, chờ nhà cung cấp xác nhận đơn';
                    } elseif ($representativePo->status === 'confirmed') {
                        $releaseStr = $representativePo->manufacturer_release_date ? $representativePo->manufacturer_release_date->format('d/m/Y') : 'chưa rõ';
                        $shortageReason = "Hãng đang sản xuất (Dự kiến xong ETD: {$releaseStr})";
                    } elseif (in_array($representativePo->status, ['shipping', 'partial_received'])) {
                        $arrivalStr = $representativePo->expected_arrival_date ? $representativePo->expected_arrival_date->format('d/m/Y') : 'chưa rõ';
                        $shortageReason = "Hàng đang trên đường vận chuyển về VN (Dự kiến ETA: {$arrivalStr})";
                    }
                }
            }
            $row['shortage_reason'] = $shortageReason;

            if ($currentStatusStep === 'pr') {
                $statusLabel = 'Chờ đặt hàng';
                $statusColor = 'bg-gray-100 text-gray-600';
                $statusIcon = 'fas fa-clock';
            } elseif ($currentStatusStep === 'po') {
                $poStatusVal = $representativePo ? $representativePo->status : '';
                if ($poStatusVal === 'draft') {
                    $statusLabel = 'PO Nháp';
                    $statusColor = 'bg-yellow-100 text-yellow-800';
                    $statusIcon = 'fas fa-file-signature';
                } elseif ($poStatusVal === 'pending_approval') {
                    $statusLabel = 'Chờ duyệt PO';
                    $statusColor = 'bg-orange-100 text-orange-800';
                    $statusIcon = 'fas fa-user-clock';
                } elseif ($poStatusVal === 'approved') {
                    $statusLabel = 'Đã duyệt PO';
                    $statusColor = 'bg-indigo-100 text-indigo-800';
                    $statusIcon = 'fas fa-check-double';
                } elseif ($poStatusVal === 'sent') {
                    $statusLabel = 'Đã gửi NCC';
                    $statusColor = 'bg-blue-100 text-blue-800';
                    $statusIcon = 'fas fa-paper-plane';
                } else {
                    $statusLabel = 'Đang đặt hàng';
                    $statusColor = 'bg-blue-100 text-blue-800';
                    $statusIcon = 'fas fa-shopping-cart';
                }
            } elseif ($currentStatusStep === 'vendor_confirm') {
                $statusLabel = 'Hãng xác nhận';
                $statusColor = 'bg-cyan-100 text-cyan-800';
                $statusIcon = 'fas fa-user-check';
            } elseif ($currentStatusStep === 'production') {
                $statusLabel = 'Đang sản xuất';
                $statusColor = 'bg-purple-100 text-purple-800';
                $statusIcon = 'fas fa-industry';
            } elseif ($currentStatusStep === 'mfg_export') {
                $statusLabel = 'Xuất kho hãng';
                $statusColor = 'bg-pink-100 text-pink-800';
                $statusIcon = 'fas fa-sign-out-alt';
            } elseif ($currentStatusStep === 'transit') {
                $statusLabel = 'Đang vận chuyển';
                $statusColor = 'bg-amber-100 text-amber-800';
                $statusIcon = 'fas fa-shipping-fast';
            } elseif ($currentStatusStep === 'arrived_vn') {
                $statusLabel = 'Đã về VN';
                $statusColor = 'bg-teal-100 text-teal-800';
                $statusIcon = 'fas fa-plane-arrival';
            } elseif ($currentStatusStep === 'warehouse_received') {
                if ($row['received'] < $row['requested']) {
                    $statusLabel = 'Nhập kho 1 phần';
                    $statusColor = 'bg-emerald-100 text-emerald-800';
                    $statusIcon = 'fas fa-warehouse';
                } else {
                    $statusLabel = 'Đã nhập kho';
                    $statusColor = 'bg-emerald-200 text-emerald-950';
                    $statusIcon = 'fas fa-warehouse';
                }
            } elseif ($currentStatusStep === 'delivered_sales') {
                $statusLabel = 'Đã giao Sales';
                $statusColor = 'bg-green-100 text-green-800';
                $statusIcon = 'fas fa-check-circle';
            }

            $row['status'] = $currentStatusStep;
            $row['status_label'] = $statusLabel;
            $row['status_color'] = $statusColor;
            $row['status_icon'] = $statusIcon;

            // Đếm thống kê
            if (isset($statsCounts[$currentStatusStep])) {
                $statsCounts[$currentStatusStep]++;
            }
        }
        unset($row);

        // Filter by status nếu có
        if ($statusFilter) {
            $grouped = array_filter($grouped, fn($r) => $r['status'] === $statusFilter);
        }

        // Sort: chưa xong trước, đã xong cuối
        $statusOrder = [
            'pr' => 0,
            'po' => 1,
            'vendor_confirm' => 2,
            'production' => 3,
            'mfg_export' => 4,
            'transit' => 5,
            'arrived_vn' => 6,
            'warehouse_received' => 7,
            'delivered_sales' => 8,
        ];
        uasort($grouped, function($a, $b) use ($statusOrder) {
            return ($statusOrder[$a['status']] ?? 99) <=> ($statusOrder[$b['status']] ?? 99);
        });

        // Paginate thủ công
        $page = $request->input('page', 1);
        $perPage = 20;
        $total = count($grouped);
        $items = collect(array_slice($grouped, ($page - 1) * $perPage, $perPage));
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $vendors = \App\Models\Supplier::orderBy('name')->get();

        return view('sales.order-tracking', [
            'rows' => $paginator,
            'vendors' => $vendors,
            'statsCounts' => $statsCounts,
        ]);
    }

    /**
     * Upload PNL attachment dynamically
     */
    public function uploadPnlAttachment(Request $request, Sale $sale)
    {
        $this->authorize('update', $sale);

        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs(
                'pnl-attachments/' . $sale->id,
                time() . '_' . Str::random(5) . '_' . $originalName,
                'public'
            );

            $attachment = PnlApprovalAttachment::create([
                'sale_id' => $sale->id,
                'uploaded_by' => auth()->id(),
                'file_name' => $originalName,
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => true,
                'attachment' => [
                    'id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'size' => $attachment->file_size_human,
                    'icon' => $attachment->file_icon,
                    'download_url' => route('sales.pnl-attachments.download', [$sale, $attachment]),
                    'delete_url' => route('sales.pnl-attachments.delete', [$sale, $attachment]),
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'File không hợp lệ.'], 400);
    }

    /**
     * Delete PNL attachment
     */
    public function deletePnlAttachment(Sale $sale, PnlApprovalAttachment $attachment)
    {
        $this->authorize('update', $sale);

        if ($attachment->sale_id !== $sale->id) {
            abort(403);
        }

        // Không cho phép xóa tệp đã gửi duyệt (thuộc lịch sử duyệt)
        if ($attachment->approval_history_id !== null) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Không thể xóa tệp đã gửi duyệt trong lịch sử.'], 403);
            }
            return back()->with('error', 'Không thể xóa tệp đã gửi duyệt trong lịch sử.');
        }

        // Xóa file vật lý
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Đã xóa file đính kèm P&L thành công.');
    }

    /**
     * Download PNL attachment
     */
    public function downloadPnlAttachment(Sale $sale, PnlApprovalAttachment $attachment)
    {
        // Ai được quyền xem đơn hàng hoặc duyệt P&L thì được download
        $this->authorize('view', $sale);

        if ($attachment->sale_id !== $sale->id) {
            abort(403);
        }

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File không tồn tại.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Preview PNL attachment (serve raw file for in-browser preview)
     */
    public function previewPnlAttachment(Sale $sale, PnlApprovalAttachment $attachment)
    {
        $this->authorize('view', $sale);

        if ($attachment->sale_id !== $sale->id) {
            abort(403);
        }

        $path = Storage::disk('public')->path($attachment->file_path);

        if (!file_exists($path)) {
            abort(404, 'File không tồn tại.');
        }

        return response()->file($path);
    }

    /**
     * Helper to handle bulk upload of P&L attachments in main P&L form save
     */
    private function handlePnlAttachmentsUpload(Request $request, Sale $sale): void
    {
        if ($request->hasFile('pnl_attachments')) {
            foreach ($request->file('pnl_attachments') as $file) {
                if ($file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $path = $file->storeAs(
                        'pnl-attachments/' . $sale->id,
                        time() . '_' . Str::random(5) . '_' . $originalName,
                        'public'
                    );

                    PnlApprovalAttachment::create([
                        'sale_id' => $sale->id,
                        'uploaded_by' => auth()->id(),
                        'file_name' => $originalName,
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        }
    }

    /**
     * BOD approves payment exception for the whole Sale
     */
    public function approvePaymentException(Request $request, Sale $sale)
    {
        $user = auth()->user();
        $isAuthorized = $user->hasRole('director') || 
                        $user->hasRole('super_admin') || 
                        $user->hasRole('admin') || 
                        ($sale->payment_exception_delegated_to === $user->id) ||
                        ($sale->user_id === $user->id) ||
                        $user->hasRole('sales');

        if (!$isAuthorized) {
            return back()->with('error', 'Bạn không có quyền duyệt ngoại lệ thanh toán.');
        }

        $request->validate([
            'reason' => 'nullable|string|max:1000',
            'note' => 'nullable|string|max:1000',
            'payment_exception_file' => 'nullable|file|max:20480',
            'payment_exception_files' => 'nullable|array',
            'payment_exception_files.*' => 'file|max:20480',
        ]);

        $paths = [];
        if ($request->hasFile('payment_exception_file')) {
            $paths[] = $request->file('payment_exception_file')->store('payment-exceptions/' . $sale->id, 'public');
        }
        if ($request->hasFile('payment_exception_files')) {
            foreach ($request->file('payment_exception_files') as $file) {
                $paths[] = $file->store('payment-exceptions/' . $sale->id, 'public');
            }
        }

        $note = trim((string)($request->input('note') ?? $request->input('reason') ?? ''));
        if (empty($paths) && empty($note)) {
            return back()->with('error', 'Vui lòng nhập ghi chú lý do duyệt ngoại lệ hoặc đính kèm tệp phê duyệt.');
        }

        $sale->is_payment_exception = true;
        $sale->payment_exception_file = empty($paths) ? null : (count($paths) === 1 ? $paths[0] : json_encode($paths));
        $sale->save();

        // Ghi log Audit Trail
        $logReason = !empty($note) 
            ? 'Duyệt ngoại lệ/preload toàn đơn. Lý do: ' . $note . ' (Người thực hiện: ' . $user->name . ')'
            : 'Đã duyệt ngoại lệ toàn đơn hàng. Người thực hiện: ' . $user->name;

        \App\Models\PaymentApprovalLog::create([
            'sale_id' => $sale->id,
            'action' => 'bod_exception_approved',
            'new_value' => 'exception_approved',
            'reason' => $logReason,
            'attachment_path' => empty($paths) ? null : (count($paths) === 1 ? $paths[0] : json_encode($paths)),
            'performed_by' => $user->id,
            'performed_at' => now(),
        ]);

        // Create notification for Sales
        $salesPerson = $sale->user;
        if ($salesPerson) {
            \App\Models\Notification::create([
                'user_id' => $salesPerson->id,
                'type' => 'payment_alert',
                'title' => 'Đơn hàng được duyệt ngoại lệ',
                'message' => "Đơn hàng {$sale->code} đã được duyệt ngoại lệ thanh toán.",
                'link' => route('sales.show', $sale->id),
                'icon' => 'fas fa-shield-alt',
                'color' => 'blue',
            ]);
        }

        return back()->with('success', 'Đã duyệt ngoại lệ thanh toán cho đơn hàng thành công!');
    }

    /**
     * Sales uploads UNC/Proof for a specific milestone
     */
    public function submitMilestoneProof(Request $request, Sale $sale, $index)
    {
        $user = auth()->user();
        $mayUploadProof = $sale->user_id === $user->id
            || $user->hasRole('sales_manager')
            || $user->hasRole('admin')
            || $user->hasRole('super_admin');

        if (!$mayUploadProof) {
            abort(403, 'Chỉ Sales phụ trách đơn hàng hoặc quản lý Sales được tải UNC/chứng từ.');
        }

        $request->validate([
            'proof_file' => 'required|file|max:20480',
        ]);

        $schedule = $sale->paymentSchedules()->skip($index)->first();
        if (!$schedule) {
            return back()->with('error', 'Mốc thanh toán không tồn tại.');
        }

        if ($request->hasFile('proof_file')) {
            $file = $request->file('proof_file');
            $path = $file->store('payment-proofs/' . $sale->id, 'public');
            
            $oldStatus = $schedule->status;
            $schedule->status = 'pending_finance';
            $schedule->proof_file_path = $path;
            $schedule->save();

            // Create PaymentEvidence record
            \App\Models\PaymentEvidence::create([
                'schedule_id' => $schedule->id,
                'doc_type' => $schedule->required_docs !== 'none' ? $schedule->required_docs : 'unc',
                'reference_number' => $request->input('reference_number'),
                'amount' => $schedule->amount,
                'file_path' => $path,
                'uploaded_by' => auth()->id(),
                'status' => 'pending',
            ]);

            // Ghi log
            \App\Models\PaymentApprovalLog::create([
                'schedule_id' => $schedule->id,
                'sale_id' => $sale->id,
                'action' => 'proof_uploaded',
                'old_value' => $oldStatus,
                'new_value' => 'pending_finance',
                'reason' => 'Sales uploaded proof document.',
                'attachment_path' => $path,
                'performed_by' => auth()->id(),
                'performed_at' => now(),
            ]);

            // Notify Finance/Accountants
            $accountants = User::whereHas('roles', function ($q) {
                $q->where('slug', 'accountant')
                  ->orWhere('slug', 'admin')
                  ->orWhere('slug', 'super_admin');
            })->get();

            $senderName = auth()->user()->name ?? 'Sales';
            foreach ($accountants as $acc) {
                \App\Models\Notification::create([
                    'user_id' => $acc->id,
                    'type' => 'payment_alert',
                    'title' => 'Yêu cầu xác nhận thanh toán',
                    'message' => "{$senderName} đã upload UNC cho đợt " . ($schedule->milestone_name) . " của đơn {$sale->code}.",
                    'link' => route('sales.show', $sale->id),
                    'icon' => 'fas fa-file-invoice-dollar',
                    'color' => 'yellow',
                ]);
            }

            return back()->with('success', 'Đã tải lên chứng từ thanh toán thành công. Chờ Finance xác nhận.');
        }

        return back()->with('error', 'Vui lòng chọn tệp chứng từ.');
    }

    /**
     * Finance confirms payment for a milestone
     */
    public function confirmMilestonePayment(Request $request, Sale $sale, $index)
    {
        $user = auth()->user();
        $isAuthorized = $user->hasRole('accountant') || 
                        $user->hasRole('director') || 
                        $user->hasRole('super_admin') || 
                        $user->hasRole('admin');

        if (!$isAuthorized) {
            return back()->with('error', 'Bạn không có quyền xác nhận thanh toán.');
        }

        $schedule = $sale->paymentSchedules()->skip($index)->first();
        if (!$schedule) {
            return back()->with('error', 'Mốc thanh toán không tồn tại.');
        }

        // Bắt buộc upload UNC trước khi cho phép xác nhận thanh toán
        $hasProof = !empty($schedule->evidence_path) 
                    || $schedule->evidences()->whereIn('status', ['pending', 'verified'])->exists() 
                    || $schedule->status === 'pending_finance';

        if (!$hasProof) {
            return back()->with('error', 'Bắt buộc phải Upload UNC (Ủy nhiệm chi / Chứng từ thanh toán) trước khi xác nhận thanh toán.');
        }

        $oldStatus = $schedule->status;
        $schedule->status = 'paid';
        $schedule->confirmed_by = $user->name;
        $schedule->confirmed_at = now();
        $schedule->save();

        // Update PaymentEvidence status
        $evidence = $schedule->evidences()->where('status', 'pending')->latest()->first();
        if ($evidence) {
            $evidence->update([
                'status' => 'verified',
                'verified_by' => $user->name,
                'verified_at' => now(),
            ]);
        }

        // Ghi log
        \App\Models\PaymentApprovalLog::create([
            'schedule_id' => $schedule->id,
            'sale_id' => $sale->id,
            'action' => 'finance_confirmed',
            'old_value' => $oldStatus,
            'new_value' => 'paid',
            'reason' => 'Finance confirmed payment.',
            'performed_by' => $user->id,
            'performed_at' => now(),
        ]);
        
        // Recalculate total paid amount based on paid milestones
        $totalPaid = $sale->paymentSchedules()->where('status', 'paid')->sum('amount');
        $sale->paid_amount = $totalPaid;
        $sale->updateDebt();
        $sale->save();
        $sale->checkAndAutoRecordCompletion();

        // Create financial transaction
        try {
            $financialService = app(\App\Services\FinancialTransactionService::class);
            $enrichedNote = "Xác nhận thanh toán đợt \"" . $schedule->milestone_name . "\"";
            if ($schedule->percentage > 0) {
                $enrichedNote .= " ({$schedule->percentage}%)";
            }
            
            $financialService->createFromSale(
                $sale,
                $schedule->amount,
                'bank_transfer',
                $enrichedNote,
                $sale->currency_id ?: \App\Models\Currency::getBaseCurrencyId(),
                $sale->exchange_rate ?: 1
            );
        } catch (\Exception $ex) {
            \Illuminate\Support\Facades\Log::warning('Could not create financial transaction for confirmed milestone: ' . $ex->getMessage());
        }

        // Notify Sales
        $salesPerson = $sale->user;
        if ($salesPerson) {
            \App\Models\Notification::create([
                'user_id' => $salesPerson->id,
                'type' => 'payment_alert',
                'title' => 'Thanh toán đã được xác nhận',
                'message' => "Finance đã xác nhận thanh toán cho đợt " . ($schedule->milestone_name) . " của đơn {$sale->code}.",
                'link' => route('sales.show', $sale->id),
                'icon' => 'fas fa-check-circle',
                'color' => 'green',
            ]);
        }

        // Also notify PO/Logistics if blocking was cleared
        if ($schedule->blocking_stage) {
            $notifyRoles = [];
            if ($schedule->blocking_stage === 'BLOCK_PO_SEND') {
                $notifyRoles = ['po', 'buyer', 'admin', 'super_admin'];
            } elseif ($schedule->blocking_stage === 'BLOCK_WAREHOUSE_EXPORT') {
                $notifyRoles = ['warehouse', 'logistics', 'admin', 'super_admin'];
            }

            if (!empty($notifyRoles)) {
                $recipientUsers = User::whereHas('roles', function($q) use ($notifyRoles) {
                    $q->whereIn('slug', $notifyRoles);
                })->get();

                foreach ($recipientUsers as $ru) {
                    \App\Models\Notification::create([
                        'user_id' => $ru->id,
                        'type' => 'payment_alert',
                        'title' => 'Mở khóa đơn hàng',
                        'message' => "Đơn hàng {$sale->code} đã được mở khóa {$schedule->blocking_stage} sau khi Finance xác nhận thanh toán đợt {$schedule->milestone_name}.",
                        'link' => route('sales.show', $sale->id),
                        'icon' => 'fas fa-unlock',
                        'color' => 'indigo',
                    ]);
                }
            }
        }

        return back()->with('success', 'Xác nhận thanh toán đợt thành công!');
    }

    /**
     * Finance rejects payment for a milestone
     */
    public function rejectMilestonePayment(Request $request, Sale $sale, $index)
    {
        $user = auth()->user();
        $isAuthorized = $user->hasRole('accountant') || 
                        $user->hasRole('super_admin') || 
                        $user->hasRole('admin');

        if (!$isAuthorized) {
            return back()->with('error', 'Bạn không có quyền từ chối thanh toán.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $schedule = $sale->paymentSchedules()->skip($index)->first();
        if (!$schedule) {
            return back()->with('error', 'Mốc thanh toán không tồn tại.');
        }

        $oldStatus = $schedule->status;
        $reason = $request->input('reason');

        // Update PaymentEvidence status to rejected
        $evidence = $schedule->evidences()->where('status', 'pending')->latest()->first();
        if ($evidence) {
            $evidence->update([
                'status' => 'rejected',
                'notes' => $reason,
                'verified_by' => $user->name,
                'verified_at' => now(),
            ]);
        }

        // Revert schedule status to unpaid
        $schedule->status = 'unpaid';
        $schedule->proof_file_path = null; // Clear proof path so they can upload again
        $schedule->save();

        // Ghi log
        \App\Models\PaymentApprovalLog::create([
            'schedule_id' => $schedule->id,
            'sale_id' => $sale->id,
            'action' => 'finance_rejected',
            'old_value' => $oldStatus,
            'new_value' => 'unpaid',
            'reason' => $reason,
            'performed_by' => $user->id,
            'performed_at' => now(),
        ]);
        
        // Notify Salesperson
        if ($sale->user_id) {
            \App\Models\Notification::create([
                'user_id' => $sale->user_id,
                'type' => 'payment_alert',
                'title' => 'Chứng từ thanh toán bị từ chối',
                'message' => "Finance đã từ chối UNC cho đợt \"{$schedule->milestone_name}\" của đơn {$sale->code}. Lý do: {$reason}",
                'link' => route('sales.show', $sale->id),
                'icon' => 'fas fa-times-circle',
                'color' => 'red',
            ]);
        }

        return back()->with('success', 'Đã từ chối chứng từ thanh toán của đợt này.');
    }

    /**
     * Delete an ad-hoc manual payment milestone
     */
    public function deleteMilestone(Sale $sale, $index)
    {
        $user = auth()->user();
        $isAuthorized = $user->hasRole('accountant') || 
                        $user->hasRole('super_admin') || 
                        $user->hasRole('admin') || 
                        ($sale->user_id === $user->id);

        if (!$isAuthorized) {
            return back()->with('error', 'Bạn không có quyền xóa mốc thanh toán này.');
        }

        $schedule = $sale->paymentSchedules()->skip($index)->first();
        if (!$schedule) {
            return back()->with('error', 'Mốc thanh toán không tồn tại.');
        }

        if ($schedule->trigger_type !== 'MANUAL') {
            return back()->with('error', 'Chỉ có thể xóa các mốc thanh toán được thêm thủ công (MANUAL).');
        }

        if ($schedule->status === 'paid') {
            return back()->with('error', 'Không thể xóa mốc thanh toán đã hoàn thành.');
        }

        // Delete evidences and logs first
        $schedule->evidences()->delete();
        $schedule->logs()->delete();
        $schedule->delete();

        // Recalculate sale's paid_amount and debt
        $totalPaid = $sale->paymentSchedules()->where('status', 'paid')->sum('amount');
        $sale->paid_amount = $totalPaid;
        $sale->updateDebt();
        $sale->save();

        return back()->with('success', 'Đã xóa mốc thanh toán thủ công thành công.');
    }

    /**
     * Update BOM directly from sale detail page (show.blade.php)
     */
    public function updateBom(Request $request, Sale $sale)
    {
        if ($sale->hasPayment()) {
            return back()->with('error', 'Đơn hàng đã có thanh toán, không thể điều chỉnh BOM.');
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable',
            'items.*.product_name' => 'nullable|string|max:2000',
            'items.*.new_name' => 'nullable|string|max:2000',
            'items.*.new_code' => 'nullable|string|max:100',
            'items.*.new_unit' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required',
            'items.*.vat' => 'nullable',
            'items.*.warranty_months' => 'nullable|integer',
            'items.*.is_service' => 'nullable|boolean',
            'items.*.is_from_stock' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $isForeign = $sale->currency && !$sale->currency->is_base;
            $rate = $sale->exchange_rate ?: 1;

            $sale->items()->delete();

            $subtotal = 0;
            $vatAmount = 0;
            $costTotal = 0;

            foreach ($request->input('items') as $idx => $itemData) {
                if (empty($itemData['product_id']) && empty($itemData['product_name']) && empty($itemData['new_name'])) {
                    continue;
                }

                $qty = (float)$itemData['quantity'];
                $rawPrice = str_replace(['.', ','], ['', ''], (string)($itemData['price'] ?? 0));
                $price = (float)$rawPrice;
                $vatRate = isset($itemData['vat']) ? (float)$itemData['vat'] : 8;
                $effectiveVatRate = $vatRate < 0 ? 0 : $vatRate;

                $product = null;
                if (!empty($itemData['product_id']) && is_numeric($itemData['product_id'])) {
                    $product = \App\Models\Product::find($itemData['product_id']);
                }

                $rawProductName = trim($itemData['product_name'] ?? '');
                $newName = trim($itemData['new_name'] ?? '');
                $newCode = trim($itemData['new_code'] ?? '');

                // If product not found by numeric ID, check or create new product
                if (!$product && (($itemData['product_id'] ?? '') === 'new' || !empty($rawProductName) || !empty($newName) || !empty($newCode))) {
                    $searchCode = preg_replace('/^\[SP Mới\]\s*/u', '', $newCode ?: $rawProductName);
                    $searchName = preg_replace('/^\[SP Mới\]\s*/u', '', $newName ?: $rawProductName);

                    if (!empty($searchCode) || !empty($searchName)) {
                        $product = \App\Models\Product::where('code', $searchCode)
                            ->orWhere('name', $searchName)
                            ->first();

                        if (!$product) {
                            $product = \App\Models\Product::create([
                                'code' => $searchCode ?: ('SP-' . strtoupper(\Illuminate\Support\Str::random(6))),
                                'name' => $searchName ?: $searchCode,
                                'unit' => $itemData['new_unit'] ?? 'Cái',
                                'category' => 'A',
                                'price' => $price,
                                'warranty_months' => $itemData['warranty_months'] ?? 12,
                            ]);
                        }
                    }
                }

                $productName = $product ? $product->name : ($rawProductName ?: 'Sản phẩm ' . ($idx + 1));
                $unitCost = $product ? ($product->calculated_cost ?: ($product->cost ?: 0)) : 0;
                $lineSubtotal = $qty * $price;
                $lineVat = $lineSubtotal * ($effectiveVatRate / 100);

                \App\Models\SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product ? $product->id : null,
                    'product_name' => $productName,
                    'quantity' => $qty,
                    'price' => $price,
                    'cost_price' => $unitCost,
                    'total' => $lineSubtotal,
                    'cost_total' => $qty * $unitCost,
                    'vat' => $vatRate,
                    'vat_amount' => $lineVat,
                    'warranty_months' => $itemData['warranty_months'] ?? ($product->warranty_months ?? 12),
                    'warranty_start_date' => (!empty($itemData['warranty_months']) || ($product && $product->warranty_months)) ? $sale->date : null,
                    'is_service' => !empty($itemData['is_service']),
                    'is_from_stock' => !empty($itemData['is_from_stock']),
                ]);

                $subtotal += $lineSubtotal;
                $vatAmount += $lineVat;
                $costTotal += ($qty * $unitCost);
            }

            $discountRate = (float)($sale->discount ?: 0);
            $discountAmount = $subtotal * ($discountRate / 100);
            $total = $subtotal - $discountAmount + $vatAmount;
            $grossProfit = $subtotal - $discountAmount - $costTotal;
            $grossProfitMargin = $subtotal > 0 ? round(($grossProfit / $subtotal) * 100, 2) : 0;

            $sale->update([
                'subtotal' => $subtotal,
                'vat_amount' => $vatAmount,
                'total' => $total,
                'cost_total' => $costTotal,
                'gross_profit' => $grossProfit,
                'gross_profit_margin' => $grossProfitMargin,
            ]);

            $sale->updateDebt();

            DB::commit();
            return back()->with('success', 'Đã cập nhật BOM sản phẩm của đơn hàng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi khi cập nhật BOM: ' . $e->getMessage());
        }
    }

    /**
     * Add manual milestone / payment term directly from show page
     */
    public function addMilestone(Request $request, Sale $sale)
    {
        $request->validate([
            'milestone_name' => 'required|string|max:255',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'amount' => 'nullable|string',
            'blocking_stage' => 'nullable|string|in:BLOCK_PO_SEND,BLOCK_WAREHOUSE_EXPORT,NONE',
            'due_days' => 'nullable|integer|min:0',
            'required_docs' => 'nullable|string|max:255',
        ]);

        $percentage = (float)($request->input('percentage') ?: 0);
        $rawAmount = str_replace(['.', ','], ['', ''], (string)$request->input('amount'));
        $amount = (float)$rawAmount;

        if ($percentage > 0 && $amount <= 0) {
            $amount = round($sale->total * ($percentage / 100));
        } elseif ($amount > 0 && $percentage <= 0 && $sale->total > 0) {
            $percentage = round(($amount / $sale->total) * 100, 2);
        }

        $nextSort = ($sale->paymentSchedules()->max('sort_order') ?: 0) + 1;

        $schedule = \App\Models\SalePaymentSchedule::create([
            'sale_id' => $sale->id,
            'sort_order' => $nextSort,
            'milestone_name' => $request->input('milestone_name'),
            'percentage' => $percentage,
            'amount' => $amount,
            'trigger_type' => 'MANUAL',
            'blocking_stage' => $request->input('blocking_stage') ?: 'NONE',
            'due_base' => $request->input('due_base') ?: 'contract_date',
            'due_days' => (int)($request->input('due_days') ?: 0),
            'required_docs' => $request->input('required_docs') ?: 'UNC',
            'status' => 'unpaid',
        ]);

        return back()->with('success', 'Đã thêm điều khoản thanh toán "' . $schedule->milestone_name . '" thành công!');
    }

    /**
     * Add external expense directly from show page
     */
    public function addExpense(Request $request, Sale $sale)
    {
        $request->validate([
            'type' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'input_mode' => 'required|in:fixed,percent',
            'percent_value' => 'nullable|numeric|min:0',
            'amount' => 'nullable|string',
        ]);

        $rawAmount = str_replace(['.', ','], ['', ''], (string)$request->input('amount'));
        $amount = (float)$rawAmount;
        $percentValue = (float)$request->input('percent_value');

        if ($request->input('input_mode') === 'percent' && $percentValue > 0) {
            $amount = round($sale->subtotal * ($percentValue / 100));
        }

        \App\Models\SaleExpense::create([
            'sale_id' => $sale->id,
            'type' => $request->input('type'),
            'description' => $request->input('description'),
            'input_mode' => $request->input('input_mode'),
            'percent_value' => $percentValue,
            'amount' => $amount,
        ]);

        return back()->with('success', 'Đã thêm chi phí ngoài thành công!');
    }

    /**
     * Apply predefined payment template to sale milestones
     */
    public function applyPaymentTemplate(Request $request, Sale $sale)
    {
        $request->validate([
            'template_id' => 'required|exists:payment_templates,id',
        ]);

        $template = \App\Models\PaymentTemplate::with('items')->findOrFail($request->template_id);
        
        $hasPaid = $sale->paymentSchedules()->where('status', 'paid')->exists();
        if ($hasPaid) {
            return back()->with('error', 'Đơn hàng đã có đợt thanh toán hoàn tất, không thể thay đổi toàn bộ mẫu lộ trình.');
        }

        $sale->paymentSchedules()->delete();

        foreach ($template->items as $idx => $item) {
            $percentage = (float) $item->percentage;
            $amount = round($sale->total * ($percentage / 100));

            \App\Models\SalePaymentSchedule::create([
                'sale_id' => $sale->id,
                'template_id' => $template->id,
                'template_version' => $template->version ?? 1,
                'sort_order' => $idx + 1,
                'milestone_name' => $item->milestone_name,
                'percentage' => $percentage,
                'amount' => $amount,
                'trigger_type' => $item->trigger_type ?? 'MANUAL',
                'blocking_stage' => $item->blocking_stage ?? 'NONE',
                'due_base' => $item->due_base ?: 'contract_date',
                'due_days' => (int) ($item->due_days ?? 0),
                'required_docs' => $item->required_docs ?? 'UNC',
                'status' => 'unpaid',
            ]);
        }

        $sale->update([
            'payment_term_type' => 'template_' . $template->id,
            'payment_term' => $template->name . ($template->description ? ': ' . $template->description : ''),
        ]);

        return back()->with('success', 'Đã áp dụng mẫu điều khoản thanh toán "' . $template->name . '" thành công!');
    }

    /**
     * Save/Sync all milestones directly from show page
     */
    public function syncMilestones(Request $request, Sale $sale)
    {
        if ($sale->pl_status === 'approved') {
            return back()->with('error', 'Đơn hàng đã được duyệt P&L, không thể thay đổi điều khoản thanh toán.');
        }

        $hasPaid = $sale->paymentSchedules()->where('status', 'paid')->exists();
        if ($hasPaid) {
            return back()->with('error', 'Đơn hàng đã có đợt thanh toán hoàn tất, không thể thay đổi toàn bộ mẫu lộ trình.');
        }

        $validated = $request->validate([
            'template_id' => 'nullable|string',
            'milestones' => 'required|array|min:1',
            'milestones.*.milestone_name' => 'required|string|max:255',
            'milestones.*.percentage' => 'nullable|numeric|min:0|max:100',
            'milestones.*.amount' => 'nullable',
            'milestones.*.timing' => 'nullable|string',
            'milestones.*.required_before' => 'nullable|string',
            'milestones.*.is_blocking' => 'nullable|string',
            'milestones.*.due_days' => 'nullable|integer|min:0',
            'milestones.*.required_docs' => 'nullable|string',
            'milestones.*.status' => 'nullable|string',
            'milestones.*.confirmed_by' => 'nullable|string',
            'milestones.*.confirmed_at' => 'nullable|string',
            'milestones.*.proof_file_path' => 'nullable|string',
            'milestones.*.bod_approval_file_path' => 'nullable|string',
            'milestones.*.delegated_to_id' => 'nullable|integer',
        ]);

        $milestones = $validated['milestones'];
        
        // Recalculate amounts if needed
        foreach ($milestones as &$ms) {
            $pct = (float)($ms['percentage'] ?? 0);
            if ($pct > 0) {
                $ms['amount'] = round($sale->total * ($pct / 100));
            } else {
                $rawAmount = str_replace(['.', ',', ' '], '', (string)($ms['amount'] ?? '0'));
                $ms['amount'] = (float)$rawAmount;
            }
        }
        unset($ms);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $sale->syncPaymentSchedules($milestones);

            $templateId = $request->input('template_id');
            if ($templateId && str_starts_with($templateId, 'template_')) {
                $rawId = str_replace('template_', '', $templateId);
                $tpl = \App\Models\PaymentTemplate::find($rawId);
                if ($tpl) {
                    $sale->update([
                        'payment_term_type' => 'template_' . $tpl->id,
                        'payment_term' => $tpl->name . ($tpl->description ? ': ' . $tpl->description : ''),
                    ]);
                }
            } elseif ($templateId === 'customer_default') {
                $sale->update(['payment_term_type' => 'customer_default']);
            } else {
                $sale->update(['payment_term_type' => 'custom']);
            }

            \Illuminate\Support\Facades\DB::commit();
            return back()->with('success', 'Đã lưu và áp dụng điều khoản thanh toán thành công!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Add default expenses to sale
     */
    public function addDefaultExpenses(Sale $sale)
    {
        $defaults = \App\Models\SaleExpense::defaultExpenses();
        $added = 0;

        foreach ($defaults as $def) {
            $exists = $sale->expenses()->where('type', $def['type'])->exists();
            if (!$exists) {
                $sale->expenses()->create([
                    'type' => $def['type'],
                    'input_mode' => $def['input_mode'] ?? 'fixed',
                    'percent_value' => $def['percent_value'] ?? null,
                    'amount' => $def['amount'] ?? 0,
                    'description' => $def['description'] ?? '',
                ]);
                $added++;
            }
        }

        $sale->load('expenses');
        $this->syncOrderExpensesToPnlItems($sale);
        $sale->calculateMargin();
        $sale->save();

        return back()->with('success', "Đã thêm {$added} chi phí mặc định vào đơn hàng thành công!");
    }

    /**
     * Update/sync all expenses directly from show page
     */
    public function syncExpenses(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'expenses' => 'nullable|array',
            'expenses.*.type' => 'required|string|max:255',
            'expenses.*.input_mode' => 'required|in:fixed,percent',
            'expenses.*.percent_value' => 'nullable|numeric|min:0',
            'expenses.*.amount' => 'nullable|numeric|min:0',
            'expenses.*.description' => 'nullable|string|max:500',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $oldExpenseTypes = $sale->expenses()->pluck('type', 'id')->toArray();
            $sale->expenses()->delete();
            $newExpensesMap = [];

            if (!empty($validated['expenses'])) {
                $uniqueExpenses = collect($validated['expenses'])
                    ->filter(fn($e) => !empty(trim($e['type'] ?? '')))
                    ->unique(fn($e) => trim($e['type']))
                    ->values();

                foreach ($uniqueExpenses as $expense) {
                    $type = trim($expense['type']);
                    $inputMode = $expense['input_mode'] ?? 'fixed';
                    $amount = $inputMode === 'percent'
                        ? 0
                        : round(floatval($expense['amount'] ?? 0), 2);
                    $percentValue = $inputMode === 'percent'
                        ? round(floatval($expense['percent_value'] ?? 0), 2)
                        : null;

                    $newExp = \App\Models\SaleExpense::create([
                        'sale_id' => $sale->id,
                        'type' => $type,
                        'input_mode' => $inputMode,
                        'percent_value' => $percentValue,
                        'description' => $expense['description'] ?? '',
                        'amount' => $amount,
                    ]);

                    $oldId = array_search($type, $oldExpenseTypes);
                    if ($oldId !== false) {
                        $newExpensesMap[$oldId] = $newExp->id;
                        unset($oldExpenseTypes[$oldId]);
                    }
                }
            }

            // Sync order expenses to P&L items & calculate margin
            $sale->load('expenses', 'items.product');
            $this->syncOrderExpensesToPnlItems($sale);
            $sale->calculateMargin();
            $sale->save();

            \Illuminate\Support\Facades\DB::commit();
            return back()->with('success', 'Đã lưu và cập nhật toàn bộ chi phí đơn hàng thành công!');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Có lỗi khi cập nhật chi phí: ' . $e->getMessage());
        }
    }

    /**
     * Delete an expense from sale
     */
    public function deleteExpense(Sale $sale, \App\Models\SaleExpense $expense)
    {
        if ((int)$expense->sale_id !== (int)$sale->id) {
            abort(403);
        }

        $expense->delete();
        $sale->load('expenses');
        $this->syncOrderExpensesToPnlItems($sale);
        $sale->calculateMargin();
        $sale->save();

        return back()->with('success', 'Đã xóa chi phí thành công!');
    }

    /**
     * BOD approves exception for a specific milestone
     */
    public function approveMilestoneException(Request $request, Sale $sale, $index)
    {
        $user = auth()->user();
        $schedule = $sale->paymentSchedules()->skip($index)->first();
        if (!$schedule) {
            return back()->with('error', 'Mốc thanh toán không tồn tại.');
        }

        $isAuthorized = $user->hasRole('director') || 
                        $user->hasRole('super_admin') || 
                        $user->hasRole('admin') || 
                        ($schedule->delegated_to_id === $user->id) ||
                        ($sale->payment_exception_delegated_to === $user->id) ||
                        ($sale->user_id === $user->id) ||
                        $user->hasRole('sales');

        if (!$isAuthorized) {
            return back()->with('error', 'Bạn không có quyền duyệt ngoại lệ thanh toán.');
        }

        $request->validate([
            'exception_reason' => 'nullable|string|max:1000',
            'reason' => 'nullable|string|max:1000',
            'bod_approval_file' => 'nullable|file|max:20480',
            'bod_approval_files' => 'nullable|array',
            'bod_approval_files.*' => 'file|max:20480',
        ]);

        $paths = [];
        if ($request->hasFile('bod_approval_file')) {
            $paths[] = $request->file('bod_approval_file')->store('milestone-exceptions/' . $sale->id, 'public');
        }
        if ($request->hasFile('bod_approval_files')) {
            foreach ($request->file('bod_approval_files') as $file) {
                $paths[] = $file->store('milestone-exceptions/' . $sale->id, 'public');
            }
        }

        $note = trim((string)($request->input('exception_reason') ?? $request->input('reason') ?? ''));
        if (empty($paths) && empty($note)) {
            return back()->with('error', 'Vui lòng nhập ghi chú lý do duyệt ngoại lệ hoặc đính kèm tệp phê duyệt.');
        }

        $oldStatus = $schedule->status;
        $schedule->status = 'exception_approved';
        $schedule->bod_approval_file_path = empty($paths) ? null : (count($paths) === 1 ? $paths[0] : json_encode($paths));
        $schedule->save();

        // Ghi log
        $logReason = !empty($note)
            ? 'Duyệt ngoại lệ đợt "' . $schedule->milestone_name . '". Lý do: ' . $note . ' (Người thực hiện: ' . $user->name . ')'
            : 'Đã duyệt ngoại lệ cho đợt: ' . $schedule->milestone_name . '. Người thực hiện: ' . $user->name;

        \App\Models\PaymentApprovalLog::create([
            'schedule_id' => $schedule->id,
            'sale_id' => $sale->id,
            'action' => 'bod_exception_approved',
            'old_value' => $oldStatus,
            'new_value' => 'exception_approved',
            'reason' => $logReason,
            'attachment_path' => empty($paths) ? null : (count($paths) === 1 ? $paths[0] : json_encode($paths)),
            'performed_by' => $user->id,
            'performed_at' => now(),
        ]);

            // Notify Sales
            $salesPerson = $sale->user;
            if ($salesPerson) {
                \App\Models\Notification::create([
                    'user_id' => $salesPerson->id,
                    'type' => 'payment_alert',
                    'title' => 'Đợt thanh toán được duyệt ngoại lệ',
                    'message' => "Đợt " . ($schedule->milestone_name) . " của đơn {$sale->code} đã được duyệt ngoại lệ.",
                    'link' => route('sales.show', $sale->id),
                    'icon' => 'fas fa-unlock-alt',
                    'color' => 'blue',
                ]);
            }

            // Also notify PO/Logistics
            if ($schedule->blocking_stage) {
                $notifyRoles = [];
                if ($schedule->blocking_stage === 'BLOCK_PO_SEND') {
                    $notifyRoles = ['po', 'buyer', 'admin', 'super_admin'];
                } elseif ($schedule->blocking_stage === 'BLOCK_WAREHOUSE_EXPORT') {
                    $notifyRoles = ['warehouse', 'logistics', 'admin', 'super_admin'];
                }

                if (!empty($notifyRoles)) {
                    $recipientUsers = User::whereHas('roles', function($q) use ($notifyRoles) {
                        $q->whereIn('slug', $notifyRoles);
                    })->get();

                    foreach ($recipientUsers as $ru) {
                        \App\Models\Notification::create([
                            'user_id' => $ru->id,
                            'type' => 'payment_alert',
                            'title' => 'Ngoại lệ được phê duyệt',
                            'message' => "Đơn hàng {$sale->code} đã được mở khóa ngoại lệ cho giai đoạn {$schedule->blocking_stage}.",
                            'link' => route('sales.show', $sale->id),
                            'icon' => 'fas fa-shield-alt',
                            'color' => 'purple',
                        ]);
                    }
                }
            }

            return back()->with('success', 'Đã duyệt ngoại lệ thanh toán đợt thành công!');
    }

    /**
     * BOD delegates exception approval of the whole sale to a specific user
     */
    public function delegateSaleException(Request $request, Sale $sale)
    {
        $user = auth()->user();
        if (!$user->hasRole('director') && !$user->hasRole('super_admin') && !$user->hasRole('admin')) {
            return back()->with('error', 'Chỉ BOD mới có quyền ủy quyền duyệt ngoại lệ.');
        }

        $request->validate([
            'delegate_user_id' => 'nullable|exists:users,id',
        ]);

        $delegateUserId = $request->input('delegate_user_id');
        $sale->payment_exception_delegated_to = $delegateUserId;
        $sale->save();

        if ($delegateUserId) {
            $delegateUser = User::find($delegateUserId);
            \App\Models\PaymentApprovalLog::create([
                'sale_id' => $sale->id,
                'action' => 'delegated',
                'new_value' => $delegateUser->name,
                'reason' => 'BOD delegated exception approval of this sale to ' . $delegateUser->name,
                'performed_by' => $user->id,
                'performed_at' => now(),
            ]);

            \App\Models\Notification::create([
                'user_id' => $delegateUserId,
                'type' => 'payment_alert',
                'title' => 'Bạn được ủy quyền duyệt ngoại lệ',
                'message' => "BOD đã ủy quyền cho bạn duyệt ngoại lệ thanh toán cho đơn hàng {$sale->code}.",
                'link' => route('sales.show', $sale->id),
                'icon' => 'fas fa-user-shield',
                'color' => 'indigo',
            ]);

            return back()->with('success', 'Đã ủy quyền duyệt ngoại lệ đơn hàng cho ' . $delegateUser->name . ' thành công!');
        } else {
            \App\Models\PaymentApprovalLog::create([
                'sale_id' => $sale->id,
                'action' => 'delegation_revoked',
                'reason' => 'BOD revoked exception approval delegation of this sale.',
                'performed_by' => $user->id,
                'performed_at' => now(),
            ]);

            return back()->with('success', 'Đã hủy ủy quyền duyệt ngoại lệ đơn hàng.');
        }
    }

    /**
     * BOD delegates exception approval of a specific milestone to a specific user
     */
    public function delegateMilestoneException(Request $request, Sale $sale, $index)
    {
        $user = auth()->user();
        if (!$user->hasRole('director') && !$user->hasRole('super_admin') && !$user->hasRole('admin')) {
            return back()->with('error', 'Chỉ BOD mới có quyền ủy quyền duyệt ngoại lệ.');
        }

        $request->validate([
            'delegate_user_id' => 'nullable|exists:users,id',
        ]);

        $schedule = $sale->paymentSchedules()->skip($index)->first();
        if (!$schedule) {
            return back()->with('error', 'Mốc thanh toán không tồn tại.');
        }

        $delegateUserId = $request->input('delegate_user_id');
        $schedule->delegated_to_id = $delegateUserId;
        $schedule->save();

        if ($delegateUserId) {
            $delegateUser = User::find($delegateUserId);
            \App\Models\PaymentApprovalLog::create([
                'schedule_id' => $schedule->id,
                'sale_id' => $sale->id,
                'action' => 'delegated',
                'new_value' => $delegateUser->name,
                'reason' => 'BOD delegated exception approval of milestone ' . $schedule->milestone_name . ' to ' . $delegateUser->name,
                'performed_by' => $user->id,
                'performed_at' => now(),
            ]);

            \App\Models\Notification::create([
                'user_id' => $delegateUserId,
                'type' => 'payment_alert',
                'title' => 'Bạn được ủy quyền duyệt ngoại lệ đợt',
                'message' => "BOD đã ủy quyền cho bạn duyệt ngoại lệ đợt {$schedule->milestone_name} cho đơn hàng {$sale->code}.",
                'link' => route('sales.show', $sale->id),
                'icon' => 'fas fa-user-shield',
                'color' => 'indigo',
            ]);

            return back()->with('success', 'Đã ủy quyền duyệt ngoại lệ đợt cho ' . $delegateUser->name . ' thành công!');
        } else {
            \App\Models\PaymentApprovalLog::create([
                'schedule_id' => $schedule->id,
                'sale_id' => $sale->id,
                'action' => 'delegation_revoked',
                'reason' => 'BOD revoked exception approval delegation of milestone ' . $schedule->milestone_name . '.',
                'performed_by' => $user->id,
                'performed_at' => now(),
            ]);

            return back()->with('success', 'Đã hủy ủy quyền duyệt ngoại lệ đợt.');
        }
    }

    /**
     * Update delivery date
     */
    public function updateDeliveryDate(Request $request, Sale $sale)
    {
        $request->validate([
            'delivery_date' => 'required|date',
        ]);

        $date = $request->input('delivery_date');
        $sale->update(['delivery_date' => $date]);
        $sale->updateMilestoneDueDates();
        
        $sale->checkAndAutoRecordCompletion();
        if ($sale->status === 'approved') {
            $sale->update(['status' => 'shipping']);
        }

        // Also update any completed export voucher linked to this sale
        $completedExport = \App\Models\Export::where('reference_id', $sale->id)
            ->where('reference_type', 'sale')
            ->where('status', 'completed')
            ->latest()
            ->first();
        if ($completedExport) {
            $completedExport->update(['date' => $date]);
        }

        return back()->with('success', 'Đã cập nhật ngày giao hàng thành công và tính lại các hạn thanh toán.');
    }

    public function createPartialExport(Request $request, Sale $sale)
    {
        $this->authorize('update', $sale);

        if ($sale->status !== 'approved' && $sale->status !== 'shipping') {
            return back()->with('error', 'Đơn hàng bán chưa được duyệt. Không thể tạo yêu cầu xuất kho.');
        }

        $hasConfirmedInvoice = $sale->invoiceRequests()->where('status', 'official_issued')->exists();
        if (!$hasConfirmedInvoice) {
            return back()->with('error', 'Cần Sales xác nhận hóa đơn trước khi tạo yêu cầu xuất kho.');
        }

        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:0',
        ]);

        $itemsToExport = [];
        $totalQty = 0;

        DB::beginTransaction();
        try {
            foreach ($request->items as $itemData) {
                $productId = $itemData['product_id'];
                $qty = (int)$itemData['qty'];

                if ($qty <= 0) continue;

                // Tính số lượng tối đa có thể xuất cho sản phẩm này
                $totalOrdered = \App\Models\SaleItem::where('sale_id', $sale->id)
                    ->where('product_id', $productId)
                    ->sum('quantity');

                $totalExported = \App\Models\ExportItem::whereHas('export', function ($q) use ($sale) {
                        $q->where('reference_type', 'sale')
                          ->where('reference_id', $sale->id)
                          ->where('status', '!=', 'cancelled');
                    })
                    ->where('product_id', $productId)
                    ->sum('quantity');

                $remaining = $totalOrdered - $totalExported;

                if ($qty > $remaining) {
                    throw new \Exception("Số lượng xuất cho sản phẩm ID {$productId} ({$qty}) vượt quá số lượng còn lại có thể xuất ({$remaining}).");
                }

                $salespersonName = $sale->employee?->name ?? $sale->user?->name;
                $saleItem = \App\Models\SaleItem::where('sale_id', $sale->id)
                    ->where('product_id', $productId)
                    ->first();

                $isFromStock = $saleItem ? ($saleItem->is_from_stock || (!$sale->isProjectOrder() && $sale->type === 'retail')) : false;

                if ($isFromStock) {
                    $heldBySales = \App\Models\ProductItem::where('product_id', $productId)
                        ->where('warehouse_id', $request->warehouse_id)
                        ->where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
                        ->where('borrower', $salespersonName)
                        ->sum('quantity');

                    if ($qty > $heldBySales) {
                        $productName = $saleItem ? $saleItem->product_name : "ID: {$productId}";
                        throw new \Exception("Sản phẩm '{$productName}' là hàng bán có sẵn trong kho, nhưng Sales ({$salespersonName}) chỉ đang giữ/mượn {$heldBySales}/{$qty} cái trong kho này. Vui lòng tạo 'Yêu cầu mượn hàng' để giữ hàng trước khi xuất.");
                    }
                } else {
                    $availableForSales = \App\Models\ProductItem::where('product_id', $productId)
                        ->where('warehouse_id', $request->warehouse_id)
                        ->where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
                        ->where(function ($q) use ($salespersonName) {
                            $q->whereNull('borrower')->orWhere('borrower', '');
                            if ($salespersonName) {
                                $q->orWhere('borrower', $salespersonName);
                            }
                        })
                        ->sum('quantity');

                    if ($qty > $availableForSales) {
                        $productName = $saleItem ? $saleItem->product_name : "ID: {$productId}";
                        $heldByOthersCount = \App\Models\ProductItem::where('product_id', $productId)
                            ->where('warehouse_id', $request->warehouse_id)
                            ->where('status', \App\Models\ProductItem::STATUS_IN_STOCK)
                            ->whereNotNull('borrower')
                            ->where('borrower', '!=', '')
                            ->when($salespersonName, fn($q) => $q->where('borrower', '!=', $salespersonName))
                            ->sum('quantity');

                        $msg = "Số lượng xuất cho sản phẩm '{$productName}' ({$qty}) vượt quá số lượng khả dụng trong kho được chọn ({$availableForSales}).";
                        if ($heldByOthersCount > 0) {
                            $msg .= " (Hiện có {$heldByOthersCount} sản phẩm đang được Sales khác giữ. Vui lòng tạo yêu cầu mượn hàng trước nếu cần xuất hàng này).";
                        }
                        throw new \Exception($msg);
                    }
                }

                $itemsToExport[] = [
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_price' => $saleItem ? $saleItem->price : 0,
                    'total' => $saleItem ? ($saleItem->price * $qty) : 0,
                    'is_liquidation' => $saleItem ? $saleItem->is_liquidation : false,
                    'product_name' => $saleItem ? $saleItem->product_name : '',
                ];

                $totalQty += $qty;
            }

            if (empty($itemsToExport)) {
                return back()->with('error', 'Vui lòng chọn số lượng xuất lớn hơn 0 cho ít nhất một sản phẩm.');
            }

            // Tạo mã code cho phiếu xuất
            $dateStr = date('Ymd');
            $prefix = 'EXP-' . $dateStr . '-';
            $lastExport = \App\Models\Export::where('code', 'like', $prefix . '%')
                ->orderBy('code', 'desc')
                ->first();
            if ($lastExport) {
                $parts = explode('-', $lastExport->code);
                $lastSeq = (int) end($parts);
                $nextSeq = $lastSeq + 1;
            } else {
                $nextSeq = 1;
            }
            $exportCode = $prefix . sprintf('%04d', $nextSeq);

            $export = \App\Models\Export::create([
                'code' => $exportCode,
                'warehouse_id' => $request->warehouse_id,
                'project_id' => $sale->project_id,
                'customer_id' => $sale->customer_id,
                'date' => now(),
                'employee_id' => auth()->id(),
                'total_qty' => $totalQty,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'note' => $request->note ?? "Yêu cầu xuất kho (một phần) từ đơn hàng {$sale->code}",
                'status' => 'draft',
            ]);

            foreach ($itemsToExport as $item) {
                \App\Models\ExportItem::create([
                    'export_id' => $export->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'requested_quantity' => $item['quantity'],
                    'is_liquidation' => $item['is_liquidation'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                    'comments' => "Yêu cầu xuất cho sản phẩm {$item['product_name']} (Số lượng: {$item['quantity']})",
                ]);
            }

            DB::commit();

            $export->update(['status' => 'pending_admin']);

            // Thông báo cho Admin/Sales Manager
            $admins = \App\Models\User::whereHas('roles', function($q) {
                $q->whereIn('slug', ['super_admin', 'admin', 'sales_manager']);
            })->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'export_request',
                    'title' => 'Yêu cầu xuất kho mới',
                    'message' => auth()->user()->name . " đã gửi yêu cầu xuất kho {$export->code} chờ duyệt.",
                    'link' => route('exports.show', $export->id),
                    'icon' => 'fas fa-file-export',
                    'color' => 'blue',
                ]);
            }

            return back()->with('success', "Đã tạo yêu cầu xuất kho {$export->code} và gửi duyệt Admin thành công!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi tạo yêu cầu xuất: ' . $e->getMessage());
        }
    }

    private function calculateTimeline($requested, $ordered, $received, $poItems, $sale = null, $prCreatedAt = null)
    {
        $statusWeights = [
            'draft' => 0,
            'pending_approval' => 1,
            'approved' => 2,
            'sent' => 3,
            'confirmed' => 4,
            'shipping' => 5,
            'partial_received' => 6,
            'received' => 7,
        ];

        $representativePo = null;
        $maxStatusWeight = -1;

        foreach ($poItems as $poItem) {
            $po = $poItem instanceof \App\Models\PurchaseOrder ? $poItem : ($poItem->purchaseOrder ?? null);
            if ($po) {
                $weight = $statusWeights[$po->status] ?? 0;
                if ($weight > $maxStatusWeight) {
                    $maxStatusWeight = $weight;
                    $representativePo = $po;
                }
            }
        }

        $timeline = [];

        // 1. PR
        $timeline['pr'] = [
            'label' => 'Yêu cầu (PR)',
            'status' => 'completed',
            'date' => $prCreatedAt ? \Carbon\Carbon::parse($prCreatedAt)->format('d/m/Y') : null,
            'details' => $prCreatedAt ? 'Yêu cầu mua hàng (PR) đã được tạo.' : 'Đơn hàng mua trực tiếp (không qua PR).'
        ];

        // 2. PO
        $poStatus = 'pending';
        $poDate = null;
        $poDetails = 'Chưa đặt hàng (PO).';
        if ($ordered > 0) {
            $poDate = $representativePo ? ($representativePo->order_date ? $representativePo->order_date->format('d/m/Y') : null) : null;
            if ($ordered >= $requested) {
                $poStatus = 'completed';
                $poDetails = "Đã đặt đủ số lượng ({$ordered}/{$requested}).";
            } else {
                $poStatus = 'active';
                $poDetails = "Đang đặt hàng ({$ordered}/{$requested}).";
            }
        }
        $timeline['po'] = [
            'label' => 'Đặt hàng (PO)',
            'status' => $poStatus,
            'date' => $poDate,
            'details' => $poDetails
        ];

        // 3. Vendor Confirm
        $vcStatus = 'pending';
        $vcDate = null;
        $vcDetails = 'Nhà cung cấp chưa xác nhận.';
        if ($representativePo) {
            $confirmedAt = $representativePo->confirmed_at;
            $poStatusVal = $representativePo->status;
            if (in_array($poStatusVal, ['confirmed', 'shipping', 'partial_received', 'received']) || $confirmedAt) {
                $vcStatus = 'completed';
                $vcDate = $confirmedAt ? $confirmedAt->format('d/m/Y') : null;
                $vcDetails = 'Nhà cung cấp đã xác nhận đơn hàng.';
            } elseif ($poStatusVal === 'sent') {
                $vcStatus = 'active';
                $vcDetails = 'Đang chờ nhà cung cấp xác nhận.';
            }
        }
        $timeline['vendor_confirm'] = [
            'label' => 'Vendor Confirm',
            'status' => $vcStatus,
            'date' => $vcDate,
            'details' => $vcDetails
        ];

        // 4. Sản xuất
        $prodStatus = 'pending';
        $prodDate = null;
        $prodDetails = 'Chưa bắt đầu sản xuất.';
        if ($vcStatus === 'completed' && $representativePo) {
            $releaseDate = $representativePo->manufacturer_release_date;
            $poStatusVal = $representativePo->status;
            if (in_array($poStatusVal, ['shipping', 'partial_received', 'received'])) {
                $prodStatus = 'completed';
                $prodDate = $releaseDate ? $releaseDate->format('d/m/Y') : null;
                $prodDetails = 'Đã sản xuất xong.';
            } else {
                $prodStatus = 'active';
                $prodDate = $releaseDate ? $releaseDate->format('d/m/Y') : null;
                $prodDetails = $releaseDate ? 'Dự kiến sản xuất xong ngày ' . $releaseDate->format('d/m/Y') . '.' : 'Đang sản xuất.';
            }
        }
        $timeline['production'] = [
            'label' => 'Sản xuất',
            'status' => $prodStatus,
            'date' => $prodDate,
            'details' => $prodDetails
        ];

        // 5. Xuất kho hãng
        $mfgExportStatus = 'pending';
        $mfgExportDate = null;
        $mfgExportDetails = 'Hãng chưa xuất kho.';
        if ($prodStatus === 'completed' && $representativePo) {
            $releaseDate = $representativePo->manufacturer_release_date;
            $poStatusVal = $representativePo->status;
            if (in_array($poStatusVal, ['shipping', 'partial_received', 'received']) || ($releaseDate && $releaseDate->isPast())) {
                $mfgExportStatus = 'completed';
                $mfgExportDate = $releaseDate ? $releaseDate->format('d/m/Y') : null;
                $mfgExportDetails = 'Hãng đã xuất kho.';
            } else {
                $mfgExportStatus = 'active';
                $mfgExportDetails = 'Chờ hãng xuất kho.';
            }
        }
        $timeline['mfg_export'] = [
            'label' => 'Xuất kho hãng',
            'status' => $mfgExportStatus,
            'date' => $mfgExportDate,
            'details' => $mfgExportDetails
        ];

        // 6. Đang vận chuyển
        $transStatus = 'pending';
        $transDate = null;
        $transDetails = 'Chưa vận chuyển.';
        if ($mfgExportStatus === 'completed' && $representativePo) {
            $arrivalDate = $representativePo->expected_arrival_date;
            $poStatusVal = $representativePo->status;
            if (in_array($poStatusVal, ['shipping', 'partial_received'])) {
                $transStatus = 'active';
                $transDate = $arrivalDate ? $arrivalDate->format('d/m/Y') : null;
                $transDetails = $arrivalDate ? 'Dự kiến về VN ngày ' . $arrivalDate->format('d/m/Y') . '.' : 'Đang trên đường vận chuyển.';
            } elseif ($poStatusVal === 'received') {
                $transStatus = 'completed';
                $transDetails = 'Vận chuyển đã hoàn tất.';
            } else {
                $transStatus = 'active';
                $transDetails = 'Đang xử lý vận chuyển.';
            }
        }
        $timeline['transit'] = [
            'label' => 'Đang vận chuyển',
            'status' => $transStatus,
            'date' => $transDate,
            'details' => $transDetails
        ];

        // 7. Đã về VN
        $vnStatus = 'pending';
        $vnDate = null;
        $vnDetails = 'Chưa về VN.';
        if ($representativePo) {
            $arrivalDate = $representativePo->expected_arrival_date;
            $actualDelivery = $representativePo->actual_delivery;
            $poStatusVal = $representativePo->status;

            if ($actualDelivery || in_array($poStatusVal, ['partial_received', 'received'])) {
                $vnStatus = 'completed';
                $vnDate = $actualDelivery ? $actualDelivery->format('d/m/Y') : ($arrivalDate ? $arrivalDate->format('d/m/Y') : null);
                $vnDetails = 'Hàng đã về Việt Nam.';
            } elseif ($transStatus === 'active') {
                if ($arrivalDate && $arrivalDate->isPast()) {
                    $vnStatus = 'active';
                    $vnDetails = 'Đang làm thủ tục thông quan tại VN.';
                }
            }
        }
        $timeline['arrived_vn'] = [
            'label' => 'Đã về VN',
            'status' => $vnStatus,
            'date' => $vnDate,
            'details' => $vnDetails
        ];

        // 8. Đã nhập kho
        $whStatus = 'pending';
        $whDate = null;
        $whDetails = 'Chưa nhập kho.';
        if ($received > 0) {
            $whDate = $representativePo ? ($representativePo->actual_delivery ? $representativePo->actual_delivery->format('d/m/Y') : null) : null;
            if ($received >= $requested) {
                $whStatus = 'completed';
                $whDetails = "Đã nhập kho đủ ({$received}/{$requested}).";
            } else {
                $whStatus = 'active';
                $whDetails = "Đã nhập kho một phần ({$received}/{$requested}).";
            }
        } elseif ($vnStatus === 'completed') {
            $whStatus = 'active';
            $whDetails = 'Đang chờ thủ tục nhập kho.';
        }
        $timeline['warehouse_received'] = [
            'label' => 'Đã nhập kho',
            'status' => $whStatus,
            'date' => $whDate,
            'details' => $whDetails
        ];

        // 9. Đã giao Sales
        $saleStatus = 'pending';
        $saleDate = null;
        $saleDetails = 'Chưa bàn giao.';
        if ($sale) {
            $saleStatusVal = $sale->status;
            $deliveryDate = $sale->delivery_date;
            if ($saleStatusVal === 'completed' || ($deliveryDate && $deliveryDate->isPast())) {
                $saleStatus = 'completed';
                $saleDate = $deliveryDate ? $deliveryDate->format('d/m/Y') : null;
                $saleDetails = 'Đã bàn giao cho Sales/Khách hàng.';
            } elseif ($whStatus === 'completed' || $saleStatusVal === 'shipping') {
                $saleStatus = 'active';
                $saleDetails = 'Đang chuẩn bị bàn giao.';
            }
        }
        $timeline['delivered_sales'] = [
            'label' => 'Đã giao Sales',
            'status' => $saleStatus,
            'date' => $saleDate,
            'details' => $saleDetails
        ];

        return $timeline;
    }

    /**
     * A sales header can point to one project while individual items point to
     * others.  This is allowed, but must be an explicit user decision because
     * it affects Partner/EU and downstream PO grouping.
     */
    private function hasMultipleProjects(array $products, mixed $headerProjectId): bool
    {
        $projectIds = collect($products)
            ->map(fn (array $product) => $product['project_id'] ?? $headerProjectId)
            ->filter(fn ($projectId) => filled($projectId))
            ->map(fn ($projectId) => (int) $projectId)
            ->unique();

        return $projectIds->count() > 1;
    }

    /**
     * Persist an expiry date per serial while retaining the legacy shared
     * expiry date as a fallback for existing order requests.
     */
    private function buildSerialExpiryDates(mixed $serials, mixed $expiryDates, mixed $sharedExpiry): array
    {
        $serials = is_array($serials)
            ? $serials
            : (blank($serials) ? [] : preg_split('/\s*,\s*/', (string) $serials));
        $expiryDates = is_array($expiryDates) ? $expiryDates : [];
        $result = [];

        foreach ($serials as $index => $serial) {
            $serial = trim((string) $serial);
            if ($serial === '') {
                continue;
            }

            $result[$serial] = filled($expiryDates[$index] ?? null)
                ? $expiryDates[$index]
                : $sharedExpiry;
        }

        return $result;
    }

    /** Build trusted deal flags. Trade up is inherited from its registered project. */
    private function resolveDealFlags(array $validated, Request $request, ?int $projectId = null): array
    {
        $projectId = $projectId ?? ($validated['project_id'] ?? null);
        $project = !empty($projectId)
            ? Project::select('id', 'deal_type')->find($projectId)
            : null;
        $matrix = $validated['trade_up_matrix'];
        $isProjectTradeUp = $project?->deal_type === 'trade_up';

        if ($isProjectTradeUp && $matrix === 'none') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'trade_up_matrix' => 'Đơn tạo từ ĐKDA Trade up phải chọn đúng hoặc không đúng matrix.',
            ]);
        }

        return [
            'is_license_vnet' => $request->boolean('is_license_vnet'),
            'trade_up_matrix' => $matrix,
            'ohf_cost_added' => $matrix === 'incorrect' && $request->boolean('ohf_cost_added'),
        ];
    }

    /** Trade up requires one serial per hardware item before an order request can be sent. */
    private function validateTradeUpOrderRequestSerials(Sale $sale, array $items, bool $isDraft): void
    {
        if ($isDraft || $sale->trade_up_matrix === 'none') {
            return;
        }

        foreach ($items as $index => $item) {
            if (strtoupper((string) ($item['type'] ?? '')) !== 'HW') {
                continue;
            }

            $serials = is_array($item['serial_number'] ?? null)
                ? $item['serial_number']
                : preg_split('/\s*,\s*/', (string) ($item['serial_number'] ?? ''));
            $count = count(array_filter($serials, fn ($serial) => trim((string) $serial) !== ''));
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($quantity < 1 || $count !== $quantity) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "order_request_items.{$index}.serial_number" => "Đơn Trade up yêu cầu nhập đủ {$quantity} S/N cho dòng HW " . ($item['part_number'] ?? '') . '.',
                ]);
            }
        }
    }

    /** Ensure a saved payment schedule always represents the whole order. */
    private function validatePaymentTermPercentages(array $paymentTerms): ?string
    {
        if (empty($paymentTerms)) {
            return null;
        }

        $totalPercent = collect($paymentTerms)
            ->filter(fn ($term) => is_array($term) && filled($term['milestone_name'] ?? null))
            ->sum(fn ($term) => (float) ($term['percentage'] ?? 0));

        return abs($totalPercent - 100) > 0.01
            ? 'Tổng tỷ lệ các đợt thanh toán phải bằng 100%.'
            : null;
    }

    /**
     * Orders worth at least the configured large order threshold (default 1 billion VND)
     * may not be configured as a single 100% payment collected only after delivery.
     * Splitting the schedule or collecting a pre-order/pre-export milestone remains allowed.
     */
    private function validateLargeOrderPaymentTerms(array $paymentTerms, float $total): ?string
    {
        $threshold = (float) Setting::get('large_order_post_delivery_threshold', 1000000000);
        if ($threshold <= 0 || $total < $threshold || empty($paymentTerms)) {
            return null;
        }

        $totalPercent = collect($paymentTerms)->sum(fn ($term) => (float) ($term['percentage'] ?? $term['percent'] ?? 0));
        $allAfterDelivery = collect($paymentTerms)->every(function ($term) {
            $timing = $term['timing'] ?? null;
            $requiredBefore = $term['required_before'] ?? null;

            return in_array($timing, ['after_delivery', 'after_invoice'], true)
                || $requiredBefore === 'after_delivery';
        });

        $formattedThreshold = $threshold >= 1_000_000_000
            ? rtrim(rtrim(number_format($threshold / 1_000_000_000, 2, '.', ''), '0'), '.') . ' tỷ đồng'
            : number_format($threshold, 0, ',', '.') . ' VNĐ';

        return $totalPercent >= 99.99 && $allAfterDelivery
            ? "Đơn hàng từ {$formattedThreshold} không được áp dụng thanh toán 100% sau giao hàng. Vui lòng thiết lập ít nhất một đợt thanh toán trước đặt hàng hoặc trước xuất hàng."
            : null;
    }
}
