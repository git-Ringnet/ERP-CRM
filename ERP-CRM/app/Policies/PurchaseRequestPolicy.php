<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_purchase_requests');
    }

    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $this->checkPermission($user, 'view_purchase_requests');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_purchase_requests');
    }

    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $this->checkPermission($user, 'edit_purchase_requests');
    }

    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $this->checkPermission($user, 'delete_purchase_requests');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_purchase_requests');
    }
}
