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
        // If user has view_all_quotations, allow
        if ($this->checkPermission($user, 'view_all_quotations')) {
            return true;
        }

        // If user has view_own_quotations or view_quotations, only allow if they own the quotation
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
        return $this->checkPermission($user, 'edit_quotations') || $this->checkPermission($user, 'approve_quotations');
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $this->checkPermission($user, 'delete_quotations');
    }
}
