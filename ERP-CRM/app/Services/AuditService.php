<?php

namespace App\Services;

use App\Models\PermissionAuditLog;
use App\Models\Role;
use Illuminate\Support\Facades\Log;

class AuditService implements AuditServiceInterface
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
    public function log(string $actionType, string $entityType, int $entityId, array $data): void
    {
        try {
            PermissionAuditLog::create([
                'user_id' => auth()->id(),
                'action' => $actionType,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'old_value' => $data['old_value'] ?? null,
                'new_value' => $data['new_value'] ?? null,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            // Log the error but don't fail the operation
            Log::error('Failed to create audit log', [
                'action' => $actionType,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log role creation.
     *
     * @param Role $role The created role
     * @param int $actorId The user who created the role
     * @return void
     */
    public function logRoleCreated(Role $role, int $actorId): void
    {
        $this->log(
            actionType: 'created',
            entityType: 'role',
            entityId: $role->id,
            data: [
                'new_value' => [
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'status' => $role->status,
                ],
            ]
        );
    }

    /**
     * Log role update.
     *
     * @param Role $role The updated role
     * @param array $changes Array of changes (field => [old, new])
     * @param int $actorId The user who updated the role
     * @return void
     */
    public function logRoleUpdated(Role $role, array $changes, int $actorId): void
    {
        $oldValue = [];
        $newValue = [];

        foreach ($changes as $field => $values) {
            $oldValue[$field] = $values[0] ?? null;
            $newValue[$field] = $values[1] ?? null;
        }

        $this->log(
            actionType: 'updated',
            entityType: 'role',
            entityId: $role->id,
            data: [
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ]
        );
    }

    /**
     * Log role deletion.
     *
     * @param int $roleId The deleted role ID
     * @param int $actorId The user who deleted the role
     * @return void
     */
    public function logRoleDeleted(int $roleId, int $actorId): void
    {
        $this->log(
            actionType: 'deleted',
            entityType: 'role',
            entityId: $roleId,
            data: []
        );
    }

    /**
     * Log role assignment to user.
     *
     * @param int $userId The user receiving the role
     * @param int $roleId The role being assigned
     * @param int $actorId The user performing the assignment
     * @return void
     */
    public function logRoleAssignment(int $userId, int $roleId, int $actorId): void
    {
        $this->log(
            actionType: 'assigned',
            entityType: 'user_role',
            entityId: $userId,
            data: [
                'new_value' => [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                ],
            ]
        );
    }

    /**
     * Log permission assignment to role.
     *
     * @param int $roleId The role receiving permissions
     * @param array $permissionIds Array of permission IDs being assigned
     * @param int $actorId The user performing the assignment
     * @return void
     */
    public function logPermissionAssignment(int $roleId, array $permissionIds, int $actorId): void
    {
        $this->log(
            actionType: 'assigned',
            entityType: 'role_permission',
            entityId: $roleId,
            data: [
                'new_value' => [
                    'role_id' => $roleId,
                    'permission_ids' => $permissionIds,
                ],
            ]
        );
    }

    /**
     * Log unauthorized access attempt.
     *
     * @param int $userId The user attempting unauthorized access
     * @param string $resource The resource being accessed
     * @return void
     */
    public function logUnauthorizedAccess(int $userId, string $resource): void
    {
        $this->log(
            actionType: 'unauthorized_access',
            entityType: 'access_attempt',
            entityId: $userId,
            data: [
                'new_value' => [
                    'user_id' => $userId,
                    'resource' => $resource,
                ],
            ]
        );
    }
}
