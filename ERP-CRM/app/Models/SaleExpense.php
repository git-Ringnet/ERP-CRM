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
        'description',
        'amount',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Relationship with Sale
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'shipping' => 'Vận chuyển',
            'marketing' => 'Marketing',
            'commission' => 'Hoa hồng',
            'other' => 'Khác',
            default => 'Không xác định',
        };
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'shipping' => 'fa-truck',
            'marketing' => 'fa-bullhorn',
            'commission' => 'fa-percentage',
            'other' => 'fa-receipt',
            default => 'fa-dollar-sign',
        };
    }

    /**
     * Get type color
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'shipping' => 'bg-blue-100 text-blue-800',
            'marketing' => 'bg-orange-100 text-orange-800',
            'commission' => 'bg-green-100 text-green-800',
            'other' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
