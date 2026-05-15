<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'abv_name',
        'email',
        'phone',
        'address',
        'type',
        'tax_code',
        'website',
        'debt_limit',
        'debt_limit_type',
        'debt_limit_value',
        'debt_days',
        'note',
        'am',
        'payment_terms',
    ];

    protected $casts = [
        'debt_limit' => 'decimal:2',
        'is_locked' => 'boolean',
        'payment_terms' => 'array',
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
                ->orWhere('tax_code', 'like', "%{$search}%")
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

    /**
     * Relationship with Contacts
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Relationship with Sales
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Relationship with Projects
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Relationship with Exports
     */
    public function exports()
    {
        return $this->hasMany(Export::class);
    }

    /**
     * Relationship with PaymentHistory
     */
    public function paymentHistories()
    {
        return $this->hasMany(PaymentHistory::class);
    }

    /**
     * Relationship with Activities (CRM)
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Relationship with Customer Care Stages
     */
    public function careStages()
    {
        return $this->hasMany(CustomerCareStage::class);
    }

    /**
     * Get current/latest care stage
     */
    public function currentCareStage()
    {
        return $this->hasOne(CustomerCareStage::class)->latestOfMany();
    }

    /**
     * Get total debt amount
     */
    public function getTotalDebtAttribute(): float
    {
        return $this->sales()
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->sum('debt_amount');
    }

    /**
     * Get total sales amount
     */
    public function getTotalSalesAttribute(): float
    {
        return $this->sales()
            ->whereIn('status', ['approved', 'shipping', 'completed'])
            ->sum('total');
    }

    /**
     * Check if customer is over debt limit
     */
    public function isOverDebtLimit(): bool
    {
        $limit = $this->debt_limit;

        if ($this->debt_limit_type === 'percent') {
            $totalSales = $this->total_sales;
            $limit = ($totalSales * ($this->debt_limit_value ?: 0)) / 100;
        }

        if (!$limit || $limit <= 0) {
            return false;
        }
        
        return $this->total_debt > $limit;
    }
}
