<?php

namespace App\Policies;

use App\Models\WorkSchedule;
use App\Models\User;

class WorkSchedulePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_work_schedules');
    }

    public function view(User $user, WorkSchedule $workSchedule): bool
    {
        return $this->checkPermission($user, 'view_work_schedules');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_work_schedules');
    }

    public function update(User $user, WorkSchedule $workSchedule): bool
    {
        return $this->checkPermission($user, 'edit_work_schedules');
    }

    public function delete(User $user, WorkSchedule $workSchedule): bool
    {
        return $this->checkPermission($user, 'delete_work_schedules');
    }
}
