<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\UserPermissionController;
use App\Models\Permission;
use App\Models\User;
use App\Repositories\PermissionRepository;
use App\Services\AuditServiceInterface;
use App\Services\CacheServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UserPermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    private PermissionRepository $permissionRepo;
    private CacheServiceInterface $cache;
    private AuditServiceInterface $audit;
    private UserPermissionController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->permissionRepo = Mockery::mock(PermissionRepository::class);
        $this->cache = Mockery::mock(CacheServiceInterface::class);
        $this->audit = Mockery::mock(AuditServiceInterface::class);

        $this->controller = new UserPermissionController(
            $this->permissionRepo,
            $this->cache,
            $this->audit
        );

        // Bypass middleware for all tests
        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_show_displays_user_direct_permissions(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create();
        $assignedBy = User::factory()->create();
        $user->directPermissions()->attach($permission->id, [
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($user);

        // Act - use JSON request to avoid view rendering
        $response = $this->getJson(route('users.permissions.show', $user->id));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user',
                'direct_permissions',
                'available_permissions',
            ],
        ]);
    }

    public function test_show_returns_json_for_api_requests(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create();
        $assignedBy = User::factory()->create();
        $user->directPermissions()->attach($permission->id, [
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->getJson(route('users.permissions.show', $user->id));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user',
                'direct_permissions',
                'available_permissions',
            ],
        ]);
    }

    public function test_assign_assigns_permission_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create();

        $this->permissionRepo
            ->shouldReceive('attachUserPermission')
            ->once()
            ->with($user->id, $permission->id, Mockery::any());

        $this->cache
            ->shouldReceive('forget')
            ->once()
            ->with(sprintf('user_permissions:%d', $user->id));

        $this->audit
            ->shouldReceive('log')
            ->once();

        $this->actingAs($user);

        // Act
        $response = $this->post(route('users.permissions.assign', $user->id), [
            'permission_id' => $permission->id,
        ]);

        // Assert
        $response->assertRedirect(route('users.permissions.show', $user->id));
        $response->assertSessionHas('success');
    }

    public function test_assign_returns_json_for_api_requests(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create();

        $this->permissionRepo
            ->shouldReceive('attachUserPermission')
            ->once();

        $this->cache
            ->shouldReceive('forget')
            ->once();

        $this->audit
            ->shouldReceive('log')
            ->once();

        $this->actingAs($user);

        // Act
        $response = $this->postJson(route('users.permissions.assign', $user->id), [
            'permission_id' => $permission->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_assign_validates_permission_id(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->post(route('users.permissions.assign', $user->id), [
            'permission_id' => 'invalid',
        ]);

        // Assert
        $response->assertSessionHasErrors('permission_id');
    }

    public function test_revoke_removes_permission_from_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create();

        $this->permissionRepo
            ->shouldReceive('findById')
            ->once()
            ->with($permission->id)
            ->andReturn($permission);

        $this->permissionRepo
            ->shouldReceive('detachUserPermission')
            ->once()
            ->with($user->id, $permission->id);

        $this->cache
            ->shouldReceive('forget')
            ->once()
            ->with(sprintf('user_permissions:%d', $user->id));

        $this->audit
            ->shouldReceive('log')
            ->once();

        $this->actingAs($user);

        // Act
        $response = $this->delete(route('users.permissions.revoke', [$user->id, $permission->id]));

        // Assert
        $response->assertRedirect(route('users.permissions.show', $user->id));
        $response->assertSessionHas('success');
    }

    public function test_revoke_returns_json_for_api_requests(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::factory()->create();

        $this->permissionRepo
            ->shouldReceive('findById')
            ->once()
            ->andReturn($permission);

        $this->permissionRepo
            ->shouldReceive('detachUserPermission')
            ->once();

        $this->cache
            ->shouldReceive('forget')
            ->once();

        $this->audit
            ->shouldReceive('log')
            ->once();

        $this->actingAs($user);

        // Act
        $response = $this->deleteJson(route('users.permissions.revoke', [$user->id, $permission->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_revoke_handles_non_existent_permission(): void
    {
        // Arrange
        $user = User::factory()->create();
        $nonExistentPermissionId = 99999;

        $this->permissionRepo
            ->shouldReceive('findById')
            ->once()
            ->with($nonExistentPermissionId)
            ->andReturn(null);

        $this->actingAs($user);

        // Act
        $response = $this->delete(route('users.permissions.revoke', [$user->id, $nonExistentPermissionId]));

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
