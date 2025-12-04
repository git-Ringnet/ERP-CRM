<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mở rộng bảng users để chứa thông tin nhân viên (employees)
     * User = Employee trong hệ thống ERP
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Mã nhân viên (unique)
            $table->string('employee_code', 20)->unique()->nullable()->after('id');
            
            // Thông tin cá nhân
            $table->date('birth_date')->nullable()->after('name');
            $table->string('phone', 20)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->string('id_card', 20)->nullable()->after('address'); // CCCD/CMND
            
            // Thông tin công việc
            $table->string('department', 100)->nullable()->after('id_card');
            $table->string('position', 100)->nullable()->after('department');
            $table->date('join_date')->nullable()->after('position');
            $table->decimal('salary', 15, 2)->default(0)->after('join_date');
            
            // Thông tin ngân hàng
            $table->string('bank_account', 30)->nullable()->after('salary');
            $table->string('bank_name', 100)->nullable()->after('bank_account');
            
            // Trạng thái và ghi chú
            $table->enum('status', ['active', 'leave', 'resigned'])->default('active')->after('bank_name');
            $table->text('note')->nullable()->after('status');
            
            // Avatar
            $table->string('avatar')->nullable()->after('note');
            
            // Soft delete
            $table->softDeletes();
            
            // Indexes cho tìm kiếm và lọc
            $table->index('department');
            $table->index('status');
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['department']);
            $table->dropIndex(['status']);
            $table->dropIndex(['position']);
            
            // Drop columns
            $table->dropColumn([
                'employee_code',
                'birth_date',
                'phone',
                'address',
                'id_card',
                'department',
                'position',
                'join_date',
                'salary',
                'bank_account',
                'bank_name',
                'status',
                'note',
                'avatar',
                'deleted_at'
            ]);
        });
    }
};
