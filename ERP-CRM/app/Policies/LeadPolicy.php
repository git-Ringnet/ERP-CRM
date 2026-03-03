<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_leads');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->checkPermission($user, 'view_leads');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_leads');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->checkPermission($user, 'edit_leads');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->checkPermission($user, 'delete_leads');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_leads');
    }
}
