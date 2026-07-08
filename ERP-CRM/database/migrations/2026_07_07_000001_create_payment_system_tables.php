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
        // 1. payment_templates
        Schema::create('payment_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. payment_template_items
        Schema::create('payment_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('payment_templates')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->string('milestone_name');
            $table->decimal('percentage', 5, 2);
            $table->string('trigger_type'); // ON_CONTRACT_SIGNED, ON_GOODS_DELIVERED, ON_INVOICE_ISSUED, etc.
            $table->string('trigger_value')->nullable();
            $table->string('blocking_stage')->nullable(); // BLOCK_PO_SEND, BLOCK_WAREHOUSE_EXPORT, etc.
            $table->string('due_base'); // contract_date, delivery_date, invoice_date
            $table->integer('due_days')->default(0);
            $table->string('required_docs')->default('none'); // unc, credit_note, cash_receipt, none
            $table->timestamps();
        });

        // 3. sale_payment_schedules
        Schema::create('sale_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('payment_templates')->nullOnDelete();
            $table->integer('template_version')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('milestone_name');
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 15, 2);
            $table->string('trigger_type');
            $table->string('trigger_value')->nullable();
            $table->string('blocking_stage')->nullable();
            $table->string('due_base');
            $table->integer('due_days')->default(0);
            $table->string('required_docs')->default('none');
            $table->string('status')->default('pending'); // pending, partially_paid, paid, overdue, waived, exception_approved
            $table->date('trigger_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('proof_file_path')->nullable();
            $table->string('bod_approval_file_path')->nullable();
            $table->string('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        // 4. payment_evidences
        Schema::create('payment_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('sale_payment_schedules')->cascadeOnDelete();
            $table->string('doc_type'); // unc, credit_note, cash_receipt, other
            $table->string('reference_number')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('file_path');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, verified, rejected
            $table->text('notes')->nullable();
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // 5. payment_approval_logs
        Schema::create('payment_approval_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->nullable()->constrained('sale_payment_schedules')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('action'); // proof_uploaded, finance_confirmed, finance_rejected, bod_exception_approved, bod_exception_rejected, etc.
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();
        });

        // 6. Seed default templates
        $templates = [
            [
                'code' => 'PREPAID_100',
                'name' => '100% Trả trước',
                'description' => 'Khách hàng thanh toán toàn bộ 100% trước khi thực hiện đặt hàng.',
                'items' => [
                    [
                        'sort_order' => 1,
                        'milestone_name' => 'Thanh toán 100%',
                        'percentage' => 100.00,
                        'trigger_type' => 'ON_CONTRACT_SIGNED',
                        'blocking_stage' => 'BLOCK_PO_SEND',
                        'due_base' => 'contract_date',
                        'due_days' => 0,
                        'required_docs' => 'unc',
                    ]
                ]
            ],
            [
                'code' => 'COOP_30_70',
                'name' => 'Cọc 30% - Thanh toán 70%',
                'description' => 'Cọc 30% trước khi đặt hàng, thanh toán nốt 70% trước khi xuất kho.',
                'items' => [
                    [
                        'sort_order' => 1,
                        'milestone_name' => 'Đặt cọc đợt 1',
                        'percentage' => 30.00,
                        'trigger_type' => 'ON_CONTRACT_SIGNED',
                        'blocking_stage' => 'BLOCK_PO_SEND',
                        'due_base' => 'contract_date',
                        'due_days' => 5,
                        'required_docs' => 'unc',
                    ],
                    [
                        'sort_order' => 2,
                        'milestone_name' => 'Thanh toán đợt 2',
                        'percentage' => 70.00,
                        'trigger_type' => 'ON_GOODS_DELIVERED',
                        'blocking_stage' => 'BLOCK_WAREHOUSE_EXPORT',
                        'due_base' => 'delivery_date',
                        'due_days' => 0,
                        'required_docs' => 'unc',
                    ]
                ]
            ],
            [
                'code' => 'COOP_50_50',
                'name' => 'Cọc 50% - Thanh toán 50%',
                'description' => 'Cọc 50% trước khi đặt hàng, thanh toán nốt 50% trước khi xuất kho.',
                'items' => [
                    [
                        'sort_order' => 1,
                        'milestone_name' => 'Đặt cọc đợt 1',
                        'percentage' => 50.00,
                        'trigger_type' => 'ON_CONTRACT_SIGNED',
                        'blocking_stage' => 'BLOCK_PO_SEND',
                        'due_base' => 'contract_date',
                        'due_days' => 5,
                        'required_docs' => 'unc',
                    ],
                    [
                        'sort_order' => 2,
                        'milestone_name' => 'Thanh toán đợt 2',
                        'percentage' => 50.00,
                        'trigger_type' => 'ON_GOODS_DELIVERED',
                        'blocking_stage' => 'BLOCK_WAREHOUSE_EXPORT',
                        'due_base' => 'delivery_date',
                        'due_days' => 0,
                        'required_docs' => 'unc',
                    ]
                ]
            ],
            [
                'code' => 'POSTPAID_30_DAYS',
                'name' => 'Thanh toán sau giao hàng (30 ngày)',
                'description' => 'Thanh toán 100% trong vòng 30 ngày kể từ ngày nhận hàng.',
                'items' => [
                    [
                        'sort_order' => 1,
                        'milestone_name' => 'Thanh toán sau giao hàng',
                        'percentage' => 100.00,
                        'trigger_type' => 'ON_GOODS_DELIVERED',
                        'blocking_stage' => null,
                        'due_base' => 'delivery_date',
                        'due_days' => 30,
                        'required_docs' => 'none',
                    ]
                ]
            ]
        ];

        foreach ($templates as $t) {
            $templateId = DB::table('payment_templates')->insertGetId([
                'code' => $t['code'],
                'name' => $t['name'],
                'description' => $t['description'],
                'version' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            foreach ($t['items'] as $item) {
                DB::table('payment_template_items')->insert(array_merge($item, [
                    'template_id' => $templateId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]));
            }
        }

        // 7. Migrate existing JSON data from sales.payment_terms
        $sales = DB::table('sales')->whereNotNull('payment_terms')->get();

        foreach ($sales as $sale) {
            $milestones = json_decode($sale->payment_terms, true);
            if (is_array($milestones)) {
                foreach ($milestones as $index => $ms) {
                    $milestoneName = $ms['milestone_name'] ?? $ms['label'] ?? ('Đợt ' . ($index + 1));
                    $percentage = (float)($ms['percentage'] ?? $ms['percent'] ?? 0);
                    $amount = (float)($ms['amount'] ?? 0);
                    $timing = $ms['timing'] ?? 'after_contract';
                    $requiredBefore = $ms['required_before'] ?? 'after_delivery';
                    $isBlocking = ($ms['is_blocking'] ?? 'yes') === 'yes';
                    $requiredDocs = $ms['required_docs'] ?? 'none';
                    $status = $ms['status'] ?? 'unpaid';
                    $dueDays = (int)($ms['due_days'] ?? $ms['days'] ?? 0);
                    $dueDate = isset($ms['due_date']) ? $ms['due_date'] : null;
                    $confirmedBy = $ms['confirmed_by'] ?? null;
                    $confirmedAt = isset($ms['confirmed_at']) ? $ms['confirmed_at'] : null;
                    $proofFilePath = $ms['proof_file_path'] ?? null;
                    $bodApprovalFilePath = $ms['bod_approval_file_path'] ?? null;

                    // Map timing to trigger_type & due_base
                    $triggerType = 'ON_CONTRACT_SIGNED';
                    $dueBase = 'contract_date';
                    if ($timing === 'after_delivery') {
                        $triggerType = 'ON_GOODS_DELIVERED';
                        $dueBase = 'delivery_date';
                    } elseif ($timing === 'after_invoice') {
                        $triggerType = 'ON_INVOICE_ISSUED';
                        $dueBase = 'invoice_date';
                    } elseif ($timing === 'after_delivery_notice') {
                        $triggerType = 'ON_DELIVERY_NOTICE';
                        $dueBase = 'delivery_date';
                    } elseif ($timing === 'before_export') {
                        $triggerType = 'BEFORE_EXPORT';
                        $dueBase = 'contract_date';
                    }

                    // Map requiredBefore & isBlocking to blocking_stage
                    $blockingStage = null;
                    if ($isBlocking) {
                        if ($requiredBefore === 'before_order') {
                            $blockingStage = 'BLOCK_PO_SEND';
                        } elseif ($requiredBefore === 'before_export') {
                            $blockingStage = 'BLOCK_WAREHOUSE_EXPORT';
                        }
                    }

                    // Map specific statuses
                    $newStatus = 'pending';
                    if ($status === 'paid') {
                        $newStatus = 'paid';
                    } elseif ($status === 'approved_preload') {
                        $newStatus = 'exception_approved';
                    } elseif ($status === 'approved_export_before_payment') {
                        $newStatus = 'exception_approved';
                    } elseif ($status === 'overdue') {
                        $newStatus = 'overdue';
                    }

                    DB::table('sale_payment_schedules')->insert([
                        'sale_id' => $sale->id,
                        'sort_order' => $index + 1,
                        'milestone_name' => $milestoneName,
                        'percentage' => $percentage,
                        'amount' => $amount,
                        'trigger_type' => $triggerType,
                        'blocking_stage' => $blockingStage,
                        'due_base' => $dueBase,
                        'due_days' => $dueDays,
                        'required_docs' => $requiredDocs,
                        'status' => $newStatus,
                        'due_date' => $dueDate,
                        'proof_file_path' => $proofFilePath,
                        'bod_approval_file_path' => $bodApprovalFilePath,
                        'confirmed_by' => $confirmedBy,
                        'confirmed_at' => $confirmedAt,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_approval_logs');
        Schema::dropIfExists('payment_evidences');
        Schema::dropIfExists('sale_payment_schedules');
        Schema::dropIfExists('payment_template_items');
        Schema::dropIfExists('payment_templates');
    }
};
