<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any purchase orders.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_purchase_orders') ||
               $this->checkPermission($user, 'view_all_purchase_orders') ||
               $this->checkPermission($user, 'view_group_purchase_orders') ||
               $this->checkPermission($user, 'view_own_purchase_orders');
    }

    /**
     * Determine whether the user can view the purchase order.
     *
     * @param User $user
     * @param PurchaseOrder $purchaseOrder
     * @return bool
     */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        // If user has view_all_purchase_orders, allow
        if ($this->checkPermission($user, 'view_all_purchase_orders')) {
            return true;
        }

        // A user who is allowed to approve must be able to open the PO that
        // appears in their approval queue, even when that permission was
        // granted directly instead of through a role with view-all access.
        if ($this->checkPermission($user, 'approve_purchase_orders')) {
            return true;
        }

        if ($this->checkPermission($user, 'view_group_purchase_orders') || $user->hasRole('sales_manager') || $user->leadingGroups()->where('status', 'active')->exists()) {
            $managedIds = $user->getLeadGroupMemberIds();
            if (in_array($purchaseOrder->created_by, $managedIds)) {
                return true;
            }
            if ($purchaseOrder->sale && in_array($purchaseOrder->sale->user_id, $managedIds)) {
                return true;
            }
            return $purchaseOrder->items()->whereHas('saleOrderRequestItem.saleOrderRequest.sale', function ($sQ) use ($managedIds) {
                $sQ->whereIn('user_id', $managedIds);
            })->exists();
        }

        if ($this->checkPermission($user, 'view_own_purchase_orders') || $this->checkPermission($user, 'view_purchase_orders')) {
            if ($purchaseOrder->created_by === $user->id) {
                return true;
            }
            if ($purchaseOrder->sale && $purchaseOrder->sale->user_id === $user->id) {
                return true;
            }
            return $purchaseOrder->items()->whereHas('saleOrderRequestItem.saleOrderRequest.sale', function ($sQ) use ($user) {
                $sQ->where('user_id', $user->id);
            })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create purchase orders.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_purchase_orders');
    }

    /**
     * Determine whether the user can update the purchase order.
     *
     * @param User $user
     * @param PurchaseOrder $purchaseOrder
     * @return bool
     */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkPermission($user, 'edit_purchase_orders') || $this->checkPermission($user, 'approve_purchase_orders');
    }

    /**
     * Determine whether the user can delete the purchase order.
     *
     * @param User $user
     * @param PurchaseOrder $purchaseOrder
     * @return bool
     */
    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determine whether the user can approve the purchase order.
     *
     * @param User $user
     * @param PurchaseOrder $purchaseOrder
     * @return bool
     */
    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->checkPermission($user, 'approve_purchase_orders') ||
               $user->hasAnyRole(['purchase_manager', 'purchase_staff']);
    }

    /**
     * Determine whether the user can export purchase orders.
     *
     * @param User $user
     * @return bool
     */
    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_purchase_orders');
    }

    /**
     * Được xem giá mua / tổng tiền / file đính kèm của PO (thông tin nội bộ PO Team).
     *
     * Sales (kể cả Sales Manager / trưởng nhóm) KHÔNG có quyền này: họ chỉ xem bản rút gọn
     * của PO (trạng thái, mốc thời gian, S/N, license) cho các dòng thuộc SO của mình.
     */
    public function viewPrices(User $user, ?PurchaseOrder $purchaseOrder = null): bool
    {
        if ($purchaseOrder && (int) $purchaseOrder->created_by === (int) $user->id) {
            return true;
        }

        return $user->hasAnyRole(\App\Models\Sale::PO_TEAM_ROLES)
            || $this->checkPermission($user, 'view_all_purchase_orders')
            || $this->checkPermission($user, 'approve_purchase_orders')
            || $this->checkPermission($user, 'create_purchase_orders')
            || $this->checkPermission($user, 'edit_purchase_orders');
    }

    /**
     * Dùng cho các API phụ trợ màn hình tạo / sửa PO (sinh mã, kiểm tra mã).
     */
    public function manage(User $user): bool
    {
        return $this->checkPermission($user, 'create_purchase_orders')
            || $this->checkPermission($user, 'edit_purchase_orders')
            || $this->checkPermission($user, 'approve_purchase_orders');
    }
}
