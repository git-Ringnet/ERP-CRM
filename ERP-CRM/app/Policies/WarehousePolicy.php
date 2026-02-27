<?php

namespace App\Policies;

use App\Models\Warehouse;
use App\Models\User;

class WarehousePolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any warehouses.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_warehouses');
    }

    /**
     * Determine whether the user can view the warehouse.
     *
     * @param User $user
     * @param Warehouse $warehouse
     * @return bool
     */
    public function view(User $user, Warehouse $warehouse): bool
    {
        return $this->checkPermission($user, 'view_warehouses');
    }

    /**
     * Determine whether the user can create warehouses.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_warehouses');
    }

    /**
     * Determine whether the user can update the warehouse.
     *
     * @param User $user
     * @param Warehouse $warehouse
     * @return bool
     */
    public function update(User $user, Warehouse $warehouse): bool
    {
        return $this->checkPermission($user, 'edit_warehouses');
    }

    /**
     * Determine whether the user can delete the warehouse.
     *
     * @param User $user
     * @param Warehouse $warehouse
     * @return bool
     */
    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $this->checkPermission($user, 'delete_warehouses');
    }
}
