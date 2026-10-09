<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'is_fulfill')) {
                    $table->boolean('is_fulfill')->default(false)->after('ohf_cost_added');
                }
            });
        }

        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_items', 'deal_item_type')) {
                    $table->string('deal_item_type')->nullable()->after('product_name');
                }
            });
        }

        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $table) {
                if (!Schema::hasColumn('quotations', 'is_fulfill')) {
                    $table->boolean('is_fulfill')->default(false)->after('project_id');
                }
            });
        }

        if (Schema::hasTable('quotation_items')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                if (!Schema::hasColumn('quotation_items', 'deal_item_type')) {
                    $table->string('deal_item_type')->nullable()->after('product_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (Schema::hasColumn('sales', 'is_fulfill')) {
                    $table->dropColumn('is_fulfill');
                }
            });
        }

        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                if (Schema::hasColumn('sale_items', 'deal_item_type')) {
                    $table->dropColumn('deal_item_type');
                }
            });
        }

        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $table) {
                if (Schema::hasColumn('quotations', 'is_fulfill')) {
                    $table->dropColumn('is_fulfill');
                }
            });
        }

        if (Schema::hasTable('quotation_items')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                if (Schema::hasColumn('quotation_items', 'deal_item_type')) {
                    $table->dropColumn('deal_item_type');
                }
            });
        }
    }
};
