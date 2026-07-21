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
        Schema::create('projects', function (Blueprint $table) {

            $table->id();
            $table->string('code', 50);
            $table->string('name');
            $table->foreignId('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->text('address')->nullable();
            $table->string('eu_province', 100)->nullable();
            $table->string('eu_industry', 100)->nullable();
            $table->enum('collaborate_type', ['partner','end_user'])->nullable();
            $table->foreignId('collaborate_customer_id')->nullable();
            $table->string('collaborate_company')->nullable();
            $table->string('collaborate_tax_code', 100)->nullable();
            $table->string('collaborate_pic_name')->nullable();
            $table->string('collaborate_pic_title')->nullable()->comment('Ch?c danh PIC');
            $table->string('collaborate_pic_phone', 50)->nullable()->comment('S?T PIC');
            $table->string('collaborate_pic_email')->nullable()->comment('Email PIC');
            $table->text('description')->nullable();
            $table->decimal('budget', 18, 2)->default(0.00);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('estimated_close_months')->nullable();
            $table->text('bom_file')->nullable()->comment('???ng d?n file BOM upload');
            $table->text('bom_data')->nullable()->comment('N?i dung BOM list d?ng text');
            $table->decimal('net_to_tech_horizon', 18, 2)->nullable();
            $table->string('stage', 50)->nullable();
            $table->string('deal_type', 50)->nullable()->comment('Lo?i deal: new_buy, trade_up');
            $table->enum('status', ['planning','in_progress','completed','cancelled','on_hold'])->default('planning');
            $table->foreignId('manager_id')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('marketing_event_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->string('distributor_am')->nullable()->comment('Distributor AM: Name | Email (auto from login)');
            $table->string('eu_name_vi')->nullable();
            $table->string('eu_name_en')->nullable();
            $table->string('eu_name_abbr', 100)->nullable();
            $table->string('eu_tax_code', 100)->nullable()->comment('End-user MST ho?c website');
            $table->timestamps();
            $table->unique('code', 'projects_code_unique');

            // Foreign keys
            $table->foreign('collaborate_customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
