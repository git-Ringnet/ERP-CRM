<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\RoleRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleService implements RoleServiceInterface
{
    /**
     * Create a new RoleService instance.
     *
     * @param RoleRepository $roleRepo
     * @param CacheServiceInterface $cache
     * @param AuditServiceInterface $audit
     */
    public function __construct(
        private RoleRepository $roleRepo,
        private CacheServiceInterface $cache,
        private AuditServiceInterface $audit
    ) {}

    /**
     * Create a new role.
     * Validates uniqueness of role slug and logs creation.
     *
     * @param array $data Role data (name, slug, description, status)
     * @return Role
     * @throws ValidationException
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            // Check for uniqueness
            if (isset($data['slug']) && $this->roleRepo->findBySlug($data['slug'])) {
                throw ValidationException::withMessages([
                    'slug' => ['A role with this slug already exists.'],
                ]);
            }

            // Create the role
            $role = Role::create($data);

            // Log creation
            $this->audit->logRoleCreated($role, auth()->id() ?? 0);

            return $role;
        });
    }

    /**
     * Update an existing role.
     * Preserves role ID and updates only specified fields.
     * Logs changes.
     *
     * @param int $roleId
     * @param array $data Role data to update
     * @return Role
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws ValidationException
     */
    public function updateRole(int $roleId, array $data): Role
    {
        return DB::transaction(function () use ($roleId, $data) {
            $role = $this->roleRepo->findById($roleId);

            if (!$role) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                    "Role with ID {$roleId} not found."
                );
            }

            // Check for slug uniqueness if slug is being changed
            if (isset($data['slug']) && $data['slug'] !== $role->slug) {
                $existingRole = $this->roleRepo->findBySlug($data['slug']);
                if ($existingRole) {
                    throw ValidationException::withMessages([
                        'slug' => ['A role with this slug already exists.'],
                    ]);
                }
            }

            // Track changes for audit log
            $changes = [];
            foreach ($data as $field => $newValue) {
                if ($role->{$field} !== $newValue) {
                    $changes[$field] = [$role->{$field}, $newValue];
                }
            }

            // Update the role
            $role->update($data);

            // Log changes if any
            if (!empty($changes)) {
                $this->audit->logRoleUpdated($role, $changes, auth()->id() ?? 0);
            }

            return $role;
        });
    }

    /**
     * Delete a role.
     * Checks for user assignments and prevents deletion if users are assigned.
     * Logs deletion.
     *
     * @param int $roleId
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws ValidationException
     */
    public function deleteRole(int $roleId): bool
    {
        return DB::transaction(function () use ($roleId) {
            $role = $this->roleRepo->findById($roleId);

            if (!$role) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                    "Role with ID {$roleId} not found."
                );
            }

            // Check if role has users assigned
            if ($this->roleRepo->hasUsers($roleId)) {
                throw ValidationException::withMessages([
                    'role' => ['Cannot delete role with assigned users.'],
                ]);
            }

            // Log deletion before deleting
            $this->audit->logRoleDeleted($roleId, auth()->id() ?? 0);

            // Delete the role (cascade will remove permission assignments)
            return $role->delete();
        });
    }

    /**
     * Assign a role to a user.
     * Verifies both user and role exist, attaches role, invalidates cache, and logs.
     *
     * @param int $userId
     * @param int $roleId
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function assignRoleToUser(int $userId, int $roleId): void
    {
        DB::transaction(function () use ($userId, $roleId) {
            // Verify user exists
            $user = User::findOrFail($userId);

            // Verify role exists
            $role = $this->roleRepo->findById($roleId);
            if (!$role) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                    "Role with ID {$roleId} not found."
                );
            }

            // Attach role to user
            $assignedBy = auth()->id();
            $this->roleRepo->attachUserRole($userId, $roleId, $assignedBy);

            // Invalidate user's permission cache
            $this->cache->forget(sprintf('user_permissions:%d', $userId));

            // Log assignment
            $this->audit->logRoleAssignment($userId, $roleId, auth()->id() ?? 0);
        });
    }

    /**
     * Remove a role from a user.
     * Detaches role, invalidates cache, and logs.
     *
     * @param int $userId
     * @param int $roleId
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function removeRoleFromUser(int $userId, int $roleId): void
    {
        DB::transaction(function () use ($userId, $roleId) {
            // Verify user exists
            $user = User::findOrFail($userId);

            // Verify role exists
            $role = $this->roleRepo->findById($roleId);
            if (!$role) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                    "Role with ID {$roleId} not found."
                );
            }

            // Detach role from user
            $this->roleRepo->detachUserRole($userId, $roleId);

            // Invalidate user's permission cache
            $this->cache->forget(sprintf('user_permissions:%d', $userId));

            // Log removal
            $this->audit->log(
                actionType: 'removed',
                entityType: 'user_role',
                entityId: $userId,
                data: [
                    'old_value' => [
                        'user_id' => $userId,
                        'role_id' => $roleId,
                    ],
                ]
            );
        });
    }

    /**
     * Sync user roles (replace all roles with the given set).
     * Syncs roles, invalidates cache, and logs.
     *
     * @param int $userId
     * @param array $roleIds
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function syncUserRoles(int $userId, array $roleIds): void
    {
        DB::transaction(function () use ($userId, $roleIds) {
            // Verify user exists
            $user = User::findOrFail($userId);

            // Get current roles for audit log
            $oldRoleIds = $user->roles()->pluck('roles.id')->toArray();

            // Prepare sync data with assigned_by and assigned_at
            $syncData = [];
            $assignedBy = auth()->id();
            foreach ($roleIds as $roleId) {
                $syncData[$roleId] = [
                    'assigned_by' => $assignedBy,
                    'assigned_at' => now(),
                ];
            }

            // Sync roles
            $user->roles()->sync($syncData);

            // Invalidate user's permission cache
            $this->cache->forget(sprintf('user_permissions:%d', $userId));

            // Log sync operation
            $this->audit->log(
                actionType: 'synced',
                entityType: 'user_role',
                entityId: $userId,
                data: [
                    'old_value' => ['role_ids' => $oldRoleIds],
                    'new_value' => ['role_ids' => $roleIds],
                ]
            );
        });
    }

    /**
     * Assign permissions to a role.
     * Syncs permissions, invalidates cache for all users with this role, and logs.
     *
     * @param int $roleId
     * @param array $permissionIds
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function assignPermissionsToRole(int $roleId, array $permissionIds): void
    {
        DB::transaction(function () use ($roleId, $permissionIds) {
            // Verify role exists
            $role = $this->roleRepo->findById($roleId);
            if (!$role) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                    "Role with ID {$roleId} not found."
                );
            }

            // Get current permissions for audit log
            $oldPermissionIds = $role->permissions()->pluck('permissions.id')->toArray();

            // Sync permissions
            $role->syncPermissions($permissionIds);

            // Invalidate cache for all users with this role
            $users = $role->users;
            foreach ($users as $user) {
                $this->cache->forget(sprintf('user_permissions:%d', $user->id));
            }

            // Log permission assignment
            $this->audit->log(
                actionType: 'synced',
                entityType: 'role_permission',
                entityId: $roleId,
                data: [
                    'old_value' => ['permission_ids' => $oldPermissionIds],
                    'new_value' => ['permission_ids' => $permissionIds],
                ]
            );
        });
    }
}
