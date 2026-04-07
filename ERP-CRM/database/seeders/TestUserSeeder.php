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
        $password = Hash::make('password');
        $now = Carbon::now();

        $testUsers = [
            [
                'email' => 'sales_manager@erp.com',
                'name' => 'Sales Manager User',
                'employee_code' => 'SM001',
                'role_slug' => 'sales_manager',
                'position' => 'Sales Manager',
                'department' => 'Sales'
            ],
            [
                'email' => 'sales_staff@erp.com',
                'name' => 'Sales Staff User',
                'employee_code' => 'SS001',
                'role_slug' => 'sales_staff',
                'position' => 'Sales Staff',
                'department' => 'Sales'
            ],
            [
                'email' => 'legal@erp.com',
                'name' => 'Legal Team User',
                'employee_code' => 'LG001',
                'role_slug' => 'legal_team',
                'position' => 'Legal Officer',
                'department' => 'Legal'
            ],
            [
                'email' => 'bod@erp.com',
                'name' => 'BOD User',
                'employee_code' => 'BOD001',
                'role_slug' => 'director',
                'position' => 'Director',
                'department' => 'BOD'
            ],
            [
                'email' => 'logistic@erp.com',
                'name' => 'Logistic Team User',
                'employee_code' => 'LO001',
                'role_slug' => 'warehouse_manager',
                'position' => 'Logistic Manager',
                'department' => 'Logistic'
            ],
            [
                'email' => 'po@erp.com',
                'name' => 'PO Team User',
                'employee_code' => 'PO001',
                'role_slug' => 'purchase_manager',
                'position' => 'PO Manager',
                'department' => 'PO'
            ],
            [
                'email' => 'finance@erp.com',
                'name' => 'Finance Team User',
                'employee_code' => 'FN001',
                'role_slug' => 'accountant',
                'position' => 'Accountant',
                'department' => 'Finance'
            ],
        ];

        // Get admin user for 'assigned_by' reference
        $admin = User::where('email', 'admin@erp.com')->first();
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
