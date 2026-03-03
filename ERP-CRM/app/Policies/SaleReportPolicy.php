<?php

namespace App\Policies;

use App\Models\User;

class SaleReportPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_sale_reports');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_sale_reports');
    }
}
