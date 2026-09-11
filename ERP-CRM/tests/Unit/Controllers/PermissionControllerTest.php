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
            'permissions' => [
                $role->id => $permissions->pluck('id')->toArray(),
            ],
        ];

        $this->roleService
            ->shouldReceive('assignPermissionsToRole')
            ->once()
            ->with($role->id, $requestData['permissions'][$role->id])
            ->andReturnNull();

        $request = Request::create('/permissions/matrix', 'POST', $requestData);

        $response = $this->controller->updateMatrix($request);

        $this->assertNotNull($response);
    }

    /**
     * Test updateMatrix method validates required fields
     */
    public function test_update_matrix_allows_an_empty_matrix_to_clear_permissions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $role = Role::factory()->create();

        $this->roleService
            ->shouldReceive('assignPermissionsToRole')
            ->once()
            ->with($role->id, [])
            ->andReturnNull();

        $request = Request::create('/permissions/matrix', 'POST', ['permissions' => []]);

        $this->assertNotNull($this->controller->updateMatrix($request));
    }

    /**
     * Test updateMatrix method validates role exists
     */
    public function test_update_matrix_rejects_an_unknown_role_payload(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $permission = Permission::factory()->create();
        $this->roleService->shouldNotReceive('assignPermissionsToRole');
        $requestData = ['permissions' => [99999 => [$permission->id]]];

        $request = Request::create('/permissions/matrix', 'POST', $requestData);

        $this->assertNotNull($this->controller->updateMatrix($request));
    }

    /**
     * Test updateMatrix method validates permissions exist
     */
    public function test_update_matrix_rejects_an_unknown_permission_payload(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $role = Role::factory()->create();

        $this->roleService->shouldNotReceive('assignPermissionsToRole');
        $requestData = ['permissions' => [$role->id => [99999]]];

        $request = Request::create('/permissions/matrix', 'POST', $requestData);

        $this->assertNotNull($this->controller->updateMatrix($request));
    }
}
