<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any sales.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_sales') ||
               $this->checkPermission($user, 'view_all_sales') ||
               $this->checkPermission($user, 'view_own_sales');
    }

    /**
     * Determine whether the user can view the sale.
     *
     * @param User $user
     * @param Sale $sale
     * @return bool
     */
    public function view(User $user, Sale $sale): bool
    {
        // 1. Super Admin, Admin, Director, Accountant, Order Management can view all
        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'accountant', 'order_management'])) {
            return true;
        }

        // 2. Option xem tất cả: Nếu được cấp quyền view_all_sales (trong Ma trận quyền hoặc quyền riêng), cho phép xem tất cả
        if ($this->checkPermission($user, 'view_all_sales')) {
            return true;
        }

        // 3. Sales Manager hoặc Trưởng nhóm (hoặc có view_group_sales): xem đơn của bản thân và các sales thuộc nhóm mình quản lý
        if ($this->checkPermission($user, 'view_group_sales') || $user->hasRole('sales_manager') || $user->leadingGroups()->where('status', 'active')->exists()) {
            $managedIds = $user->getLeadGroupMemberIds();
            return in_array($sale->user_id, $managedIds) || ($sale->secondary_user_id && in_array($sale->secondary_user_id, $managedIds));
        }

        // 4. If user has view_own_sales or view_sales, allow if they are primary or secondary PIC
        if ($this->checkPermission($user, 'view_own_sales') || $this->checkPermission($user, 'view_sales')) {
            return $sale->user_id === $user->id || $sale->secondary_user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create sales.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_sales');
    }

    /**
     * Determine whether the user can update the sale.
     *
     * @param User $user
     * @param Sale $sale
     * @return bool
     */
    public function update(User $user, Sale $sale): bool
    {
        // Disallow editing if order is cancelled
        if ($sale->status === 'cancelled') {
            return false;
        }

        // Disallow editing if payment has already been recorded
        if ($sale->hasPayment()) {
            return false;
        }

        // Chặn sửa nếu đơn hàng đã duyệt P&L trừ khi có quyền edit_approved_pnl_sales
        if ($sale->pl_status === 'approved' && !$this->checkPermission($user, 'edit_approved_pnl_sales')) {
            return false;
        }

        // If pending approval, only allow users with approve_sales permission (BOD/Legal) to edit
        if ($sale->isPendingApproval()) {
            return $this->checkPermission($user, 'approve_sales');
        }

        // If user does not have view_all_sales and is manager or team leader: only allow if sale belongs to self or managed team
        if (!$this->checkPermission($user, 'view_all_sales')
            && ($user->hasRole('sales_manager') || $user->leadingGroups()->where('status', 'active')->exists() || $this->checkPermission($user, 'view_group_sales'))
            && !$user->hasAnyRole(['super_admin', 'admin', 'director'])) {
            $managedIds = $user->getLeadGroupMemberIds();
            if (!in_array($sale->user_id, $managedIds) && !($sale->secondary_user_id && in_array($sale->secondary_user_id, $managedIds))) {
                return false;
            }
        }

        // Standard Sales: allow if primary or secondary PIC
        if (!$this->checkPermission($user, 'view_all_sales')
            && !$user->hasRole('sales_manager')
            && !$user->hasAnyRole(['super_admin', 'admin', 'director'])) {
            if ($sale->user_id !== $user->id && $sale->secondary_user_id !== $user->id) {
                return false;
            }
        }

        return $this->checkPermission($user, 'edit_sales') || $this->checkPermission($user, 'approve_sales');
    }

    /**
     * Determine whether the user can approve the sale (change status).
     * Separated from update() to prevent sales_staff from approving orders.
     *
     * @param User $user
     * @param Sale $sale
     * @return bool
     */
    public function approve(User $user, Sale $sale): bool
    {
        return $this->checkPermission($user, 'approve_sales');
    }

    /**
     * Determine whether the user can approve P&L for the sale.
     *
     * @param User $user
     * @param Sale $sale
     * @return bool
     */
    public function approvePnl(User $user, Sale $sale): bool
    {
        return $this->checkPermission($user, 'approve_sales');
    }

    /**
     * Determine whether the user can edit the sale after P&L is approved.
     *
     * @param User $user
     * @param Sale $sale
     * @return bool
     */
    public function editApprovedPnl(User $user, Sale $sale): bool
    {
        return $this->checkPermission($user, 'edit_approved_pnl_sales');
    }

    /**
     * Determine whether the user can delete the sale after P&L is approved.
     *
     * @param User $user
     * @param Sale $sale
     * @return bool
     */
    public function deleteApprovedPnl(User $user, Sale $sale): bool
    {
        return $this->checkPermission($user, 'delete_approved_pnl_sales');
    }

    /**
     * Determine whether the user can delete the sale.
     *
     * @param User $user
     * @param Sale $sale
     * @return bool
     */
    public function delete(User $user, Sale $sale): bool
    {
        // Quản trị viên (Super Admin, Admin) có toàn quyền xóa đơn hàng
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // Chặn xóa nếu đơn hàng đã duyệt P&L trừ khi có quyền delete_approved_pnl_sales
        if ($sale->pl_status === 'approved' && !$this->checkPermission($user, 'delete_approved_pnl_sales')) {
            return false;
        }

        return $this->checkPermission($user, 'delete_sales');
    }

    /**
     * Determine whether the user can force cascade delete any sale order across all statuses.
     * Only applies to Administrators (Super Admin, Admin).
     *
     * @param User $user
     * @param Sale $sale
     * @return bool
     */
    public function adminForceDelete(User $user, Sale $sale): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determine whether the user can export sales.
     *
     * @param User $user
     * @return bool
     */
    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_sales');
    }
}
