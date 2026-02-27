<?php

namespace App\Policies;

use App\Models\Import;
use App\Models\User;

class ImportPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any imports.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_imports');
    }

    /**
     * Determine whether the user can view the import.
     *
     * @param User $user
     * @param Import $import
     * @return bool
     */
    public function view(User $user, Import $import): bool
    {
        return $this->checkPermission($user, 'view_imports');
    }

    /**
     * Determine whether the user can create imports.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_imports');
    }

    /**
     * Determine whether the user can update the import.
     *
     * @param User $user
     * @param Import $import
     * @return bool
     */
    public function update(User $user, Import $import): bool
    {
        return $this->checkPermission($user, 'edit_imports');
    }

    /**
     * Determine whether the user can delete the import.
     *
     * @param User $user
     * @param Import $import
     * @return bool
     */
    public function delete(User $user, Import $import): bool
    {
        return $this->checkPermission($user, 'delete_imports');
    }

    /**
     * Determine whether the user can approve the import.
     *
     * @param User $user
     * @param Import $import
     * @return bool
     */
    public function approve(User $user, Import $import): bool
    {
        return $this->checkPermission($user, 'approve_imports');
    }
}
