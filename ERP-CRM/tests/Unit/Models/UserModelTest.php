<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_employee_can_be_created(): void
    {
        $user = User::create([
            'name' => 'Test Employee',
            'email' => 'employee@example.com',
            'password' => bcrypt('password'),
            'employee_code' => 'EMP001',
            'phone' => '0123456789',
            'department' => 'IT',
            'position' => 'Developer',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('users', [
            'employee_code' => 'EMP001',
            'name' => 'Test Employee',
        ]);
    }

    public function test_user_fillable_attributes(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('employee_code', $fillable);
        $this->assertContains('department', $fillable);
        $this->assertContains('position', $fillable);
    }
}
