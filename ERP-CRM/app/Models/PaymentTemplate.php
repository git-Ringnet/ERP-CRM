<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'version',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(PaymentTemplateItem::class, 'template_id')->orderBy('sort_order');
    }

    public function incrementVersion(): void
    {
        $this->increment('version');
    }
}
