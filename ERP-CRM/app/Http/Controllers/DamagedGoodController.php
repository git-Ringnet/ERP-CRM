<?php

namespace App\Http\Controllers;

use App\Exports\DamagedGoodsExport;
use App\Http\Requests\DamagedGoodRequest;
use App\Models\DamagedGood;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Warehouse;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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
        $warehouses = Warehouse::orderBy('name')->get();

        return view('damaged-goods.create', compact('products', 'users', 'warehouses'));
    }

    public function store(DamagedGoodRequest $request)
    {
        $damagedGood = new DamagedGood($request->validated());

        if (!$request->filled('code')) {
            $damagedGood->code = $damagedGood->generateCode();
        }

        if ($request->warehouse_id) {
            $isValid = true;
            $msg = '';

            if ($request->product_item_ids) {
                // Validate multiple items
                $count = count($request->product_item_ids);
                if ($request->quantity != $count) {
                    $isValid = false;
                    $msg = "Số lượng nhập ({$request->quantity}) không khớp với số lượng serial đã chọn ({$count}).";
                } else {
                    foreach ($request->product_item_ids as $itemId) {
                        $item = ProductItem::find($itemId);
                        if (!$item || $item->status !== ProductItem::STATUS_IN_STOCK) {
                            $isValid = false;
                            $msg = "Item {$item->sku} không còn trong kho.";
                            break;
                        }
                    }
                }
            } elseif ($request->product_item_id) {
                // Fallback for single item (if UI sends single)
                $item = ProductItem::find($request->product_item_id);
                if (!$item || $item->status !== ProductItem::STATUS_IN_STOCK) {
                    $isValid = false;
                    $msg = 'Item đã chọn không còn trong kho.';
                } elseif ($request->quantity > $item->quantity) {
                    $isValid = false;
                    $msg = "Số lượng nhập ({$request->quantity}) vượt quá tồn kho của item ({$item->quantity}).";
                }
            } else {
                $totalAvailable = ProductItem::where('product_id', $request->product_id)
                    ->where('warehouse_id', $request->warehouse_id)
                    ->where('status', ProductItem::STATUS_IN_STOCK)
                    ->sum('quantity');
                if ($request->quantity > $totalAvailable) {
                    $isValid = false;
                    $msg = "Số lượng nhập ({$request->quantity}) vượt quá tổng tồn kho ({$totalAvailable}).";
                }
            }

            if (!$isValid) {
                return back()->with('error', $msg)->withInput();
            }
        }

        $damagedGood->save();

        // Attach items if multiple selected
        if ($request->product_item_ids) {
            $damagedGood->items()->attach($request->product_item_ids);
            // Optionally set primary item logic if needed, but pivot is enough
        } elseif ($request->product_item_id) {
            // Backwards compatibility or single select
            $damagedGood->items()->attach($request->product_item_id);
        }

        // Gửi thông báo cho tất cả users (trừ người tạo)
        $currentUserId = auth()->id();
        $recipientIds = User::where('id', '!=', $currentUserId)->pluck('id')->toArray();

        if (!empty($recipientIds)) {
            $damagedGood->load(['product', 'discoveredBy']);
            $notificationService = new NotificationService();
            $notificationService->notifyDamagedGoodCreated($damagedGood, $recipientIds);
        }

        return redirect()
            ->route('damaged-goods.show', $damagedGood)
            ->with('success', 'Đã tạo báo cáo hàng hư hỏng/thanh lý thành công');
    }

    public function show(DamagedGood $damagedGood)
    {
        $damagedGood->load(['product', 'discoveredBy', 'items']);

        return view('damaged-goods.show', compact('damagedGood'));
    }

    public function edit(DamagedGood $damagedGood)
    {
        if ($damagedGood->status !== 'pending') {
            return redirect()->route('damaged-goods.show', $damagedGood)
                ->with('error', 'Không thể chỉnh sửa báo cáo đã được xử lý hoặc duyệt.');
        }

        $products = Product::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('damaged-goods.edit', compact('damagedGood', 'products', 'users', 'warehouses'));
    }

    public function update(DamagedGoodRequest $request, DamagedGood $damagedGood)
    {
        if ($damagedGood->status !== 'pending') {
            return back()->with('error', 'Không thể chỉnh sửa báo cáo đã được xử lý hoặc duyệt.');
        }

        $damagedGood->update($request->validated());

        return redirect()
            ->route('damaged-goods.show', $damagedGood)
            ->with('success', 'Đã cập nhật báo cáo thành công');
    }

    public function destroy(DamagedGood $damagedGood)
    {
        if ($damagedGood->status !== 'pending') {
            return redirect()
                ->route('damaged-goods.index')
                ->with('error', 'Chỉ có thể xóa các báo cáo ở trạng thái chờ duyệt.');
        }

        $damagedGood->delete();

        return redirect()
            ->route('damaged-goods.index')
            ->with('success', 'Đã xóa báo cáo thành công');
    }

    public function updateStatus(Request $request, DamagedGood $damagedGood)
    {
        if ($damagedGood->status !== 'pending') {
            // For now, allow approved -> processed if we decide later, but user asked for strict restriction.
            // "ban đầu tôi duyệt hàng hủy xong tôi lại cập nhật chờ duyệt được" -> Main complaint is reverting.
            // "chỉ cho cập nhật lúc chờ duyệt thôi chứ" -> Strict.
            return back()->with('error', 'Chỉ có thể cập nhật trạng thái khi báo cáo đang chờ duyệt.');
        }

        $request->validate([
            'status' => 'required|in:pending,approved,rejected,processed',
            'solution' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $damagedGood->status;
        $newStatus = $request->status;

        $damagedGood->update([
            'status' => $newStatus,
            'solution' => $request->solution,
        ]);

        // Gửi thông báo cho người tạo báo cáo khi duyệt/từ chối
        if ($oldStatus !== $newStatus) {
            $notificationService = new NotificationService();

            if ($newStatus === 'approved') {
                // --- VALIDATION: CHECK STOCK BEFORE APPROVAL ---
                $pivotItems = $damagedGood->items;

                if ($pivotItems->count() > 0) {
                    foreach ($pivotItems as $item) {
                        if ($item->status !== ProductItem::STATUS_IN_STOCK) {
                            $damagedGood->update(['status' => $oldStatus]);
                            return back()->with('error', "Item {$item->sku} không còn trong kho. Không thể duyệt.");
                        }
                    }
                    // Check if quantity matches count (optional check)
                    if ($damagedGood->quantity != $pivotItems->count()) {
                        // Warning or strict? STRICT basically says "quantity" field is just a summary. 
                        // But if user manually changed Quantity without updating items, it's a mismatch. 
                        // Let's assume quantity is correct or update it? 
                        // For now just valid items existence.
                    }
                } elseif ($damagedGood->product_item_id) {
                    $item = ProductItem::find($damagedGood->product_item_id);
                    if (!$item || $item->status !== ProductItem::STATUS_IN_STOCK) {
                        // Revert status update
                        $damagedGood->update(['status' => $oldStatus]);
                        return back()->with('error', 'Sản phẩm này hiện không còn trong kho (hoặc đã bán/hỏng), không thể duyệt báo cáo.');
                    }
                    if ($damagedGood->quantity > $item->quantity) {
                        $damagedGood->update(['status' => $oldStatus]);
                        return back()->with('error', "Số lượng báo cáo ({$damagedGood->quantity}) lớn hơn số lượng tồn của item này ({$item->quantity}).");
                    }
                } elseif ($damagedGood->warehouse_id) {
                    // Check total available quantity in that warehouse
                    $totalAvailable = ProductItem::where('product_id', $damagedGood->product_id)
                        ->where('warehouse_id', $damagedGood->warehouse_id)
                        ->where('status', ProductItem::STATUS_IN_STOCK)
                        ->sum('quantity');

                    if ($totalAvailable < $damagedGood->quantity) {
                        // Revert status update
                        $damagedGood->update(['status' => $oldStatus]);
                        return back()->with('error', "Không đủ tồn kho để duyệt. Kho hiện có {$totalAvailable}, báo cáo {$damagedGood->quantity}.");
                    }
                } else {
                    if (!$damagedGood->warehouse_id && !$damagedGood->product_item_id) {
                        $damagedGood->update(['status' => $oldStatus]);
                        return back()->with('error', 'Thiếu thông tin kho hàng. Không thể trừ tồn kho.');
                    }
                }

                if ($damagedGood->discovered_by) {
                    $notificationService->notifyDamagedGoodApproved($damagedGood, $damagedGood->discovered_by);
                }

                // --- INVENTORY INTEGRATION ---
                // 1. Pivot Items
                if ($pivotItems->count() > 0) {
                    foreach ($pivotItems as $item) {
                        $item->status = ($damagedGood->type === 'liquidation')
                            ? ProductItem::STATUS_LIQUIDATION
                            : ProductItem::STATUS_DAMAGED;
                        $item->save();
                    }
                }
                // 2. Single Item (Legacy)
                elseif ($damagedGood->product_item_id) {
                    $item = ProductItem::find($damagedGood->product_item_id);
                    if ($item && $item->status === ProductItem::STATUS_IN_STOCK) {
                        if ($damagedGood->type === 'liquidation') {
                            $item->status = ProductItem::STATUS_LIQUIDATION;
                        } else {
                            $item->status = ProductItem::STATUS_DAMAGED;
                        }
                        $item->save();
                    }
                }
                // 3. Bulk (No specific items linked)
                elseif ($damagedGood->warehouse_id) {
                    $qtyNeeded = $damagedGood->quantity;
                    $items = ProductItem::where('product_id', $damagedGood->product_id)
                        ->where('warehouse_id', $damagedGood->warehouse_id)
                        ->where('status', ProductItem::STATUS_IN_STOCK)
                        ->orderBy('quantity', 'desc')
                        ->get();

                    foreach ($items as $item) {
                        if ($qtyNeeded <= 0)
                            break;

                        if ($item->quantity > $qtyNeeded) {
                            $item->quantity -= $qtyNeeded;
                            $item->save();

                            $newItem = $item->replicate();
                            $newItem->sku = ProductItem::generateNoSku($item->product_id);
                            $newItem->quantity = $qtyNeeded;
                            $newItem->status = ($damagedGood->type === 'liquidation')
                                ? ProductItem::STATUS_LIQUIDATION
                                : ProductItem::STATUS_DAMAGED;
                            $newItem->save();

                            $qtyNeeded = 0;
                        } else {
                            $qtyNeeded -= $item->quantity;
                            $item->status = ($damagedGood->type === 'liquidation')
                                ? ProductItem::STATUS_LIQUIDATION
                                : ProductItem::STATUS_DAMAGED;
                            $item->save();
                        }
                    }
                }

            } elseif ($newStatus === 'rejected') {
                $reason = $request->solution ?? '';
                $notificationService->notifyDamagedGoodRejected($damagedGood, $damagedGood->discovered_by, $reason);
            }
        }

        return redirect()
            ->route('damaged-goods.show', $damagedGood)
            ->with('success', 'Đã cập nhật trạng thái thành công');
    }

    /**
     * API to get items for a product in a warehouse
     */
    public function getProductItems(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $items = ProductItem::where('product_id', $request->product_id)
            ->where('warehouse_id', $request->warehouse_id)
            ->where('status', ProductItem::STATUS_IN_STOCK)
            ->select('id', 'sku', 'quantity')
            ->get();

        return response()->json([
            'items' => $items,
            'total_stock' => $items->sum('quantity')
        ]);
    }

    public function export(Request $request)
    {
        $filters = $request->only(['type', 'status', 'start_date', 'end_date', 'product_id', 'search']);

        return Excel::download(
            new DamagedGoodsExport($filters),
            'hang-hu-hong-thanh-ly-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
