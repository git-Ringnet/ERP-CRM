<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketItem;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductItemBorrowLog;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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

        return view('tickets.index', compact('myTickets', 'pendingApprovals', 'isManagerOrAdmin'));
    }

    public function create(Request $request)
    {
        $products = Product::select('id', 'code')->orderBy('code')->get();
        $preselectedProductId = $request->query('product_id');
        $preselectedProductCode = null;
        if ($preselectedProductId) {
            $preselectedProductCode = Product::where('id', $preselectedProductId)->value('code');
        }
        return view('tickets.create', compact('products', 'preselectedProductId', 'preselectedProductCode'));
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:preload,borrow',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selected_serial_ids' => 'nullable|array',
            'items.*.selected_serial_ids.*' => 'exists:product_items,id',
            // Borrow ticket validation
            'source' => 'required_if:type,borrow|in:warehouse,sales',
            'target_user_id' => 'required_if:source,sales|nullable|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $ticket = Ticket::create([
                'code' => Ticket::generateCode(),
                'user_id' => auth()->id(),
                'type' => $request->type,
                'source' => $request->type === 'borrow' ? $request->source : null,
                'target_user_id' => ($request->type === 'borrow' && $request->source === 'sales') ? $request->target_user_id : null,
                'status' => 'pending',
                'note' => $request->note,
            ]);

            foreach ($request->items as $item) {
                $allocatedIds = null;
                if ($request->type === 'borrow' && isset($item['selected_serial_ids'])) {
                    $allocatedIds = $item['selected_serial_ids'];
                }

                TicketItem::create([
                    'ticket_id' => $ticket->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'allocated_item_ids' => $allocatedIds,
                ]);
            }

            // --- Notifications ---
            $senderName = auth()->user()->name;
            if ($ticket->type === 'preload') {
                // Notify Admins and Procurement/PO team
                $admins = User::whereHas('roles', function ($q) {
                    $q->whereIn('slug', ['super_admin', 'admin', 'purchase_manager']);
                })->get();

                foreach ($admins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'ticket_preload',
                        'title' => 'Yêu cầu đặt hàng Preload mới',
                        'message' => "{$senderName} đã gửi yêu cầu đặt hàng Preload mới {$ticket->code}.",
                        'link' => route('tickets.show', $ticket->id),
                        'icon' => 'fas fa-cart-plus',
                        'color' => 'blue',
                    ]);
                }
            } else {
                // Borrow Request
                if ($ticket->source === 'warehouse') {
                    // Notify Warehouse Team and Admins
                    $warehouseUsers = User::whereHas('roles', function ($q) {
                        $q->whereIn('slug', ['super_admin', 'warehouse_manager', 'warehouse_staff']);
                    })->get();

                    foreach ($warehouseUsers as $whUser) {
                        Notification::create([
                            'user_id' => $whUser->id,
                            'type' => 'ticket_borrow_warehouse',
                            'title' => 'Yêu cầu mượn hàng từ kho',
                            'message' => "{$senderName} đã gửi yêu cầu mượn hàng từ kho {$ticket->code}.",
                            'link' => route('tickets.show', $ticket->id),
                            'icon' => 'fas fa-boxes',
                            'color' => 'teal',
                        ]);
                    }
                } else {
                    // Notify target salesperson
                    Notification::create([
                        'user_id' => $ticket->target_user_id,
                        'type' => 'ticket_borrow_sales',
                        'title' => 'Yêu cầu mượn hàng từ bạn',
                        'message' => "{$senderName} gửi yêu cầu mượn hàng từ đơn của bạn ({$ticket->code}).",
                        'link' => route('tickets.show', $ticket->id),
                        'icon' => 'fas fa-people-arrows',
                        'color' => 'orange',
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('tickets.index')->with('success', 'Tạo yêu cầu (Ticket) thành công.');

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
                // Perform allocation shifting
                foreach ($ticket->items as $ticketItem) {
                    $productId = $ticketItem->product_id;
                    $qtyNeeded = $ticketItem->quantity;

                    $itemsToShift = collect();

                    // Check if specific serials were selected during creation
                    if (!empty($ticketItem->allocated_item_ids)) {
                        $itemsToShift = ProductItem::whereIn('id', $ticketItem->allocated_item_ids)
                            ->where('status', ProductItem::STATUS_IN_STOCK)
                            ->get();
                    }

                    // Fallback to auto-allocation if no items were pre-selected
                    if ($itemsToShift->isEmpty()) {
                        if ($ticket->source === 'warehouse') {
                            // Find general in-stock items with no borrower
                            $itemsToShift = ProductItem::where('product_id', $productId)
                                ->where('status', ProductItem::STATUS_IN_STOCK)
                                ->where(function ($q) {
                                    $q->whereNull('borrower')->orWhere('borrower', '');
                                })
                                ->limit($qtyNeeded)
                                ->get();
                        } else {
                            // Find items held by target salesperson
                            $targetUser = User::find($ticket->target_user_id);
                            $targetName = $targetUser ? $targetUser->name : '';

                            // Get candidate items
                            $candidates = ProductItem::with(['import.purchaseOrder.sale'])
                                ->where('product_id', $productId)
                                ->where('status', ProductItem::STATUS_IN_STOCK)
                                ->get();

                            $filtered = $candidates->filter(function ($item) use ($targetName, $ticket) {
                                if ($item->borrower === $targetName) {
                                    return true;
                                }
                                if (empty($item->borrower) && $item->import && $item->import->purchaseOrder && $item->import->purchaseOrder->sale && (int)$item->import->purchaseOrder->sale->user_id === (int)$ticket->target_user_id) {
                                    return true;
                                }
                                return false;
                            });

                            $itemsToShift = $filtered->take($qtyNeeded);
                        }
                    }

                    if ($itemsToShift->count() < $qtyNeeded) {
                        throw new \Exception("Không đủ thiết bị khả dụng trong kho để thực hiện mượn hàng (Yêu cầu: {$qtyNeeded}, Khả dụng: " . $itemsToShift->count() . ").");
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
                            'note' => $ticket->source === 'warehouse' ? 'Mượn từ kho' : "Mượn từ Sales: {$oldBorrowerName}",
                        ]);
                    }

                    $ticketItem->update(['allocated_item_ids' => $allocatedIds]);
                }
            }

            if ($ticket->type === 'preload') {
                $existingPr = \App\Models\SaleOrderRequest::where('note', 'like', '%' . $ticket->code . '%')->first();
                if (!$existingPr) {
                    $pr = \App\Models\SaleOrderRequest::create([
                        'code' => \App\Models\SaleOrderRequest::generateCode(),
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
                'title' => 'Yêu cầu mượn/đặt hàng đã được duyệt',
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

        $items = ProductItem::with([
            'warehouse',
            'import.purchaseOrder.sale.user'
        ])
        ->where('product_id', $productId)
        ->where('status', ProductItem::STATUS_IN_STOCK)
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

            if ($holderName) {
                $key = $holderUserId ?: $holderName;
                if (!isset($salesStock[$key])) {
                    $salesStock[$key] = [
                        'user_id' => $holderUserId,
                        'name' => $holderName,
                        'qty' => 0,
                        'items' => []
                    ];
                }
                $salesStock[$key]['qty'] += $item->quantity ?: 1;
                $salesStock[$key]['items'][] = $serialInfo;
            } else {
                $warehouseId = $item->warehouse_id;
                $warehouseName = $item->warehouse ? $item->warehouse->name : 'Kho chung';
                if (!isset($warehouseStock[$warehouseId])) {
                    $warehouseStock[$warehouseId] = [
                        'warehouse_id' => $warehouseId,
                        'name' => $warehouseName,
                        'qty' => 0,
                        'items' => []
                    ];
                }
                $warehouseStock[$warehouseId]['qty'] += $item->quantity ?: 1;
                $warehouseStock[$warehouseId]['items'][] = $serialInfo;
            }
        }

        return response()->json([
            'success' => true,
            'warehouses' => array_values($warehouseStock),
            'sales' => array_values($salesStock)
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

        $products = Product::where('code', 'like', "%{$q}%")
            ->select('id', 'code')
            ->limit(20)
            ->get();

        return response()->json($products);
    }
}
