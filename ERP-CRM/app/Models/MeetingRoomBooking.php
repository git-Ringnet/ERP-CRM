<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MeetingRoomBooking extends Model
{
    protected $fillable = [
        'room_name',
        'title',
        'description',
        'start_time',
        'end_time',
        'created_by',
        'status',
        'is_private',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_private' => 'boolean',
    ];

    public const ROOMS = [
        'Phòng họp lớn (Tầng 1)',
        'Phòng họp nhỏ (Tầng 2)',
        'Phòng họp VIP (Tầng 3)',
        'Phòng đào tạo / Training',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingRoomAttendee::class);
    }

    public function attendeeUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_room_attendees')
            ->withPivot(['status', 'note', 'responded_at'])
            ->withTimestamps();
    }

    /**
     * Check whether a user is authorized to see the full details of this meeting.
     */
    public function canViewDetails(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // Creator and Admins/Directors can always view
        if ($this->created_by === $user->id || $user->hasAnyRole(['super_admin', 'admin', 'director', 'bod'])) {
            return true;
        }

        // Invited attendees can view
        return $this->attendees()->where('user_id', $user->id)->exists();
    }
}
