<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SalePaymentSchedule;

class PaymentRuleEngine
{
    /**
     * Check if a Sale is eligible for a specific action stage
     */
    public function checkStage(Sale $sale, string $stage): array
    {
        // If the whole sale is under a payment exception approved by BOD, everything is allowed
        if ($sale->is_payment_exception) {
            return [
                'allowed' => true,
                'blocked_milestones' => [],
                'has_exception' => true,
            ];
        }

        // Fetch any schedule that is blocking this stage and not yet paid/waived/approved
        $blockingSchedules = SalePaymentSchedule::where('sale_id', $sale->id)
            ->where('blocking_stage', $stage)
            ->whereNotIn('status', ['paid', 'waived', 'exception_approved'])
            ->get();

        if ($blockingSchedules->isEmpty()) {
            return [
                'allowed' => true,
                'blocked_milestones' => [],
                'has_exception' => false,
            ];
        }

        return [
            'allowed' => false,
            'blocked_milestones' => $blockingSchedules->pluck('milestone_name')->toArray(),
            'has_exception' => false,
        ];
    }

    /**
     * Check if PO can be sent
     */
    public function canSendPO(Sale $sale): array
    {
        return $this->checkStage($sale, 'BLOCK_PO_SEND');
    }

    /**
     * Check if Warehouse Export can be performed
     */
    public function canExportWarehouse(Sale $sale): array
    {
        return $this->checkStage($sale, 'BLOCK_WAREHOUSE_EXPORT');
    }

    /**
     * Check if invoice can be created
     */
    public function canCreateInvoice(Sale $sale): array
    {
        return $this->checkStage($sale, 'BLOCK_INVOICE');
    }
}
