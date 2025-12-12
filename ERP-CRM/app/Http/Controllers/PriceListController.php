<?php

namespace App\Http\Controllers;

use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PriceListController extends Controller
{
    public function index(Request $request)
    {
        $query = PriceList::with('customer');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $priceLists = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('price-lists.index', compact('priceLists'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $code = $this->generateCode();

        return view('price-lists.create', compact('products', 'customers', 'code'));
    }

    private function generateCode(): string
    {
        $date = date('Ymd');
        $prefix = 'PL-' . $date . '-';

        $last = PriceList::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        $number = $last ? intval(substr($last->code, -4)) + 1 : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:price_lists,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:standard,customer,promotion,wholesale'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.min_quantity' => ['nullable', 'numeric', 'min:1'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::beginTransaction();
        try {
            $priceList = PriceList::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'type' => $validated['type'],
                'customer_id' => $validated['customer_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'priority' => $validated['priority'] ?? 0,
                'is_active' => true,
            ]);

            foreach ($validated['items'] as $item) {
                PriceListItem::create([
                    'price_list_id' => $priceList->id,
                    'product_id' => $item['product_id'],
                    'price' => $item['price'],
                    'min_quantity' => $item['min_quantity'] ?? 1,
                    'discount_percent' => $item['discount_percent'] ?? 0,
                ]);
            }

            DB::commit();

            return redirect()->route('price-lists.index')
                ->with('success', 'Bảng giá đã được tạo thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function show(PriceList $priceList)
    {
        $priceList->load('items.product', 'customer');
        return view('price-lists.show', compact('priceList'));
    }

    public function edit(PriceList $priceList)
    {
        $priceList->load('items');
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('price-lists.edit', compact('priceList', 'products', 'customers'));
    }

    public function update(Request $request, PriceList $priceList)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('price_lists')->ignore($priceList->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:standard,customer,promotion,wholesale'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.min_quantity' => ['nullable', 'numeric', 'min:1'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::beginTransaction();
        try {
            $priceList->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'type' => $validated['type'],
                'customer_id' => $validated['customer_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'priority' => $validated['priority'] ?? 0,
            ]);

            $priceList->items()->delete();

            foreach ($validated['items'] as $item) {
                PriceListItem::create([
                    'price_list_id' => $priceList->id,
                    'product_id' => $item['product_id'],
                    'price' => $item['price'],
                    'min_quantity' => $item['min_quantity'] ?? 1,
                    'discount_percent' => $item['discount_percent'] ?? 0,
                ]);
            }

            DB::commit();

            return redirect()->route('price-lists.index')
                ->with('success', 'Bảng giá đã được cập nhật.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(PriceList $priceList)
    {
        DB::beginTransaction();
        try {
            $priceList->items()->delete();
            $priceList->delete();
            DB::commit();

            return redirect()->route('price-lists.index')
                ->with('success', 'Bảng giá đã được xóa.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function toggle(PriceList $priceList)
    {
        $priceList->update(['is_active' => !$priceList->is_active]);

        $status = $priceList->is_active ? 'kích hoạt' : 'tắt';
        return back()->with('success', "Đã {$status} bảng giá.");
    }

    public function getForCustomer(Customer $customer)
    {
        $priceList = PriceList::getForCustomer($customer->id);
        
        if (!$priceList) {
            return response()->json(['message' => 'Không tìm thấy bảng giá'], 404);
        }

        $priceList->load('items.product');

        return response()->json($priceList);
    }
}
