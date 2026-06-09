<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Create 7 test accounts for the business process flow
     */
    public function run(): void
    {
        $password = Hash::make('123456');
        $now = Carbon::now();
 
        $testUsers = [
            [
                'email' => 'ketoan@demo.com',
                'name' => 'Demo Kế Toán',
                'employee_code' => 'KT001',
                'role_slug' => 'accountant',
                'position' => 'Accountant',
                'department' => 'Finance'
            ],
            [
                'email' => 'quankho@demo.com',
                'name' => 'Demo Thủ Kho',
                'employee_code' => 'QK001',
                'role_slug' => 'warehouse_manager',
                'position' => 'Logistic Manager',
                'department' => 'Logistic'
            ],
            [
                'email' => 'baohanh@demo.com',
                'name' => 'Demo Bảo Hành',
                'employee_code' => 'BH001',
                'role_slug' => 'sales_staff',
                'position' => 'Sales Staff',
                'department' => 'Sales'
            ],
        ];
 
        // Get admin user for 'assigned_by' reference
        $admin = User::where('email', 'admin@demo.com')->first();
        $adminId = $admin ? $admin->id : 1;

        foreach ($testUsers as $userData) {
            $roleSlug = $userData['role_slug'];
            unset($userData['role_slug']);

            // Create or update user
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'password' => $password,
                    'status' => 'active',
                    'join_date' => $now,
                    'phone' => '090000000' . rand(0, 9)
                ])
            );

            // Assign role
            $role = Role::where('slug', $roleSlug)->first();
            if ($role && !$user->roles->contains($role->id)) {
                $user->roles()->syncWithoutDetaching([$role->id => [
                    'assigned_by' => $adminId,
                    'assigned_at' => $now,
                ]]);
            }
            
            $this->command->info("User created/updated: {$userData['email']} - Role: {$roleSlug}");
        }
    }
}
