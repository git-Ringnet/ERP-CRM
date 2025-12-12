<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, password, number, boolean
            $table->string('group')->default('general'); // general, email, system
            $table->timestamps();
        });

        // Insert default email settings
        DB::table('settings')->insert([
            ['key' => 'mail_mailer', 'value' => 'smtp', 'type' => 'text', 'group' => 'email', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mail_host', 'value' => 'smtp.gmail.com', 'type' => 'text', 'group' => 'email', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mail_port', 'value' => '587', 'type' => 'number', 'group' => 'email', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mail_username', 'value' => '', 'type' => 'text', 'group' => 'email', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mail_password', 'value' => '', 'type' => 'password', 'group' => 'email', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mail_encryption', 'value' => 'tls', 'type' => 'text', 'group' => 'email', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mail_from_address', 'value' => 'noreply@minierp.com', 'type' => 'text', 'group' => 'email', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mail_from_name', 'value' => 'Mini ERP', 'type' => 'text', 'group' => 'email', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
