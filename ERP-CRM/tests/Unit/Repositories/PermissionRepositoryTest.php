<?php

namespace Tests\Unit\Repositories;

use App\Models\Permission;
use App\Models\User;
use App\Repositories\PermissionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PermissionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PermissionRepository();
    }

    public function test_find_by_id_returns_permission_when_exists(): void
    {
        $permission = Permission::factory()->create();

        $result = $this->repository->findById($permission->id);

        $this->assertNotNull($result);
        $this->assertEquals($permission->id, $result->id);
        $this->assertEquals($permission->name, $result->name);
    }

    public function test_find_by_id_returns_null_when_not_exists(): void
    {
        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    public function test_find_by_slug_returns_permission_when_exists(): void
    {
        $permission = Permission::factory()->create(['slug' => 'view_customers']);

        $result = $this->repository->findBySlug('view_customers');

        $this->assertNotNull($result);
        $this->assertEquals('view_customers', $result->slug);
    }

    public function test_find_by_slug_returns_null_when_not_exists(): void
    {
        $result = $this->repository->findBySlug('non_existent');

        $this->assertNull($result);
    }

    public function test_get_all_returns_all_permissions(): void
    {
        Permission::factory()->count(5)->create();

        $result = $this->repository->getAll();

        $this->assertCount(5, $result);
    }

    public function test_get_by_module_returns_permissions_for_specific_module(): void
    {
        Permission::factory()->create([
            'module' => 'customers',
            'action' => 'view',
            'slug' => 'view_customers',
        ]);
        Permission::factory()->create([
            'module' => 'customers',
            'action' => 'create',
            'slug' => 'create_customers',
        ]);
        Permission::factory()->create([
            'module' => 'sales',
            'action' => 'view',
            'slug' => 'view_sales',
        ]);

        $result = $this->repository->getByModule('customers');

        $this->assertCount(2, $result);
        $this->assertTrue($result->every(fn($p) => $p->module === 'customers'));
    }

    public function test_get_user_direct_permissions_returns_only_direct_permissions(): void
    {
        $user = User::factory()->create();
        
        $perm1 = Permission::factory()->create();
        $perm2 = Permission::factory()->create();
        $perm3 = Permission::factory()->create();

        // Attach direct permissions to user
        $user->directPermissions()->attach([$perm1->id, $perm2->id]);

        $result = $this->repository->getUserDirectPermissions($user->id);

        $this->assertCount(2, $result);
        $this->assertContains($perm1->id, $result->pluck('id')->toArray());
        $this->assertContains($perm2->id, $result->pluck('id')->toArray());
        $this->assertNotContains($perm3->id, $result->pluck('id')->toArray());
    }

    public function test_attach_user_permission_assigns_permission_to_user(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create();
        $assignedBy = User::factory()->create();

        $this->repository->attachUserPermission($user->id, $permission->id, $assignedBy->id);

        $this->assertTrue($user->directPermissions()->where('permissions.id', $permission->id)->exists());
        
        $pivot = $user->directPermissions()->where('permissions.id', $permission->id)->first()->pivot;
        $this->assertEquals($assignedBy->id, $pivot->assigned_by);
        $this->assertNotNull($pivot->assigned_at);
    }

    public function test_attach_user_permission_is_idempotent(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create();
        $assignedBy = User::factory()->create();

        $this->repository->attachUserPermission($user->id, $permission->id, $assignedBy->id);
        $this->repository->attachUserPermission($user->id, $permission->id, $assignedBy->id);

        $this->assertEquals(1, $user->directPermissions()->where('permissions.id', $permission->id)->count());
    }

    public function test_detach_user_permission_removes_permission_from_user(): void
    {
        $user = User::factory()->create();
        $permission = Permission::factory()->create();
        $user->directPermissions()->attach($permission->id);

        $this->assertTrue($user->directPermissions()->where('permissions.id', $permission->id)->exists());

        $this->repository->detachUserPermission($user->id, $permission->id);

        $this->assertFalse($user->fresh()->directPermissions()->where('permissions.id', $permission->id)->exists());
    }
}
