<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create contacts table
        if (!Schema::hasTable('contacts')) {
            Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
        });
        }

        // 2. Data Migration: Move contact_person from customers to contacts
        if (DB::table('contacts')->count() == 0) {
            $customers = DB::table('customers')->get();
        foreach ($customers as $customer) {
            if ($customer->contact_person) {
                DB::table('contacts')->insert([
                    'customer_id' => $customer->id,
                    'name' => $customer->contact_person,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        }

        // 3. Modify customers table
        Schema::table('customers', function (Blueprint $table) {
            // Drop code and contact_person
            if (Schema::hasColumn('customers', 'code')) {
                $table->dropUnique(['code']); // Drop unique index first if exists
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('customers', 'contact_person')) {
                $table->dropColumn('contact_person');
            }

            // Make tax_code mandatory and unique
            // Handle NULL tax_codes before changing constraint
            DB::table('customers')->whereNull('tax_code')->update([
                'tax_code' => DB::raw("CONCAT('TEMP-', id)")
            ]);

            $table->string('tax_code', 50)->nullable(false)->change();
            $table->unique('tax_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'code')) {
                $table->string('code', 50)->nullable()->unique();
            }
            if (!Schema::hasColumn('customers', 'contact_person')) {
                $table->string('contact_person', 255)->nullable();
            }
            
            // Revert tax_code changes
            // dropping unique index might fail if it doesn't exist
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('customers');
            if (array_key_exists('customers_tax_code_unique', $indexes)) {
                $table->dropUnique('customers_tax_code_unique');
            }
            
            $table->string('tax_code', 50)->nullable()->change();
        });

        Schema::dropIfExists('contacts');
        Schema::dropIfExists('contacts_table_and_modify_customers');
    }
};
