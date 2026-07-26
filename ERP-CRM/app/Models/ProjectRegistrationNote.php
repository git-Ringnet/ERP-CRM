<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectRegistrationNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'user_role',
        'content',
        'attachments',
        'sla_extended_days',
    ];

    protected $casts = [
        'attachments' => 'array',
        'sla_extended_days' => 'integer',
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
