<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any sales.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_sales');
    }

    /**
     * Determine whether the user can view the sale.
     *
     * @param User $user
     * @param Sale $sale
     * @return bool
     */
    public function view(User $user, Sale $sale): bool
    {
        return $this->checkPermission($user, 'view_sales');
    }

    /**
     * Determine whether the user can create sales.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_sales');
    }

    /**
     * Determine whether the user can update the sale.
     *
     * @param User $user
     * @param Sale $sale
     * @return bool
     */
    public function update(User $user, Sale $sale): bool
    {
        return $this->checkPermission($user, 'edit_sales');
    }

    /**
     * Determine whether the user can delete the sale.
     *
     * @param User $user
     * @param Sale $sale
     * @return bool
     */
    public function delete(User $user, Sale $sale): bool
    {
        return $this->checkPermission($user, 'delete_sales');
    }

    /**
     * Determine whether the user can export sales.
     *
     * @param User $user
     * @return bool
     */
    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_sales');
    }
}
