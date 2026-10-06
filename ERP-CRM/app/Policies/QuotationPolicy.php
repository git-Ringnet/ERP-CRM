<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any quotations.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'sales_manager', 'sales_staff', 'order_management', 'accountant']) || 
            in_array($user->department, ['Sales', 'BU1', 'BU2', 'BU3', 'Kinh doanh'])) {
            return true;
        }

        return $this->checkPermission($user, 'view_quotations') ||
               $this->checkPermission($user, 'view_all_quotations') ||
               $this->checkPermission($user, 'view_own_quotations');
    }

    /**
     * Determine whether the user can view the quotation.
     *
     * @param User $user
     * @param Quotation $quotation
     * @return bool
     */
    public function view(User $user, Quotation $quotation): bool
    {
        // 1. Super Admin, Admin, Director, Order Management, Accountant can view all
        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'order_management', 'accountant'])) {
            return true;
        }

        // 2. Option xem tất cả: Nếu được cấp quyền view_all_quotations (trong Ma trận quyền hoặc quyền riêng), cho phép xem tất cả
        if ($this->checkPermission($user, 'view_all_quotations')) {
            return true;
        }

        // 3. Sales Manager hoặc Trưởng nhóm (hoặc có view_group_quotations): Xem báo giá của bản thân và các sales thuộc nhóm mình quản lý
        if ($this->checkPermission($user, 'view_group_quotations') || $user->hasRole('sales_manager') || $user->leadingGroups()->where('status', 'active')->exists()) {
            $managedIds = $user->getLeadGroupMemberIds();
            return in_array($quotation->created_by, $managedIds) || ($quotation->project_id && $quotation->project && in_array($quotation->project->manager_id, $managedIds));
        }

        // 4. Creator or assigned sales can view
        if ($quotation->created_by === $user->id) {
            return true;
        }

        // 5. Project manager can view
        if ($quotation->project_id && $quotation->project && $quotation->project->manager_id === $user->id) {
            return true;
        }

        if ($this->checkPermission($user, 'view_own_quotations') || $this->checkPermission($user, 'view_quotations')) {
            return $quotation->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create quotations.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'sales_staff', 'order_management']) || 
            in_array($user->department, ['Sales', 'BU1', 'BU2', 'BU3', 'Kinh doanh'])) {
            return true;
        }

        return $this->checkPermission($user, 'create_quotations');
    }

    /**
     * Determine whether the user can update the quotation.
     *
     * @param User $user
     * @param Quotation $quotation
     * @return bool
     */
    public function update(User $user, Quotation $quotation): bool
    {
        if ($quotation->status === 'converted' || !empty($quotation->converted_to_sale_id)) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'admin', 'order_management'])) {
            return true;
        }

        // If user does not have view_all_quotations: Sales Manager / Group Lead can edit if belongs to self or managed sales team
        if (!$this->checkPermission($user, 'view_all_quotations')
            && ($user->hasRole('sales_manager') || $user->leadingGroups()->where('status', 'active')->exists() || $this->checkPermission($user, 'view_group_quotations'))) {
            $managedIds = $user->getLeadGroupMemberIds();
            return in_array($quotation->created_by, $managedIds) || ($quotation->project_id && $quotation->project && in_array($quotation->project->manager_id, $managedIds));
        }

        if ($quotation->created_by === $user->id) {
            return true;
        }

        if ($quotation->project_id && $quotation->project && $quotation->project->manager_id === $user->id) {
            return true;
        }

        // Báo giá 4: Sales staff can edit their quotation before conversion
        if ($user->hasRole('sales_staff') || in_array($user->department, ['Sales', 'BU1', 'BU2', 'BU3', 'Kinh doanh'])) {
            return $quotation->created_by === $user->id;
        }

        return $this->checkPermission($user, 'edit_quotations') || $this->checkPermission($user, 'approve_quotations');
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        if ($quotation->status === 'converted' || !empty($quotation->converted_to_sale_id)) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        // If user does not have view_all_quotations: Sales Manager / Group Lead can delete if belongs to self or managed sales team
        if (!$this->checkPermission($user, 'view_all_quotations')
            && ($user->hasRole('sales_manager') || $user->leadingGroups()->where('status', 'active')->exists() || $this->checkPermission($user, 'view_group_quotations'))) {
            $managedIds = $user->getLeadGroupMemberIds();
            return in_array($quotation->created_by, $managedIds) || ($quotation->project_id && $quotation->project && in_array($quotation->project->manager_id, $managedIds));
        }

        if ($quotation->created_by === $user->id) {
            return true;
        }

        if ($quotation->project_id && $quotation->project && $quotation->project->manager_id === $user->id) {
            return true;
        }

        // Báo giá 4: Sales staff can delete their quotation before conversion
        if ($user->hasRole('sales_staff') || in_array($user->department, ['Sales', 'BU1', 'BU2', 'BU3', 'Kinh doanh'])) {
            return empty($quotation->converted_to_sale_id) && $quotation->status !== 'converted';
        }

        return $this->checkPermission($user, 'delete_quotations');
    }
}
