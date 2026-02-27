<?php

namespace Tests\Unit\Models;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $permission = Permission::create([
            'name' => 'View Customers',
            'slug' => 'view_customers',
            'description' => 'Permission to view customers',
            'module' => 'customers',
            'action' => 'view',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'View Customers',
            'slug' => 'view_customers',
            'module' => 'customers',
            'action' => 'view',
        ]);
    }

    /** @test */
    public function it_belongs_to_many_roles()
    {
        $permission = Permission::create([
            'name' => 'View Customers',
            'slug' => 'view_customers',
            'description' => 'Permission to view customers',
            'module' => 'customers',
            'action' => 'view',
        ]);

        $role = Role::create([
            'name' => 'Sales Manager',
            'slug' => 'sales_manager',
            'description' => 'Sales Manager Role',
            'status' => 'active',
        ]);

        $role->permissions()->attach($permission->id);

        $this->assertTrue($permission->roles->contains($role));
        $this->assertEquals(1, $permission->roles->count());
    }

    /** @test */
    public function it_belongs_to_many_users()
    {
        $permission = Permission::create([
            'name' => 'View Customers',
            'slug' => 'view_customers',
            'description' => 'Permission to view customers',
            'module' => 'customers',
            'action' => 'view',
        ]);

        $user = User::factory()->create();

        // Directly attach using the permission's users relationship
        $permission->users()->attach($user->id, [
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);

        $this->assertTrue($permission->users->contains($user));
        $this->assertEquals(1, $permission->users->count());
    }

    /** @test */
    public function it_can_scope_by_module()
    {
        Permission::create([
            'name' => 'View Customers',
            'slug' => 'view_customers',
            'description' => 'Permission to view customers',
            'module' => 'customers',
            'action' => 'view',
        ]);

        Permission::create([
            'name' => 'Create Sales',
            'slug' => 'create_sales',
            'description' => 'Permission to create sales',
            'module' => 'sales',
            'action' => 'create',
        ]);

        $customerPermissions = Permission::byModule('customers')->get();

        $this->assertEquals(1, $customerPermissions->count());
        $this->assertEquals('view_customers', $customerPermissions->first()->slug);
    }

    /** @test */
    public function it_can_scope_by_action()
    {
        Permission::create([
            'name' => 'View Customers',
            'slug' => 'view_customers',
            'description' => 'Permission to view customers',
            'module' => 'customers',
            'action' => 'view',
        ]);

        Permission::create([
            'name' => 'Create Customers',
            'slug' => 'create_customers',
            'description' => 'Permission to create customers',
            'module' => 'customers',
            'action' => 'create',
        ]);

        $viewPermissions = Permission::byAction('view')->get();

        $this->assertEquals(1, $viewPermissions->count());
        $this->assertEquals('view_customers', $viewPermissions->first()->slug);
    }

    /** @test */
    public function it_can_generate_slug_from_action_and_module()
    {
        $slug = Permission::generateSlug('view', 'customers');
        $this->assertEquals('view_customers', $slug);

        $slug = Permission::generateSlug('CREATE', 'SALES');
        $this->assertEquals('create_sales', $slug);

        $slug = Permission::generateSlug('Approve', 'Purchase_Orders');
        $this->assertEquals('approve_purchase_orders', $slug);
    }
}
