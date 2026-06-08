<?php

namespace App\Concerns;

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\URL;

trait HasCongregations
{
    /**
     * Get all of the congregations the user belongs to.
     *
     * @return BelongsToMany<Congregation, $this>
     */
    public function congregations(): BelongsToMany
    {
        return $this->belongsToMany(Congregation::class, 'congregation_members', 'user_id', 'congregation_id')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get the user's current congregation.
     *
     * @return BelongsTo<Congregation, $this>
     */
    public function currentCongregation(): BelongsTo
    {
        return $this->belongsTo(Congregation::class, 'current_congregation_id');
    }

    /**
     * Get all of the memberships for the user.
     *
     * @return HasMany<Membership, $this>
     */
    public function congregationMemberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    /**
     * Switch to the given congregation.
     */
    public function switchCongregation(Congregation $congregation): bool
    {
        if (! $this->belongsToCongregation($congregation)) {
            return false;
        }

        $this->update(['current_congregation_id' => $congregation->id]);
        $this->setRelation('currentCongregation', $congregation);

        URL::defaults(['current_congregation' => $congregation->slug]);

        return true;
    }

    /**
     * Determine if the user belongs to the given congregation.
     */
    public function belongsToCongregation(Congregation $congregation): bool
    {
        return $this->congregations()->where('congregations.id', $congregation->id)->exists();
    }

    /**
     * Determine if the given congregation is the user's current congregation.
     */
    public function isCurrentCongregation(Congregation $congregation): bool
    {
        return $this->current_congregation_id === $congregation->id;
    }

    /**
     * Get the user's role in the given congregation.
     */
    public function congregationRole(Congregation $congregation): ?CongregationRole
    {
        return $this->congregationMemberships()
            ->where('congregation_id', $congregation->id)
            ->first()
            ?->role;
    }

    /**
     * Determine if the user has the superadmin role in the given (or current) congregation.
     */
    public function isSuperadmin(?Congregation $congregation = null): bool
    {
        $congregation ??= $this->currentCongregation;

        if (! $congregation) {
            return false;
        }

        return $this->congregationRole($congregation) === CongregationRole::Superadmin;
    }

    /**
     * Determine if the user has the admin role (or higher) in the given (or current) congregation.
     */
    public function isAdmin(?Congregation $congregation = null): bool
    {
        $congregation ??= $this->currentCongregation;

        if (! $congregation) {
            return false;
        }

        $role = $this->congregationRole($congregation);

        return $role !== null && $role->isAtLeast(CongregationRole::Admin);
    }

    /**
     * Determine if the user is a member (any role) of the given (or current) congregation.
     */
    public function isMember(?Congregation $congregation = null): bool
    {
        $congregation ??= $this->currentCongregation;

        if (! $congregation) {
            return false;
        }

        return $this->congregationRole($congregation) !== null;
    }

    /**
     * Get a fallback congregation (excluding the given one).
     */
    public function fallbackCongregation(?Congregation $excluding = null): ?Congregation
    {
        return $this->congregations()
            ->when($excluding, fn ($query) => $query->where('congregations.id', '!=', $excluding->id))
            ->orderByRaw('LOWER(congregations.name)')
            ->first();
    }
}
