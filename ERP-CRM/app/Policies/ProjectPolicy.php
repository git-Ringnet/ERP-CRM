<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_projects');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->checkPermission($user, 'view_projects');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_projects');
    }

    public function update(User $user, Project $project): bool
    {
        return $this->checkPermission($user, 'edit_projects');
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->checkPermission($user, 'delete_projects');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_projects');
    }
}
