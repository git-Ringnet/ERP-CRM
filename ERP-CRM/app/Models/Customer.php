<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'address',
        'type',
        'tax_code',
        'website',
        'contact_person',
        'debt_limit',
        'debt_days',
        'note',
    ];

    protected $casts = [
        'debt_limit' => 'decimal:2',
        'debt_days' => 'integer',
    ];

    /**
     * Scope for searching customers by name, code, email, or phone
     * Requirements: 1.9
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for filtering customers by type
     * Requirements: 1.10
     */
    public function scopeFilterByType(Builder $query, ?string $type): Builder
    {
        if (empty($type)) {
            return $query;
        }

        return $query->where('type', $type);
    }
}
