<?php

namespace App\Http\Controllers;

use App\Http\Requests\DamagedGoodRequest;
use App\Models\DamagedGood;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class DamagedGoodController extends Controller
{
    public function index(Request $request)
    {
        $query = DamagedGood::with(['product', 'discoveredBy']);

        // Filter by type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Search by code
        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        $damagedGoods = $query->latest()->paginate(10);

        $products = Product::orderBy('name')->get();

        return view('damaged-goods.index', compact('damagedGoods', 'products'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('damaged-goods.create', compact('products', 'users'));
    }

    public function store(DamagedGoodRequest $request)
    {
        $damagedGood = new DamagedGood($request->validated());

        if (!$request->filled('code')) {
            $damagedGood->code = $damagedGood->generateCode();
        }

        $damagedGood->save();

        return redirect()
            ->route('damaged-goods.show', $damagedGood)
            ->with('success', 'Đã tạo báo cáo hàng hư hỏng/thanh lý thành công');
    }

    public function show(DamagedGood $damagedGood)
    {
        $damagedGood->load(['product', 'discoveredBy']);

        return view('damaged-goods.show', compact('damagedGood'));
    }

    public function edit(DamagedGood $damagedGood)
    {
        $products = Product::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('damaged-goods.edit', compact('damagedGood', 'products', 'users'));
    }

    public function update(DamagedGoodRequest $request, DamagedGood $damagedGood)
    {
        $damagedGood->update($request->validated());

        return redirect()
            ->route('damaged-goods.show', $damagedGood)
            ->with('success', 'Đã cập nhật báo cáo thành công');
    }

    public function destroy(DamagedGood $damagedGood)
    {
        if ($damagedGood->status === 'processed') {
            return redirect()
                ->route('damaged-goods.index')
                ->with('error', 'Không thể xóa báo cáo đã xử lý');
        }

        $damagedGood->delete();

        return redirect()
            ->route('damaged-goods.index')
            ->with('success', 'Đã xóa báo cáo thành công');
    }

    public function updateStatus(Request $request, DamagedGood $damagedGood)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected,processed',
            'solution' => 'nullable|string|max:1000',
        ]);

        $damagedGood->update([
            'status' => $request->status,
            'solution' => $request->solution,
        ]);

        return redirect()
            ->route('damaged-goods.show', $damagedGood)
            ->with('success', 'Đã cập nhật trạng thái thành công');
    }
}
