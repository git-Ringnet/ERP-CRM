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
        Schema::create('supplier_po_configs', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('supplier_id')->unique()->constrained('suppliers')->onDelete('cascade');
            $table->string('template_type')->default('sale_contract'); // 'fortinet' | 'sale_contract'
            
            // SELLER INFO
            $table->string('seller_name')->nullable();
            $table->string('seller_address_line1')->nullable();
            $table->string('seller_address_line2')->nullable();
            $table->string('seller_tel')->nullable();
            $table->string('seller_fax')->nullable();
            $table->string('seller_contact')->nullable();
            $table->string('seller_beneficiary')->nullable();
            $table->string('seller_beneficiary_address')->nullable();
            $table->string('seller_bank_name')->nullable();
            $table->string('seller_bank_account')->nullable();
            $table->string('seller_bank_address_line1')->nullable();
            $table->string('seller_bank_address_line2')->nullable();
            $table->string('seller_bank_aba')->nullable();
            $table->string('seller_swift_code')->nullable();
            
            // PORTS
            $table->string('port_loading')->nullable();
            $table->string('port_discharge')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_po_configs');
    }
};
