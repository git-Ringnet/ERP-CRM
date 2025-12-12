<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'document_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function levels()
    {
        return $this->hasMany(ApprovalLevel::class, 'workflow_id')->orderBy('level');
    }

    public function getMaxLevelAttribute(): int
    {
        return $this->levels()->max('level') ?? 0;
    }

    public static function getForDocumentType(string $type): ?self
    {
        return static::where('document_type', $type)
            ->where('is_active', true)
            ->first();
    }
}
