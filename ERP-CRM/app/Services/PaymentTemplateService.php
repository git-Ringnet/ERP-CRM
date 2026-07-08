<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\PaymentTemplate;

class PaymentTemplateService
{
    /**
     * Snapshot a PaymentTemplate to a Sale's payment schedules
     */
    public function applyTemplate(Sale $sale, PaymentTemplate $template): void
    {
        $sale->paymentSchedules()->delete();

        foreach ($template->items as $item) {
            $sale->paymentSchedules()->create([
                'template_id' => $template->id,
                'template_version' => $template->version,
                'sort_order' => $item->sort_order,
                'milestone_name' => $item->milestone_name,
                'percentage' => $item->percentage,
                'amount' => round(($sale->total * $item->percentage) / 100, 2),
                'trigger_type' => $item->trigger_type,
                'trigger_value' => $item->trigger_value,
                'blocking_stage' => $item->blocking_stage,
                'due_base' => $item->due_base,
                'due_days' => $item->due_days,
                'required_docs' => $item->required_docs,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Sync dynamic custom milestones (e.g. from array input)
     */
    public function applyCustomMilestones(Sale $sale, array $milestones): void
    {
        $sale->syncPaymentSchedules($milestones);
    }
}
