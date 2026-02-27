<?php

namespace Tests\Unit\Traits;

use App\Models\Role;
use App\Models\User;
use App\Traits\HasRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasRolesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $adminRole;
    protected Role $managerRole;
    protected Role $staffRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test roles
        $this->adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'System administrator',
            'status' => 'active',
        ]);

        $this->managerRole = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Department manager',
            'status' => 'active',
        ]);

        $this->staffRole = Role::create([
            'name' => 'Staff',
            'slug' => 'staff',
            'description' => 'Regular staff',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_can_get_user_roles_relationship()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $this->user->roles());
    }

    /** @test */
    public function it_can_assign_role_by_slug()
    {
        $this->user->assignRole('admin');

        $this->assertTrue($this->user->hasRole('admin'));
        $this->assertCount(1, $this->user->roles);
    }

    /** @test */
    public function it_can_assign_role_by_instance()
    {
        $this->user->assignRole($this->adminRole);

        $this->assertTrue($this->user->hasRole('admin'));
        $this->assertCount(1, $this->user->roles);
    }

    /** @test */
    public function it_can_assign_multiple_roles()
    {
        $this->user->assignRole('admin');
        $this->user->assignRole('manager');

        $this->assertTrue($this->user->hasRole('admin'));
        $this->assertTrue($this->user->hasRole('manager'));
        $this->assertCount(2, $this->user->roles);
    }

    /** @test */
    public function it_does_not_duplicate_role_assignments()
    {
        $this->user->assignRole('admin');
        $this->user->assignRole('admin');

        $this->assertCount(1, $this->user->roles);
    }

    /** @test */
    public function it_can_check_if_user_has_role()
    {
        $this->user->assignRole('admin');

        $this->assertTrue($this->user->hasRole('admin'));
        $this->assertFalse($this->user->hasRole('manager'));
    }

    /** @test */
    public function it_can_check_if_user_has_any_role()
    {
        $this->user->assignRole('admin');

        $this->assertTrue($this->user->hasAnyRole(['admin', 'manager']));
        $this->assertTrue($this->user->hasAnyRole(['manager', 'admin']));
        $this->assertFalse($this->user->hasAnyRole(['manager', 'staff']));
    }

    /** @test */
    public function it_can_check_if_user_has_all_roles()
    {
        $this->user->assignRole('admin');
        $this->user->assignRole('manager');

        $this->assertTrue($this->user->hasAllRoles(['admin', 'manager']));
        $this->assertFalse($this->user->hasAllRoles(['admin', 'manager', 'staff']));
        $this->assertFalse($this->user->hasAllRoles(['staff']));
    }

    /** @test */
    public function it_can_remove_role_by_slug()
    {
        $this->user->assignRole('admin');
        $this->user->assignRole('manager');

        $this->user->removeRole('admin');

        $this->assertFalse($this->user->hasRole('admin'));
        $this->assertTrue($this->user->hasRole('manager'));
        $this->assertCount(1, $this->user->roles);
    }

    /** @test */
    public function it_can_remove_role_by_instance()
    {
        $this->user->assignRole($this->adminRole);
        $this->user->assignRole($this->managerRole);

        $this->user->removeRole($this->adminRole);

        $this->assertFalse($this->user->hasRole('admin'));
        $this->assertTrue($this->user->hasRole('manager'));
        $this->assertCount(1, $this->user->roles);
    }

    /** @test */
    public function it_stores_assigned_by_and_assigned_at_in_pivot()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $this->user->assignRole('admin');

        $pivot = $this->user->roles()->first()->pivot;
        $this->assertEquals($admin->id, $pivot->assigned_by);
        $this->assertNotNull($pivot->assigned_at);
    }

    /** @test */
    public function it_throws_exception_when_assigning_non_existent_role()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->user->assignRole('non-existent-role');
    }

    /** @test */
    public function it_throws_exception_when_removing_non_existent_role()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->user->removeRole('non-existent-role');
    }

    /** @test */
    public function it_returns_false_for_has_role_when_user_has_no_roles()
    {
        $this->assertFalse($this->user->hasRole('admin'));
    }

    /** @test */
    public function it_returns_false_for_has_any_role_when_user_has_no_roles()
    {
        $this->assertFalse($this->user->hasAnyRole(['admin', 'manager']));
    }

    /** @test */
    public function it_returns_false_for_has_all_roles_when_user_has_no_roles()
    {
        $this->assertFalse($this->user->hasAllRoles(['admin', 'manager']));
    }

    /** @test */
    public function it_returns_true_for_has_all_roles_with_empty_array()
    {
        // Edge case: empty array should return true (vacuous truth)
        $this->assertTrue($this->user->hasAllRoles([]));
    }
}
