<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectStatusUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'forecast_stage',
        'support_request_type',
        'support_request_note',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
