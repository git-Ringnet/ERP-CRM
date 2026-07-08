<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'sort_order',
        'milestone_name',
        'percentage',
        'trigger_type',
        'trigger_value',
        'blocking_stage',
        'due_base',
        'due_days',
        'required_docs',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'due_days' => 'integer',
        'sort_order' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(PaymentTemplate::class, 'template_id');
    }
}
