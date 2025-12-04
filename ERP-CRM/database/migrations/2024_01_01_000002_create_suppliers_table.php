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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('phone', 20);
            $table->text('address')->nullable();
            $table->string('tax_code', 50)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('contact_person', 255)->nullable();
            $table->integer('payment_terms')->default(30);
            $table->string('product_type', 255)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
