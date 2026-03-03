<?php

namespace App\Policies;

use App\Models\User;

class ExcelImportPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_excel_imports');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_excel_imports');
    }
}
