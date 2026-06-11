<?php

namespace App\Concerns;

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
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
        if (! $this->belongsToCongregation($congregation) && ! $this->isSuperadminInSameKingdomHall($congregation)) {
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
     *
     * Returns the direct membership role, or Superadmin if the user is a
     * superadmin in another congregation that shares the same Kingdom Hall.
     */
    public function congregationRole(Congregation $congregation): ?CongregationRole
    {
        $directRole = $this->congregationMemberships()
            ->where('congregation_id', $congregation->id)
            ->first()
            ?->role;

        if ($directRole !== null) {
            return $directRole;
        }

        // Superadmins in the same Kingdom Hall get superadmin access
        if ($this->isSuperadminInSameKingdomHall($congregation)) {
            return CongregationRole::Superadmin;
        }

        return null;
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
     * Determine if the user is a superadmin in any congregation that shares
     * the same Kingdom Hall as the given congregation.
     */
    public function isSuperadminInSameKingdomHall(Congregation $congregation): bool
    {
        if (! $congregation->kingdom_hall_id) {
            return false;
        }

        return Membership::where('user_id', $this->id)
            ->where('role', CongregationRole::Superadmin)
            ->whereHas('congregation', fn ($query) => $query->where('kingdom_hall_id', $congregation->kingdom_hall_id))
            ->exists();
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

    /**
     * Get the user's congregations as an array of data suitable for the frontend.
     *
     * @return Collection<int, array{id: string, name: string, slug: string, congregation_number: string|null, role: string|null, roleLabel: string|null, isCurrent: bool}>
     */
    public function toUserCongregations(bool $includeCurrent = false): Collection
    {
        return $this->congregations()
            ->get()
            ->map(function (Congregation $congregation) use ($includeCurrent) {
                if (! $includeCurrent && $this->isCurrentCongregation($congregation)) {
                    return null;
                }

                $role = $this->congregationRole($congregation);

                return [
                    'id' => $congregation->id,
                    'name' => $congregation->name,
                    'slug' => $congregation->slug,
                    'congregation_number' => $congregation->congregation_number,
                    'role' => $role?->value,
                    'roleLabel' => $role?->label(),
                    'isCurrent' => $this->isCurrentCongregation($congregation),
                ];
            })
            ->filter()
            ->values();
    }
}
