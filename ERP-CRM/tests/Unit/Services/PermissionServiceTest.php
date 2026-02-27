<?php

namespace Tests\Unit\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Services\CacheServiceInterface;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PermissionService $service;
    private CacheServiceInterface $cache;
    private PermissionRepository $permissionRepo;
    private RoleRepository $roleRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = app(CacheServiceInterface::class);
        $this->permissionRepo = app(PermissionRepository::class);
        $this->roleRepo = app(RoleRepository::class);
        $this->service = new PermissionService(
            $this->cache,
            $this->permissionRepo,
            $this->roleRepo
        );
    }

    public function test_compute_effective_permissions_returns_union_of_role_and_direct_permissions(): void
    {
        // Create user
        $user = User::factory()->create();

        // Create role with permissions
        $role = Role::factory()->create(['status' => 'active']);
        $rolePermissions = Permission::factory()->count(3)->create();
        $role->permissions()->attach($rolePermissions->pluck('id'));

        // Assign role to user
        $user->roles()->attach($role->id);

        // Create direct permissions
        $directPermissions = Permission::factory()->count(2)->create();
        $user->directPermissions()->attach($directPermissions->pluck('id'));

        // Compute effective permissions
        $effective = $this->service->computeEffectivePermissions($user->id);

        // Should have 5 unique permissions (3 from role + 2 direct)
        $this->assertCount(5, $effective);
        $this->assertTrue($effective->contains('id', $rolePermissions[0]->id));
        $this->assertTrue($effective->contains('id', $directPermissions[0]->id));
    }

    public function test_compute_effective_permissions_excludes_inactive_role_permissions(): void
    {
        // Create user
        $user = User::factory()->create();

        // Create active role with permissions
        $activeRole = Role::factory()->create(['status' => 'active']);
        $activePermissions = Permission::factory()->count(2)->create();
        $activeRole->permissions()->attach($activePermissions->pluck('id'));

        // Create inactive role with permissions
        $inactiveRole = Role::factory()->create(['status' => 'inactive']);
        $inactivePermissions = Permission::factory()->count(2)->create();
        $inactiveRole->permissions()->attach($inactivePermissions->pluck('id'));

        // Assign both roles to user
        $user->roles()->attach([$activeRole->id, $inactiveRole->id]);

        // Compute effective permissions
        $effective = $this->service->computeEffectivePermissions($user->id);

        // Should only have permissions from active role
        $this->assertCount(2, $effective);
        $this->assertTrue($effective->contains('id', $activePermissions[0]->id));
        $this->assertFalse($effective->contains('id', $inactivePermissions[0]->id));
    }

    public function test_compute_effective_permissions_removes_duplicates(): void
    {
        // Create user
        $user = User::factory()->create();

        // Create shared permission
        $sharedPermission = Permission::factory()->create();

        // Create role with shared permission
        $role = Role::factory()->create(['status' => 'active']);
        $role->permissions()->attach($sharedPermission->id);
        $user->roles()->attach($role->id);

        // Assign same permission directly to user
        $user->directPermissions()->attach($sharedPermission->id);

        // Compute effective permissions
        $effective = $this->service->computeEffectivePermissions($user->id);

        // Should have only 1 permission (no duplicates)
        $this->assertCount(1, $effective);
        $this->assertEquals($sharedPermission->id, $effective->first()->id);
    }

    public function test_get_user_permissions_uses_cache(): void
    {
        // Create user with permissions
        $user = User::factory()->create();
        $role = Role::factory()->create(['status' => 'active']);
        $permissions = Permission::factory()->count(2)->create();
        $role->permissions()->attach($permissions->pluck('id'));
        $user->roles()->attach($role->id);

        // First call should compute and cache
        $result1 = $this->service->getUserPermissions($user->id);
        $this->assertCount(2, $result1);

        // Delete permissions from database
        $role->permissions()->detach();

        // Second call should return cached result (still 2 permissions)
        $result2 = $this->service->getUserPermissions($user->id);
        $this->assertCount(2, $result2);
    }

    public function test_check_permission_returns_true_when_user_has_permission(): void
    {
        // Create user with permission
        $user = User::factory()->create();
        $permission = Permission::factory()->create(['slug' => 'view_customers']);
        $role = Role::factory()->create(['status' => 'active']);
        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);

        // Check permission
        $result = $this->service->checkPermission($user->id, 'view_customers');

        $this->assertTrue($result);
    }

    public function test_check_permission_returns_false_when_user_lacks_permission(): void
    {
        // Create user without permission
        $user = User::factory()->create();

        // Check permission
        $result = $this->service->checkPermission($user->id, 'view_customers');

        $this->assertFalse($result);
    }

    public function test_invalidate_user_cache_clears_cache(): void
    {
        // Create user with permissions
        $user = User::factory()->create();
        $role = Role::factory()->create(['status' => 'active']);
        $permissions = Permission::factory()->count(2)->create();
        $role->permissions()->attach($permissions->pluck('id'));
        $user->roles()->attach($role->id);

        // Cache permissions
        $result1 = $this->service->getUserPermissions($user->id);
        $this->assertCount(2, $result1);

        // Remove permissions
        $role->permissions()->detach();

        // Invalidate cache
        $this->service->invalidateUserCache($user->id);

        // Should now return fresh data (0 permissions)
        $result2 = $this->service->getUserPermissions($user->id);
        $this->assertCount(0, $result2);
    }

    public function test_invalidate_role_users_cache_clears_cache_for_all_users_with_role(): void
    {
        // Create role with permissions
        $role = Role::factory()->create(['status' => 'active']);
        $permissions = Permission::factory()->count(2)->create();
        $role->permissions()->attach($permissions->pluck('id'));

        // Create multiple users with this role
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user1->roles()->attach($role->id);
        $user2->roles()->attach($role->id);

        // Cache permissions for both users
        $this->service->getUserPermissions($user1->id);
        $this->service->getUserPermissions($user2->id);

        // Remove permissions from role
        $role->permissions()->detach();

        // Invalidate cache for all users with this role
        $this->service->invalidateRoleUsersCache($role->id);

        // Both users should now have fresh data (0 permissions)
        $result1 = $this->service->getUserPermissions($user1->id);
        $result2 = $this->service->getUserPermissions($user2->id);

        $this->assertCount(0, $result1);
        $this->assertCount(0, $result2);
    }

    public function test_invalidate_role_users_cache_handles_non_existent_role(): void
    {
        // Should not throw exception for non-existent role
        $this->service->invalidateRoleUsersCache(99999);

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function test_compute_effective_permissions_returns_empty_collection_for_user_without_permissions(): void
    {
        // Create user without any roles or permissions
        $user = User::factory()->create();

        // Compute effective permissions
        $effective = $this->service->computeEffectivePermissions($user->id);

        $this->assertInstanceOf(Collection::class, $effective);
        $this->assertCount(0, $effective);
    }

    public function test_get_user_permissions_returns_collection(): void
    {
        // Create user
        $user = User::factory()->create();

        // Get permissions
        $result = $this->service->getUserPermissions($user->id);

        $this->assertInstanceOf(Collection::class, $result);
    }
}
