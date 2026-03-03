<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

class OpportunityPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_opportunities');
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        return $this->checkPermission($user, 'view_opportunities');
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
