<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 20)->nullable();
            $table->string('name');
            $table->date('birth_date')->nullable();
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('id_card', 20)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->date('join_date')->nullable();
            $table->decimal('salary', 15, 2)->default(0.00);
            $table->string('bank_account', 30)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->enum('status', ['active','leave','resigned'])->default('active');
            $table->enum('timekeeping_type', ['regular','irregular'])->default('regular');
            $table->foreignId('work_location_id')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->text('note')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index('department', 'users_department_index');
            $table->unique('email', 'users_email_unique');
            $table->unique('employee_code', 'users_employee_code_unique');
            $table->index('position', 'users_position_index');
            $table->index('status', 'users_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
