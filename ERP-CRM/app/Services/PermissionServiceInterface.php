<?php

namespace App\Services;

use Illuminate\Support\Collection;

interface PermissionServiceInterface
{
    /**
     * Get a user's effective permissions (with caching).
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserPermissions(int $userId): Collection;

    /**
     * Check if a user has a specific permission.
     *
     * @param int $userId
     * @param string $permission Permission slug
     * @return bool
     */
    public function checkPermission(int $userId, string $permission): bool;

    /**
     * Compute effective permissions for a user.
     * Returns the union of role-based permissions and direct permissions.
     *
     * @param int $userId
     * @return Collection
     */
    public function computeEffectivePermissions(int $userId): Collection;

    /**
     * Invalidate a user's permission cache.
     *
     * @param int $userId
     * @return void
     */
    public function invalidateUserCache(int $userId): void;

    /**
     * Invalidate permission cache for all users with a specific role.
     *
     * @param int $roleId
     * @return void
     */
    public function invalidateRoleUsersCache(int $roleId): void;
}
