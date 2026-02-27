<?php

namespace Tests\Unit\Traits;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class HasPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->artisan('migrate');
    }

    /** @test */
    public function it_has_direct_permissions_relationship()
    {
        $user = User::factory()->create();
        $permission = Permission::create([
            'name' => 'View Customers',
            'slug' => 'view_customers',
            'description' => 'Can view customers',
            'module' => 'customers',
            'action' => 'view',
        ]);

        $user->directPermissions()->attach($permission->id, [
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->directPermissions());
        $this->assertCount(1, $user->directPermissions);
        $this->assertEquals('view_customers', $user->directPermissions->first()->slug);
    }

    /** @test */
    public function it_returns_pivot_data_for_direct_permissions()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $permission = Permission::create([
            'name' => 'Create Customers',
            'slug' => 'create_customers',
            'description' => 'Can create customers',
            'module' => 'customers',
            'action' => 'create',
        ]);

        $assignedAt = now();
        $user->directPermissions()->attach($permission->id, [
            'assigned_by' => $admin->id,
            'assigned_at' => $assignedAt,
        ]);

        $directPermission = $user->directPermissions->first();
        $this->assertEquals($admin->id, $directPermission->pivot->assigned_by);
        $this->assertNotNull($directPermission->pivot->assigned_at);
    }

    /** @test */
    public function can_method_integrates_with_permission_service()
    {
        $user = User::factory()->create();
        
        // Mock PermissionService
        $mockService = Mockery::mock(PermissionService::class);
        $mockService->shouldReceive('checkPermission')
            ->with($user->id, 'view_customers')
            ->andReturn(true);
        
        $this->app->instance(PermissionService::class, $mockService);

        $result = $user->can('view_customers');
        
        $this->assertTrue($result);
    }

    /** @test */
    public function can_method_returns_false_when_permission_not_granted()
    {
        $user = User::factory()->create();
        
        // Mock PermissionService
        $mockService = Mockery::mock(PermissionService::class);
        $mockService->shouldReceive('checkPermission')
            ->with($user->id, 'delete_customers')
            ->andReturn(false);
        
        $this->app->instance(PermissionService::class, $mockService);

        $result = $user->can('delete_customers');
        
        $this->assertFalse($result);
    }

    /** @test */
    public function get_all_permissions_returns_collection_from_service()
    {
        $user = User::factory()->create();
        
        $expectedPermissions = collect([
            Permission::create([
                'name' => 'View Customers',
                'slug' => 'view_customers',
                'description' => 'Can view customers',
                'module' => 'customers',
                'action' => 'view',
            ]),
            Permission::create([
                'name' => 'Create Customers',
                'slug' => 'create_customers',
                'description' => 'Can create customers',
                'module' => 'customers',
                'action' => 'create',
            ]),
        ]);
        
        // Mock PermissionService
        $mockService = Mockery::mock(PermissionService::class);
        $mockService->shouldReceive('getUserPermissions')
            ->with($user->id)
            ->andReturn($expectedPermissions);
        
        $this->app->instance(PermissionService::class, $mockService);

        $result = $user->getAllPermissions();
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('view_customers', $result->first()->slug);
    }

    /** @test */
    public function get_effective_permissions_returns_same_as_get_all_permissions()
    {
        $user = User::factory()->create();
        
        $expectedPermissions = collect([
            Permission::create([
                'name' => 'View Customers',
                'slug' => 'view_customers',
                'description' => 'Can view customers',
                'module' => 'customers',
                'action' => 'view',
            ]),
        ]);
        
        // Mock PermissionService
        $mockService = Mockery::mock(PermissionService::class);
        $mockService->shouldReceive('getUserPermissions')
            ->with($user->id)
            ->twice()
            ->andReturn($expectedPermissions);
        
        $this->app->instance(PermissionService::class, $mockService);

        $allPermissions = $user->getAllPermissions();
        $effectivePermissions = $user->getEffectivePermissions();
        
        $this->assertEquals($allPermissions, $effectivePermissions);
    }

    /** @test */
    public function direct_permissions_can_be_attached_and_detached()
    {
        $user = User::factory()->create();
        $permission1 = Permission::create([
            'name' => 'View Customers',
            'slug' => 'view_customers',
            'description' => 'Can view customers',
            'module' => 'customers',
            'action' => 'view',
        ]);
        $permission2 = Permission::create([
            'name' => 'Edit Customers',
            'slug' => 'edit_customers',
            'description' => 'Can edit customers',
            'module' => 'customers',
            'action' => 'edit',
        ]);

        // Attach permissions
        $user->directPermissions()->attach($permission1->id, [
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);
        $user->directPermissions()->attach($permission2->id, [
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);

        $this->assertCount(2, $user->directPermissions);

        // Detach one permission
        $user->directPermissions()->detach($permission1->id);
        $user->refresh();

        $this->assertCount(1, $user->directPermissions);
        $this->assertEquals('edit_customers', $user->directPermissions->first()->slug);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
