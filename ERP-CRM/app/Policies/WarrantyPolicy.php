<?php

namespace App\Policies;

use App\Models\User;

class WarrantyPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_warranties');
    }

    public function view(User $user, $saleItem): bool
    {
        return $this->checkPermission($user, 'view_warranties');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_warranties');
    }
}
