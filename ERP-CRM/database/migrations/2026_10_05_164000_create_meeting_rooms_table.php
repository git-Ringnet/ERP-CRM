<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tạo bảng meeting_rooms nếu chưa có
        if (!Schema::hasTable('meeting_rooms')) {
            Schema::create('meeting_rooms', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('location')->nullable();
                $table->integer('capacity')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->default('active'); // active, inactive
                $table->timestamps();
            });

            // Seed danh sách 4 phòng họp ban đầu
            $defaultRooms = [
                [
                    'name' => 'Phòng họp lớn (Tầng 1)',
                    'location' => 'Tầng 1',
                    'capacity' => 20,
                    'description' => 'Máy chiếu, micro, bảng trắng',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Phòng họp nhỏ (Tầng 2)',
                    'location' => 'Tầng 2',
                    'capacity' => 8,
                    'description' => 'Tivi thông minh, bảng flipchart',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Phòng họp VIP (Tầng 3)',
                    'location' => 'Tầng 3',
                    'capacity' => 12,
                    'description' => 'Hệ thống họp trực tuyến video conference cao cấp',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Phòng đào tạo / Training',
                    'location' => 'Tầng 4',
                    'capacity' => 30,
                    'description' => 'Máy chiếu hội trường, dàn âm thanh, bục phát biểu',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('meeting_rooms')->insert($defaultRooms);
        }

        // 2. Thêm cột meeting_room_id vào bảng meeting_room_bookings nếu chưa có
        if (Schema::hasTable('meeting_room_bookings') && !Schema::hasColumn('meeting_room_bookings', 'meeting_room_id')) {
            Schema::table('meeting_room_bookings', function (Blueprint $table) {
                $table->foreignId('meeting_room_id')->nullable()->after('id')->constrained('meeting_rooms')->nullOnDelete();
            });

            // Map existing room_name to meeting_room_id
            $rooms = DB::table('meeting_rooms')->get();
            foreach ($rooms as $room) {
                DB::table('meeting_room_bookings')
                    ->where('room_name', $room->name)
                    ->update(['meeting_room_id' => $room->id]);
            }
        }

        // 3. Đăng ký các quyền vào bảng permissions
        if (Schema::hasTable('permissions')) {
            $perms = [
                [
                    'slug' => 'view_meeting_rooms',
                    'name' => 'Xem lịch phòng họp',
                    'description' => 'Cho phép xem lịch và tình trạng phòng họp',
                    'module' => 'meeting_rooms',
                    'action' => 'view',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'slug' => 'create_meeting_room_bookings',
                    'name' => 'Đặt lịch phòng họp',
                    'description' => 'Cho phép tạo và book lịch phòng họp mới',
                    'module' => 'meeting_rooms',
                    'action' => 'create',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'slug' => 'manage_meeting_rooms',
                    'name' => 'Quản lý danh sách phòng họp (Thêm/Sửa/Xóa phòng)',
                    'description' => 'Quyền quản trị danh mục phòng họp, thêm phòng mới, chỉnh sửa thông tin hoặc tạm dừng hoạt động của phòng',
                    'module' => 'meeting_rooms',
                    'action' => 'manage',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            foreach ($perms as $perm) {
                $exists = DB::table('permissions')->where('slug', $perm['slug'])->first();
                if (!$exists) {
                    $permId = DB::table('permissions')->insertGetId($perm);
                } else {
                    $permId = $exists->id;
                }

                // Gán quyền quản lý cho Admin, Director, Super Admin
                if ($perm['slug'] === 'manage_meeting_rooms') {
                    $adminRoles = DB::table('roles')->whereIn('slug', ['super_admin', 'admin', 'director', 'admin_hr'])->get();
                    foreach ($adminRoles as $role) {
                        DB::table('role_permissions')->updateOrInsert([
                            'role_id' => $role->id,
                            'permission_id' => $permId,
                        ]);
                    }
                } else {
                    // Quyền xem và đặt phòng: cấp cho tất cả các role
                    $allRoles = DB::table('roles')->where('status', 'active')->get();
                    foreach ($allRoles as $role) {
                        DB::table('role_permissions')->updateOrInsert([
                            'role_id' => $role->id,
                            'permission_id' => $permId,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('meeting_room_bookings') && Schema::hasColumn('meeting_room_bookings', 'meeting_room_id')) {
            Schema::table('meeting_room_bookings', function (Blueprint $table) {
                $table->dropForeign(['meeting_room_id']);
                $table->dropColumn('meeting_room_id');
            });
        }

        Schema::dropIfExists('meeting_rooms');

        DB::table('permissions')->where('module', 'meeting_rooms')->delete();
    }
};
