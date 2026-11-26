<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('projects', 'return_undo_snapshot')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->json('return_undo_snapshot')->nullable()->after('intake_note');
            });
        }

        if (!Schema::hasColumn('sales', 'return_undo_snapshot')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->json('return_undo_snapshot')->nullable()->after('pl_status');
            });
        }

        if (!Schema::hasColumn('technical_tickets', 'supplier_ids')) {
            Schema::table('technical_tickets', function (Blueprint $table) {
                $table->json('supplier_ids')->nullable()->after('supplier_id');
            });
        }

        $techEng = Role::where('slug', 'technical_engineer')->first();
        $viewSales = Permission::where('slug', 'view_sales')->first();
        if ($techEng && $viewSales) {
            $techEng->permissions()->detach($viewSales->id);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'return_undo_snapshot')) {
            Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('return_undo_snapshot'));
        }
        if (Schema::hasColumn('sales', 'return_undo_snapshot')) {
            Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('return_undo_snapshot'));
        }
        if (Schema::hasColumn('technical_tickets', 'supplier_ids')) {
            Schema::table('technical_tickets', fn (Blueprint $table) => $table->dropColumn('supplier_ids'));
        }
    }
};
