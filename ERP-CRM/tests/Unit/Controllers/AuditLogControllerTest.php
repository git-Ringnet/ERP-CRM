<?php

namespace Tests\Unit\Controllers;

use App\Models\PermissionAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass middleware for all tests
        $this->withoutMiddleware();
    }

    public function test_index_displays_audit_logs(): void
    {
        // Arrange
        $user = User::factory()->create();
        $log1 = PermissionAuditLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'entity_type' => 'role',
            'entity_id' => 1,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $log2 = PermissionAuditLog::create([
            'user_id' => $user->id,
            'action' => 'updated',
            'entity_type' => 'permission',
            'entity_id' => 2,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        // Act - use JSON request to avoid view rendering
        $response = $this->getJson(route('audit-logs.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'logs',
                'filters' => [
                    'users',
                    'action_types',
                    'entity_types',
                ],
            ],
        ]);
    }

    public function test_index_returns_json_for_api_requests(): void
    {
        // Arrange
        $user = User::factory()->create();
        PermissionAuditLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'entity_type' => 'role',
            'entity_id' => 1,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->getJson(route('audit-logs.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'logs',
                'filters' => [
                    'users',
                    'action_types',
                    'entity_types',
                ],
            ],
        ]);
    }

    public function test_index_filters_by_date_range(): void
    {
        // Arrange
        $user = User::factory()->create();
        
        $oldLog = PermissionAuditLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'entity_type' => 'role',
            'entity_id' => 1,
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subDays(10),
        ]);

        $recentLog = PermissionAuditLog::create([
            'user_id' => $user->id,
            'action' => 'updated',
            'entity_type' => 'permission',
            'entity_id' => 2,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->getJson(route('audit-logs.index', [
            'date_from' => now()->subDays(5)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.logs.data');
        $this->assertCount(1, $data);
        $this->assertEquals($recentLog->id, $data[0]['id']);
    }

    public function test_index_filters_by_user(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $log1 = PermissionAuditLog::create([
            'user_id' => $user1->id,
            'action' => 'created',
            'entity_type' => 'role',
            'entity_id' => 1,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $log2 = PermissionAuditLog::create([
            'user_id' => $user2->id,
            'action' => 'updated',
            'entity_type' => 'permission',
            'entity_id' => 2,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($user1);

        // Act
        $response = $this->getJson(route('audit-logs.index', [
            'user_id' => $user1->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.logs.data');
        $this->assertCount(1, $data);
        $this->assertEquals($log1->id, $data[0]['id']);
    }

    public function test_index_filters_by_action_type(): void
    {
        // Arrange
        $user = User::factory()->create();

        $createdLog = PermissionAuditLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'entity_type' => 'role',
            'entity_id' => 1,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $updatedLog = PermissionAuditLog::create([
            'user_id' => $user->id,
            'action' => 'updated',
            'entity_type' => 'permission',
            'entity_id' => 2,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->getJson(route('audit-logs.index', [
            'action' => 'created',
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.logs.data');
        $this->assertCount(1, $data);
        $this->assertEquals($createdLog->id, $data[0]['id']);
    }

    public function test_index_filters_by_entity_type(): void
    {
        // Arrange
        $user = User::factory()->create();

        $roleLog = PermissionAuditLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'entity_type' => 'role',
            'entity_id' => 1,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $permissionLog = PermissionAuditLog::create([
            'user_id' => $user->id,
            'action' => 'updated',
            'entity_type' => 'permission',
            'entity_id' => 2,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($user);

        // Act
        $response = $this->getJson(route('audit-logs.index', [
            'entity_type' => 'role',
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.logs.data');
        $this->assertCount(1, $data);
        $this->assertEquals($roleLog->id, $data[0]['id']);
    }

    public function test_index_validates_filter_parameters(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act - invalid action type
        $response = $this->get(route('audit-logs.index', [
            'action' => 'invalid_action',
        ]));

        // Assert
        $response->assertSessionHasErrors('action');
    }

    public function test_index_validates_date_range(): void
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act - date_to before date_from
        $response = $this->get(route('audit-logs.index', [
            'date_from' => now()->format('Y-m-d'),
            'date_to' => now()->subDays(5)->format('Y-m-d'),
        ]));

        // Assert
        $response->assertSessionHasErrors('date_to');
    }

    public function test_index_respects_pagination(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Create 25 logs
        for ($i = 0; $i < 25; $i++) {
            PermissionAuditLog::create([
                'user_id' => $user->id,
                'action' => 'created',
                'entity_type' => 'role',
                'entity_id' => $i,
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
            ]);
        }

        $this->actingAs($user);

        // Act - request 10 per page
        $response = $this->getJson(route('audit-logs.index', [
            'per_page' => 10,
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.logs.data');
        $this->assertCount(10, $data);
    }
}
