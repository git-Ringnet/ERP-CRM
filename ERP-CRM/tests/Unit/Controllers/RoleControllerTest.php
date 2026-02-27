<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\RoleController;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private RoleServiceInterface $roleService;
    private RoleController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleService = Mockery::mock(RoleServiceInterface::class);
        $this->controller = new RoleController($this->roleService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test index method returns roles with pagination
     */
    public function test_index_returns_roles_with_pagination(): void
    {
        // Skip this test as views are not yet implemented (Task 10.1)
        $this->markTestSkipped('Views not yet implemented - will be created in Task 10.1');
    }

    /**
     * Test store method creates a new role
     */
    public function test_store_creates_new_role(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $roleData = [
            'name' => 'Test Role',
            'slug' => 'test-role',
            'description' => 'Test Description',
            'status' => 'active',
        ];

        $role = new Role($roleData);
        $role->id = 1;

        $this->roleService
            ->shouldReceive('createRole')
            ->once()
            ->with($roleData)
            ->andReturn($role);

        $request = Request::create('/roles', 'POST', $roleData);

        $response = $this->controller->store($request);

        $this->assertNotNull($response);
    }

    /**
     * Test update method updates an existing role
     */
    public function test_update_updates_existing_role(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $role = Role::factory()->create();

        $updateData = [
            'name' => 'Updated Role',
            'slug' => 'updated-role',
            'description' => 'Updated Description',
            'status' => 'active',
        ];

        $this->roleService
            ->shouldReceive('updateRole')
            ->once()
            ->with($role->id, $updateData)
            ->andReturn($role);

        $request = Request::create("/roles/{$role->id}", 'PUT', $updateData);

        $response = $this->controller->update($request, $role->id);

        $this->assertNotNull($response);
    }

    /**
     * Test destroy method deletes a role
     */
    public function test_destroy_deletes_role(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $role = Role::factory()->create();

        $this->roleService
            ->shouldReceive('deleteRole')
            ->once()
            ->with($role->id)
            ->andReturn(true);

        $request = Request::create("/roles/{$role->id}", 'DELETE');

        $response = $this->controller->destroy($request, $role->id);

        $this->assertNotNull($response);
    }

    /**
     * Test destroy method handles validation exception when role has users
     */
    public function test_destroy_handles_role_with_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $role = Role::factory()->create();

        $this->roleService
            ->shouldReceive('deleteRole')
            ->once()
            ->with($role->id)
            ->andThrow(ValidationException::withMessages([
                'role' => ['Cannot delete role with assigned users.'],
            ]));

        $request = Request::create("/roles/{$role->id}", 'DELETE');

        $response = $this->controller->destroy($request, $role->id);

        $this->assertNotNull($response);
    }
}
