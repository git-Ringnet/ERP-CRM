<?php

namespace App\Policies;

use App\Models\User;

class BusinessDashboardPolicy extends BasePolicy
{
    /**
     * Determine if the user can view the business dashboard.
     *
     * @param User $user
     * @return bool
     */
    public function viewDashboard(User $user): bool
    {
        return $this->checkPermission($user, 'view_business_dashboard');
    }

    /**
     * Determine if the user can export business reports.
     *
     * @param User $user
     * @return bool
     */
    public function exportReports(User $user): bool
    {
        return $this->checkPermission($user, 'export_business_reports');
    }
}
