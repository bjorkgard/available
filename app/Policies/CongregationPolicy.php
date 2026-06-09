<?php

namespace App\Policies;

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\Membership;
use App\Models\User;

class CongregationPolicy
{
    /**
     * Determine whether the user can view the congregation.
     *
     * User must be a member of this congregation OR be a superadmin in the same Kingdom Hall.
     */
    public function view(User $user, Congregation $congregation): bool
    {
        return $user->belongsToCongregation($congregation)
            || $this->isSuperadminInSameKingdomHall($user, $congregation);
    }

    /**
     * Determine whether the user can update the congregation.
     *
     * User must be admin of this congregation OR superadmin in same Kingdom Hall.
     */
    public function update(User $user, Congregation $congregation): bool
    {
        return $this->isAdminOfCongregation($user, $congregation)
            || $this->isSuperadminInSameKingdomHall($user, $congregation);
    }

    /**
     * Determine whether the user can delete the congregation.
     *
     * User must be admin of this congregation OR superadmin in same Kingdom Hall.
     */
    public function delete(User $user, Congregation $congregation): bool
    {
        return $this->isAdminOfCongregation($user, $congregation)
            || $this->isSuperadminInSameKingdomHall($user, $congregation);
    }

    /**
     * Determine whether the user can move the congregation to a different Kingdom Hall.
     *
     * User must be admin of this congregation OR superadmin in same Kingdom Hall.
     */
    public function move(User $user, Congregation $congregation): bool
    {
        return $this->isAdminOfCongregation($user, $congregation)
            || $this->isSuperadminInSameKingdomHall($user, $congregation);
    }

    /**
     * Determine whether the user can manage members of the congregation.
     *
     * User must be admin of this congregation OR superadmin in same Kingdom Hall.
     */
    public function manageMembers(User $user, Congregation $congregation): bool
    {
        return $this->isAdminOfCongregation($user, $congregation)
            || $this->isSuperadminInSameKingdomHall($user, $congregation);
    }

    /**
     * Determine if the user is an admin (or superadmin) of the given congregation.
     */
    private function isAdminOfCongregation(User $user, Congregation $congregation): bool
    {
        $role = $user->congregationRole($congregation);

        return $role !== null && $role->isAtLeast(CongregationRole::Admin);
    }

    /**
     * Determine if the user is a superadmin in any congregation that shares
     * the same Kingdom Hall as the target congregation.
     */
    private function isSuperadminInSameKingdomHall(User $user, Congregation $congregation): bool
    {
        if (! $congregation->kingdom_hall_id) {
            return false;
        }

        return Membership::where('user_id', $user->id)
            ->where('role', CongregationRole::Superadmin)
            ->whereHas('congregation', function ($query) use ($congregation) {
                $query->where('kingdom_hall_id', $congregation->kingdom_hall_id);
            })
            ->exists();
    }
}
