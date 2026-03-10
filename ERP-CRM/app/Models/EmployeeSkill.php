<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSkill extends Model
{
    protected $fillable = [
        'user_id',
        'skill_id',
        'level',
        'note',
        'evaluated_at',
        'evaluated_by',
    ];

    protected $casts = [
        'evaluated_at' => 'date',
        'level' => 'integer',
    ];

    public const LEVELS = [
        1 => 'Chưa biết',
        2 => 'Cơ bản',
        3 => 'Trung bình',
        4 => 'Khá',
        5 => 'Thành thạo',
    ];

    public const LEVEL_COLORS = [
        1 => 'secondary',
        2 => 'info',
        3 => 'primary',
        4 => 'warning',
        5 => 'success',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function getLevelLabelAttribute()
    {
        return self::LEVELS[$this->level] ?? 'N/A';
    }

    public function getLevelColorAttribute()
    {
        return self::LEVEL_COLORS[$this->level] ?? 'secondary';
    }
}
