<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGroup extends Model
{
    use HasFactory;

    protected $table = 'user_groups';

    protected $fillable = [
        'name',
        'code',
        'description',
        'leader_id',
        'department',
        'status',
    ];

    /**
     * Team Leader
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * Group Members
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_group_members', 'user_group_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Tickets assigned to this group
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(TechnicalTicket::class, 'user_group_id');
    }
}
