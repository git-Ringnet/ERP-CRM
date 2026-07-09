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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // preload, borrow
            $table->string('source')->nullable(); // warehouse, sales
            $table->foreignId('target_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('note')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->text('allocated_item_ids')->nullable(); // JSON array of product_item IDs
            $table->timestamps();
        });

        Schema::create('product_item_borrow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_item_id')->constrained('product_items')->onDelete('cascade');
            $table->foreignId('from_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('to_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->onDelete('set null');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_item_borrow_logs');
        Schema::dropIfExists('ticket_items');
        Schema::dropIfExists('tickets');
    }
};
