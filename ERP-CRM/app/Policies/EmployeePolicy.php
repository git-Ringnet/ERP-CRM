<?php

namespace App\Policies;

use App\Models\User;

class EmployeePolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any employees.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_employees');
    }

    /**
     * Determine whether the user can view the employee.
     *
     * @param User $user
     * @param User $employee
     * @return bool
     */
    public function view(User $user, User $employee): bool
    {
        return $this->checkPermission($user, 'view_employees');
    }

    /**
     * Determine whether the user can create employees.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_employees');
    }

    /**
     * Determine whether the user can update the employee.
     *
     * @param User $user
     * @param User $employee
     * @return bool
     */
    public function update(User $user, User $employee): bool
    {
        return $this->checkPermission($user, 'edit_employees');
    }

    /**
     * Determine whether the user can delete the employee.
     *
     * @param User $user
     * @param User $employee
     * @return bool
     */
    public function delete(User $user, User $employee): bool
    {
        return $this->checkPermission($user, 'delete_employees');
    }
}
