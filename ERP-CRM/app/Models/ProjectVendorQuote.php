<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectVendorQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'version_number',
        'vendor_deal_id',
        'quote_file',
        'quote_note',
        'valid_until',
        'requote_reason',
        'created_by',
    ];

    protected $casts = [
        'quote_file' => 'array',
        'valid_until' => 'date',
        'version_number' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
