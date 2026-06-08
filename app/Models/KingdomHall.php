<?php

namespace App\Models;

use Database\Factories\KingdomHallFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KingdomHall extends Model
{
    /** @use HasFactory<KingdomHallFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'street_address',
        'zip_code',
        'city',
        'number_of_rooms',
    ];

    /**
     * Get the congregations that meet in this Kingdom Hall.
     *
     * @return HasMany<Congregation, $this>
     */
    public function congregations(): HasMany
    {
        return $this->hasMany(Congregation::class);
    }

    /**
     * Get the rooms in this Kingdom Hall.
     *
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number_of_rooms' => 'integer',
        ];
    }
}
