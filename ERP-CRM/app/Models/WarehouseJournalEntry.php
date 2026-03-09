<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseJournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_date',
        'reference_type',
        'reference_id',
        'reference_code',
        'transaction_sub_type',
        'debit_account',
        'credit_account',
        'amount',
        'description',
        'created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the referenced model (Import, Export, or Transfer).
     */
    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Get the actual related model based on reference_type.
     */
    public function getReferencedModelAttribute()
    {
        return match ($this->reference_type) {
            'import' => Import::find($this->reference_id),
            'export' => Export::find($this->reference_id),
            'transfer' => Transfer::find($this->reference_id),
            default => null,
        };
    }

    /**
     * Get type label in Vietnamese.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->reference_type) {
            'import' => 'Nhập kho',
            'export' => 'Xuất kho',
            'transfer' => 'Chuyển kho',
            default => $this->reference_type,
        };
    }

    /**
     * Get sub-type label in Vietnamese.
     */
    public function getSubTypeLabelAttribute(): string
    {
        return match ($this->transaction_sub_type) {
            'from_supplier' => 'Từ NCC',
            'direct' => 'Mua trực tiếp',
            'project' => 'Cho dự án',
            'liquidation' => 'Thanh lý',
            'internal' => 'Nội bộ',
            default => $this->transaction_sub_type ?? '',
        };
    }

    /**
     * Scope: Filter by reference type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('reference_type', $type);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('entry_date', [$from, $to]);
    }
}
