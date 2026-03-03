<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_activity_logs');
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $this->checkPermission($user, 'view_activity_logs');
    }
}
