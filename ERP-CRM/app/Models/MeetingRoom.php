<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingRoom extends Model
{
    protected $fillable = [
        'name',
        'location',
        'capacity',
        'description',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(MeetingRoomBooking::class, 'meeting_room_id');
    }

    /**
     * Get active rooms for dropdown selection
     */
    public static function getActiveRooms()
    {
        return static::where('status', 'active')->orderBy('name')->get();
    }
}
