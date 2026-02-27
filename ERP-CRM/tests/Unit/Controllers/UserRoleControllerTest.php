<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\UserRoleController;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class UserRoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private RoleServiceInterface $roleService;
    private UserRoleController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock RoleService
        $this->roleService = Mockery::mock(RoleServiceInterface::class);
        $this->controller = new UserRoleController($this->roleService);

        // Bypass middleware for all tests
        $this->withoutMiddleware();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_show_displays_user_roles_and_available_roles(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role1 = Role::factory()->create(['status' => 'active']);
        $role2 = Role::factory()->create(['status' => 'active']);
        $assignedBy = User::factory()->create();
        $user->roles()->attach($role1->id, [
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($user);

        // Act - use JSON request to avoid view rendering
        $response = $this->getJson(route('users.roles.show', $user->id));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user',
                'current_roles',
                'available_roles',
            ],
        ]);
    }

    public function test_show_returns_json_for_api_requests(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::factory()->create(['status' => 'active']);
        $assignedBy = User::factory()->create();
        $user->roles()->attach($role->id, [
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->getJson(route('users.roles.show', $user->id));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user',
                'current_roles',
                'available_roles',
            ],
        ]);
    }

    public function test_assign_assigns_role_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $this->roleService
            ->shouldReceive('assignRoleToUser')
            ->once()
            ->with($user->id, $role->id);

        $this->actingAs($user);

        // Act
        $response = $this->post(route('users.roles.assign', $user->id), [
            'role_id' => $role->id,
        ]);

        // Assert
        $response->assertRedirect(route('users.roles.show', $user->id));
        $response->assertSessionHas('success');
    }

    public function test_assign_returns_json_for_api_requests(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $this->roleService
            ->shouldReceive('assignRoleToUser')
            ->once()
            ->with($user->id, $role->id);

        $this->actingAs($user);

        // Act
        $response = $this->postJson(route('users.roles.assign', $user->id), [
            'role_id' => $role->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_assign_validates_role_id(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->post(route('users.roles.assign', $user->id), [
            'role_id' => 'invalid',
        ]);

        // Assert
        $response->assertSessionHasErrors('role_id');
    }

    public function test_revoke_removes_role_from_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $this->roleService
            ->shouldReceive('removeRoleFromUser')
            ->once()
            ->with($user->id, $role->id);

        $this->actingAs($user);

        // Act
        $response = $this->delete(route('users.roles.revoke', [$user->id, $role->id]));

        // Assert
        $response->assertRedirect(route('users.roles.show', $user->id));
        $response->assertSessionHas('success');
    }

    public function test_revoke_returns_json_for_api_requests(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $this->roleService
            ->shouldReceive('removeRoleFromUser')
            ->once()
            ->with($user->id, $role->id);

        $this->actingAs($user);

        // Act
        $response = $this->deleteJson(route('users.roles.revoke', [$user->id, $role->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_sync_syncs_user_roles(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();

        $this->roleService
            ->shouldReceive('syncUserRoles')
            ->once()
            ->with($user->id, [$role1->id, $role2->id]);

        $this->actingAs($user);

        // Act
        $response = $this->put(route('users.roles.sync', $user->id), [
            'role_ids' => [$role1->id, $role2->id],
        ]);

        // Assert
        $response->assertRedirect(route('users.roles.show', $user->id));
        $response->assertSessionHas('success');
    }

    public function test_sync_returns_json_for_api_requests(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();

        $this->roleService
            ->shouldReceive('syncUserRoles')
            ->once()
            ->with($user->id, [$role1->id, $role2->id]);

        $this->actingAs($user);

        // Act
        $response = $this->putJson(route('users.roles.sync', $user->id), [
            'role_ids' => [$role1->id, $role2->id],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_sync_validates_role_ids(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act
        $response = $this->put(route('users.roles.sync', $user->id), [
            'role_ids' => 'invalid',
        ]);

        // Assert
        $response->assertSessionHasErrors('role_ids');
    }
}
