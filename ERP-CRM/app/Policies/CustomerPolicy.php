<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any customers.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_customers');
    }

    /**
     * Determine whether the user can view the customer.
     *
     * @param User $user
     * @param Customer $customer
     * @return bool
     */
    public function view(User $user, Customer $customer): bool
    {
        return $this->checkPermission($user, 'view_customers');
    }

    /**
     * Determine whether the user can create customers.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_customers');
    }

    /**
     * Determine whether the user can update the customer.
     *
     * @param User $user
     * @param Customer $customer
     * @return bool
     */
    public function update(User $user, Customer $customer): bool
    {
        return $this->checkPermission($user, 'edit_customers');
    }

    /**
     * Determine whether the user can delete the customer.
     *
     * @param User $user
     * @param Customer $customer
     * @return bool
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $this->checkPermission($user, 'delete_customers');
    }
}
