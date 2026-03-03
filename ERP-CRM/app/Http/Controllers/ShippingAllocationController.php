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
        $this->authorize('viewAny', ShippingAllocation::class);

        $query = ShippingAllocation::with(['import', 'createdBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('import', fn($q) => $q->where('code', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('import_id')) {
            $query->where('import_id', $request->import_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('allocation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('allocation_date', '<=', $request->date_to);
        }

        $allocations = $query->orderBy('created_at', 'desc')->paginate(15);

        $imports = Import::where('status', 'approved')
            ->whereDoesntHave('shippingAllocation')
            ->orderBy('date', 'desc')
            ->get();

        return view('shipping-allocations.index', compact('allocations', 'imports'));
    }

    public function create(): View
    {
        $this->authorize('create', ShippingAllocation::class);

        $imports = Import::where('status', 'approved')
            ->whereDoesntHave('shippingAllocation')
            ->with(['items.product', 'supplier'])
            ->orderBy('date', 'desc')
            ->get();

        $code = ShippingAllocation::generateCode();

        return view('shipping-allocations.create', compact('imports', 'code'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ShippingAllocation::class);
        
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
        $this->authorize('view', $shippingAllocation);

        $shippingAllocation->load(['import.items.product', 'allocations.product', 'createdBy']);

        return view('shipping-allocations.show', compact('shippingAllocation'));
    }

    public function edit(ShippingAllocation $shippingAllocation): View|RedirectResponse
    {
        $this->authorize('update', $shippingAllocation);

        if ($shippingAllocation->status !== 'pending') {
            return redirect()->route('shipping-allocations.index')
                ->with('error', 'Chỉ có thể sửa phân bổ ở trạng thái Chờ duyệt!');
        }

        $shippingAllocation->load(['import.items.product', 'allocations']);

        return view('shipping-allocations.edit', compact('shippingAllocation'));
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
        $this->authorize('delete', $shippingAllocation);

        if ($shippingAllocation->status !== 'pending') {
            return redirect()->route('shipping-allocations.index')
                ->with('error', 'Chỉ có thể xóa phân bổ ở trạng thái Chờ duyệt!');
        }

        $shippingAllocation->delete();

        return redirect()->route('shipping-allocations.index')
            ->with('success', 'Đã xóa phân bổ chi phí vận chuyển!');
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
