<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\PermissionController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class PermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    private RoleServiceInterface $roleService;
    private PermissionController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleService = Mockery::mock(RoleServiceInterface::class);
        $this->controller = new PermissionController($this->roleService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test index method returns permissions grouped by module
     */
    public function test_index_returns_permissions_grouped_by_module(): void
    {
        // Skip this test as views are not yet implemented (Task 10.2)
        $this->markTestSkipped('Views not yet implemented - will be created in Task 10.2');
    }

    /**
     * Test matrix method returns roles and permissions
     */
    public function test_matrix_returns_roles_and_permissions(): void
    {
        // Skip this test as views are not yet implemented (Task 10.2)
        $this->markTestSkipped('Views not yet implemented - will be created in Task 10.2');
    }

    /**
     * Test updateMatrix method updates role permissions
     */
    public function test_update_matrix_updates_role_permissions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $role = Role::factory()->create();
        $permissions = Permission::factory()->count(3)->create();

        $requestData = [
            'role_id' => $role->id,
            'permission_ids' => $permissions->pluck('id')->toArray(),
        ];

        $this->roleService
            ->shouldReceive('assignPermissionsToRole')
            ->once()
            ->with($role->id, $requestData['permission_ids'])
            ->andReturnNull();

        $request = Request::create('/permissions/matrix', 'POST', $requestData);

        $response = $this->controller->updateMatrix($request);

        $this->assertNotNull($response);
    }

    /**
     * Test updateMatrix method validates required fields
     */
    public function test_update_matrix_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/permissions/matrix', 'POST', []);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->controller->updateMatrix($request);
    }

    /**
     * Test updateMatrix method validates role exists
     */
    public function test_update_matrix_validates_role_exists(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $requestData = [
            'role_id' => 99999, // Non-existent role
            'permission_ids' => [1, 2, 3],
        ];

        $request = Request::create('/permissions/matrix', 'POST', $requestData);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->controller->updateMatrix($request);
    }

    /**
     * Test updateMatrix method validates permissions exist
     */
    public function test_update_matrix_validates_permissions_exist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $role = Role::factory()->create();

        $requestData = [
            'role_id' => $role->id,
            'permission_ids' => [99999], // Non-existent permission
        ];

        $request = Request::create('/permissions/matrix', 'POST', $requestData);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->controller->updateMatrix($request);
    }
}
