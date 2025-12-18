<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleExpense;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class SaleController extends Controller
{
    /**
     * Display a listing of sales with search and filter functionality.
     */
    public function index(Request $request)
    {
        $query = Sale::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
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

        $sales = $query->with('project')->orderBy('created_at', 'desc')->paginate(10);
        $projects = Project::whereIn('status', ['planning', 'in_progress'])->orderBy('name')->get();

        return view('sales.index', compact('sales', 'projects'));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create(Request $request)
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $projects = Project::whereIn('status', ['planning', 'in_progress'])->orderBy('name')->get();
        
        // Generate sale code
        $code = $this->generateSaleCode();
        
        // Pre-select project if passed from project detail page
        $selectedProjectId = $request->get('project_id');
        $selectedProject = $selectedProjectId ? Project::find($selectedProjectId) : null;
        
        return view('sales.create', compact('customers', 'products', 'projects', 'code', 'selectedProject'));
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
            'expenses.*.type' => ['nullable', 'in:shipping,marketing,commission,other'],
            'expenses.*.description' => ['nullable', 'string'],
            'expenses.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            $code = $validated['code'];
            
            $customer = Customer::find($validated['customer_id']);
            
            // Calculate totals
            $subtotal = 0;
            foreach ($validated['products'] as $item) {
                $subtotal += $item['quantity'] * $item['price'];
            }
            
            $discountAmount = $subtotal * ($validated['discount'] ?? 0) / 100;
            $afterDiscount = $subtotal - $discountAmount;
            $vatAmount = $afterDiscount * ($validated['vat'] ?? 10) / 100;
            $total = $afterDiscount + $vatAmount;

            // Create sale
            $sale = Sale::create([
                'code' => $code,
                'type' => $validated['type'],
                'project_id' => $validated['project_id'] ?? null,
                'customer_id' => $validated['customer_id'],
                'customer_name' => $customer->name,
                'date' => $validated['date'],
                'delivery_address' => $validated['delivery_address'],
                'subtotal' => $subtotal,
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
            ]);

            // Create sale items with cost price and project
            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                $costPrice = $product->cost ?? 0;
                $quantity = $item['quantity'];
                
                // Use item-level project_id if set, otherwise use sale-level project_id
                $itemProjectId = $item['project_id'] ?? $validated['project_id'] ?? null;
                
                // Get warranty: use input value if provided, otherwise use product default
                $warrantyMonths = isset($item['warranty_months']) && $item['warranty_months'] !== '' 
                    ? (int)$item['warranty_months'] 
                    : $product->warranty_months;
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'project_id' => $itemProjectId,
                    'quantity' => $quantity,
                    'price' => $item['price'],
                    'cost_price' => $costPrice,
                    'total' => $quantity * $item['price'],
                    'cost_total' => $quantity * $costPrice,
                    'warranty_months' => $warrantyMonths,
                    'warranty_start_date' => $warrantyMonths ? $validated['date'] : null,
                ]);
            }

            // Create sale expenses (only if type is set)
            if (!empty($validated['expenses'])) {
                foreach ($validated['expenses'] as $expense) {
                    // Skip empty expense rows
                    if (empty($expense['type']) || empty($expense['amount'])) {
                        continue;
                    }
                    SaleExpense::create([
                        'sale_id' => $sale->id,
                        'type' => $expense['type'],
                        'description' => $expense['description'] ?? '',
                        'amount' => $expense['amount'],
                        'note' => $expense['note'] ?? null,
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
        $sale->load(['items.product', 'customer', 'expenses', 'project']);
        return view('sales.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified sale.
     */
    public function edit(Sale $sale)
    {
        $sale->load('items');
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $projects = Project::whereIn('status', ['planning', 'in_progress'])->orderBy('name')->get();
        
        return view('sales.edit', compact('sale', 'customers', 'products', 'projects'));
    }

    /**
     * Update the specified sale in storage.
     */
    public function update(Request $request, Sale $sale)
    {
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
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::find($validated['customer_id']);
            
            // Calculate totals
            $subtotal = 0;
            foreach ($validated['products'] as $item) {
                $subtotal += $item['quantity'] * $item['price'];
            }
            
            $discountAmount = $subtotal * ($validated['discount'] ?? 0) / 100;
            $afterDiscount = $subtotal - $discountAmount;
            $vatAmount = $afterDiscount * ($validated['vat'] ?? 10) / 100;
            $total = $afterDiscount + $vatAmount;

            // Update sale
            $sale->update([
                'code' => $validated['code'],
                'type' => $validated['type'],
                'project_id' => $validated['project_id'] ?? null,
                'customer_id' => $validated['customer_id'],
                'customer_name' => $customer->name,
                'date' => $validated['date'],
                'delivery_address' => $validated['delivery_address'],
                'subtotal' => $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'vat' => $validated['vat'] ?? 10,
                'total' => $total,
                'cost' => $validated['cost'] ?? 0,
                'paid_amount' => $validated['paid_amount'] ?? 0,
                'status' => $validated['status'],
                'note' => $validated['note'],
            ]);

            // Delete old items and create new ones with cost price and project
            $sale->items()->delete();
            
            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                $costPrice = $product->cost ?? 0;
                $quantity = $item['quantity'];
                
                // Use item-level project_id if set, otherwise use sale-level project_id
                $itemProjectId = $item['project_id'] ?? $validated['project_id'] ?? null;
                
                // Get warranty: use input value if provided, otherwise use product default
                $warrantyMonths = isset($item['warranty_months']) && $item['warranty_months'] !== '' 
                    ? (int)$item['warranty_months'] 
                    : $product->warranty_months;
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'project_id' => $itemProjectId,
                    'quantity' => $quantity,
                    'price' => $item['price'],
                    'cost_price' => $costPrice,
                    'total' => $quantity * $item['price'],
                    'cost_total' => $quantity * $costPrice,
                    'warranty_months' => $warrantyMonths,
                    'warranty_start_date' => $warrantyMonths ? $validated['date'] : null,
                ]);
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
        // TODO: Implement export functionality
        return back()->with('info', 'Chức năng xuất Excel đang được phát triển.');
    }

    /**
     * Generate PDF invoice
     */
    public function generatePdf(Sale $sale)
    {
        $sale->load('items', 'customer');
        
        // TODO: Implement PDF generation with DomPDF or similar
        // For now, return a simple view that can be printed
        return view('sales.invoice', compact('sale'));
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Sale $sale)
    {
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
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        DB::beginTransaction();
        try {
            $newPaidAmount = $sale->paid_amount + $validated['amount'];
            
            if ($newPaidAmount > $sale->total) {
                return back()->with('error', 'Số tiền thanh toán vượt quá tổng đơn hàng.');
            }

            $sale->paid_amount = $newPaidAmount;
            $sale->updateDebt();
            $sale->save();

            // TODO: Create payment record in payments table
            
            DB::commit();
            return back()->with('success', 'Đã ghi nhận thanh toán ' . number_format($validated['amount']) . ' đ');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Update sale status
     */
    public function updateStatus(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,shipping,completed,cancelled'],
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

        $sale->status = $newStatus;
        $sale->save();

        $statusLabels = [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'shipping' => 'Đang giao hàng',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];

        return back()->with('success', 'Đã cập nhật trạng thái đơn hàng thành "' . $statusLabels[$newStatus] . '"');
    }
}
