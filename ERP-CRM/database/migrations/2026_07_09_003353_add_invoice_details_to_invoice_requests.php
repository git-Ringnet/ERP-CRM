<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_requests', function (Blueprint $table) {
            $table->foreignId('export_id')->nullable()->after('sale_id')->constrained('exports')->onDelete('set null');
            $table->string('seller_name')->nullable()->after('rejection_reason');
            $table->string('seller_company')->nullable()->after('seller_name');
            $table->text('invoice_content_note')->nullable()->after('seller_company');
            $table->string('customer_email')->nullable()->after('invoice_content_note');
            $table->string('delivery_address')->nullable()->after('customer_email');
            $table->string('delivery_contact')->nullable()->after('delivery_address');
            $table->string('delivery_phone')->nullable()->after('delivery_contact');
            $table->text('payment_terms_note')->nullable()->after('delivery_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_requests', function (Blueprint $table) {
            $table->dropForeign(['export_id']);
            $table->dropColumn([
                'export_id',
                'seller_name',
                'seller_company',
                'invoice_content_note',
                'customer_email',
                'delivery_address',
                'delivery_contact',
                'delivery_phone',
                'payment_terms_note',
            ]);
        });
    }
};
