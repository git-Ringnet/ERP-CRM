<?php

namespace Tests\Unit\Repositories;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\RoleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private RoleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RoleRepository();
    }

    public function test_find_by_id_returns_role_when_exists(): void
    {
        $role = Role::factory()->create();

        $result = $this->repository->findById($role->id);

        $this->assertNotNull($result);
        $this->assertEquals($role->id, $result->id);
        $this->assertEquals($role->name, $result->name);
    }

    public function test_find_by_id_returns_null_when_not_exists(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_find_by_slug_returns_role_when_exists(): void
    {
        $role = Role::factory()->create(['slug' => 'test-role']);

        $result = $this->repository->findBySlug('test-role');

        $this->assertNotNull($result);
        $this->assertEquals('test-role', $result->slug);
    }

    public function test_find_by_slug_returns_null_when_not_exists(): void
    {
        $result = $this->repository->findBySlug('non-existent');

        $this->assertNull($result);
    }

    public function test_get_all_returns_all_roles_when_active_only_false(): void
    {
        Role::factory()->create(['status' => 'active']);
        Role::factory()->create(['status' => 'inactive']);

        $result = $this->repository->getAll(false);

        $this->assertCount(2, $result);
    }

    public function test_get_all_returns_only_active_roles_when_active_only_true(): void
    {
        Role::factory()->create(['status' => 'active']);
        Role::factory()->create(['status' => 'inactive']);

        $result = $this->repository->getAll(true);

        $this->assertCount(1, $result);
        $this->assertEquals('active', $result->first()->status);
    }

    public function test_get_user_roles_returns_roles_assigned_to_user(): void
    {
        $user = User::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();
        $role3 = Role::factory()->create();

        $user->roles()->attach([$role1->id, $role2->id]);

        $result = $this->repository->getUserRoles($user->id);

        $this->assertCount(2, $result);
        $this->assertContains($role1->id, $result->pluck('id')->toArray());
        $this->assertContains($role2->id, $result->pluck('id')->toArray());
        $this->assertNotContains($role3->id, $result->pluck('id')->toArray());
    }

    public function test_get_user_role_permissions_returns_union_of_permissions_from_active_roles(): void
    {
        $user = User::factory()->create();
        
        $role1 = Role::factory()->create(['status' => 'active']);
        $role2 = Role::factory()->create(['status' => 'active']);
        $inactiveRole = Role::factory()->create(['status' => 'inactive']);

        $perm1 = Permission::factory()->create();
        $perm2 = Permission::factory()->create();
        $perm3 = Permission::factory()->create();
        $perm4 = Permission::factory()->create();

        $role1->permissions()->attach([$perm1->id, $perm2->id]);
        $role2->permissions()->attach([$perm2->id, $perm3->id]); // perm2 is duplicate
        $inactiveRole->permissions()->attach([$perm4->id]);

        $user->roles()->attach([$role1->id, $role2->id, $inactiveRole->id]);

        $result = $this->repository->getUserRolePermissions($user->id);

        // Should return perm1, perm2, perm3 (unique, from active roles only)
        // Should NOT include perm4 (from inactive role)
        $this->assertCount(3, $result);
        $this->assertContains($perm1->id, $result->pluck('id')->toArray());
        $this->assertContains($perm2->id, $result->pluck('id')->toArray());
        $this->assertContains($perm3->id, $result->pluck('id')->toArray());
        $this->assertNotContains($perm4->id, $result->pluck('id')->toArray());
    }

    public function test_attach_user_role_assigns_role_to_user(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $assignedBy = User::factory()->create();

        $this->repository->attachUserRole($user->id, $role->id, $assignedBy->id);

        $this->assertTrue($user->roles()->where('roles.id', $role->id)->exists());
        
        $pivot = $user->roles()->where('roles.id', $role->id)->first()->pivot;
        $this->assertEquals($assignedBy->id, $pivot->assigned_by);
        $this->assertNotNull($pivot->assigned_at);
    }

    public function test_attach_user_role_is_idempotent(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $assignedBy = User::factory()->create();

        $this->repository->attachUserRole($user->id, $role->id, $assignedBy->id);
        $this->repository->attachUserRole($user->id, $role->id, $assignedBy->id);

        $this->assertEquals(1, $user->roles()->where('roles.id', $role->id)->count());
    }

    public function test_detach_user_role_removes_role_from_user(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role->id);

        $this->assertTrue($user->roles()->where('roles.id', $role->id)->exists());

        $this->repository->detachUserRole($user->id, $role->id);

        $this->assertFalse($user->fresh()->roles()->where('roles.id', $role->id)->exists());
    }

    public function test_has_users_returns_true_when_role_has_users(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role->id);

        $result = $this->repository->hasUsers($role->id);

        $this->assertTrue($result);
    }

    public function test_has_users_returns_false_when_role_has_no_users(): void
    {
        $role = Role::factory()->create();

        $result = $this->repository->hasUsers($role->id);

        $this->assertFalse($result);
    }
}
