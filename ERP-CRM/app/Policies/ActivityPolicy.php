<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_activities');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $this->checkPermission($user, 'view_activities');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_activities');
    }

    public function update(User $user, Activity $activity): bool
    {
        return $this->checkPermission($user, 'edit_activities');
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $this->checkPermission($user, 'delete_activities');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_activities');
    }
}
