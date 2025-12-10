<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Requirements: 1.4 - Remove unnecessary columns from products table
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Remove unnecessary columns
            $table->dropColumn([
                'price',
                'cost',
                'stock',
                'min_stock',
                'max_stock',
                'management_type',
                'auto_generate_serial',
                'serial_prefix',
                'expiry_months',
                'track_expiry',
            ]);
        });

        // Update category column to CHAR(1) for single letter categories (A-Z)
        Schema::table('products', function (Blueprint $table) {
            $table->char('category', 1)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Restore removed columns
            $table->decimal('price', 15, 2)->default(0)->after('unit');
            $table->decimal('cost', 15, 2)->default(0)->after('price');
            $table->integer('stock')->default(0)->after('cost');
            $table->integer('min_stock')->default(0)->after('stock');
            $table->integer('max_stock')->default(0)->after('min_stock');
            $table->enum('management_type', ['normal', 'serial', 'lot'])->default('normal')->after('max_stock');
            $table->boolean('auto_generate_serial')->default(false)->after('management_type');
            $table->string('serial_prefix', 20)->nullable()->after('auto_generate_serial');
            $table->integer('expiry_months')->nullable()->after('serial_prefix');
            $table->boolean('track_expiry')->default(false)->after('expiry_months');
        });

        // Restore category column
        Schema::table('products', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->change();
        });
    }
};
