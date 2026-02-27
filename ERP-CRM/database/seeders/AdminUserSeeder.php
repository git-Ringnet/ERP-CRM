<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Create default admin user for testing and assign Super Admin role
     */
    public function run(): void
    {
        // Check if admin user already exists
        $user = \App\Models\User::where('email', 'admin@erp.com')->first();
        
        if (!$user) {
            $user = \App\Models\User::create([
                'employee_code' => 'ADMIN001',
                'name' => 'Administrator',
                'email' => 'admin@erp.com',
                'password' => Hash::make('password'),
                'phone' => '0901234567',
                'department' => 'IT',
                'position' => 'System Administrator',
                'status' => 'active',
                'join_date' => now(),
            ]);
            
            $this->command->info('Admin user created: admin@erp.com / password');
        } else {
            $this->command->info('Admin user already exists.');
        }
        
        // Assign Super Admin role
        $superAdminRole = \App\Models\Role::where('slug', 'super_admin')->first();
        
        if ($superAdminRole && !$user->roles->contains($superAdminRole->id)) {
            $user->roles()->attach($superAdminRole->id, [
                'assigned_by' => $user->id,
                'assigned_at' => now(),
            ]);
            $this->command->info('Super Admin role assigned to admin user.');
        } else {
            $this->command->info('Super Admin role already assigned or not found.');
        }
    }
}
