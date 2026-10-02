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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'brand')) {
                $table->string('brand', 100)->nullable()->after('name')->comment('Hãng / Nhà sản xuất');
                $table->index('brand');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'brand')) {
                $table->dropIndex(['brand']);
                $table->dropColumn('brand');
            }
        });
    }
};
