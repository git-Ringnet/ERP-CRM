<?php

namespace Tests\Unit\Services;

use App\Models\PermissionAuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditService $auditService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = new AuditService();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_log_creates_audit_entry(): void
    {
        $this->auditService->log(
            actionType: 'created',
            entityType: 'role',
            entityId: 1,
            data: [
                'new_value' => ['name' => 'Test Role'],
            ]
        );

        $this->assertDatabaseHas('permission_audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'created',
            'entity_type' => 'role',
            'entity_id' => 1,
        ]);

        $log = PermissionAuditLog::first();
        $this->assertEquals(['name' => 'Test Role'], $log->new_value);
        $this->assertNotNull($log->ip_address);
    }

    public function test_log_role_created(): void
    {
        $role = Role::factory()->create([
            'name' => 'Test Role',
            'slug' => 'test-role',
            'description' => 'Test Description',
            'status' => 'active',
        ]);

        $this->auditService->logRoleCreated($role, $this->user->id);

        $this->assertDatabaseHas('permission_audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'created',
            'entity_type' => 'role',
            'entity_id' => $role->id,
        ]);

        $log = PermissionAuditLog::first();
        $this->assertEquals('Test Role', $log->new_value['name']);
        $this->assertEquals('test-role', $log->new_value['slug']);
        $this->assertEquals('Test Description', $log->new_value['description']);
        $this->assertEquals('active', $log->new_value['status']);
    }

    public function test_log_role_updated(): void
    {
        $role = Role::factory()->create();
        $changes = [
            'name' => ['Old Name', 'New Name'],
            'status' => ['active', 'inactive'],
        ];

        $this->auditService->logRoleUpdated($role, $changes, $this->user->id);

        $this->assertDatabaseHas('permission_audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'updated',
            'entity_type' => 'role',
            'entity_id' => $role->id,
        ]);

        $log = PermissionAuditLog::first();
        $this->assertEquals('Old Name', $log->old_value['name']);
        $this->assertEquals('New Name', $log->new_value['name']);
        $this->assertEquals('active', $log->old_value['status']);
        $this->assertEquals('inactive', $log->new_value['status']);
    }

    public function test_log_role_deleted(): void
    {
        $roleId = 123;

        $this->auditService->logRoleDeleted($roleId, $this->user->id);

        $this->assertDatabaseHas('permission_audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'deleted',
            'entity_type' => 'role',
            'entity_id' => $roleId,
        ]);
    }

    public function test_log_role_assignment(): void
    {
        $userId = 456;
        $roleId = 789;

        $this->auditService->logRoleAssignment($userId, $roleId, $this->user->id);

        $this->assertDatabaseHas('permission_audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'assigned',
            'entity_type' => 'user_role',
            'entity_id' => $userId,
        ]);

        $log = PermissionAuditLog::first();
        $this->assertEquals($userId, $log->new_value['user_id']);
        $this->assertEquals($roleId, $log->new_value['role_id']);
    }

    public function test_log_permission_assignment(): void
    {
        $roleId = 123;
        $permissionIds = [1, 2, 3];

        $this->auditService->logPermissionAssignment($roleId, $permissionIds, $this->user->id);

        $this->assertDatabaseHas('permission_audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'assigned',
            'entity_type' => 'role_permission',
            'entity_id' => $roleId,
        ]);

        $log = PermissionAuditLog::first();
        $this->assertEquals($roleId, $log->new_value['role_id']);
        $this->assertEquals($permissionIds, $log->new_value['permission_ids']);
    }

    public function test_log_unauthorized_access(): void
    {
        $userId = 999;
        $resource = '/admin/settings';

        $this->auditService->logUnauthorizedAccess($userId, $resource);

        $this->assertDatabaseHas('permission_audit_logs', [
            'user_id' => $this->user->id,
            'action' => 'unauthorized_access',
            'entity_type' => 'access_attempt',
            'entity_id' => $userId,
        ]);

        $log = PermissionAuditLog::first();
        $this->assertEquals($userId, $log->new_value['user_id']);
        $this->assertEquals($resource, $log->new_value['resource']);
    }

    public function test_log_stores_ip_address(): void
    {
        $this->auditService->log(
            actionType: 'created',
            entityType: 'role',
            entityId: 1,
            data: []
        );

        $log = PermissionAuditLog::first();
        $this->assertNotNull($log->ip_address);
    }

    public function test_log_handles_null_old_value(): void
    {
        $this->auditService->log(
            actionType: 'created',
            entityType: 'role',
            entityId: 1,
            data: [
                'new_value' => ['name' => 'Test'],
            ]
        );

        $log = PermissionAuditLog::first();
        $this->assertNull($log->old_value);
        $this->assertNotNull($log->new_value);
    }

    public function test_log_handles_null_new_value(): void
    {
        $this->auditService->log(
            actionType: 'deleted',
            entityType: 'role',
            entityId: 1,
            data: [
                'old_value' => ['name' => 'Test'],
            ]
        );

        $log = PermissionAuditLog::first();
        $this->assertNotNull($log->old_value);
        $this->assertNull($log->new_value);
    }
}
