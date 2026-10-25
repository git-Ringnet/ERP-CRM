<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketItem;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductItemBorrowLog;
use App\Models\User;
use App\Models\Notification;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Borrowing is an operational runrate flow only. Project and license
     * stock must remain reserved for their original purpose.
     */
    private function runrateWarehouseId(): ?int
    {
        return Warehouse::where('code', 'WH_RUNRATE')->value('id');
    }

    /**
     * Sắp xếp thiết bị để lấy $qty cái từ ít PO nhất: có PO đủ số lượng thì lấy PO nhập sớm nhất trong đó,
     * không thì lấy PO nhiều hàng trước. Giữ hàng theo model nhưng suất giữ vẫn gắn S/N, nên tránh chia lẻ nhiều PO.
     */
    private function orderByFewestPos(\Illuminate\Support\Collection $items, int $qty): \Illuminate\Support\Collection
    {
        $groups = $items->groupBy(fn ($item) => (int) $item->import_id)->sortKeys();
        $fullGroup = $groups->first(fn ($group) => $group->count() >= $qty);

        $ordered = $fullGroup
            ? collect([$fullGroup])->concat($groups->reject(fn ($group) => $group === $fullGroup))
            : $groups->sortByDesc(fn ($group) => $group->count());

        return $ordered->flatten(1)->values();
    }

    /**
     * Display a listing of tickets.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isManagerOrAdmin = $user->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff']);

        $myTicketsQuery = Ticket::with(['user', 'target_user', 'items.product'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $myTicketsQuery->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $myTicketsQuery->where('status', $request->status);
        }
        $myTickets = $myTicketsQuery->paginate(10, ['*'], 'page_my');

        // Pending approvals list
        $pendingApprovalsQuery = Ticket::with(['user', 'target_user', 'items.product'])
            ->where('status', 'pending');

        if ($isManagerOrAdmin) {
            // Admins/Warehouse see all pending tickets (both preload and warehouse-source borrow requests)
        } else {
            // Salespersons see only borrow requests where they are the target user
            $pendingApprovalsQuery->where('target_user_id', $user->id)
                                  ->where('source', 'sales');
        }
        $pendingApprovals = $pendingApprovalsQuery->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'page_pending');

        // Admin / Kho: tổng hợp hàng runrate đang giữ
        $holdSummary = collect();
        if ($isManagerOrAdmin) {
            $runrateWarehouseId = $this->runrateWarehouseId();
            if ($runrateWarehouseId) {
                $hasLog = 'EXISTS (SELECT 1 FROM product_item_borrow_logs l WHERE l.product_item_id = product_items.id)';
                $holdSummary = DB::table('product_items')
                    ->where('product_items.warehouse_id', $runrateWarehouseId)
                    ->where('product_items.status', ProductItem::STATUS_IN_STOCK)
                    ->whereNotNull('product_items.borrower')
                    ->where('product_items.borrower', '<>', '')
                    ->join('products', 'products.id', '=', 'product_items.product_id')
                    ->when($request->filled('hold_q'), function ($q) use ($request) {
                        $term = '%' . $request->hold_q . '%';
                        $q->where(fn ($w) => $w->where('product_items.borrower', 'like', $term)
                            ->orWhere('products.code', 'like', $term)
                            ->orWhere('products.name', 'like', $term));
                    })
                    ->groupBy('product_items.borrower', 'product_items.product_id', 'products.code', 'products.name')
                    ->select(
                        'product_items.borrower',
                        'products.code as product_code',
                        'products.name as product_name',
                        DB::raw('SUM(product_items.quantity) as qty'),
                        DB::raw("SUM(CASE WHEN {$hasLog} THEN product_items.quantity ELSE 0 END) as qty_from_tickets")
                    )
                    ->orderBy('product_items.borrower')
                    ->orderBy('products.code')
                    ->get();
            }
        }
        return view('tickets.index', compact('myTickets', 'pendingApprovals', 'isManagerOrAdmin', 'holdSummary'));
    }

    /**
     * Admin / Kho: lịch sử chuyển giữ hàng runrate (giữ từ kho, chuyển giữa Sales, dịch chuyển khi xuất kho).
     */
    public function holdLogs(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff']) && $user->department !== 'Warehouse') {
            abort(403, 'Bạn không có quyền xem lịch sử giữ hàng.');
        }

        $logs = ProductItemBorrowLog::with(['product_item.product', 'from_user', 'to_user', 'ticket'])
            ->when($request->filled('product_id'), fn ($q) => $q->whereHas('product_item', fn ($p) => $p->where('product_id', $request->product_id)))
            ->when($request->filled('user_id'), fn ($q) => $q->where(fn ($w) => $w->where('from_user_id', $request->user_id)->orWhere('to_user_id', $request->user_id)))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $selectedProduct = $request->filled('product_id') ? Product::find($request->product_id, ['id', 'code', 'name']) : null;
        $users = User::whereIn('id', ProductItemBorrowLog::select('from_user_id')->whereNotNull('from_user_id'))
            ->orWhereIn('id', ProductItemBorrowLog::select('to_user_id')->whereNotNull('to_user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tickets.hold-logs', compact('logs', 'selectedProduct', 'users'));
    }

    /**
     * Admin / Kho: toàn bộ lịch sử ticket của mọi nhân viên.
     */
    public function all(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff'])) {
            abort(403, 'Bạn không có quyền xem toàn bộ lịch sử yêu cầu.');
        }

        $allTickets = Ticket::with(['user', 'target_user', 'approver', 'items.product'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $ticketUsers = User::whereIn('id', Ticket::select('user_id')->distinct())->orderBy('name')->get(['id', 'name']);

        return view('tickets.all', compact('allTickets', 'ticketUsers'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $canChooseBorrower = $user->hasAnyRole(['super_admin', 'admin', 'director', 'warehouse_manager', 'warehouse_staff']) || $user->department === 'BOD';
        $users = User::where('status', 'active')->orderBy('name')->get();
        $products = Product::select('id', 'code', 'name')->orderBy('code')->get();
        $preselectedProductId = $request->query('product_id');
        $preselectedProductCode = null;
        if ($preselectedProductId) {
            $preselectedProductCode = Product::where('id', $preselectedProductId)->value('code');
        }
        return view('tickets.create', compact('products', 'preselectedProductId', 'preselectedProductCode', 'canChooseBorrower', 'users'));
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $canChooseBorrower = $user->hasAnyRole(['super_admin', 'admin', 'director', 'warehouse_manager', 'warehouse_staff']) || $user->department === 'BOD';

        $request->validate([
            'type' => 'required|in:preload,borrow',
            'borrower_user_id' => 'nullable|exists:users,id',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.source' => 'nullable|in:warehouse,sales',
            'items.*.target_user_id' => 'nullable|exists:users,id',
            // Borrow ticket validation (fallback for backward compatibility)
            'source' => 'nullable|in:warehouse,sales',
            'target_user_id' => 'nullable|exists:users,id',
        ]);

        $borrowerUserId = ($canChooseBorrower && $request->filled('borrower_user_id'))
            ? (int) $request->borrower_user_id
            : $user->id;

        if ($request->type === 'borrow') {
            $runrateWarehouseId = $this->runrateWarehouseId();
            if (!$runrateWarehouseId) {
                return back()->withInput()->with('error', 'Chưa cấu hình Kho runrate, không thể tạo yêu cầu giữ hàng.');
            }

            foreach ($request->items as $idx => $item) {
                $itemSource = $item['source'] ?? $request->source ?? 'warehouse';
                $itemTargetUserId = ($itemSource === 'sales') ? ($item['target_user_id'] ?? $request->target_user_id ?? null) : null;

                if ($itemSource === 'sales' && empty($itemTargetUserId)) {
                    return back()->withInput()->with('error', "Dòng thứ " . ($idx + 1) . ": Vui lòng chọn nhân viên muốn giữ hàng.");
                }

                $hasRunrateStock = ProductItem::where('product_id', $item['product_id'])
                    ->where('status', ProductItem::STATUS_IN_STOCK)
                    ->where('warehouse_id', $runrateWarehouseId)
                    ->exists();

                if (!$hasRunrateStock) {
                    $pCode = Product::where('id', $item['product_id'])->value('code') ?? 'N/A';
                    return back()->withInput()->with('error', "Sản phẩm '{$pCode}' chỉ được phép giữ từ Hàng runrate. Hàng dự án và license không được phép giữ.");
                }
            }
        }

        DB::beginTransaction();
        try {
            $senderName = auth()->user()->name;
            $borrower = User::find($borrowerUserId);
            $borrowerName = $borrower ? $borrower->name : $senderName;
            $senderDisplay = ($borrowerUserId !== auth()->id()) ? "{$senderName} (tạo hộ cho {$borrowerName})" : $senderName;

            if ($request->type === 'preload') {
                $ticket = Ticket::create([
                    'code' => Ticket::generateCode(),
                    'user_id' => $borrowerUserId,
                    'type' => 'preload',
                    'source' => null,
                    'target_user_id' => null,
                    'status' => 'pending',
                    'note' => $request->note,
                ]);

                foreach ($request->items as $item) {
                    TicketItem::create([
                        'ticket_id' => $ticket->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'allocated_item_ids' => null,
                    ]);
                }

                // Notify Admins and Procurement/PO team
                $admins = User::whereHas('roles', function ($q) {
                    $q->whereIn('slug', ['super_admin', 'admin', 'purchase_manager']);
                })->get();

                foreach ($admins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'ticket_preload',
                        'title' => 'Yêu cầu đặt hàng Preload mới',
                        'message' => "{$senderDisplay} đã gửi yêu cầu đặt hàng Preload mới {$ticket->code}.",
                        'link' => route('tickets.show', $ticket->id),
                        'icon' => 'fas fa-cart-plus',
                        'color' => 'blue',
                    ]);
                }
            } else {
                // Group borrow items by source & target_user_id
                $grouped = [];
                foreach ($request->items as $item) {
                    $itemSource = $item['source'] ?? $request->source ?? 'warehouse';
                    $itemTargetUserId = ($itemSource === 'sales') ? ($item['target_user_id'] ?? $request->target_user_id ?? null) : null;
                    $groupKey = $itemSource . '_' . ($itemTargetUserId ?: '0');

                    if (!isset($grouped[$groupKey])) {
                        $grouped[$groupKey] = [
                            'source' => $itemSource,
                            'target_user_id' => $itemTargetUserId,
                            'items' => [],
                        ];
                    }
                    $grouped[$groupKey]['items'][] = $item;
                }

                $createdTickets = [];
                foreach ($grouped as $group) {
                    $ticket = Ticket::create([
                        'code' => Ticket::generateCode(),
                        'user_id' => $borrowerUserId,
                        'type' => 'borrow',
                        'source' => $group['source'],
                        'target_user_id' => $group['target_user_id'],
                        'status' => 'pending',
                        'note' => $request->note,
                    ]);

                    foreach ($group['items'] as $item) {
                        TicketItem::create([
                            'ticket_id' => $ticket->id,
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'allocated_item_ids' => null,
                        ]);
                    }

                    if ($borrowerUserId !== auth()->id()) {
                        Notification::create([
                            'user_id' => $borrowerUserId,
                            'type' => 'ticket_borrow_assigned',
                            'title' => 'Phiếu giữ hàng được tạo cho bạn',
                            'message' => "{$senderName} đã tạo phiếu giữ hàng {$ticket->code} đứng tên bạn.",
                            'link' => route('tickets.show', $ticket->id),
                            'icon' => 'fas fa-people-arrows',
                            'color' => 'teal',
                        ]);
                    }

                    if ($ticket->source === 'warehouse') {
                        // Notify Warehouse Team and Admins
                        $warehouseUsers = User::whereHas('roles', function ($q) {
                            $q->whereIn('slug', ['super_admin', 'warehouse_manager', 'warehouse_staff']);
                        })->get();

                        foreach ($warehouseUsers as $whUser) {
                            Notification::create([
                                'user_id' => $whUser->id,
                                'type' => 'ticket_borrow_warehouse',
                                'title' => 'Yêu cầu giữ hàng từ kho',
                                'message' => "{$senderDisplay} đã gửi yêu cầu giữ hàng từ kho {$ticket->code}.",
                                'link' => route('tickets.show', $ticket->id),
                                'icon' => 'fas fa-boxes',
                                'color' => 'teal',
                            ]);
                        }
                    } else {
                        // Notify target salesperson
                        if ($ticket->target_user_id) {
                            Notification::create([
                                'user_id' => $ticket->target_user_id,
                                'type' => 'ticket_borrow_sales',
                                'title' => 'Yêu cầu giữ hàng từ bạn',
                                'message' => "{$senderDisplay} gửi yêu cầu giữ hàng từ đơn của bạn ({$ticket->code}).",
                                'link' => route('tickets.show', $ticket->id),
                                'icon' => 'fas fa-people-arrows',
                                'color' => 'orange',
                            ]);
                        }
                    }

                    $createdTickets[] = $ticket;
                }
            }

            DB::commit();
            $msg = (!empty($createdTickets) && count($createdTickets) > 1)
                ? 'Đã tạo thành công ' . count($createdTickets) . ' yêu cầu giữ hàng theo từng nguồn tương ứng.'
                : 'Tạo yêu cầu (Ticket) thành công.';
            return redirect()->route('tickets.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Display details.
     */
    public function show(Ticket $ticket)
    {
        $ticket->load(['user', 'target_user', 'approver', 'items.product']);
        $isManagerOrAdmin = auth()->user()->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff']);
        
        $canApprove = false;
        if ($ticket->status === 'pending') {
            if ($ticket->type === 'preload') {
                $canApprove = $isManagerOrAdmin;
            } else {
                if ($ticket->source === 'warehouse') {
                    $canApprove = $isManagerOrAdmin;
                } else {
                    $canApprove = ((int)$ticket->target_user_id === (int)auth()->id()) || $isManagerOrAdmin;
                }
            }
        }

        return view('tickets.show', compact('ticket', 'canApprove', 'isManagerOrAdmin'));
    }

    /**
     * Approve ticket.
     */
    public function approve(Ticket $ticket)
    {
        $user = auth()->user();
        $isManagerOrAdmin = $user->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff']);

        if ($ticket->status !== 'pending') {
            return back()->with('error', 'Yêu cầu này không ở trạng thái Chờ duyệt.');
        }

        // Authorize
        $authorized = false;
        if ($ticket->type === 'preload') {
            $authorized = $isManagerOrAdmin;
        } else {
            if ($ticket->source === 'warehouse') {
                $authorized = $isManagerOrAdmin;
            } else {
                $authorized = ((int)$ticket->target_user_id === (int)$user->id) || $isManagerOrAdmin;
            }
        }

        if (!$authorized) {
            return back()->with('error', 'Bạn không có quyền duyệt yêu cầu này.');
        }

        DB::beginTransaction();
        try {
            $approverName = $user->name;

            if ($ticket->type === 'borrow') {
                $runrateWarehouseId = $this->runrateWarehouseId();
                if (!$runrateWarehouseId) {
                    throw new \Exception('Chưa cấu hình Kho runrate, không thể duyệt yêu cầu giữ hàng.');
                }

                // Perform allocation shifting
                foreach ($ticket->items as $ticketItem) {
                    $productId = $ticketItem->product_id;
                    $qtyNeeded = $ticketItem->quantity;

                    // Thiết bị được phép chuyển cho người yêu cầu: Giữ từ kho → chỉ thiết bị chưa ai giữ;
                    // Giữ từ Sales → thiết bị Sales đó đang giữ (hoặc hàng chưa phân bổ của đơn Sales đó).
                    // Phải kiểm tra lại lúc duyệt vì serial chọn lúc tạo ticket có thể đã bị người khác giữ.
                    $targetUser = $ticket->source === 'warehouse' ? null : User::find($ticket->target_user_id);
                    $targetName = $targetUser ? $targetUser->name : '';
                    $isAllowed = function ($item) use ($ticket, $targetName) {
                        if ($ticket->source === 'warehouse') {
                            return empty($item->borrower);
                        }
                        if ($item->borrower === $targetName) {
                            return true;
                        }
                        return empty($item->borrower) && $item->import && $item->import->purchaseOrder && $item->import->purchaseOrder->sale && (int)$item->import->purchaseOrder->sale->user_id === (int)$ticket->target_user_id;
                    };

                    $candidates = ProductItem::with(['import.purchaseOrder.sale'])
                        ->where('product_id', $productId)
                        ->where('status', ProductItem::STATUS_IN_STOCK)
                        ->where('warehouse_id', $runrateWarehouseId)
                        ->notCqLockedForOthers() // Hàng yêu cầu CQ đặt riêng cho đơn không được giữ / chuyển
                        ->get()
                        ->filter($isAllowed);

                    // Ưu tiên serial đã chọn lúc tạo ticket (nếu vẫn còn khả dụng), thiếu thì tự lấy thêm
                    // theo PO: lấy gọn trong 1 PO, tránh suất giữ bị chia lẻ nhiều PO
                    $preselectedIds = array_map('intval', (array) ($ticketItem->allocated_item_ids ?? []));
                    $itemsToShift = $candidates->filter(fn ($item) => in_array($item->id, $preselectedIds, true))
                        ->concat($this->orderByFewestPos(
                            $candidates->reject(fn ($item) => in_array($item->id, $preselectedIds, true)),
                            max(0, $qtyNeeded - count($preselectedIds))
                        ))
                        ->take($qtyNeeded)
                        ->values();

                    if ($itemsToShift->count() < $qtyNeeded) {
                        throw new \Exception("Không đủ thiết bị khả dụng trong kho để thực hiện giữ hàng (Yêu cầu: {$qtyNeeded}, Khả dụng: " . $itemsToShift->count() . ").");
                    }

                    $allocatedIds = [];
                    foreach ($itemsToShift as $item) {
                        $oldBorrowerName = $item->borrower ?: ($ticket->source === 'sales' ? User::find($ticket->target_user_id)->name : null);
                        $oldBorrowerUserId = null;
                        if ($oldBorrowerName) {
                            $oldUser = User::where('name', $oldBorrowerName)->first();
                            $oldBorrowerUserId = $oldUser ? $oldUser->id : null;
                        }

                        // Shift allocation to borrower
                        $item->update(['borrower' => $ticket->user->name]);
                        $allocatedIds[] = $item->id;

                        // Create log
                        ProductItemBorrowLog::create([
                            'product_item_id' => $item->id,
                            'from_user_id' => $oldBorrowerUserId,
                            'to_user_id' => $ticket->user_id,
                            'ticket_id' => $ticket->id,
                            'note' => $ticket->source === 'warehouse' ? 'Giữ từ kho' : "Giữ từ Sales: {$oldBorrowerName}",
                        ]);
                    }

                    $ticketItem->update(['allocated_item_ids' => $allocatedIds]);
                }
            }

            if ($ticket->type === 'preload') {
                $existingPr = \App\Models\SaleOrderRequest::where('note', 'like', '%' . $ticket->code . '%')->first();
                if (!$existingPr) {
                    $pr = \App\Models\SaleOrderRequest::createWithGeneratedCode([
                        'sale_id' => null,
                        'source_type' => 'ticket',
                        'ticket_id' => $ticket->id,
                        'created_by' => $ticket->user_id,
                        'note' => "Yêu cầu Preload được duyệt từ Ticket " . $ticket->code . ". Lý do/Ghi chú: " . $ticket->note,
                        'sent_at' => now(),
                        'status' => \App\Models\SaleOrderRequest::STATUS_PROCESSING,
                    ]);

                    foreach ($ticket->items as $item) {
                        $vendorId = null;
                        $vendorName = 'Unknown Vendor';
                        
                        $lastPrItem = \App\Models\SaleOrderRequestItem::where('product_id', $item->product_id)
                            ->whereNotNull('vendor_id')
                            ->latest()
                            ->first();
                        if ($lastPrItem) {
                            $vendorId = $lastPrItem->vendor_id;
                            $vendorName = $lastPrItem->vendor;
                        } else {
                            $anySupplier = \App\Models\Supplier::first();
                            if ($anySupplier) {
                                $vendorId = $anySupplier->id;
                                $vendorName = $anySupplier->name;
                            }
                        }

                        \App\Models\SaleOrderRequestItem::create([
                            'sale_order_request_id' => $pr->id,
                            'sale_item_id' => null,
                            'vendor_id' => $vendorId,
                            'vendor' => $vendorName,
                            'type' => 'HW',
                            'needs_cq' => false,
                            'part_number' => $item->product->code,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'unit' => $item->product->unit ?: 'Cái',
                            'si_name' => 'Preload Order',
                            'eu_name_mst' => 'Preload Order',
                        ]);
                    }
                }
            }

            // Update ticket
            $ticket->update([
                'status' => 'approved',
                'approver_id' => $user->id,
            ]);

            // Notify borrower
            Notification::create([
                'user_id' => $ticket->user_id,
                'type' => 'ticket_approved',
                'title' => 'Yêu cầu giữ/đặt hàng đã được duyệt',
                'message' => "Yêu cầu {$ticket->code} của bạn đã được duyệt bởi {$approverName}.",
                'link' => route('tickets.show', $ticket->id),
                'icon' => 'fas fa-check-circle',
                'color' => 'green',
            ]);

            DB::commit();
            return redirect()->route('tickets.show', $ticket->id)->with('success', 'Duyệt yêu cầu thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Duyệt thất bại: ' . $e->getMessage());
        }
    }

    /**
     * Reject ticket.
     */
    public function reject(Request $request, Ticket $ticket)
    {
        $request->validate([
            'reject_reason' => 'required|string|max:500',
        ]);

        $user = auth()->user();
        $isManagerOrAdmin = $user->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff']);

        if ($ticket->status !== 'pending') {
            return back()->with('error', 'Yêu cầu này không ở trạng thái Chờ duyệt.');
        }

        // Authorize
        $authorized = false;
        if ($ticket->type === 'preload') {
            $authorized = $isManagerOrAdmin;
        } else {
            if ($ticket->source === 'warehouse') {
                $authorized = $isManagerOrAdmin;
            } else {
                $authorized = ((int)$ticket->target_user_id === (int)$user->id) || $isManagerOrAdmin;
            }
        }

        if (!$authorized) {
            return back()->with('error', 'Bạn không có quyền từ chối yêu cầu này.');
        }

        $ticket->update([
            'status' => 'rejected',
            'approver_id' => $user->id,
            'reject_reason' => $request->reject_reason,
        ]);

        // Notify borrower
        Notification::create([
            'user_id' => $ticket->user_id,
            'type' => 'ticket_rejected',
            'title' => 'Yêu cầu của bạn bị từ chối',
            'message' => "Yêu cầu {$ticket->code} đã bị từ chối. Lý do: {$request->reject_reason}",
            'link' => route('tickets.show', $ticket->id),
            'icon' => 'fas fa-times-circle',
            'color' => 'red',
        ]);

        return redirect()->route('tickets.show', $ticket->id)->with('success', 'Đã từ chối yêu cầu.');
    }

    /**
     * Fetch holders list for a product (AJAX).
     */
    public function getHolders(Request $request)
    {
        $productId = $request->query('product_id');
        if (!$productId) {
            return response()->json(['success' => false, 'warehouses' => [], 'sales' => []]);
        }

        $runrateWarehouseId = $this->runrateWarehouseId();
        if (!$runrateWarehouseId) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa cấu hình Kho runrate.',
                'warehouses' => [],
                'sales' => [],
            ], 422);
        }

        $items = ProductItem::with([
            'warehouse',
            'import.purchaseOrder.sale.user',
            // PO item is an accessor on ProductItem, not an Eloquent relationship.
            // Load the actual relation through the import's purchase order so
            // isProjectItem() can determine whether an item is eligible to borrow.
            'import.purchaseOrder.items.saleOrderRequestItem'
        ])
        ->where('product_id', $productId)
        ->where('status', ProductItem::STATUS_IN_STOCK)
        ->where('warehouse_id', $runrateWarehouseId)
        ->notCqLockedForOthers() // Hàng yêu cầu CQ đặt riêng cho đơn không nằm trong nguồn giữ hàng
        ->get();

        $warehouseStock = [];
        $salesStock = [];

        foreach ($items as $item) {
            $holderName = null;
            $holderUserId = null;

            if (!empty($item->borrower)) {
                $holderName = $item->borrower;
                $user = User::where('name', $holderName)->first();
                $holderUserId = $user ? $user->id : null;
            } elseif ($item->import && $item->import->purchaseOrder && $item->import->purchaseOrder->sale && $item->import->purchaseOrder->sale->user) {
                $user = $item->import->purchaseOrder->sale->user;
                $holderName = $user->name;
                $holderUserId = $user->id;
            }

            $isPlaceholder = $item->isNoSku();
            $serialInfo = [
                'id' => $item->id,
                'sku' => $item->sku,
                'is_placeholder' => $isPlaceholder
            ];

            // Hàng runrate giữ theo model + số lượng: gom theo người giữ / kho, không tách theo PO.
            // PO chỉ dùng để tham khảo (S/N thực tế xuất ra sẽ tự xác định PO nguồn).
            $poCode = $item->purchase_order_code ?: ($item->import?->po_code ?: null);
            $qty = $item->quantity ?: 1;

            if ($holderName) {
                $key = $holderUserId ?: $holderName;
                $salesStock[$key] ??= [
                    'user_id' => $holderUserId,
                    'name' => $holderName,
                    'qty' => 0,
                    'po_breakdown' => [],
                    'item_ids' => [],
                    'items' => []
                ];
                $bucket = &$salesStock[$key];
            } else {
                $key = $item->warehouse_id;
                $warehouseStock[$key] ??= [
                    'warehouse_id' => $item->warehouse_id,
                    'name' => $item->warehouse ? $item->warehouse->name : 'Kho chung',
                    'qty' => 0,
                    'po_breakdown' => [],
                    'item_ids' => [],
                    'items' => []
                ];
                $bucket = &$warehouseStock[$key];
            }

            $poKey = $poCode ?: 'Chưa gắn PO';
            $bucket['qty'] += $qty;
            $bucket['po_breakdown'][$poKey] = ($bucket['po_breakdown'][$poKey] ?? 0) + $qty;
            $bucket['item_ids'][] = $item->id;
            $bucket['items'][] = $serialInfo;
            unset($bucket);
        }

        $formatBreakdown = function (array $bucket) {
            $bucket['po_breakdown'] = collect($bucket['po_breakdown'])
                ->map(fn ($qty, $po) => ['po_code' => $po, 'qty' => $qty])
                ->values()
                ->all();
            return $bucket;
        };

        return response()->json([
            'success' => true,
            'warehouses' => array_map($formatBreakdown, array_values($warehouseStock)),
            'sales' => array_map($formatBreakdown, array_values($salesStock))
        ]);
    }

    /**
     * Search products dynamically (AJAX).
     */
    public function searchProducts(Request $request)
    {
        $q = $request->query('q');
        if (empty($q)) {
            return response()->json([]);
        }

        $products = Product::where(function ($query) use ($q) {
                $query->where('code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%");
            })
            ->select('id', 'code', 'name')
            ->limit(20)
            ->get();

        return response()->json($products);
    }
}
