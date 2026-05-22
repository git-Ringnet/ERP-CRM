<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // === Distributor Information ===
            $table->foreignId('vendor_id')->nullable()->after('marketing_event_id')
                  ->constrained('suppliers')->nullOnDelete()
                  ->comment('Vendor đang hợp tác');
            $table->string('distributor_am')->nullable()->after('vendor_id')
                  ->comment('Distributor AM: Name | Email (auto from login)');

            // === End-User Information ===
            $table->string('eu_name_vi')->nullable()->after('distributor_am')
                  ->comment('End-user tên tiếng Việt');
            $table->string('eu_name_en')->nullable()->after('eu_name_vi')
                  ->comment('End-user tên tiếng Anh');
            $table->string('eu_name_abbr', 100)->nullable()->after('eu_name_en')
                  ->comment('End-user tên viết tắt');
            $table->string('eu_tax_code', 100)->nullable()->after('eu_name_abbr')
                  ->comment('End-user MST hoặc website');
            $table->string('eu_province', 100)->nullable()->after('address')
                  ->comment('Tỉnh/Thành phố');
            $table->string('eu_industry', 100)->nullable()->after('eu_province')
                  ->comment('Ngành nghề');

            // === Collaboration ===
            $table->enum('collaborate_type', ['partner', 'end_user'])->nullable()->after('eu_industry')
                  ->comment('Loại hợp tác: Partner hoặc End-user');
            $table->foreignId('collaborate_customer_id')->nullable()->after('collaborate_type')
                  ->constrained('customers')->nullOnDelete()
                  ->comment('FK customer khi type=partner');
            $table->string('collaborate_company')->nullable()->after('collaborate_customer_id')
                  ->comment('Tên công ty hợp tác');
            $table->string('collaborate_tax_code', 100)->nullable()->after('collaborate_company')
                  ->comment('MST công ty hợp tác');
            $table->string('collaborate_pic_name')->nullable()->after('collaborate_tax_code')
                  ->comment('Tên người liên hệ');
            $table->string('collaborate_pic_title')->nullable()->after('collaborate_pic_name')
                  ->comment('Chức danh PIC');
            $table->string('collaborate_pic_phone', 50)->nullable()->after('collaborate_pic_title')
                  ->comment('SĐT PIC');
            $table->string('collaborate_pic_email')->nullable()->after('collaborate_pic_phone')
                  ->comment('Email PIC');

            // === Project Information enhancements ===
            $table->integer('estimated_close_months')->nullable()->after('end_date')
                  ->comment('Thời hạn ước tính: 3, 6, hoặc 9 tháng');
            $table->string('bom_file')->nullable()->after('estimated_close_months')
                  ->comment('Đường dẫn file BOM upload');
            $table->text('bom_data')->nullable()->after('bom_file')
                  ->comment('Nội dung BOM list dạng text');
            $table->decimal('net_to_tech_horizon', 18, 2)->nullable()->after('bom_data')
                  ->comment('Giá trị Net to Tech Horizon');
            $table->string('stage', 50)->nullable()->after('net_to_tech_horizon')
                  ->comment('Giai đoạn dự án');
            $table->string('deal_type', 50)->nullable()->after('stage')
                  ->comment('Loại deal: new_buy, trade_up');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['collaborate_customer_id']);

            $table->dropColumn([
                'vendor_id', 'distributor_am',
                'eu_name_vi', 'eu_name_en', 'eu_name_abbr', 'eu_tax_code',
                'eu_province', 'eu_industry',
                'collaborate_type', 'collaborate_customer_id',
                'collaborate_company', 'collaborate_tax_code',
                'collaborate_pic_name', 'collaborate_pic_title',
                'collaborate_pic_phone', 'collaborate_pic_email',
                'estimated_close_months', 'bom_file', 'bom_data',
                'net_to_tech_horizon', 'stage', 'deal_type',
            ]);
        });
    }
};
