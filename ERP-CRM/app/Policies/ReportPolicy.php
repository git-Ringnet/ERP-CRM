<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any reports.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_reports');
    }

    /**
     * Determine whether the user can export reports.
     *
     * @param User $user
     * @return bool
     */
    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_reports');
    }
}
