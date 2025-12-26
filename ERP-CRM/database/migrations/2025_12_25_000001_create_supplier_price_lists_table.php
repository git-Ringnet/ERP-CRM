<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->string('file_name')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 15, 4)->default(1);
            $table->enum('price_type', ['list', 'partner', 'cost'])->default('list');
            $table->text('notes')->nullable();
            $table->json('import_log')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('supplier_price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_price_list_id')->constrained()->onDelete('cascade');
            $table->string('sku', 100);
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('list_price', 15, 2)->nullable();
            $table->decimal('price_1yr', 15, 2)->nullable();
            $table->decimal('price_2yr', 15, 2)->nullable();
            $table->decimal('price_3yr', 15, 2)->nullable();
            $table->decimal('price_4yr', 15, 2)->nullable();
            $table->decimal('price_5yr', 15, 2)->nullable();
            $table->string('source_sheet')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();

            $table->index(['supplier_price_list_id', 'sku']);
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_price_list_items');
        Schema::dropIfExists('supplier_price_lists');
    }
};
