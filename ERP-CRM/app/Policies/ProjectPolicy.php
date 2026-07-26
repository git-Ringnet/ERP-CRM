<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'purchase_manager', 'purchase_staff']) || 
            $user->department === 'PM' || 
            $user->department === 'PO') {
            return true;
        }
        return $this->checkPermission($user, 'view_projects');
    }

    public function view(User $user, Project $project): bool
    {
        // Admin, PM, PO, BOD (director) see all
        if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'purchase_manager', 'purchase_staff']) || 
            $user->department === 'PM' || 
            $user->department === 'PO') {
            return true;
        }

        // Sales Manager see team projects
        if ($user->hasRole('sales_manager')) {
            return $user->department === $project->manager?->department;
        }

        // Sales Staff see own projects
        return $user->id === $project->manager_id;
    }

    public function viewReport(User $user): bool
    {
        // Admin, PM, PO, BOD (director) see dashboard and report
        return $user->hasAnyRole(['super_admin', 'admin', 'director', 'purchase_manager', 'purchase_staff']) || 
               $user->department === 'PM' || 
               $user->department === 'PO';
    }

    public function create(User $user): bool
    {
        // BOD (director) cannot create projects
        if ($user->hasRole('director')) {
            return false;
        }
        // PM/PO can create
        if ($user->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff']) || 
            $user->department === 'PM' || 
            $user->department === 'PO') {
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

        // Admin, PM, PO can update
        if ($user->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff']) || 
            $user->department === 'PM' || 
            $user->department === 'PO') {
            return true;
        }

        // Sales owner can update
        return $user->id === $project->manager_id;
    }

    public function delete(User $user, Project $project): bool
    {
        // Only super_admin, admin, PM, PO can delete
        return $user->hasAnyRole(['super_admin', 'admin', 'purchase_manager']) || 
               $user->department === 'PM' || 
               $user->department === 'PO';
    }

    public function export(User $user): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff']) || 
            $user->department === 'PM' || 
            $user->department === 'PO') {
            return true;
        }
        return $this->checkPermission($user, 'export_projects');
    }
}
