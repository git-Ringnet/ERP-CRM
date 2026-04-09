<?php

namespace App\Services;

use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use Illuminate\Support\Collection;

class PermissionService implements PermissionServiceInterface
{
    /**
     * Cache key format for user permissions.
     */
    private const CACHE_KEY_FORMAT = 'user_permissions:%d';

    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Create a new PermissionService instance.
     *
     * @param CacheServiceInterface $cache
     * @param PermissionRepository $permissionRepo
     * @param RoleRepository $roleRepo
     */
    public function __construct(
        private CacheServiceInterface $cache,
        private PermissionRepository $permissionRepo,
        private RoleRepository $roleRepo
    ) {}

    /**
     * Get a user's effective permissions (with caching).
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserPermissions(int $userId): Collection
    {
        $cacheKey = $this->getCacheKey($userId);

        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => $this->computeEffectivePermissions($userId)
        );
    }

    /**
     * Check if a user has a specific permission.
     *
     * @param int $userId
     * @param string $permission Permission slug
     * @return bool
     */
    public function checkPermission(int $userId, string $permission): bool
    {
        // Super Admin bypass: if user has super_admin role, grant all permissions
        $user = \App\Models\User::find($userId);
        if ($user && $user->roles()->where('slug', 'super_admin')->exists()) {
            return true;
        }

        $permissions = $this->getUserPermissions($userId);

        return $permissions->contains('slug', $permission);
    }

    /**
     * Compute effective permissions for a user.
     * Returns the union of role-based permissions and direct permissions.
     *
     * @param int $userId
     * @return Collection
     */
    public function computeEffectivePermissions(int $userId): Collection
    {
        // Get role-based permissions (only from active roles)
        $rolePermissions = $this->roleRepo->getUserRolePermissions($userId);

        // Get direct permissions
        $directPermissions = $this->permissionRepo->getUserDirectPermissions($userId);

        // Return union (merge and remove duplicates by id)
        return $rolePermissions->merge($directPermissions)->unique('id');
    }

    /**
     * Invalidate a user's permission cache.
     *
     * @param int $userId
     * @return void
     */
    public function invalidateUserCache(int $userId): void
    {
        $this->cache->forget($this->getCacheKey($userId));
    }

    /**
     * Invalidate permission cache for all users with a specific role.
     *
     * @param int $roleId
     * @return void
     */
    public function invalidateRoleUsersCache(int $roleId): void
    {
        // Get all user IDs associated with this role
        $role = $this->roleRepo->findById($roleId);
        if (!$role) return;

        $userIds = $role->users()->pluck('users.id')->toArray();

        // Invalidate cache for each user
        foreach ($userIds as $userId) {
            $this->invalidateUserCache($userId);
        }
    }

    /**
     * Get the cache key for a specific user's permissions.
     */
    private function getCacheKey(int $userId): string
    {
        return sprintf(self::CACHE_KEY_FORMAT, $userId);
    }
}
