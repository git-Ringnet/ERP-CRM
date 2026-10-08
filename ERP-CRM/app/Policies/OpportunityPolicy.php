<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

class OpportunityPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'sales_manager']) ||
            $this->checkPermission($user, 'view_opportunities');
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'sales_manager']) ||
            $this->checkPermission($user, 'view_opportunities') ||
            $opportunity->assigned_to === $user->id ||
            $opportunity->created_by === $user->id ||
            $opportunity->technical_user_id === $user->id ||
            $opportunity->attendees()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_opportunities');
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        return $this->checkPermission($user, 'edit_opportunities');
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        return $this->checkPermission($user, 'delete_opportunities');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_opportunities');
    }
}
