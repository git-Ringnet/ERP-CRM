<?php

namespace App\Policies;

use App\Models\PriceList;
use App\Models\User;

class PriceListPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_price_lists');
    }

    public function view(User $user, PriceList $priceList): bool
    {
        return $this->checkPermission($user, 'view_price_lists');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_price_lists');
    }

    public function update(User $user, PriceList $priceList): bool
    {
        return $this->checkPermission($user, 'edit_price_lists');
    }

    public function delete(User $user, PriceList $priceList): bool
    {
        return $this->checkPermission($user, 'delete_price_lists');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_price_lists');
    }
}
