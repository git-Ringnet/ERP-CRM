<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleExpense;
use App\Models\Customer;
use App\Models\Product;
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

        $sales = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('sales.index', compact('sales'));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        
        // Generate sale code
        $code = $this->generateSaleCode();
        
        return view('sales.create', compact('customers', 'products', 'code'));
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
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:sales,code'],
            'type' => ['required', 'in:retail,project'],
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
            'expenses' => ['nullable', 'array'],
            'expenses.*.type' => ['required_with:expenses', 'in:shipping,marketing,commission,other'],
            'expenses.*.description' => ['required_with:expenses', 'string'],
            'expenses.*.amount' => ['required_with:expenses', 'numeric', 'min:0'],
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

            // Create sale items with cost price
            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                $costPrice = $product->cost ?? 0;
                $quantity = $item['quantity'];
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $item['price'],
                    'cost_price' => $costPrice,
                    'total' => $quantity * $item['price'],
                    'cost_total' => $quantity * $costPrice,
                ]);
            }

            // Create sale expenses
            if (!empty($validated['expenses'])) {
                foreach ($validated['expenses'] as $expense) {
                    SaleExpense::create([
                        'sale_id' => $sale->id,
                        'type' => $expense['type'],
                        'description' => $expense['description'],
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
        $sale->load(['items.product', 'customer', 'expenses']);
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
        
        return view('sales.edit', compact('sale', 'customers', 'products'));
    }

    /**
     * Update the specified sale in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('sales')->ignore($sale->id)],
            'type' => ['required', 'in:retail,project'],
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

            // Calculate margin and debt
            $sale->calculateMargin();
            $sale->updateDebt();
            $sale->save();

            // Delete old items and create new ones with cost price
            $sale->items()->delete();
            
            foreach ($validated['products'] as $item) {
                $product = Product::find($item['product_id']);
                $costPrice = $product->cost ?? 0;
                $quantity = $item['quantity'];
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $item['price'],
                    'cost_price' => $costPrice,
                    'total' => $quantity * $item['price'],
                    'cost_total' => $quantity * $costPrice,
                ]);
            }

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
