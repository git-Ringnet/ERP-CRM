<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->text('description')->nullable()->after('product_name');
        });

        // Migrate existing data: copy product_name to description for existing items
        DB::table('quotation_items')->whereNull('description')->update([
            'description' => DB::raw('product_name'),
        ]);
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
