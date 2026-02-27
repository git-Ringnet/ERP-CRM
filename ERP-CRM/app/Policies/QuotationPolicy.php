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
        return $this->checkPermission($user, 'view_quotations');
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
        return $this->checkPermission($user, 'edit_quotations');
    }

    /**
     * Determine whether the user can delete the quotation.
     *
     * @param User $user
     * @param Quotation $quotation
     * @return bool
     */
    public function delete(User $user, Quotation $quotation): bool
    {
        return $this->checkPermission($user, 'delete_quotations');
    }

    /**
     * Determine whether the user can approve the quotation.
     *
     * @param User $user
     * @param Quotation $quotation
     * @return bool
     */
    public function approve(User $user, Quotation $quotation): bool
    {
        return $this->checkPermission($user, 'approve_quotations');
    }
}
