<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SaleOrderRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Giữ lại PO bị hủy để Sales chỉnh sửa (P&L / yêu cầu đặt hàng) và đưa sản
 * phẩm quay về đúng PO cũ khi yêu cầu đặt hàng được duyệt lại, thay vì đi
 * qua Gom đơn và tạo đơn đặt hàng với hãng mới.
 */
class PurchaseOrderRevisionService
{
    /** Trạng thái PO còn có thể nhận lại sản phẩm sau khi Sales chỉnh sửa. */
    private const REATTACHABLE_STATUSES = ['cancelled', 'draft', 'pending_approval'];

    public function __construct(
        private PurchaseOrderApprovalNotificationService $approvalNotificationService
    ) {}

    /**
     * Đánh dấu PO vừa hủy là "chờ Sales chỉnh sửa" cho các yêu cầu đặt hàng liên quan.
     */
    public function markAwaitingRevision(PurchaseOrder $po, string $previousStatus, array $sorIds): void
    {
        $sorIds = array_values(array_unique(array_map('intval', array_filter($sorIds))));
        if (empty($sorIds)) {
            return;
        }

        $po->update([
            'awaiting_revision' => true,
            'revision_previous_status' => $previousStatus,
            'revision_sale_order_request_ids' => $sorIds,
        ]);
    }

    /**
     * Khi yêu cầu đặt hàng được duyệt lại (chuyển sang Đang xử lý), đưa các
     * sản phẩm của yêu cầu về PO cũ đang chờ chỉnh sửa.
     *
     * @return string[] Mã các PO đã nhận lại sản phẩm
     */
    public function reattachApprovedRequest(SaleOrderRequest $sor): array
    {
        if ($sor->status !== SaleOrderRequest::STATUS_PROCESSING) {
            return [];
        }

        $pos = PurchaseOrder::where('awaiting_revision', true)->get()
            ->filter(fn ($po) => in_array((int) $sor->id, array_map('intval', $po->revision_sale_order_request_ids ?? []), true));

        $restoredCodes = [];

        foreach ($pos as $po) {
            try {
                $restored = DB::transaction(fn () => $this->reattachToPo($po->id, $sor));
                if ($restored) {
                    $restoredCodes[] = $po->code;
                }
            } catch (\Throwable $e) {
                Log::error("Could not reattach SOR #{$sor->id} to PO #{$po->id}: " . $e->getMessage());
            }
        }

        return $restoredCodes;
    }

