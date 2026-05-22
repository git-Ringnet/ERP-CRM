<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SalesRevenue;

class SalesRevenuePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_sales_revenues');
    }

    public function view(User $user, SalesRevenue $salesRevenue): bool
    {
        return $this->checkPermission($user, 'view_sales_revenues');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_sales_revenues');
    }

    public function update(User $user, SalesRevenue $salesRevenue): bool
    {
        return $this->checkPermission($user, 'edit_sales_revenues');
    }

    public function delete(User $user, SalesRevenue $salesRevenue): bool
    {
        return $this->checkPermission($user, 'delete_sales_revenues');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'view_sales_revenues');
    }
}
