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
        return $this->checkPermission($user, 'view_sales') ||
               $this->checkPermission($user, 'view_all_sales') ||
               $this->checkPermission($user, 'view_own_sales');
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
        // If user has view_all_sales or general view_sales, allow
        if ($this->checkPermission($user, 'view_all_sales') || $this->checkPermission($user, 'view_sales')) {
            return true;
        }

        // If user has view_own_sales, only allow if they own the sale
        if ($this->checkPermission($user, 'view_own_sales')) {
            return $sale->user_id === $user->id;
        }

        return false;
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
