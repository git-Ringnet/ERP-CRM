<?php

namespace App\Http\Controllers;

use App\Models\PurchasePricing;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PurchasePricingController extends Controller
{
    public function index(Request $request): View
    {
        $query = PurchasePricing::with(['product', 'supplier', 'creator']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('pricing_method')) {
            $query->where('pricing_method', $request->pricing_method);
        }

        $pricings = $query->orderBy('created_at', 'desc')->paginate(15);
        $suppliers = Supplier::orderBy('name')->get();

        // Statistics
        $stats = [
            'total_products' => PurchasePricing::distinct('product_id')->count(),
            'avg_purchase_price' => PurchasePricing::avg('purchase_price') ?? 0,
            'avg_warehouse_price' => PurchasePricing::avg('warehouse_price') ?? 0,
            'total_service_cost' => PurchasePricing::sum('total_service_cost') ?? 0,
        ];

        return view('purchase-pricings.index', compact('pricings', 'suppliers', 'stats'));
    }

    public function create(): View
    {
        $products = Product::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $purchaseOrders = PurchaseOrder::where('status', 'received')->orderBy('code', 'desc')->get();

        return view('purchase-pricings.create', compact('products', 'suppliers', 'purchaseOrders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
            'loading_cost' => 'nullable|numeric|min:0',
            'inspection_cost' => 'nullable|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0',
            'pricing_method' => 'required|in:fifo,lifo,average',
            'note' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['discount_percent'] = $validated['discount_percent'] ?? 0;
        $validated['vat_percent'] = $validated['vat_percent'] ?? 10;
        $validated['shipping_cost'] = $validated['shipping_cost'] ?? 0;
        $validated['loading_cost'] = $validated['loading_cost'] ?? 0;
        $validated['inspection_cost'] = $validated['inspection_cost'] ?? 0;
        $validated['other_cost'] = $validated['other_cost'] ?? 0;

        $pricing = new PurchasePricing($validated);
        $pricing->calculatePrices();
        $pricing->save();

        return redirect()->route('purchase-pricings.index')
            ->with('success', 'Đã thêm giá nhập thành công!');
    }

    public function show(PurchasePricing $purchasePricing): View
    {
        $purchasePricing->load(['product', 'supplier', 'purchaseOrder', 'creator']);
        
        // Get price history for this product
        $priceHistory = PurchasePricing::where('product_id', $purchasePricing->product_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('purchase-pricings.show', compact('purchasePricing', 'priceHistory'));
    }

    public function edit(PurchasePricing $purchasePricing): View
    {
        $products = Product::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $purchaseOrders = PurchaseOrder::where('status', 'received')->orderBy('code', 'desc')->get();

        return view('purchase-pricings.edit', compact('purchasePricing', 'products', 'suppliers', 'purchaseOrders'));
    }

    public function update(Request $request, PurchasePricing $purchasePricing): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'vat_percent' => 'nullable|numeric|min:0|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
            'loading_cost' => 'nullable|numeric|min:0',
            'inspection_cost' => 'nullable|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0',
            'pricing_method' => 'required|in:fifo,lifo,average',
            'note' => 'nullable|string',
        ]);

        $purchasePricing->fill($validated);
        $purchasePricing->calculatePrices();
        $purchasePricing->save();

        return redirect()->route('purchase-pricings.index')
            ->with('success', 'Đã cập nhật giá nhập thành công!');
    }

    public function destroy(PurchasePricing $purchasePricing): RedirectResponse
    {
        $purchasePricing->delete();

        return redirect()->route('purchase-pricings.index')
            ->with('success', 'Đã xóa giá nhập thành công!');
    }

    public function recalculate(Request $request): RedirectResponse
    {
        $method = $request->input('method', 'average');
        
        PurchasePricing::chunk(100, function ($pricings) use ($method) {
            foreach ($pricings as $pricing) {
                $pricing->pricing_method = $method;
                $pricing->calculatePrices();
                $pricing->save();
            }
        });

        return redirect()->route('purchase-pricings.index')
            ->with('success', 'Đã tính toán lại giá kho thành công!');
    }
}
