<?php

namespace App\Policies;

use App\Models\ShippingAllocation;
use App\Models\User;

class ShippingAllocationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_shipping_allocations');
    }

    public function view(User $user, ShippingAllocation $shippingAllocation): bool
    {
        return $this->checkPermission($user, 'view_shipping_allocations');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_shipping_allocations');
    }

    public function update(User $user, ShippingAllocation $shippingAllocation): bool
    {
        return $this->checkPermission($user, 'edit_shipping_allocations');
    }

    public function delete(User $user, ShippingAllocation $shippingAllocation): bool
    {
        return $this->checkPermission($user, 'delete_shipping_allocations');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_shipping_allocations');
    }
}
