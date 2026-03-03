<?php

namespace App\Policies;

use App\Models\User;

class PurchaseReportPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_purchase_reports');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_purchase_reports');
    }
}
