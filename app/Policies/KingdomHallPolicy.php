<?php

namespace App\Policies;

use App\Enums\CongregationRole;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;

class KingdomHallPolicy
{
    /**
     * Determine whether the user can view the Kingdom Hall.
     *
     * A user can view a Kingdom Hall if they belong to ANY congregation in it.
     */
    public function view(User $user, KingdomHall $kingdomHall): bool
    {
        return $user->congregations()
            ->where('kingdom_hall_id', $kingdomHall->id)
            ->exists();
    }

    /**
     * Determine whether the user can update the Kingdom Hall.
     *
     * Only users with superadmin role in a congregation belonging to this KH.
     */
    public function update(User $user, KingdomHall $kingdomHall): bool
    {
        return $this->isSuperadminInKingdomHall($user, $kingdomHall);
    }

    /**
     * Determine whether the user can delete the Kingdom Hall.
     *
     * Only users with superadmin role in a congregation belonging to this KH.
     */
    public function delete(User $user, KingdomHall $kingdomHall): bool
    {
        return $this->isSuperadminInKingdomHall($user, $kingdomHall);
    }

    /**
     * Determine whether the user can add a congregation to the Kingdom Hall.
     *
     * Only users with superadmin role in a congregation belonging to this KH.
     */
    public function addCongregation(User $user, KingdomHall $kingdomHall): bool
    {
        return $this->isSuperadminInKingdomHall($user, $kingdomHall);
    }

    /**
     * Determine whether the user can manage rooms in the Kingdom Hall.
     *
     * Only users with superadmin role in a congregation belonging to this KH.
     */
    public function manageRooms(User $user, KingdomHall $kingdomHall): bool
    {
        return $this->isSuperadminInKingdomHall($user, $kingdomHall);
    }

    /**
     * Determine whether the user can delete a congregation from the Kingdom Hall.
     *
     * Only users with superadmin role in a congregation belonging to this KH.
     */
    public function deleteCongregation(User $user, KingdomHall $kingdomHall): bool
    {
        return $this->isSuperadminInKingdomHall($user, $kingdomHall);
    }

    /**
     * Check if the user has superadmin membership in any congregation
     * where congregation.kingdom_hall_id = $kingdomHall->id.
     */
    private function isSuperadminInKingdomHall(User $user, KingdomHall $kingdomHall): bool
    {
        return Membership::where('user_id', $user->id)
            ->where('role', CongregationRole::Superadmin)
            ->whereHas('congregation', fn ($query) => $query->where('kingdom_hall_id', $kingdomHall->id))
            ->exists();
    }
}
