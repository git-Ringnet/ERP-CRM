<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create technical_tickets table
        Schema::create('technical_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('open'); // open, assigned, pending, escalate, completed, closed
            $table->string('work_type'); // BOM, POC, Deployment, Training, Event, Documentation, Support After Sales, Internal
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete(); // Vendor/Supplier
            
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Technical Engineer
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // Sales/Creator
            
            $table->timestamp('sla_deadline')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // 2. Create technical_support_logs table (Report Technical - Nhật ký hỗ trợ)
        Schema::create('technical_support_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_ticket_id')->constrained('technical_tickets')->onDelete('cascade');
            $table->date('log_date');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Engineer
            $table->string('serial_number')->nullable();
            $table->text('support_content');
            $table->string('status'); // Status of the ticket/work at the time of log
            $table->string('customer_info')->nullable();
            $table->string('contact_info')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Create technical_ticket_attachments table (Tài liệu phát sinh)
        Schema::create('technical_ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_ticket_id')->constrained('technical_tickets')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('document_type'); // biên bản mượn thiết bị, biên bản bàn giao, BOM, Datasheet, HLD/LLD, etc.
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Seed Permissions
        $now = Carbon::now();
        $permissions = [
            [
                'name' => 'Xem Ticket kỹ thuật',
                'slug' => 'view_technical_tickets',
                'description' => 'Quyền xem danh sách và chi tiết ticket kỹ thuật',
                'module' => 'technical_tickets',
                'action' => 'view',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Tạo Ticket kỹ thuật',
                'slug' => 'create_technical_tickets',
                'description' => 'Quyền tạo mới ticket kỹ thuật',
                'module' => 'technical_tickets',
                'action' => 'create',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sửa Ticket kỹ thuật',
                'slug' => 'edit_technical_tickets',
                'description' => 'Quyền chỉnh sửa ticket kỹ thuật',
                'module' => 'technical_tickets',
                'action' => 'edit',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Xóa Ticket kỹ thuật',
                'slug' => 'delete_technical_tickets',
                'description' => 'Quyền xóa ticket kỹ thuật',
                'module' => 'technical_tickets',
                'action' => 'delete',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Xuất báo cáo Ticket kỹ thuật',
                'slug' => 'export_technical_tickets',
                'description' => 'Quyền xuất danh sách ticket kỹ thuật ra Excel',
                'module' => 'technical_tickets',
                'action' => 'export',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Xem Dashboard Kỹ thuật',
                'slug' => 'view_technical_dashboard',
                'description' => 'Quyền xem dashboard và các biểu đồ kỹ thuật',
                'module' => 'technical_dashboard',
                'action' => 'view',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Quản lý Nhật ký Hỗ trợ (Report Tech)',
                'slug' => 'manage_technical_support_logs',
                'description' => 'Quyền thêm/sửa/xóa nhật ký hỗ trợ kỹ thuật',
                'module' => 'technical_support_logs',
                'action' => 'manage',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // 5. Assign Permissions to Roles (Super Admin, Director, Sales Manager, Sales Staff)
        $roles = DB::table('roles')->pluck('id', 'slug')->toArray();
        $seededPermissions = DB::table('permissions')
            ->whereIn('slug', array_column($permissions, 'slug'))
            ->pluck('id', 'slug')
            ->toArray();

        // Assign all to super_admin and director
        foreach (['super_admin', 'director', 'sales_manager', 'sales_staff'] as $roleSlug) {
            if (isset($roles[$roleSlug])) {
                $roleId = $roles[$roleSlug];
                
                // Super Admin and Director get all permissions
                // Sales Manager and Sales Staff get all except delete
                foreach ($seededPermissions as $slug => $permId) {
                    if (($roleSlug === 'sales_staff' || $roleSlug === 'sales_manager') && $slug === 'delete_technical_tickets') {
                        continue;
                    }
                    
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $roleId, 'permission_id' => $permId],
                        ['created_at' => $now]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Permissions
        $slugs = [
            'view_technical_tickets',
            'create_technical_tickets',
            'edit_technical_tickets',
            'delete_technical_tickets',
            'export_technical_tickets',
            'view_technical_dashboard',
            'manage_technical_support_logs'
        ];
        
        $permIds = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id')->toArray();
        DB::table('role_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('slug', $slugs)->delete();

        Schema::dropIfExists('technical_ticket_attachments');
        Schema::dropIfExists('technical_support_logs');
        Schema::dropIfExists('technical_tickets');
    }
};
