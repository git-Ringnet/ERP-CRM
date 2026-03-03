<?php

namespace App\Policies;

use App\Models\SupplierPriceList;
use App\Models\User;

class SupplierPriceListPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_supplier_price_lists');
    }

    public function view(User $user, SupplierPriceList $supplierPriceList): bool
    {
        return $this->checkPermission($user, 'view_supplier_price_lists');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_supplier_price_lists');
    }

    public function update(User $user, SupplierPriceList $supplierPriceList): bool
    {
        return $this->checkPermission($user, 'edit_supplier_price_lists');
    }

    public function delete(User $user, SupplierPriceList $supplierPriceList): bool
    {
        return $this->checkPermission($user, 'delete_supplier_price_lists');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_supplier_price_lists');
    }
}
