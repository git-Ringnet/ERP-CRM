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
        Schema::create('warehouse_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('reference_type', 20);
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_code', 50);
            $table->string('action', 20)->default('create');
            $table->string('status', 20)->nullable();
            $table->string('transaction_sub_type', 30)->nullable();
            $table->string('debit_account', 10);
            $table->string('credit_account', 10);
            $table->decimal('amount', 18, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
            $table->index('entry_date', 'warehouse_journal_entries_entry_date_index');
            $table->index(['reference_type','reference_id'], 'warehouse_journal_entries_reference_type_reference_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_journal_entries');
    }
};
