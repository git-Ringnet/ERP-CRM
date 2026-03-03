<?php

namespace App\Policies;

use App\Models\ApprovalWorkflow;
use App\Models\User;

class ApprovalWorkflowPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_approval_workflows');
    }

    public function view(User $user, ApprovalWorkflow $approvalWorkflow): bool
    {
        return $this->checkPermission($user, 'view_approval_workflows');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_approval_workflows');
    }

    public function update(User $user, ApprovalWorkflow $approvalWorkflow): bool
    {
        return $this->checkPermission($user, 'edit_approval_workflows');
    }

    public function delete(User $user, ApprovalWorkflow $approvalWorkflow): bool
    {
        return $this->checkPermission($user, 'delete_approval_workflows');
    }
}
