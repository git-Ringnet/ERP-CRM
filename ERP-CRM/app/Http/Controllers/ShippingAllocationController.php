<?php

namespace App\Http\Controllers;

use App\Models\ShippingAllocation;
use App\Models\ShippingAllocationItem;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ShippingAllocationController extends Controller
{
    public function index(Request $request): View
    {
        $query = ShippingAllocation::with(['purchaseOrder', 'warehouse', 'creator', 'items']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function ($q2) use ($search) {
                      $q2->where('code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('allocation_method')) {
            $query->where('allocation_method', $request->allocation_method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $allocations = $query->orderBy('created_at', 'desc')->paginate(15);
        $warehouses = Warehouse::orderBy('name')->get();

        // Statistics
        $stats = [
            'total_allocations' => ShippingAllocation::count(),
            'total_shipping_cost' => ShippingAllocation::sum('total_shipping_cost') ?? 0,
            'total_products' => ShippingAllocationItem::distinct('product_id')->count(),
            'total_warehouses' => ShippingAllocation::distinct('warehouse_id')->count(),
        ];

        return view('shipping-allocations.index', compact('allocations', 'warehouses', 'stats'));
    }

    public function create(): View
    {
        $purchaseOrders = PurchaseOrder::whereIn('status', ['received', 'partial_received'])
            ->with('items.product')
            ->orderBy('code', 'desc')
            ->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('shipping-allocations.create', compact('purchaseOrders', 'warehouses', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'allocation_date' => 'required|date',
            'total_shipping_cost' => 'required|numeric|min:0',
            'allocation_method' => 'required|in:value,quantity,weight,volume',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_value' => 'required|numeric|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.volume' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $allocation = ShippingAllocation::create([
                'code' => ShippingAllocation::generateCode(),
                'purchase_order_id' => $validated['purchase_order_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'allocation_date' => $validated['allocation_date'],
                'total_shipping_cost' => $validated['total_shipping_cost'],
                'allocation_method' => $validated['allocation_method'],
                'status' => 'draft',
                'note' => $validated['note'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = new ShippingAllocationItem([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_value' => $itemData['unit_value'],
                    'weight' => $itemData['weight'] ?? null,
                    'volume' => $itemData['volume'] ?? null,
                    'allocated_cost' => 0,
                    'allocated_cost_per_unit' => 0,
                ]);
                $item->calculateTotalValue();
                $allocation->items()->save($item);
            }

            $allocation->calculateAllocation();

            // Nếu nhấn "Lưu và duyệt" thì tự động duyệt luôn
            if ($request->has('approve')) {
                $allocation->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            }

            DB::commit();

            $message = $request->has('approve') 
                ? 'Đã tạo và duyệt phiếu phân bổ thành công!' 
                : 'Đã tạo phiếu phân bổ thành công!';

            return redirect()->route('shipping-allocations.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function show(ShippingAllocation $shippingAllocation): View
    {
        $shippingAllocation->load(['purchaseOrder.supplier', 'warehouse', 'items.product', 'creator', 'approver']);

        return view('shipping-allocations.show', compact('shippingAllocation'));
    }

    public function edit(ShippingAllocation $shippingAllocation): View|RedirectResponse
    {
        if ($shippingAllocation->status !== 'draft') {
            return redirect()->route('shipping-allocations.show', $shippingAllocation)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái nháp!');
        }

        $shippingAllocation->load('items.product');
        $purchaseOrders = PurchaseOrder::whereIn('status', ['received', 'partial_received'])
            ->with('items.product')
            ->orderBy('code', 'desc')
            ->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('shipping-allocations.edit', compact('shippingAllocation', 'purchaseOrders', 'warehouses', 'products'));
    }

    public function update(Request $request, ShippingAllocation $shippingAllocation): RedirectResponse
    {
        if ($shippingAllocation->status !== 'draft') {
            return redirect()->route('shipping-allocations.show', $shippingAllocation)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái nháp!');
        }

        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'allocation_date' => 'required|date',
            'total_shipping_cost' => 'required|numeric|min:0',
            'allocation_method' => 'required|in:value,quantity,weight,volume',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_value' => 'required|numeric|min:0',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.volume' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $shippingAllocation->update([
                'purchase_order_id' => $validated['purchase_order_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'allocation_date' => $validated['allocation_date'],
                'total_shipping_cost' => $validated['total_shipping_cost'],
                'allocation_method' => $validated['allocation_method'],
                'note' => $validated['note'] ?? null,
            ]);

            $shippingAllocation->items()->delete();

            foreach ($validated['items'] as $itemData) {
                $item = new ShippingAllocationItem([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_value' => $itemData['unit_value'],
                    'weight' => $itemData['weight'] ?? null,
                    'volume' => $itemData['volume'] ?? null,
                    'allocated_cost' => 0,
                    'allocated_cost_per_unit' => 0,
                ]);
                $item->calculateTotalValue();
                $shippingAllocation->items()->save($item);
            }

            $shippingAllocation->calculateAllocation();

            DB::commit();

            return redirect()->route('shipping-allocations.index')
                ->with('success', 'Đã cập nhật phiếu phân bổ thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(ShippingAllocation $shippingAllocation): RedirectResponse
    {
        if ($shippingAllocation->status !== 'draft') {
            return redirect()->route('shipping-allocations.index')
                ->with('error', 'Chỉ có thể xóa phiếu ở trạng thái nháp!');
        }

        $shippingAllocation->items()->delete();
        $shippingAllocation->delete();

        return redirect()->route('shipping-allocations.index')
            ->with('success', 'Đã xóa phiếu phân bổ thành công!');
    }

    public function approve(ShippingAllocation $shippingAllocation): RedirectResponse
    {
        if ($shippingAllocation->status !== 'draft') {
            return redirect()->route('shipping-allocations.show', $shippingAllocation)
                ->with('error', 'Phiếu này không thể duyệt!');
        }

        $shippingAllocation->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('shipping-allocations.show', $shippingAllocation)
            ->with('success', 'Đã duyệt phiếu phân bổ thành công!');
    }

    public function complete(ShippingAllocation $shippingAllocation): RedirectResponse
    {
        if ($shippingAllocation->status !== 'approved') {
            return redirect()->route('shipping-allocations.show', $shippingAllocation)
                ->with('error', 'Phiếu này chưa được duyệt!');
        }

        $shippingAllocation->update(['status' => 'completed']);

        return redirect()->route('shipping-allocations.show', $shippingAllocation)
            ->with('success', 'Đã hoàn thành phiếu phân bổ!');
    }
}
