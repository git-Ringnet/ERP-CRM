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
        if (!Schema::hasTable('product_import_batches')) {
            Schema::create('product_import_batches', function (Blueprint $table) {
                $table->id();
                $table->string('batch_code', 100)->unique();
                $table->string('file_name')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('imported_count')->default(0);
                $table->unsignedInteger('updated_count')->default(0);
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'import_batch')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('import_batch', 100)->nullable()->index()->after('note');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'import_batch')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['import_batch']);
                $table->dropColumn('import_batch');
            });
        }

        Schema::dropIfExists('product_import_batches');
    }
};
