<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'purchase_manager', 'purchase_staff']) || 
            in_array($user->department, ['PM', 'PO', 'PM Team', 'PO Team'])) {
            return true;
        }
        return $this->checkPermission($user, 'view_projects');
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'director']) ||
            in_array($user->department, ['PM', 'PM Team'], true)) {
            return true;
        }

        if ($user->hasAnyRole(['purchase_manager', 'purchase_staff']) ||
            in_array($user->department, ['PO', 'PO Team'], true)) {
            return str_contains(strtolower((string) $project->vendor?->name), 'fortinet');
        }

        // Sales Manager see team projects
        if ($user->hasRole('sales_manager')) {
            return $user->department === $project->manager?->department ||
                   $user->department === $project->secondaryManager?->department;
        }

        // Sales Staff see own projects (primary or secondary PIC or follower)
        return $user->id === $project->manager_id || 
               $user->id === $project->secondary_manager_id ||
               ($project->relationLoaded('followers') ? $project->followers->contains('id', $user->id) : $project->followers()->where('users.id', $user->id)->exists());
    }

    public function viewReport(User $user): bool
    {
        // Admin, PM, PO, BOD (director) see dashboard and report
        return $user->hasAnyRole(['super_admin', 'admin', 'director', 'purchase_manager', 'purchase_staff']) || 
               in_array($user->department, ['PM', 'PO', 'PM Team', 'PO Team']);
    }

    public function create(User $user): bool
    {
        // BOD (director) cannot create projects
        if ($user->hasRole('director')) {
            return false;
        }
        // PM/PO can create
        if ($user->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff']) || 
            in_array($user->department, ['PM', 'PO', 'PM Team', 'PO Team'])) {
            return true;
        }
        return $this->checkPermission($user, 'create_projects');
    }

    public function update(User $user, Project $project): bool
    {
        // BOD (director) cannot update projects
        if ($user->hasRole('director')) {
            return false;
        }

        // Admin and PM can update. PO can update Fortinet projects.
        if ($user->hasAnyRole(['super_admin', 'admin']) ||
            in_array($user->department, ['PM', 'PM Team'], true)) {
            return true;
        }

        if ($user->hasAnyRole(['purchase_manager', 'purchase_staff']) ||
            in_array($user->department, ['PO', 'PO Team'], true)) {
            return str_contains(strtolower((string) $project->vendor?->name), 'fortinet');
        }

        // Sales owner can update (primary or secondary PIC or follower)
        return $user->id === $project->manager_id || 
               $user->id === $project->secondary_manager_id ||
               ($project->relationLoaded('followers') ? $project->followers->contains('id', $user->id) : $project->followers()->where('users.id', $user->id)->exists());
    }

    public function updateSecondaryPic(User $user, Project $project): bool
    {
        // BOD (director) cannot update projects
        if ($user->hasRole('director')) {
            return false;
        }

        // Admin and PM can update
        if ($user->hasAnyRole(['super_admin', 'admin']) ||
            in_array($user->department, ['PM', 'PM Team'], true)) {
            return true;
        }

        // Sales Manager can update projects in their team
        if ($user->hasRole('sales_manager')) {
            return $user->department === $project->manager?->department ||
                   $user->department === $project->secondaryManager?->department;
        }

        // Chỉ người tạo / người phụ trách chính (manager_id) mới có quyền phân công hoặc đổi người phụ trách thứ 2
        // Người theo dõi (followers) và người phụ trách thứ 2 không được phép thay đổi người phụ trách
        return $user->id === $project->manager_id;
    }

    public function processIntake(User $user, Project $project): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin']) || in_array($user->department, ['PM', 'PM Team'], true)) {
            return true;
        }

        return ($user->hasAnyRole(['purchase_manager', 'purchase_staff']) || in_array($user->department, ['PO', 'PO Team'], true)) &&
            (str_contains(strtolower((string) $project->vendor?->name), 'fortinet') || $project->assigned_team === 'po_team');
    }

    public function delete(User $user, Project $project): bool
    {
        // Only super_admin, admin, PM, PO can delete
        return $user->hasAnyRole(['super_admin', 'admin', 'purchase_manager']) || 
               in_array($user->department, ['PM', 'PO', 'PM Team', 'PO Team']);
    }

    public function export(User $user): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff']) || 
            in_array($user->department, ['PM', 'PO', 'PM Team', 'PO Team'])) {
            return true;
        }
        return $this->checkPermission($user, 'export_projects');
    }
}
