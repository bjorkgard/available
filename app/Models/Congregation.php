<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueSlugs;
use Database\Factories\CongregationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Congregation extends Model
{
    /** @use HasFactory<CongregationFactory> */
    use GeneratesUniqueSlugs, HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'congregation_number',
        'kingdom_hall_id',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Congregation $congregation) {
            if (empty($congregation->slug)) {
                $congregation->slug = static::generateUniqueSlug($congregation->name);
            }
        });

        static::updating(function (Congregation $congregation) {
            if ($congregation->isDirty('name')) {
                $congregation->slug = static::generateUniqueSlug($congregation->name, $congregation->id);
            }
        });
    }

    /**
     * Get the Kingdom Hall this congregation belongs to.
     *
     * @return BelongsTo<KingdomHall, $this>
     */
    public function kingdomHall(): BelongsTo
    {
        return $this->belongsTo(KingdomHall::class);
    }

    /**
     * Get all members of this congregation.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'congregation_members', 'congregation_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this congregation.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'congregation_id');
    }

    /**
     * Get all invitations for this congregation.
     *
     * @return HasMany<CongregationInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(CongregationInvitation::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
