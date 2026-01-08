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
        // Drop old tables in correct order (child first, then parent)
        Schema::dropIfExists('inventory_transaction_items');
        Schema::dropIfExists('inventory_transactions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate inventory_transactions table
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['import', 'export', 'transfer']);
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
            $table->date('date');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->integer('total_qty')->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            
            $table->index(['type', 'status']);
            $table->index('date');
        });

        // Recreate inventory_transaction_items table
        Schema::create('inventory_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->string('unit');
            $table->decimal('cost', 15, 2)->nullable();
            $table->string('serial_number')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }
};
