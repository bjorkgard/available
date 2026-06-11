<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'congregation_id',
        'user_id',
        'name',
        'starts_at',
        'ends_at',
        'recurrence_pattern_id',
        'is_exception',
        'original_starts_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'original_starts_at' => 'datetime',
            'is_exception' => 'boolean',
        ];
    }

    /**
     * Get the congregation that owns this booking.
     *
     * @return BelongsTo<Congregation, $this>
     */
    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    /**
     * Get the user that created this booking.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the recurrence pattern for this booking.
     *
     * @return BelongsTo<RecurrencePattern, $this>
     */
    public function recurrencePattern(): BelongsTo
    {
        return $this->belongsTo(RecurrencePattern::class);
    }

    /**
     * Get the rooms assigned to this booking.
     *
     * @return BelongsToMany<Room, $this>
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'booking_room')
            ->using(BookingRoom::class);
    }
}
