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
        Schema::table('sale_order_request_items', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->after('sale_order_request_id');
            $table->decimal('quantity', 18, 2)->default(1)->after('vendor_id'); // Dùng decimal cho linh hoạt
            $table->string('unit')->nullable()->after('quantity');
            $table->unsignedBigInteger('product_id')->nullable()->after('unit');
            $table->decimal('received_quantity', 18, 2)->default(0)->after('product_id');
            $table->text('note')->nullable()->after('received_quantity');
            
            // Foreign keys
            $table->foreign('vendor_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_order_request_item_id')->nullable()->after('purchase_order_id');
            $table->decimal('ordered_quantity', 18, 2)->nullable()->after('sale_order_request_item_id');
            
            $table->foreign('sale_order_request_item_id', 'poi_sori_foreign')->references('id')->on('sale_order_request_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign('poi_sori_foreign');
            $table->dropColumn(['sale_order_request_item_id', 'ordered_quantity']);
        });

        Schema::table('sale_order_request_items', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn(['vendor_id', 'quantity', 'received_quantity', 'unit', 'product_id', 'note']);
        });
    }
};
