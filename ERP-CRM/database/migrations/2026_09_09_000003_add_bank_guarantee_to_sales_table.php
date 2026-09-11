<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('has_bank_guarantee')->default(false)->after('payment_term_type');
            $table->string('bank_guarantee_note', 1000)->nullable()->after('has_bank_guarantee');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['has_bank_guarantee', 'bank_guarantee_note']);
        });
    }
};
