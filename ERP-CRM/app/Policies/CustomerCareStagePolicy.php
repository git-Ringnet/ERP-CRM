<?php

namespace App\Policies;

use App\Models\CustomerCareStage;
use App\Models\User;

class CustomerCareStagePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_customer_care_stages');
    }

    public function view(User $user, CustomerCareStage $customerCareStage): bool
    {
        return $this->checkPermission($user, 'view_customer_care_stages');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_customer_care_stages');
    }

    public function update(User $user, CustomerCareStage $customerCareStage): bool
    {
        return $this->checkPermission($user, 'edit_customer_care_stages');
    }

    public function delete(User $user, CustomerCareStage $customerCareStage): bool
    {
        return $this->checkPermission($user, 'delete_customer_care_stages');
    }
}
