<?php

namespace App\Http\Controllers;

use App\Models\MarketingItem;
use App\Models\MarketingItemTransaction;
use App\Models\Opportunity;
use App\Models\MarketingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MarketingItemController extends Controller
{
    public function index(Request $request)
    {
        $query = MarketingItem::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->whereColumn('stock_quantity', '<=', 'min_stock_alert');
            } elseif ($request->stock_status === 'out') {
                $query->where('stock_quantity', '<=', 0);
            }
        }

        $items = $query->orderBy('name')->paginate(15)->withQueryString();

        // Statistics
        $totalTypes = MarketingItem::count();
        $totalQuantity = MarketingItem::sum('stock_quantity');
        $totalValue = MarketingItem::select(DB::raw('SUM(stock_quantity * unit_cost) as total_val'))->value('total_val') ?? 0;
        $lowStockCount = MarketingItem::whereColumn('stock_quantity', '<=', 'min_stock_alert')->count();

        $categories = MarketingItem::CATEGORIES;
        $opportunities = Opportunity::whereIn('status', ['planned', 'confirmed', 'in_progress'])->latest('id')->take(30)->get();
        $marketingEvents = MarketingEvent::latest('id')->take(20)->get();

        // Recent transactions
        $recentTransactions = MarketingItemTransaction::with(['marketingItem', 'creator', 'opportunity', 'marketingEvent'])
            ->latest('id')
            ->take(10)
            ->get();

        return view('marketing.items.index', compact(
            'items',
            'totalTypes',
            'totalQuantity',
            'totalValue',
            'lowStockCount',
            'categories',
            'opportunities',
            'marketingEvents',
            'recentTransactions'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', array_keys(MarketingItem::CATEGORIES)),
            'unit' => 'required|string|max:50',
            'stock_quantity' => 'nullable|integer|min:0',
            'min_stock_alert' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['code'] = MarketingItem::generateCode();
        $validated['stock_quantity'] = $validated['stock_quantity'] ?? 0;
        $validated['min_stock_alert'] = $validated['min_stock_alert'] ?? 10;
        $validated['unit_cost'] = $validated['unit_cost'] ?? 0;
        $validated['status'] = $validated['status'] ?? 'active';

        DB::transaction(function () use ($validated) {
            $item = MarketingItem::create($validated);

            if ($item->stock_quantity > 0) {
                MarketingItemTransaction::create([
                    'marketing_item_id' => $item->id,
                    'type' => 'import',
                    'quantity' => $item->stock_quantity,
                    'remaining_stock' => $item->stock_quantity,
                    'created_by' => Auth::id(),
                    'reference_code' => 'INIT-' . $item->code,
                    'note' => 'Khởi tạo tồn kho ban đầu',
                ]);
            }
        });

        return redirect()->route('marketing-items.index')->with('success', 'Đã thêm vật phẩm mới vào Kho Marketing.');
    }

    public function update(Request $request, MarketingItem $marketingItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', array_keys(MarketingItem::CATEGORIES)),
            'unit' => 'required|string|max:50',
            'min_stock_alert' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $marketingItem->update($validated);

        return back()->with('success', 'Đã cập nhật thông tin vật phẩm.');
    }

    public function destroy(MarketingItem $marketingItem)
    {
        if ($marketingItem->stock_quantity > 0) {
            return back()->with('error', 'Không thể xóa vật phẩm còn tồn kho. Vui lòng xuất hết tồn trước khi xóa.');
        }

        $marketingItem->delete();
        return back()->with('success', 'Đã xóa vật phẩm khỏi Kho Marketing.');
    }

    /**
     * Nhập kho vật phẩm
     */
    public function importStock(Request $request)
    {
        $validated = $request->validate([
            'marketing_item_id' => 'required|exists:marketing_items,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_code' => 'nullable|string|max:100',
            'note' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $item = MarketingItem::lockForUpdate()->findOrFail($validated['marketing_item_id']);
            $newStock = $item->stock_quantity + $validated['quantity'];

            $updateData = ['stock_quantity' => $newStock];
            if (isset($validated['unit_cost']) && $validated['unit_cost'] > 0) {
                $updateData['unit_cost'] = $validated['unit_cost'];
            }
            $item->update($updateData);

            MarketingItemTransaction::create([
                'marketing_item_id' => $item->id,
                'type' => 'import',
                'quantity' => $validated['quantity'],
                'remaining_stock' => $newStock,
                'created_by' => Auth::id(),
                'reference_code' => $validated['reference_code'] ?? ('IMP-' . date('YmdHis')),
                'note' => $validated['note'] ?? 'Nhập bổ sung kho Marketing',
            ]);
        });

        return back()->with('success', 'Đã nhập kho vật phẩm Marketing thành công.');
    }

    /**
     * Xuất kho vật phẩm (quà tặng cho cơ hội / sự kiện)
     */
    public function exportStock(Request $request)
    {
        $validated = $request->validate([
            'marketing_item_id' => 'required|exists:marketing_items,id',
            'quantity' => 'required|integer|min:1',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'marketing_event_id' => 'nullable|exists:marketing_events,id',
            'reference_code' => 'nullable|string|max:100',
            'note' => 'nullable|string',
        ]);

        $item = MarketingItem::findOrFail($validated['marketing_item_id']);

        if ($item->stock_quantity < $validated['quantity']) {
            return back()->with('error', "Không đủ tồn kho để xuất. Tồn hiện tại: {$item->stock_quantity} {$item->unit}, yêu cầu: {$validated['quantity']} {$item->unit}.");
        }

        DB::transaction(function () use ($validated, $item) {
            $lockedItem = MarketingItem::lockForUpdate()->findOrFail($item->id);
            $newStock = $lockedItem->stock_quantity - $validated['quantity'];

            $lockedItem->update(['stock_quantity' => $newStock]);

            MarketingItemTransaction::create([
                'marketing_item_id' => $lockedItem->id,
                'type' => 'export',
                'quantity' => $validated['quantity'],
                'remaining_stock' => $newStock,
                'opportunity_id' => $validated['opportunity_id'] ?? null,
                'marketing_event_id' => $validated['marketing_event_id'] ?? null,
                'created_by' => Auth::id(),
                'reference_code' => $validated['reference_code'] ?? ('EXP-' . date('YmdHis')),
                'note' => $validated['note'] ?? 'Xuất quà tặng Marketing',
            ]);
        });

        return back()->with('success', 'Đã xuất kho vật phẩm Marketing thành công.');
    }

    /**
     * Lịch sử giao dịch của 1 vật phẩm
     */
    public function transactions(MarketingItem $marketingItem)
    {
        $transactions = $marketingItem->transactions()
            ->with(['creator', 'opportunity', 'marketingEvent'])
            ->paginate(20);

        return view('marketing.items.transactions', compact('marketingItem', 'transactions'));
    }
}
