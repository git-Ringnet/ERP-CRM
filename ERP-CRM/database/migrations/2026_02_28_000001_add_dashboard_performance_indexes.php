<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add indexes for Business Activity Dashboard query performance optimization.
     * These indexes support filtering by date ranges and status for dashboard metrics.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Composite index for date and status filtering (dashboard metrics)
            $table->index(['date', 'status'], 'idx_sales_date_status');
            
            // Composite index for customer analysis by date
            $table->index(['customer_id', 'date'], 'idx_sales_customer_date');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            // Composite index for order date and status filtering
            $table->index(['order_date', 'status'], 'idx_purchase_orders_date_status');
            
            // Composite index for supplier analysis by date
            $table->index(['supplier_id', 'order_date'], 'idx_purchase_orders_supplier_date');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            // Composite index for product sales analysis
            $table->index(['product_id', 'sale_id'], 'idx_sale_items_product_sale');
        });

        Schema::table('inventories', function (Blueprint $table) {
            // Index for warehouse-based inventory queries
            $table->index('warehouse_id', 'idx_inventory_warehouse');
            
            // Index for product-based inventory queries
            $table->index('product_id', 'idx_inventory_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_date_status');
            $table->dropIndex('idx_sales_customer_date');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_orders_date_status');
            $table->dropIndex('idx_purchase_orders_supplier_date');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_product_sale');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropIndex('idx_inventory_warehouse');
            $table->dropIndex('idx_inventory_product');
        });
    }
};
