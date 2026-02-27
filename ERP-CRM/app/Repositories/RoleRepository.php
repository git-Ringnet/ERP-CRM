<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class RoleRepository
{
    /**
     * Find a role by ID.
     *
     * @param int $id
     * @return Role|null
     */
    public function findById(int $id): ?Role
    {
        return Role::find($id);
    }

    /**
     * Find a role by slug.
     *
     * @param string $slug
     * @return Role|null
     */
    public function findBySlug(string $slug): ?Role
    {
        return Role::where('slug', $slug)->first();
    }

    /**
     * Get all roles, optionally filtering by active status.
     *
     * @param bool $activeOnly
     * @return Collection
     */
    public function getAll(bool $activeOnly = false): Collection
    {
        $query = Role::query();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get all roles assigned to a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserRoles(int $userId): Collection
    {
        return Role::whereHas('users', function ($query) use ($userId) {
            $query->where('users.id', $userId);
        })->get();
    }

    /**
     * Get all permissions from all roles assigned to a user.
     * Returns the union of all permissions from all user's active roles.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserRolePermissions(int $userId): Collection
    {
        return Role::whereHas('users', function ($query) use ($userId) {
            $query->where('users.id', $userId);
        })
        ->where('status', 'active')
        ->with('permissions')
        ->get()
        ->pluck('permissions')
        ->flatten()
        ->unique('id');
    }

    /**
     * Attach a role to a user.
     *
     * @param int $userId
     * @param int $roleId
     * @param int $assignedBy
     * @return void
     */
    public function attachUserRole(int $userId, int $roleId, int $assignedBy): void
    {
        $user = User::findOrFail($userId);
        $role = Role::findOrFail($roleId);

        $user->roles()->syncWithoutDetaching([
            $roleId => [
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
            ]
        ]);
    }

    /**
     * Detach a role from a user.
     *
     * @param int $userId
     * @param int $roleId
     * @return void
     */
    public function detachUserRole(int $userId, int $roleId): void
    {
        $user = User::findOrFail($userId);
        $user->roles()->detach($roleId);
    }

    /**
     * Check if a role has any users assigned to it.
     *
     * @param int $roleId
     * @return bool
     */
    public function hasUsers(int $roleId): bool
    {
        $role = Role::findOrFail($roleId);
        return $role->users()->exists();
    }
}
