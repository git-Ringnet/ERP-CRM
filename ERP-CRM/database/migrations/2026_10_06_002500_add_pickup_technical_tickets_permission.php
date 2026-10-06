<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $perm = Permission::firstOrCreate(
            ['slug' => 'pickup_technical_tickets'],
            [
                'name' => 'Nhận (Pickup) Ticket kỹ thuật',
                'description' => 'Quyền tự nhận (pickup) ticket kỹ thuật để xử lý',
                'module' => 'technical_tickets',
                'action' => 'pickup',
            ]
        );

        // Gán quyền cho các role kỹ thuật và quản trị, loại trừ Sales Manager và Sales Staff
        $targetRoleSlugs = ['technical_lead', 'technical_engineer', 'admin', 'director'];
        $roles = Role::whereIn('slug', $targetRoleSlugs)->get();

        foreach ($roles as $role) {
            if (!$role->permissions()->where('permissions.id', $perm->id)->exists()) {
                $role->permissions()->attach($perm->id);
            }
        }

        // Đảm bảo Sales Manager và Sales Staff KHÔNG có quyền pickup
        $salesRoles = Role::whereIn('slug', ['sales_manager', 'sales_staff'])->get();
        foreach ($salesRoles as $sr) {
            $sr->permissions()->detach($perm->id);
        }
    }

    public function down(): void
    {
        $perm = Permission::where('slug', 'pickup_technical_tickets')->first();
        if ($perm) {
            DB::table('role_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('user_permissions')->where('permission_id', $perm->id)->delete();
            $perm->delete();
        }
    }
};
