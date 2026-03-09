<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('reference_type', 20); // import, export, transfer
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_code', 50);
            $table->string('transaction_sub_type', 30)->nullable(); // from_supplier, direct, project, liquidation, internal
            $table->string('debit_account', 10); // VD: 156, 331, 621...
            $table->string('credit_account', 10);
            $table->decimal('amount', 18, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index('entry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_journal_entries');
    }
};
