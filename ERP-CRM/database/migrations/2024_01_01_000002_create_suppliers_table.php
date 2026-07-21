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
            $table->string('code', 50);
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20);
            $table->text('address')->nullable();
            $table->string('tax_code', 50)->nullable();
            $table->string('website')->nullable();
            $table->string('contact_person')->nullable();
            $table->integer('payment_terms')->default(30);
            $table->decimal('base_discount', 5, 2)->default(0.00)->comment('Chi?t kh?u c? b?n (%)');
            $table->decimal('volume_discount', 5, 2)->default(0.00)->comment('Chi?t kh?u theo s? l??ng (%)');
            $table->integer('volume_threshold')->default(0);
            $table->decimal('early_payment_discount', 5, 2)->default(0.00);
            $table->integer('early_payment_days')->default(7);
            $table->decimal('special_discount', 5, 2)->default(0.00)->comment('Chi?t kh?u ??c bi?t (%)');
            $table->text('special_discount_condition')->nullable()->comment('?i?u ki?n CK ??c bi?t');
            $table->string('product_type')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index('code', 'suppliers_code_index');
            $table->unique('code', 'suppliers_code_unique');
            $table->index('name', 'suppliers_name_index');
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
