<?php

namespace App\Models;

use App\Enums\RecurrenceFrequency;
use Database\Factories\RecurrencePatternFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurrencePattern extends Model
{
    /** @use HasFactory<RecurrencePatternFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'congregation_id',
        'frequency',
        'end_date',
        'end_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'frequency' => RecurrenceFrequency::class,
            'end_date' => 'date',
            'end_count' => 'integer',
        ];
    }

    /**
     * Get the congregation that owns this recurrence pattern.
     *
     * @return BelongsTo<Congregation, $this>
     */
    public function congregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class);
    }

    /**
     * Get the bookings for this recurrence pattern.
     *
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
