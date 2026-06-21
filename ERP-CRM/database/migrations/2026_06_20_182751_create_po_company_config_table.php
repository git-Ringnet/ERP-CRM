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
        Schema::create('po_company_config', function (Blueprint $table) {
            $table->id();
            
            // BUYER INFO
            $table->string('buyer_name')->nullable();
            $table->string('buyer_address_line1')->nullable();
            $table->string('buyer_address_line2')->nullable();
            $table->string('buyer_tel')->nullable();
            $table->string('buyer_fax')->nullable();
            $table->string('buyer_contact')->nullable();
            $table->string('buyer_bank_account')->nullable();
            $table->string('buyer_bank_name')->nullable();
            $table->string('buyer_bank_address_line1')->nullable();
            $table->string('buyer_bank_address_line2')->nullable();
            $table->string('buyer_swift_code')->nullable();
            
            // SHIP TO
            $table->string('ship_to_name')->nullable();
            $table->string('ship_to_address_line1')->nullable();
            $table->string('ship_to_address_line2')->nullable();
            $table->string('ship_to_attn')->nullable();
            
            // INVOICE TO
            $table->string('invoice_to_name')->nullable();
            $table->string('invoice_to_address_line1')->nullable();
            $table->string('invoice_to_address_line2')->nullable();
            $table->string('invoice_to_attn')->nullable();
            
            // HEADER (for Sale Contract form)
            $table->string('company_full_name')->nullable();
            $table->string('hcmc_address')->nullable();
            $table->string('hanoi_address')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('header_logo_path')->nullable();
            
            // SIGNATURE
            $table->string('signer_name')->nullable();
            $table->string('signer_title')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_company_config');
    }
};
