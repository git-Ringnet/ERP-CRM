<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Create default admin user for testing
     */
    public function run(): void
    {
        // Check if admin user already exists
        $existingUser = DB::table('users')->where('email', 'admin@ringnet.vn')->first();
        
        if (!$existingUser) {
            DB::table('users')->insert([
                'employee_code' => 'ADMIN001',
                'name' => 'Administrator',
                'email' => 'admin@ringnet.vn',
                'password' => Hash::make('password'),
                'phone' => '0901234567',
                'department' => 'IT',
                'position' => 'System Administrator',
                'status' => 'active',
                'join_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info('Admin user created: admin@ringnet.vn / password');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}
