<?php

namespace App\Policies;

use App\Models\CustomerDebt;
use App\Models\User;

class CustomerDebtPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_customer_debts');
    }

    public function view(User $user, CustomerDebt $customerDebt): bool
    {
        return $this->checkPermission($user, 'view_customer_debts');
    }

    public function export(User $user): bool
    {
        return $this->checkPermission($user, 'export_customer_debts');
    }

    public function recordPayment(User $user): bool
    {
        return $this->checkPermission($user, 'record_payment_customer_debts');
    }

    public function deletePayment(User $user): bool
    {
        return $this->checkPermission($user, 'delete_payment_customer_debts');
    }
}
