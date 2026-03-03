<?php

namespace App\Policies;

use App\Models\CostFormula;
use App\Models\User;

class CostFormulaPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_cost_formulas');
    }

    public function view(User $user, CostFormula $costFormula): bool
    {
        return $this->checkPermission($user, 'view_cost_formulas');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_cost_formulas');
    }

    public function update(User $user, CostFormula $costFormula): bool
    {
        return $this->checkPermission($user, 'edit_cost_formulas');
    }

    public function delete(User $user, CostFormula $costFormula): bool
    {
        return $this->checkPermission($user, 'delete_cost_formulas');
    }
}
