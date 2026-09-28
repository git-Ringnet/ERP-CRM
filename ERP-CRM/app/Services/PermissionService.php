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
     * In-memory request cache to eliminate repeated disk/database hits during a single request.
     */
    private static array $requestPermissionsCache = [];
    private static array $requestSuperAdminCache = [];

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
     * Get a user's effective permissions (with caching and request-level memoization).
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserPermissions(int $userId): Collection
    {
        if (isset(self::$requestPermissionsCache[$userId])) {
            return self::$requestPermissionsCache[$userId];
        }

        $cacheKey = $this->getCacheKey($userId);

        $permissions = $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => $this->computeEffectivePermissions($userId)
        );

        self::$requestPermissionsCache[$userId] = $permissions;

        return $permissions;
    }

    /**
     * Check if a user has a specific permission (ultra-fast in-memory check).
     *
     * @param int $userId
     * @param string $permission Permission slug
     * @return bool
     */
    public function checkPermission(int $userId, string $permission): bool
    {
        // 1. Super Admin bypass: memoize per request to avoid repeated DB query
        if (!array_key_exists($userId, self::$requestSuperAdminCache)) {
            $user = (auth()->check() && auth()->id() === $userId) 
                ? auth()->user() 
                : \App\Models\User::find($userId);
            
            self::$requestSuperAdminCache[$userId] = $user ? $user->hasRole('super_admin') : false;
        }

        if (self::$requestSuperAdminCache[$userId]) {
            return true;
        }

        // 2. Check permission in user's permissions collection
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
        unset(self::$requestPermissionsCache[$userId]);
        unset(self::$requestSuperAdminCache[$userId]);
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
        $role = $this->roleRepo->findById($roleId);
        if (!$role) return;

        $userIds = $role->users()->pluck('users.id')->toArray();

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
