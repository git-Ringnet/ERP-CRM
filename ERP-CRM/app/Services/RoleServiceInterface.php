<?php

namespace App\Services;

use App\Models\Role;

interface RoleServiceInterface
{
    /**
     * Create a new role.
     *
     * @param array $data Role data (name, slug, description, status)
     * @return Role
     */
    public function createRole(array $data): Role;

    /**
     * Update an existing role.
     *
     * @param int $roleId
     * @param array $data Role data to update
     * @return Role
     */
    public function updateRole(int $roleId, array $data): Role;

    /**
     * Delete a role.
     *
     * @param int $roleId
     * @return bool
     */
    public function deleteRole(int $roleId): bool;

    /**
     * Assign a role to a user.
     *
     * @param int $userId
     * @param int $roleId
     * @return void
     */
    public function assignRoleToUser(int $userId, int $roleId): void;

    /**
     * Remove a role from a user.
     *
     * @param int $userId
     * @param int $roleId
     * @return void
     */
    public function removeRoleFromUser(int $userId, int $roleId): void;

    /**
     * Sync user roles (replace all roles with the given set).
     *
     * @param int $userId
     * @param array $roleIds
     * @return void
     */
    public function syncUserRoles(int $userId, array $roleIds): void;

    /**
     * Assign permissions to a role.
     *
     * @param int $roleId
     * @param array $permissionIds
     * @return void
     */
    public function assignPermissionsToRole(int $roleId, array $permissionIds): void;
}
