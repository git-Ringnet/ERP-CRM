<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $modules = ['customers', 'suppliers', 'employees', 'products', 'warehouses', 'inventory', 
                    'imports', 'exports', 'transfers', 'damaged_goods', 'sales', 'quotations', 
                    'purchase_orders', 'reports', 'settings'];
        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export'];
        
        $action = fake()->randomElement($actions);
        $module = fake()->randomElement($modules);
        $slug = "{$action}_{$module}";
        $name = ucfirst($action) . ' ' . ucfirst(str_replace('_', ' ', $module));
        
        return [
            'name' => $name,
            'slug' => $slug,
            'description' => fake()->sentence(),
            'module' => $module,
            'action' => $action,
        ];
    }

    /**
     * Create a permission with a specific action and module.
     */
    public function withActionAndModule(string $action, string $module): static
    {
        $slug = "{$action}_{$module}";
        $name = ucfirst($action) . ' ' . ucfirst(str_replace('_', ' ', $module));
        
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => $slug,
            'module' => $module,
            'action' => $action,
        ]);
    }
}
