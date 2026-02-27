<?php

namespace App\Policies;

use App\Models\DamagedGood;
use App\Models\User;

class DamagedGoodPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any damaged goods.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_damaged_goods');
    }

    /**
     * Determine whether the user can view the damaged good.
     *
     * @param User $user
     * @param DamagedGood $damagedGood
     * @return bool
     */
    public function view(User $user, DamagedGood $damagedGood): bool
    {
        return $this->checkPermission($user, 'view_damaged_goods');
    }

    /**
     * Determine whether the user can create damaged goods.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_damaged_goods');
    }

    /**
     * Determine whether the user can update the damaged good.
     *
     * @param User $user
     * @param DamagedGood $damagedGood
     * @return bool
     */
    public function update(User $user, DamagedGood $damagedGood): bool
    {
        return $this->checkPermission($user, 'edit_damaged_goods');
    }

    /**
     * Determine whether the user can delete the damaged good.
     *
     * @param User $user
     * @param DamagedGood $damagedGood
     * @return bool
     */
    public function delete(User $user, DamagedGood $damagedGood): bool
    {
        return $this->checkPermission($user, 'delete_damaged_goods');
    }
}
