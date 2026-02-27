<?php

namespace App\Services;

use App\Models\Role;

interface AuditServiceInterface
{
    /**
     * Log a generic audit entry.
     *
     * @param string $actionType Action type (created, updated, deleted, assigned, removed)
     * @param string $entityType Entity type (role, permission, user_role, user_permission)
     * @param int $entityId Entity ID
     * @param array $data Additional data (old_value, new_value)
     * @return void
     */
    public function log(string $actionType, string $entityType, int $entityId, array $data): void;

    /**
     * Log role creation.
     *
     * @param Role $role The created role
     * @param int $actorId The user who created the role
     * @return void
     */
    public function logRoleCreated(Role $role, int $actorId): void;

    /**
     * Log role update.
     *
     * @param Role $role The updated role
     * @param array $changes Array of changes (field => [old, new])
     * @param int $actorId The user who updated the role
     * @return void
     */
    public function logRoleUpdated(Role $role, array $changes, int $actorId): void;

    /**
     * Log role deletion.
     *
     * @param int $roleId The deleted role ID
     * @param int $actorId The user who deleted the role
     * @return void
     */
    public function logRoleDeleted(int $roleId, int $actorId): void;

    /**
     * Log role assignment to user.
     *
     * @param int $userId The user receiving the role
     * @param int $roleId The role being assigned
     * @param int $actorId The user performing the assignment
     * @return void
     */
    public function logRoleAssignment(int $userId, int $roleId, int $actorId): void;

    /**
     * Log permission assignment to role.
     *
     * @param int $roleId The role receiving permissions
     * @param array $permissionIds Array of permission IDs being assigned
     * @param int $actorId The user performing the assignment
     * @return void
     */
    public function logPermissionAssignment(int $roleId, array $permissionIds, int $actorId): void;

    /**
     * Log unauthorized access attempt.
     *
     * @param int $userId The user attempting unauthorized access
     * @param string $resource The resource being accessed
     * @return void
     */
    public function logUnauthorizedAccess(int $userId, string $resource): void;
}