    private function reattachToPo(int $poId, SaleOrderRequest $sor): bool
    {
        $po = PurchaseOrder::lockForUpdate()->findOrFail($poId);
        $wasCancelled = $po->status === 'cancelled';

        $remainingSorIds = array_values(array_diff(
            array_map('intval', $po->revision_sale_order_request_ids ?? []),
            [(int) $sor->id]
        ));

        // PO đã được duyệt/đặt tiếp với hãng: không chèn thêm sản phẩm, để yêu
        // cầu đi theo luồng Gom đơn như bình thường.
        $sorItems = $sor->items()->with('saleItem')->where('is_cancelled', false)->get();
        $sorItemIds = $sor->items()->pluck('id');

        if (!in_array($po->status, self::REATTACHABLE_STATUSES, true)) {
            $this->discardCancelledLines($po, $sorItemIds);
            $this->updateRevisionState($po, $remainingSorIds);
            $po->save();
            return false;
        }

        // Sản phẩm cũ của yêu cầu này trên PO (đã bị đánh dấu hủy). Khi Sales
        // sửa yêu cầu, dòng yêu cầu bị tạo lại nên liên kết cũ trở về NULL.
        $oldItems = $po->items()
            ->where('status', 'cancelled')
            ->where(function ($q) use ($sorItemIds) {
                $q->whereNull('sale_order_request_item_id')
                    ->orWhereIn('sale_order_request_item_id', $sorItemIds);
            })
            ->get();

        $attached = 0;
        foreach ($sorItems as $prItem) {
            if ((int) $prItem->vendor_id !== (int) $po->supplier_id) {
                continue; // Đổi hãng: đi theo luồng Gom đơn
            }

            $remaining = (float) $prItem->quantity - $this->orderedOnOtherPos($prItem, $po->id);
            if ($remaining <= 0.001) {
                continue;
            }

            // Giữ giá PO Team đã nhập trước đó nếu cùng sản phẩm, ngược lại lấy từ P&L
            $previous = $oldItems->first(fn ($old) => ($prItem->product_id && (int) $old->product_id === (int) $prItem->product_id)
                || strcasecmp(trim((string) $old->product_name), trim((string) $prItem->part_number)) === 0);

            if ($previous) {
                $warehousePrice = (float) $previous->warehouse_unit_price;
                $discountPercent = (float) $previous->discount_percent;
                $unitPrice = (float) $previous->unit_price;
            } else {
                $warehousePrice = (float) ($prItem->saleItem->usd_price ?? 0);
                $discountPercent = (float) ($prItem->saleItem->discount_rate ?? 0);
                $unitPrice = round($warehousePrice * (1 - ($discountPercent / 100)), 4);
            }

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'sale_order_request_item_id' => $prItem->id,
                'product_id' => $prItem->product_id,
                'product_name' => $prItem->part_number,
                'ordered_quantity' => $remaining,
                'quantity' => $remaining,
                'unit' => $prItem->unit ?? 'Cái',
                'unit_price' => $unitPrice,
                'warehouse_unit_price' => $warehousePrice,
                'discount_percent' => $discountPercent,
                'total' => round($remaining * $unitPrice, 2),
                'vat_percent' => 0,
                'vat_amount' => 0,
                'status' => 'ordered',
            ]);
            $attached++;
        }

        if ($attached === 0) {
            // Không còn sản phẩm nào thuộc hãng của PO: giữ nguyên PO, yêu cầu
            // đi theo luồng Gom đơn.
            $this->discardCancelledLines($po, $sorItemIds);
            $this->updateRevisionState($po, $remainingSorIds);
            $po->save();
            return false;
        }

        // Thay các dòng cũ đã hủy bằng dòng mới theo yêu cầu đã chỉnh sửa
        PurchaseOrderItem::whereIn('id', $oldItems->pluck('id'))->delete();

        $actor = auth()->user()->name ?? 'System';
        $logNote = '[' . now()->format('d/m/Y H:i') . " - {$actor}] Khôi phục đơn sau khi Sales chỉnh sửa và yêu cầu đặt hàng #{$sor->code} được duyệt lại.";
        $po->note = trim(($po->note ? $po->note . "\n" : '') . $logNote);

        if ($wasCancelled) {
            // Nội dung đơn có thể đã thay đổi nên luôn cần duyệt lại PO
            // (riêng PO nháp ở Gom đơn thì quay về nháp).
            $po->status = $po->revision_previous_status === 'draft' ? 'draft' : 'pending_approval';
            $po->approved_by = null;
            $po->approved_at = null;
        }

        $this->updateRevisionState($po, $remainingSorIds);

        $po->load(['items', 'currency']);
        $po->calculateTotals();
        $po->updateDebt();
        $po->save();

        // Sau khi gắn lại, yêu cầu có thể đã được đặt đủ -> Hoàn thành
        $sor->refresh()->checkAndUpdateStatus();

        if ($wasCancelled && $po->status === 'pending_approval') {
            DB::afterCommit(fn () => $this->approvalNotificationService->notifyApprovers($po));
        }

        return true;
    }

    /**
     * Số lượng đã đặt cho dòng yêu cầu trên các PO khác (không tính PO đã hủy).
     */
    private function orderedOnOtherPos($prItem, int $poId): float
    {
        return (float) $prItem->purchaseOrderItems()
            ->where('purchase_order_id', '!=', $poId)
            ->whereHas('purchaseOrder', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->sum('ordered_quantity');
    }

    /**
     * Gỡ các dòng đã hủy của yêu cầu khỏi PO đang hoạt động, tránh bị tính là
     * "đã đặt" khiến sản phẩm không xuất hiện lại ở Gom đơn.
     */
    private function discardCancelledLines(PurchaseOrder $po, $sorItemIds): void
    {
        if ($po->status === 'cancelled' || $sorItemIds->isEmpty()) {
            return;
        }

        $po->items()
            ->where('status', 'cancelled')
            ->whereIn('sale_order_request_item_id', $sorItemIds)
            ->delete();
    }

    private function updateRevisionState(PurchaseOrder $po, array $remainingSorIds): void
    {
        $po->revision_sale_order_request_ids = $remainingSorIds ?: null;
        $po->awaiting_revision = !empty($remainingSorIds);
        if (!$po->awaiting_revision) {
            $po->revision_previous_status = null;
        }
    }
}
