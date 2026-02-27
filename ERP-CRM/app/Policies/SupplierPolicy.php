<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any suppliers.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_suppliers');
    }

    /**
     * Determine whether the user can view the supplier.
     *
     * @param User $user
     * @param Supplier $supplier
     * @return bool
     */
    public function view(User $user, Supplier $supplier): bool
    {
        return $this->checkPermission($user, 'view_suppliers');
    }

    /**
     * Determine whether the user can create suppliers.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_suppliers');
    }

    /**
     * Determine whether the user can update the supplier.
     *
     * @param User $user
     * @param Supplier $supplier
     * @return bool
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $this->checkPermission($user, 'edit_suppliers');
    }

    /**
     * Determine whether the user can delete the supplier.
     *
     * @param User $user
     * @param Supplier $supplier
     * @return bool
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $this->checkPermission($user, 'delete_suppliers');
    }
}
