<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Payroll;

class PayrollPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any payrolls.
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_employees');
    }

    /**
     * Determine whether the user can view the payroll.
     */
    public function view(User $user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'view_employees');
    }

    /**
     * Determine whether the user can create payrolls.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_employees');
    }

    /**
     * Determine whether the user can update the payroll.
     */
    public function update(User $user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'edit_employees');
    }

    /**
     * Determine whether the user can delete the payroll.
     */
    public function delete(User $user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'delete_employees');
    }
}
