<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\BusinessDashboardPolicy;
use App\Services\PermissionService;
use Mockery;
use Tests\TestCase;

class BusinessDashboardPolicyTest extends TestCase
{
    protected BusinessDashboardPolicy $policy;
    protected PermissionService $permissionService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionService = Mockery::mock(PermissionService::class);
        $this->policy = new BusinessDashboardPolicy($this->permissionService);
        
        // Create a user with an ID
        $this->user = new User();
        $this->user->id = 1;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_viewDashboard_returns_true_when_user_has_permission(): void
    {
        $this->permissionService
            ->shouldReceive('checkPermission')
            ->once()
            ->with(1, 'view_business_dashboard')
            ->andReturn(true);

        $result = $this->policy->viewDashboard($this->user);

        $this->assertTrue($result);
    }

    public function test_viewDashboard_returns_false_when_user_lacks_permission(): void
    {
        $this->permissionService
            ->shouldReceive('checkPermission')
            ->once()
            ->with(1, 'view_business_dashboard')
            ->andReturn(false);

        $result = $this->policy->viewDashboard($this->user);

        $this->assertFalse($result);
    }

    public function test_exportReports_returns_true_when_user_has_permission(): void
    {
        $this->permissionService
            ->shouldReceive('checkPermission')
            ->once()
            ->with(1, 'export_business_reports')
            ->andReturn(true);

        $result = $this->policy->exportReports($this->user);

        $this->assertTrue($result);
    }

    public function test_exportReports_returns_false_when_user_lacks_permission(): void
    {
        $this->permissionService
            ->shouldReceive('checkPermission')
            ->once()
            ->with(1, 'export_business_reports')
            ->andReturn(false);

        $result = $this->policy->exportReports($this->user);

        $this->assertFalse($result);
    }
}
