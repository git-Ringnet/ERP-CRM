<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\CheckPermission;
use App\Services\AuditServiceInterface;
use App\Services\PermissionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

class CheckPermissionTest extends TestCase
{
    private PermissionServiceInterface $permissionService;
    private AuditServiceInterface $auditService;
    private CheckPermission $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionService = Mockery::mock(PermissionServiceInterface::class);
        $this->auditService = Mockery::mock(AuditServiceInterface::class);
        $this->middleware = new CheckPermission($this->permissionService, $this->auditService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_allows_access_when_user_has_permission(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $permission = 'view_customers';

        $this->permissionService
            ->shouldReceive('checkPermission')
            ->once()
            ->with($user->id, $permission)
            ->andReturn(true);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next, $permission);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_denies_access_when_user_lacks_permission(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $permission = 'delete_customers';

        $this->permissionService
            ->shouldReceive('checkPermission')
            ->once()
            ->with($user->id, $permission)
            ->andReturn(false);

        $this->auditService
            ->shouldReceive('logUnauthorizedAccess')
            ->once()
            ->with($user->id, 'test');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Act & Assert
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized action.');

        try {
            $this->middleware->handle($request, $next, $permission);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_logs_unauthorized_access_attempt(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/customers/create', 'GET');
        $permission = 'create_customers';

        $this->permissionService
            ->shouldReceive('checkPermission')
            ->once()
            ->with($user->id, $permission)
            ->andReturn(false);

        $this->auditService
            ->shouldReceive('logUnauthorizedAccess')
            ->once()
            ->with($user->id, 'customers/create');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Act & Assert
        try {
            $this->middleware->handle($request, $next, $permission);
            $this->fail('Expected HttpException was not thrown');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Expected exception - test passes
            $this->assertEquals(403, $e->getStatusCode());
        }
    }

    public function test_returns_401_when_user_not_authenticated(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET');
        $permission = 'view_customers';

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Act & Assert
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        try {
            $this->middleware->handle($request, $next, $permission);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(401, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_passes_request_to_next_middleware_when_authorized(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $permission = 'view_customers';

        $this->permissionService
            ->shouldReceive('checkPermission')
            ->once()
            ->with($user->id, $permission)
            ->andReturn(true);

        $nextCalled = false;
        $next = function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new Response('Success', 200);
        };

        // Act
        $this->middleware->handle($request, $next, $permission);

        // Assert
        $this->assertTrue($nextCalled, 'Next middleware was not called');
    }
}
