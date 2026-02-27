<?php

namespace App\Policies;

use App\Models\Export;
use App\Models\User;

class ExportPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any exports.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $this->checkPermission($user, 'view_exports');
    }

    /**
     * Determine whether the user can view the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function view(User $user, Export $export): bool
    {
        return $this->checkPermission($user, 'view_exports');
    }

    /**
     * Determine whether the user can create exports.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create_exports');
    }

    /**
     * Determine whether the user can update the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function update(User $user, Export $export): bool
    {
        return $this->checkPermission($user, 'edit_exports');
    }

    /**
     * Determine whether the user can delete the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function delete(User $user, Export $export): bool
    {
        return $this->checkPermission($user, 'delete_exports');
    }

    /**
     * Determine whether the user can approve the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function approve(User $user, Export $export): bool
    {
        return $this->checkPermission($user, 'approve_exports');
    }
}
