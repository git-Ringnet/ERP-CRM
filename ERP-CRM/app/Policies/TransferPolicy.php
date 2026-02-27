<?php

namespace App\Policies;

use App\Models\Transfer;
use App\Models\User;

class TransferPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any transfers.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_transfers');
    }

    /**
     * Determine whether the user can view the transfer.
     *
     * @param User $user
     * @param Transfer $transfer
     * @return bool
     */
    public function view(User $user, Transfer $transfer): bool
    {
        return $this->checkPermission($user, 'view_transfers');
    }

    /**
     * Determine whether the user can create transfers.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_transfers');
    }

    /**
     * Determine whether the user can update the transfer.
     *
     * @param User $user
     * @param Transfer $transfer
     * @return bool
     */
    public function update(User $user, Transfer $transfer): bool
    {
        return $this->checkPermission($user, 'edit_transfers');
    }

    /**
     * Determine whether the user can delete the transfer.
     *
     * @param User $user
     * @param Transfer $transfer
     * @return bool
     */
    public function delete(User $user, Transfer $transfer): bool
    {
        return $this->checkPermission($user, 'delete_transfers');
    }
}
