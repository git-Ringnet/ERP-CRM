<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'type',
        'input_mode',
        'percent_value',
        'description',
        'amount',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percent_value' => 'decimal:2',
    ];

    /**
     * Default expense templates for new orders.
     */
    public static function defaultExpenses(): array
    {
        return [
            ['type' => 'Chi phí Tài chính', 'input_mode' => 'percent', 'percent_value' => '', 'amount' => '', 'description' => ''],
            ['type' => 'Lãi vay phát sinh do nợ quá hạn', 'input_mode' => 'fixed', 'percent_value' => '', 'amount' => '', 'description' => ''],
            ['type' => 'Chi phí Quản lí, Back Office & kỹ thuật', 'input_mode' => 'percent', 'percent_value' => '', 'amount' => '', 'description' => ''],
            ['type' => '24x7 Support cost', 'input_mode' => 'percent', 'percent_value' => '', 'amount' => '', 'description' => ''],
            ['type' => 'Other Support', 'input_mode' => 'percent', 'percent_value' => '', 'amount' => '', 'description' => ''],
            ['type' => 'Technical support/POC 30%', 'input_mode' => 'fixed', 'percent_value' => '', 'amount' => '', 'description' => ''],
            ['type' => 'Chi phí triển khai hợp đồng', 'input_mode' => 'fixed', 'percent_value' => '', 'amount' => '', 'description' => ''],
            ['type' => 'Thuế nhà thầu', 'input_mode' => 'fixed', 'percent_value' => '', 'amount' => '', 'description' => ''],
        ];
    }

    /**
     * Relationship with Sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get type label — just return the type itself since it's now freeform
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type ?: 'Khác';
    }
}
