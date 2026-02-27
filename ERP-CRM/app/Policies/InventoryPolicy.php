<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any inventory records.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_inventory');
    }

    /**
     * Determine whether the user can view the inventory record.
     *
     * @param User $user
     * @param Inventory $inventory
     * @return bool
     */
    public function view(User $user, Inventory $inventory): bool
    {
        return $this->checkPermission($user, 'view_inventory');
    }
}
