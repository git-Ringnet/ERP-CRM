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
        return $this->checkPermission($user, 'view_quotations');
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
        // Check if user has general view permission
        if ($this->checkPermission($user, 'view_quotations')) {
            return true;
        }

        // Check if user has view_all_quotations permission
        if ($this->checkPermission($user, 'view_all_quotations')) {
            return true;
        }

        // Check if user has view_own_quotations permission and owns the quotation
        if ($this->checkPermission($user, 'view_own_quotations')) {
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
