<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * TECH 3-7: mọi thành viên team kỹ thuật được tự pickup các ticket loại tự nhận
     * (BOM Support, Technical Documents, After-Sales, IT nội bộ, Others).
     */
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

        Role::whereIn('slug', ['technical_lead', 'technical_engineer'])->get()
            ->each(fn (Role $role) => $role->permissions()->syncWithoutDetaching([$perm->id]));
    }

    public function down(): void
    {
        // Không thu hồi: quyền pickup của role kỹ thuật là cấu hình mặc định từ trước.
    }
};
