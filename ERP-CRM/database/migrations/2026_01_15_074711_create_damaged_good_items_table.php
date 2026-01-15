<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('damaged_good_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damaged_good_id')->constrained('damaged_goods')->onDelete('cascade');
            $table->foreignId('product_item_id')->constrained('product_items')->onDelete('cascade');
            $table->unique(['damaged_good_id', 'product_item_id'], 'damaged_good_item_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damaged_good_items');
    }
};
