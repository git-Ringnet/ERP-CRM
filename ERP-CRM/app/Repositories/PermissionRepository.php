<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Collection;

class PermissionRepository
{
    /**
     * Find a permission by ID.
     *
     * @param int $id
     * @return Permission|null
     */
    public function findById(int $id): ?Permission
    {
        return Permission::find($id);
    }

    /**
     * Find a permission by slug.
     *
     * @param string $slug
     * @return Permission|null
     */
    public function findBySlug(string $slug): ?Permission
    {
        return Permission::where('slug', $slug)->first();
    }

    /**
     * Get all permissions.
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return Permission::all();
    }

    /**
     * Get all permissions for a specific module.
     *
     * @param string $module
     * @return Collection
     */
    public function getByModule(string $module): Collection
    {
        return Permission::byModule($module)->get();
    }

    /**
     * Get all direct permissions assigned to a user.
     * Returns only permissions directly assigned to the user,
     * not permissions inherited from roles.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserDirectPermissions(int $userId): Collection
    {
        return Permission::whereHas('users', function ($query) use ($userId) {
            $query->where('users.id', $userId);
        })->get();
    }

    /**
     * Attach a direct permission to a user.
     *
     * @param int $userId
     * @param int $permissionId
     * @param int $assignedBy
     * @return void
     */
    public function attachUserPermission(int $userId, int $permissionId, int $assignedBy): void
    {
        $user = User::findOrFail($userId);
        $permission = Permission::findOrFail($permissionId);

        $user->directPermissions()->syncWithoutDetaching([
            $permissionId => [
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
            ]
        ]);
    }

    /**
     * Detach a direct permission from a user.
     *
     * @param int $userId
     * @param int $permissionId
     * @return void
     */
    public function detachUserPermission(int $userId, int $permissionId): void
    {
        $user = User::findOrFail($userId);
        $user->directPermissions()->detach($permissionId);
    }
}
