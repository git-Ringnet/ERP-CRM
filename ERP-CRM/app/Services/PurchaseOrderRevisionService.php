<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\SaleOrderRequest;

/**
 * PO bị hủy để trả về Sales chỉnh sửa: PO hủy hẳn. Khi yêu cầu đặt hàng được
 * duyệt lại, sản phẩm đi vào Gom đơn để tạo PO mới (không gắn lại PO cũ).
 *
 * Service chỉ ghi nhớ PO nào đã trả về yêu cầu nào để báo cho PO Team biết
 * yêu cầu vừa duyệt là đơn làm lại sau khi trả về (kèm mã PO cũ và lý do).
 */
class PurchaseOrderRevisionService
{
    /**
     * Ghi nhận PO vừa hủy đã trả các yêu cầu đặt hàng về cho Sales.
     */
    public function markReturnedToSales(PurchaseOrder $po, string $previousStatus, array $sorIds): void
    {
        $sorIds = array_values(array_unique(array_map('intval', array_filter($sorIds))));
        if (empty($sorIds)) {
            return;
        }

        $po->update([
            'awaiting_revision' => false,
            'revision_previous_status' => $previousStatus,
            'revision_sale_order_request_ids' => $sorIds,
        ]);
    }

    /**
     * Lấy (và xóa ghi nhận) các PO đã hủy từng trả yêu cầu này về Sales.
     *
     * @return array<int, array{code: string, reason: ?string}>
     */
    public function pullReturnedPurchaseOrders(SaleOrderRequest $sor): array
    {
        $sorId = (int) $sor->id;
        $pos = PurchaseOrder::where('status', 'cancelled')
            ->whereNotNull('revision_sale_order_request_ids')
            ->get()
            ->filter(fn ($po) => in_array($sorId, array_map('intval', $po->revision_sale_order_request_ids ?? []), true));

        $result = [];
        foreach ($pos as $po) {
            $result[] = ['code' => $po->code, 'reason' => $this->lastCancelReason($po->note)];

            $remaining = array_values(array_diff(array_map('intval', $po->revision_sale_order_request_ids ?? []), [$sorId]));
            $po->revision_sale_order_request_ids = $remaining ?: null;
            if (!$remaining) {
                $po->revision_previous_status = null;
            }
            $po->save();
        }

        return $result;
    }

    /**
     * Câu mô tả ngắn để chèn vào thông báo / thông điệp, VD:
     * "Đơn làm lại sau khi PO 0088/2026/TH-Fortinet trả về (lý do: ...)".
     */
    public static function describe(array $returned): string
    {
        if (empty($returned)) {
            return '';
        }

        $parts = array_map(
            fn ($r) => 'PO ' . $r['code'] . ($r['reason'] ? ' (lý do: ' . $r['reason'] . ')' : ''),
            $returned
        );

        return 'Đơn làm lại sau khi ' . implode('; ', $parts) . ' trả về. PO cũ đã hủy, vui lòng gom và tạo PO mới.';
    }

    private function lastCancelReason(?string $note): ?string
    {
        if (!$note || !preg_match_all('/Đã hủy đơn:\s*(.+)$/mu', $note, $m)) {
            return null;
        }

        $reason = trim(end($m[1]));

        return mb_strimwidth($reason, 0, 200, '…');
    }
}
