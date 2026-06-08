<?php

namespace App\Policies;

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\Membership;
use App\Models\User;

class MemberPolicy
{
    /**
     * Determine whether the user can invite members to a congregation.
     */
    public function invite(User $user, Congregation $congregation): bool
    {
        // Superadmin in same Kingdom Hall can invite to any congregation in the KH
        if ($this->isSuperadminInKingdomHall($user, $congregation->kingdom_hall_id)) {
            return true;
        }

        // Admin can invite to own congregation only
        return $user->congregationRole($congregation) === CongregationRole::Admin;
    }

    /**
     * Determine whether the user can update a membership (change role).
     */
    public function update(User $user, Membership $membership): bool
    {
        // Cannot change your own role
        if ($membership->user_id === $user->id) {
            return false;
        }

        $congregation = $membership->congregation;

        // Superadmin in same Kingdom Hall can update any membership in KH congregations
        if ($this->isSuperadminInKingdomHall($user, $congregation->kingdom_hall_id)) {
            return true;
        }

        // Admin can update members in own congregation only (cannot assign superadmin role)
        if ($user->congregationRole($congregation) === CongregationRole::Admin) {
            // Admin cannot modify superadmin members
            if ($membership->role === CongregationRole::Superadmin) {
                return false;
            }

            return true;
        }

        // Member cannot update
        return false;
    }

    /**
     * Determine whether the user can remove a member from a congregation.
     */
    public function delete(User $user, Membership $membership): bool
    {
        // Must NOT be the last admin of the congregation
        if ($this->isLastAdmin($membership)) {
            return false;
        }

        $congregation = $membership->congregation;

        // Superadmin in same Kingdom Hall can remove from any KH congregation
        if ($this->isSuperadminInKingdomHall($user, $congregation->kingdom_hall_id)) {
            return true;
        }

        // Admin can remove from own congregation only (cannot remove superadmin)
        if ($user->congregationRole($congregation) === CongregationRole::Admin) {
            // Admin cannot remove a superadmin
            if ($membership->role === CongregationRole::Superadmin) {
                return false;
            }

            return true;
        }

        // Member cannot remove
        return false;
    }

    /**
     * Check if the user has superadmin role in any congregation within the given Kingdom Hall.
     */
    protected function isSuperadminInKingdomHall(User $user, ?string $kingdomHallId): bool
    {
        if ($kingdomHallId === null) {
            return false;
        }

        return Membership::query()
            ->where('user_id', $user->id)
            ->where('role', CongregationRole::Superadmin)
            ->whereHas('congregation', function ($query) use ($kingdomHallId) {
                $query->where('kingdom_hall_id', $kingdomHallId);
            })
            ->exists();
    }

    /**
     * Check if removing this membership would leave no admins in the congregation.
     */
    protected function isLastAdmin(Membership $membership): bool
    {
        // Only relevant if the member being removed is an admin or superadmin
        if (! $membership->role->isAtLeast(CongregationRole::Admin)) {
            return false;
        }

        $adminCount = Membership::query()
            ->where('congregation_id', $membership->congregation_id)
            ->where('id', '!=', $membership->id)
            ->whereIn('role', [CongregationRole::Admin->value, CongregationRole::Superadmin->value])
            ->count();

        return $adminCount === 0;
    }
}
