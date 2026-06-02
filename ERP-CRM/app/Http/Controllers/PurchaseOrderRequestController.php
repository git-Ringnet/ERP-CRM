<?php

namespace App\Http\Controllers;

use App\Models\SaleOrderRequest;
use App\Models\SaleOrderRequestItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CurrencyService;

class PurchaseOrderRequestController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;

        // Phân quyền: Duyệt yêu cầu (PR)
        $this->middleware('permission:view_pr_approvals')->only(['index', 'deletedList']);
        $this->middleware('permission:edit_pr_approvals')->only(['verify', 'updateNote', 'destroy', 'restore']);

        // Phân quyền: Gom đơn cần đặt
        $this->middleware('permission:view_needs_ordering')->only(['needsOrdering']);
        $this->middleware('permission:create_needs_ordering')->only(['storeFromPr', 'cancelItem', 'restoreItem']);
    }

    /**
     * Danh sách PR cho PO
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $code = $request->input('code');
        $sale_code = $request->input('sale_code');
        $note = $request->input('note');

        $query = SaleOrderRequest::with(['sale', 'creator', 'items.vendor', 'attachments']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($code) {
            $query->where('code', 'like', '%' . $code . '%');
        }

        if ($sale_code) {
            $query->whereHas('sale', function ($q) use ($sale_code) {
                $q->where('code', 'like', '%' . $sale_code . '%');
            });
        }

        if ($note) {
            $query->where('note', 'like', '%' . $note . '%');
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);
        $statusLabels = SaleOrderRequest::getStatusLabels();

        return view('purchasing.pr-list', compact('requests', 'statusLabels'));
    }

    /**
     * Duyệt hoặc trả về PR
     */
    public function verify(Request $request, $id)
    {
        $pr = SaleOrderRequest::findOrFail($id);
        $action = $request->input('action'); // approve or reject

        if ($action === 'approve') {
            $pr->status = SaleOrderRequest::STATUS_PROCESSING;
            $pr->rejection_note = null;
            $pr->save();
            return back()->with('success', 'Đã duyệt yêu cầu #' . $pr->code . '. Các sản phẩm đã sẵn sàng để đặt hàng.');
        } elseif ($action === 'reject') {
            $request->validate(['rejection_note' => 'required|string|max:1000']);
            $pr->status = SaleOrderRequest::STATUS_NEED_INFO;
            $pr->rejection_note = $request->input('rejection_note');
            $pr->save();

            // Gửi thông báo cho Sales (người tạo PR) biết PR bị trả về
            $this->notifySalesNeedInfo($pr);

            return back()->with('success', 'Đã trả yêu cầu #' . $pr->code . ' về cho bộ phận Sales.');
        }

        return back()->with('error', 'Hành động không hợp lệ.');
    }

    public function updateNote(Request $request, $id)
    {
        $pr = SaleOrderRequest::findOrFail($id);
        $request->validate(['note' => 'nullable|string|max:2000']);
        
        $pr->note = $request->input('note');
        $pr->save();

        return back()->with('success', 'Đã cập nhật ghi chú cho yêu cầu #' . $pr->code);
    }

    /**
     * 🔥 needsOrdering() (CORE)
     * Màn hình gom dữ liệu đặt hàng
     */
    public function needsOrdering()
    {
        // Lấy các PR items từ các PR đang chờ xử lý (LOẠI BỎ items đã bị hủy)
        $items = SaleOrderRequestItem::whereHas('saleOrderRequest', function($q) {
                $q->whereIn('status', [SaleOrderRequest::STATUS_SUBMITTED, SaleOrderRequest::STATUS_PROCESSING]);
            })
            ->where('is_cancelled', false)
            ->with(['saleOrderRequest.sale', 'vendor', 'product', 'purchaseOrderItems', 'saleItem'])
            ->get();

        // Lấy các items đã bị hủy (để hiển thị riêng)
        $cancelledItems = SaleOrderRequestItem::whereHas('saleOrderRequest', function($q) {
                $q->whereIn('status', [SaleOrderRequest::STATUS_SUBMITTED, SaleOrderRequest::STATUS_PROCESSING, SaleOrderRequest::STATUS_COMPLETED]);
            })
            ->where('is_cancelled', true)
            ->with(['saleOrderRequest.sale', 'vendor'])
            ->get();

        // Gom nhóm theo Vendor -> SaleOrderRequest
        $vendorGroups = [];
        
        foreach ($items as $item) {
            $vName = $item->vendor?->name ?? $item->vendor ?? 'Unknown Vendor';
            $vId = $item->vendor_id;

            // Nếu vId null, thử tìm theo name trong DB để có ID hợp lệ
            if (!$vId) {
                $found = Supplier::where('name', $vName)->first();
                $vId = $found ? $found->id : 'name-' . md5($vName);
            }

            if (!isset($vendorGroups[$vId])) {
                $vendorGroups[$vId] = [
                    'id' => $vId,
                    'name' => $vName,
                    'sales_orders' => []
                ];
            }

            $pr = $item->saleOrderRequest;
            $soKey = $pr->id;

            if (!isset($vendorGroups[$vId]['sales_orders'][$soKey])) {
                // Lấy mã SO thực tế nếu có, không thì lấy mã PR
                $displayCode = ($pr->sale && $pr->sale->code) ? $pr->sale->code : $pr->code;
                
                $vendorGroups[$vId]['sales_orders'][$soKey] = [
                    'id' => $pr->id,
                    'code' => $displayCode,
                    'pr_code' => $pr->code, // Giữ lại mã PR để tham chiếu
                    'total_usd' => 0,
                    'requested' => 0,
                    'ordered' => 0,
                    'products' => []
                ];
            }

            $ordered = $item->ordered_quantity_total;
            $remaining = max(0, $item->quantity - $ordered);

            // Chỉ thêm sản phẩm nếu còn cần đặt hàng
            if ($remaining > 0.001) {
                // Tính unit price USD từ SaleItem
                $unitPriceUsd = 0;
                if ($item->saleItem) {
                    $si = $item->saleItem;
                    $rate = (float)($si->exchange_rate ?: ($pr->sale?->exchange_rate ?: 24500));
                    
                    if ($si->estimated_cost_usd > 0) {
                        $unitPriceUsd = (float)$si->estimated_cost_usd;
                    } elseif ($si->usd_price > 0) {
                        $unitPriceUsd = (float)($si->usd_price * (1 - (($si->discount_rate ?? 0) / 100)) * (1 + (($si->import_cost_rate ?? 0) / 100)));
                    } elseif ($si->cost_total > 0 && $item->quantity > 0) {
                        // Fallback: Back-calculate from VND cost
                        $unitPriceUsd = ($si->cost_total / $item->quantity) / $rate;
                    }
                }

                $vendorGroups[$vId]['sales_orders'][$soKey]['products'][] = [
                    'id' => $item->id,
                    'part_number' => $item->part_number,
                    'unit' => $item->unit,
                    'requested' => $item->quantity,
                    'ordered' => $ordered,
                    'remaining' => $remaining,
                    'unit_price_usd' => $unitPriceUsd,
                ];

                $vendorGroups[$vId]['sales_orders'][$soKey]['total_usd'] += ($unitPriceUsd * $remaining);
                $vendorGroups[$vId]['sales_orders'][$soKey]['requested'] += $item->quantity;
                $vendorGroups[$vId]['sales_orders'][$soKey]['ordered'] += $ordered;
            }
        }

        // Lọc bỏ các SO không còn sản phẩm nào cần đặt và sắp xếp
        foreach ($vendorGroups as $vId => &$vGroup) {
            $vGroup['sales_orders'] = array_filter($vGroup['sales_orders'], function($so) {
                return count($so['products']) > 0;
            });
            
            // Nếu Vendor không còn SO nào, đánh dấu để xóa
            if (empty($vGroup['sales_orders'])) {
                unset($vendorGroups[$vId]);
            }
        }

        $currencies = \App\Models\Currency::where('is_active', 1)->get();
        $baseCurrencyId = \App\Models\Currency::getBaseCurrencyId();

        return view('purchasing.needs-ordering', compact('vendorGroups', 'currencies', 'baseCurrencyId', 'cancelledItems'));
    }

    /**
     * 🔥 storeFromPr() (QUAN TRỌNG)
     * Tạo PO từ danh sách đã gom
     */
    public function storeFromPr(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.pr_item_id' => 'required|exists:sale_order_request_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:2000',
            'currency_id' => 'required|exists:currencies,id',
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $vendor = Supplier::findOrFail($validated['vendor_id']);
            
            // 0. Xác định tiền tệ ưu tiên
            $poCurrencyId = $validated['currency_id'];
            $exchangeRate = (float)$validated['exchange_rate'];

            // Fetch items for later use in price calculation
            $prItemIds = array_column($validated['items'], 'pr_item_id');
            $prItemsForCurrency = SaleOrderRequestItem::whereIn('id', $prItemIds)->with('saleItem')->get();

            // 1. Tạo Purchase Order
            $po = PurchaseOrder::create([
                'code' => PurchaseOrder::generateCode(),
                'supplier_id' => $vendor->id,
                'order_date' => now(),
                'status' => 'pending_approval', // Chờ duyệt PO
                'created_by' => auth()->id(),
                'note' => $validated['note'],
                'currency_id' => $poCurrencyId,
                'exchange_rate' => $exchangeRate,
            ]);

            $affectedPrs = [];

            // 2. Xử lý từng item
            foreach ($validated['items'] as $itemData) {
                // 🔥 Concurrency Control: Lock PR item để tính toán chính xác
                $prItem = $prItemsForCurrency->firstWhere('id', $itemData['pr_item_id']);
                
                // Re-lock for safety if needed, or use the one from collection
                $prItem = SaleOrderRequestItem::where('id', $prItem->id)->lockForUpdate()->firstOrFail();

                // Tính toán lại Remaining thực tế
                $orderedTotal = $prItem->purchaseOrderItems()->sum('ordered_quantity');
                $remaining = $prItem->quantity - $orderedTotal;

                if ($itemData['quantity'] > $remaining + 0.001) {
                    throw new \Exception("Số lượng đặt hàng ({$itemData['quantity']}) vượt quá số lượng còn lại ({$remaining}) của mặt hàng {$prItem->part_number} trong PR #{$prItem->saleOrderRequest->code}");
                }

                // Tự động tính giá kho từ P&L nếu có (chỉ dùng cho tham khảo)
                $warehousePrice = 0;
                
                if ($prItem->saleItem) {
                    $saleItem = $prItem->saleItem;
                    
                    // Tính giá kho tạm tính USD từ P&L
                    $estUsd = $saleItem->estimated_cost_usd > 0 
                        ? (float)$saleItem->estimated_cost_usd 
                        : (float)($saleItem->usd_price * (1 - (($saleItem->discount_rate ?? 0) / 100)) * (1 + (($saleItem->import_cost_rate ?? 0) / 100)));

                    $warehousePrice = $estUsd;
                }

                // Giá mua thực tế mặc định = 0, cho người dùng tự điền
                $unitPrice = 0;

                // Tạo Purchase Order Item
                $itemTotal = 0; // Sẽ được cập nhật khi user điền giá

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'sale_order_request_item_id' => $prItem->id,
                    'product_id' => $prItem->product_id,
                    'product_name' => $prItem->part_number,
                    'ordered_quantity' => $itemData['quantity'],
                    'quantity' => $itemData['quantity'], // quantity dùng cho nhập kho
                    'unit' => $prItem->unit ?? 'Cái',
                    'unit_price' => $unitPrice,
                    'warehouse_unit_price' => $warehousePrice,
                    'total' => $itemTotal,
                    'vat_percent' => 0,
                    'vat_amount' => 0,
                    'status' => 'ordered',
                ]);

                $affectedPrs[$prItem->sale_order_request_id] = $prItem->saleOrderRequest;
            }

            // 3. Tính toán lại tổng tiền cho PO
            $po->load(['items', 'currency']);
            $po->calculateTotals();
            $po->updateDebt();
            $po->save();

            // 4. Cập nhật trạng thái cho các PR liên quan
            foreach ($affectedPrs as $pr) {
                $pr->checkAndUpdateStatus();
            }

            DB::commit();
            return redirect()->route('purchase-orders.show', $po->id)
                ->with('success', 'Đã tạo Đơn đặt hàng ' . $po->code . ' thành công từ các yêu cầu.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating PO from PR: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Hủy sản phẩm ở Gom đơn → revert PR về trạng thái submitted
     */
    public function cancelItem(Request $request, $itemId)
    {
        $item = SaleOrderRequestItem::findOrFail($itemId);
        $pr = $item->saleOrderRequest;

        DB::beginTransaction();
        try {
            // Xóa tất cả PO items liên kết với item này (nếu có)
            $poItems = $item->purchaseOrderItems;
            foreach ($poItems as $poItem) {
                $po = $poItem->purchaseOrder;
                $poItem->delete();
                
                // Recalculate PO totals
                $po->load('items');
                if ($po->items->isEmpty()) {
                    $po->update(['status' => 'cancelled']);
                } else {
                    $po->calculateTotals();
                    $po->updateDebt();
                    $po->save();
                }
            }

            // Đánh dấu item là đã hủy
            $item->update(['is_cancelled' => true]);

            // Revert PR status
            $pr->checkAndUpdateStatus();

            DB::commit();
            return back()->with('success', 'Đã hủy sản phẩm "' . $item->part_number . '". Yêu cầu sẽ được trả về Duyệt PR.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling PR item: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Khôi phục sản phẩm đã hủy ở Gom đơn
     */
    public function restoreItem(Request $request, $itemId)
    {
        $item = SaleOrderRequestItem::findOrFail($itemId);

        $item->update(['is_cancelled' => false]);
        $item->saleOrderRequest->checkAndUpdateStatus();

        return back()->with('success', 'Đã khôi phục sản phẩm "' . $item->part_number . '" về danh sách cần đặt.');
    }

    /**
     * Xóa mềm PR (SaleOrderRequest)
     */
    public function destroy($id, Request $request)
    {
        $pr = SaleOrderRequest::findOrFail($id);
        
        $this->authorize('delete', $pr);

        if (!in_array($pr->status, [SaleOrderRequest::STATUS_DRAFT, SaleOrderRequest::STATUS_SUBMITTED, SaleOrderRequest::STATUS_NEED_INFO])) {
            return back()->with('error', 'Không thể xóa yêu cầu đặt hàng đã được duyệt hoặc đang xử lý!');
        }

        DB::transaction(function () use ($pr, $request) {
            $pr->delete_reason = $request->input('reason');
            $pr->save(); // Save the delete reason to the database
            $pr->delete();
        });

        return redirect()->route('purchase-requests.index')
            ->with('success', 'Đã xóa yêu cầu đặt hàng #' . $pr->code . '!');
    }

    /**
     * Danh sách PR đã bị xóa mềm
     */
    public function deletedList(Request $request)
    {
        $code = $request->input('code');
        $sale_code = $request->input('sale_code');
        $note = $request->input('note');

        $query = SaleOrderRequest::onlyTrashed()->with(['deleteLog', 'sale', 'creator', 'items.vendor', 'attachments']);

        if ($code) {
            $query->where('code', 'like', '%' . $code . '%');
        }

        if ($sale_code) {
            $query->whereHas('sale', function ($q) use ($sale_code) {
                $q->where('code', 'like', '%' . $sale_code . '%');
            });
        }

        if ($note) {
            $query->where('note', 'like', '%' . $note . '%');
        }

        $requests = $query->orderBy('deleted_at', 'desc')->paginate(20);
        $statusLabels = SaleOrderRequest::getStatusLabels();

        return view('purchasing.pr-deleted-list', compact('requests', 'statusLabels'));
    }

    /**
     * Khôi phục PR đã bị xóa mềm
     */
    public function restore($id)
    {
        $pr = SaleOrderRequest::onlyTrashed()->findOrFail($id);
        
        $this->authorize('delete', $pr);

        DB::transaction(function () use ($pr) {
            // Xóa lý do xóa khi khôi phục
            $pr->delete_reason = null;
            $pr->save();
            $pr->restore();

            // Ghi nhận audit log qua ActivityLogService
            app(\App\Services\ActivityLogService::class)->log(
                'restored',
                $pr,
                null,
                "Khôi phục Yêu cầu đặt hàng #{$pr->code}"
            );
        });

        return redirect()->route('purchase-requests.index')
            ->with('success', 'Đã khôi phục thành công yêu cầu đặt hàng #' . $pr->code . '!');
    }

    /**
     * Gửi thông báo cho Sales khi PR bị trả về "Thiếu thông tin"
     */
    private function notifySalesNeedInfo(SaleOrderRequest $pr)
    {
        $pr->load('sale');

        // Thông báo cho người tạo PR
        $creatorId = $pr->created_by;
        if (!$creatorId || $creatorId === auth()->id()) {
            return;
        }

        $approverName = auth()->user()->name ?? 'PO Team';
        $saleCode = $pr->sale->code ?? 'N/A';

        \App\Models\Notification::create([
            'user_id' => $creatorId,
            'type' => 'order_request_need_info',
            'title' => 'Yêu cầu đặt hàng cần bổ sung thông tin',
            'message' => "{$approverName} đã trả yêu cầu đặt hàng ({$pr->code}) cho đơn {$saleCode} về vì: \"{$pr->rejection_note}\". Vui lòng chỉnh sửa và gửi lại.",
            'link' => $pr->sale ? route('sales.show', $pr->sale_id) : null,
            'icon' => 'fas fa-exclamation-triangle',
            'color' => 'orange',
        ]);
    }
}
