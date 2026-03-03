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
        // If user has view_all_purchase_orders or general view_purchase_orders, allow
        if ($this->checkPermission($user, 'view_all_purchase_orders') || $this->checkPermission($user, 'view_purchase_orders')) {
            return true;
        }

        // If user has view_own_purchase_orders, only allow if they created the purchase order
        if ($this->checkPermission($user, 'view_own_purchase_orders')) {
            return $purchaseOrder->created_by === $user->id;
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
        return $this->checkPermission($user, 'edit_purchase_orders');
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
        return $this->checkPermission($user, 'delete_purchase_orders');
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
        return $this->checkPermission($user, 'approve_purchase_orders');
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
}
