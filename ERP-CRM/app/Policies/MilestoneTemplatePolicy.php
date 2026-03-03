<?php

namespace App\Policies;

use App\Models\MilestoneTemplate;
use App\Models\User;

class MilestoneTemplatePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_milestone_templates');
    }

    public function view(User $user, MilestoneTemplate $milestoneTemplate): bool
    {
        return $this->checkPermission($user, 'view_milestone_templates');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_milestone_templates');
    }

    public function update(User $user, MilestoneTemplate $milestoneTemplate): bool
    {
        return $this->checkPermission($user, 'edit_milestone_templates');
    }

    public function delete(User $user, MilestoneTemplate $milestoneTemplate): bool
    {
        return $this->checkPermission($user, 'delete_milestone_templates');
    }
}
