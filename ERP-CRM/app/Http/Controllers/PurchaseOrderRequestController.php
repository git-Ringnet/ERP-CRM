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
    }

    /**
     * Danh sách PR cho PO
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $query = SaleOrderRequest::with(['sale', 'creator', 'items']);

        if ($status) {
            $query->where('status', $status);
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
            return back()->with('success', 'Đã trả yêu cầu #' . $pr->code . ' về cho bộ phận Sales.');
        }

        return back()->with('error', 'Hành động không hợp lệ.');
    }

    /**
     * 🔥 needsOrdering() (CORE)
     * Màn hình gom dữ liệu đặt hàng
     */
    public function needsOrdering()
    {
        // Lấy các PR items từ các PR đang chờ xử lý
        $items = SaleOrderRequestItem::whereHas('saleOrderRequest', function($q) {
                $q->whereIn('status', [SaleOrderRequest::STATUS_SUBMITTED, SaleOrderRequest::STATUS_PROCESSING]);
            })
            ->with(['saleOrderRequest', 'vendor', 'product', 'purchaseOrderItems'])
            ->get();

        // Gom nhóm theo Vendor và Sản phẩm/Part Number
        $grouped = [];
        foreach ($items as $item) {
            $key = ($item->vendor_id ?? 'no-vendor') . '-' . ($item->product_id ?? 'no-prod') . '-' . ($item->part_number ?? 'no-pn');
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'vendor_id' => $item->vendor_id,
                    'vendor_name' => $item->vendor?->name ?? $item->vendor ?? 'Unknown Vendor',
                    'product_id' => $item->product_id,
                    'part_number' => $item->part_number,
                    'unit' => $item->unit,
                    'requested' => 0,
                    'ordered' => 0,
                    'items' => [] // Danh sách các PR item gốc để truy vết
                ];
            }

            $ordered = $item->ordered_quantity_total;
            
            $grouped[$key]['requested'] += $item->quantity;
            $grouped[$key]['ordered'] += $ordered;
            $grouped[$key]['items'][] = [
                'id' => $item->id,
                'pr_code' => $item->saleOrderRequest->code,
                'quantity' => $item->quantity,
                'ordered' => $ordered,
                'remaining' => max(0, $item->quantity - $ordered)
            ];
        }

        // Chỉ giữ lại những nhóm còn cần đặt hàng (remaining > 0)
        $finalData = array_filter($grouped, function($g) {
            return ($g['requested'] - $g['ordered']) > 0.001;
        });

        // Group theo Vendor để hiển thị trên UI dễ hơn
        $vendorGroups = [];
        foreach ($finalData as $data) {
            $vName = $data['vendor_name'];
            $vId = $data['vendor_id'];

            // Nếu vId null, thử tìm theo name trong DB để có ID hợp lệ
            if (!$vId) {
                $found = Supplier::where('name', $vName)->first();
                if ($found) {
                    $vId = $found->id;
                } else {
                    // Nếu vẫn không thấy, tạo key dựa trên name
                    $vId = 'name-' . md5($vName);
                }
            }

            if (!isset($vendorGroups[$vId])) {
                $vendorGroups[$vId] = [
                    'id' => $vId,
                    'name' => $vName,
                    'products' => []
                ];
            }
            $vendorGroups[$vId]['products'][] = $data;
        }

        return view('purchasing.needs-ordering', compact('vendorGroups'));
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
        ]);

        DB::beginTransaction();
        try {
            $vendor = Supplier::findOrFail($validated['vendor_id']);
            
            // 0. Xác định tiền tệ ưu tiên
            $poCurrencyId = null;
            if ($request->filled('currency_code')) {
                $selectedCurrency = \App\Models\Currency::where('code', $request->currency_code)->first();
                if ($selectedCurrency) $poCurrencyId = $selectedCurrency->id;
            }

            if (!$poCurrencyId) {
                $vendor = Supplier::findOrFail($validated['vendor_id']);
                $poCurrencyId = $vendor->currency_id;
            }
            
            // Nếu vẫn chưa có tiền tệ, kiểm tra items
            if (!$poCurrencyId) {
                $prItemIds = array_column($validated['items'], 'pr_item_id');
                $prItemsForCurrency = SaleOrderRequestItem::whereIn('id', $prItemIds)->with('saleItem')->get();
                $hasUsdItem = $prItemsForCurrency->contains(fn($i) => $i->saleItem && $i->saleItem->estimated_cost_usd > 0);
                
                if ($hasUsdItem) {
                    $usdCurrency = \App\Models\Currency::where('code', 'USD')->first();
                    if ($usdCurrency) $poCurrencyId = $usdCurrency->id;
                }
            }

            if (!$poCurrencyId) {
                $poCurrencyId = \App\Models\Currency::getBaseCurrencyId();
            }

            // 1. Tạo Purchase Order
            $po = PurchaseOrder::create([
                'code' => PurchaseOrder::generateCode(),
                'supplier_id' => $vendor->id,
                'order_date' => now(),
                'status' => 'pending_approval', // Chờ duyệt PO
                'created_by' => auth()->id(),
                'note' => $validated['note'],
                'currency_id' => $poCurrencyId,
                'exchange_rate' => $this->currencyService->getRateById($poCurrencyId, now()),
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

                // Tự động tính giá từ P&L nếu có
                $unitPrice = 0;
                $warehousePrice = 0;
                
                if ($prItem->saleItem) {
                    $saleItem = $prItem->saleItem;
                    $usdCurrency = \App\Models\Currency::where('code', 'USD')->first();
                    $isPoUsd = $usdCurrency && $poCurrencyId == $usdCurrency->id;
                    
                    // Tính toán giá USD dự tính từ P&L
                    $estUsd = $saleItem->estimated_cost_usd > 0 
                        ? (float)$saleItem->estimated_cost_usd 
                        : (float)($saleItem->usd_price * (1 - (($saleItem->discount_rate ?? 0) / 100)) * (1 + (($saleItem->import_cost_rate ?? 0) / 100)));

                    // Tính toán giá VND dự tính từ P&L
                    $estVnd = $saleItem->cost_price > 0 ? (float)$saleItem->cost_price : round($estUsd * ($saleItem->exchange_rate ?: 1));

                    if ($isPoUsd) {
                        $unitPrice = $estUsd;
                        $warehousePrice = $estUsd;
                    } else {
                        // Nếu PO là VND hoặc tiền tệ khác
                        if ($poCurrencyId == \App\Models\Currency::getBaseCurrencyId()) {
                            $unitPrice = $estVnd;
                            $warehousePrice = $estVnd;
                        } else {
                            // Tiền tệ khác -> quy đổi từ VND sang
                            $unitPrice = $this->currencyService->fromBase($estVnd, $po->exchange_rate);
                            $warehousePrice = $unitPrice;
                        }
                    }
                }

                // Tạo Purchase Order Item
                $itemTotal = round($unitPrice * $itemData['quantity'], 2);
                $itemVatPercent = 0;
                $itemVatAmount = 0;

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
}
