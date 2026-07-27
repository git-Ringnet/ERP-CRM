<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketing_supplier_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            
            $table->foreignId('marketing_supplier_fund_id')
                ->nullable()
                ->constrained('marketing_supplier_funds', 'id', 'mkt_tx_fund_foreign')
                ->onDelete('set null');
                
            $table->foreignId('marketing_event_id')
                ->nullable()
                ->constrained('marketing_events', 'id', 'mkt_tx_event_foreign')
                ->onDelete('set null');
                
            $table->foreignId('marketing_request_id')
                ->nullable()
                ->constrained('marketing_requests', 'id', 'mkt_tx_request_foreign')
                ->onDelete('set null');
                
            $table->string('type'); // incoming, expense, receivable, collected
            $table->decimal('amount', 15, 2);
            $table->string('status')->nullable(); // pending, collected (for type='receivable')
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_supplier_transactions');
    }
};
