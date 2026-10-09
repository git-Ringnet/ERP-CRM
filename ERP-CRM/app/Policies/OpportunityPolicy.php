<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

class OpportunityPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'sales_manager']) ||
            $this->checkPermission($user, 'view_opportunities') ||
            \App\Models\OpportunityAttendee::where('user_id', $user->id)->exists();
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return true;
        }

        if ($opportunity->assigned_to === $user->id ||
            $opportunity->created_by === $user->id ||
            $opportunity->technical_user_id === $user->id) {
            return true;
        }

        return $opportunity->attendees()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_opportunities');
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'sales_manager'])) {
            return true;
        }

        // Only assigned sales or creator with edit_opportunities permission can edit
        if ($opportunity->assigned_to === $user->id || $opportunity->created_by === $user->id) {
            return $this->checkPermission($user, 'edit_opportunities');
        }

        return false;
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($opportunity->created_by === $user->id || $opportunity->assigned_to === $user->id) {
            return $this->checkPermission($user, 'delete_opportunities');
        }

        return false;
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_opportunities');
    }
}
