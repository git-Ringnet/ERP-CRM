<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/** Keeps the PO approval hand-off visible to the next responsible users. */
class PurchaseOrderApprovalNotificationService
{
    public function notifyApprovers(PurchaseOrder $purchaseOrder): void
    {
        try {
            $approvers = User::query()
                ->where('status', 'active')
                ->where('id', '!=', $purchaseOrder->created_by)
                ->where(function ($query) {
                    $query->whereHas('roles.permissions', function ($permissionQuery) {
                        $permissionQuery->where('slug', 'approve_purchase_orders');
                    })->orWhereHas('directPermissions', function ($permissionQuery) {
                        $permissionQuery->where('slug', 'approve_purchase_orders');
                    });
                })
                ->get(['id']);

            foreach ($approvers as $approver) {
                Notification::create([
                    'user_id' => $approver->id,
                    'type' => 'purchase_order_approval',
                    'title' => 'Có PO chờ duyệt',
                    'message' => "PO {$purchaseOrder->code} vừa được gửi duyệt. Vui lòng kiểm tra và duyệt hoặc từ chối.",
                    'link' => route('purchase-orders.index', ['status' => 'pending_approval']),
                    'icon' => 'fas fa-file-signature',
                    'color' => 'orange',
                    'data' => [
                        'purchase_order_id' => $purchaseOrder->id,
                        'purchase_order_code' => $purchaseOrder->code,
                    ],
                ]);
            }
        } catch (\Throwable $exception) {
            // Notification errors must not hide or roll back the PO workflow.
            Log::warning('Failed to notify PO approvers: ' . $exception->getMessage(), [
                'purchase_order_id' => $purchaseOrder->id,
            ]);
        }
    }

    public function notifyCreator(PurchaseOrder $purchaseOrder, string $title, string $message, string $color): void
    {
        if (!$purchaseOrder->created_by) {
            return;
        }

        try {
            Notification::create([
                'user_id' => $purchaseOrder->created_by,
                'type' => 'purchase_order_approval_result',
                'title' => $title,
                'message' => $message,
                'link' => route('purchase-orders.show', $purchaseOrder),
                'icon' => $color === 'green' ? 'fas fa-check-circle' : 'fas fa-times-circle',
                'color' => $color,
                'data' => [
                    'purchase_order_id' => $purchaseOrder->id,
                    'purchase_order_code' => $purchaseOrder->code,
                ],
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to notify PO creator: ' . $exception->getMessage(), [
                'purchase_order_id' => $purchaseOrder->id,
            ]);
        }
    }
}
