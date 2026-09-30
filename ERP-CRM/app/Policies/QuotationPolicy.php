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
        // Super Admin, Admin, Director, Sales Manager, Accountant can view all
        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'sales_manager', 'order_management', 'accountant']) || 
            $this->checkPermission($user, 'view_all_quotations')) {
            return true;
        }

        // Creator or assigned sales can view
        if ($quotation->created_by === $user->id || $quotation->user_id === $user->id) {
            return true;
        }

        // Project manager can view
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
        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'order_management'])) {
            return true;
        }

        if ($quotation->created_by === $user->id || $quotation->user_id === $user->id) {
            return true;
        }

        return $this->checkPermission($user, 'edit_quotations') || $this->checkPermission($user, 'approve_quotations');
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return true;
        }

        return $this->checkPermission($user, 'delete_quotations');
    }
}
