<?php

namespace App\Policies;

use App\Enums\CongregationRole;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\Membership;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view the booking.
     *
     * Any user who belongs to a congregation in the same Kingdom Hall can view it.
     */
    public function view(User $user, Booking $booking): bool
    {
        $kingdomHallId = $booking->congregation->kingdom_hall_id;

        if (! $kingdomHallId) {
            return false;
        }

        return $user->currentCongregation?->kingdom_hall_id === $kingdomHallId
            || Membership::where('user_id', $user->id)
                ->whereHas('congregation', fn ($q) => $q->where('kingdom_hall_id', $kingdomHallId))
                ->exists();
    }

    /**
     * Determine whether the user can create bookings for the given congregation.
     *
     * Any member of the congregation can create bookings.
     */
    public function create(User $user, Congregation $congregation): bool
    {
        return $user->belongsToCongregation($congregation);
    }

    /**
     * Determine whether the user can update the booking.
     *
     * Owner, Admin in same congregation, or Superadmin in same Kingdom Hall.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $this->isOwner($user, $booking)
            || $this->isAdminOfBookingCongregation($user, $booking)
            || $this->isSuperadminInSameHall($user, $booking);
    }

    /**
     * Determine whether the user can delete the booking.
     *
     * Same logic as update.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return $this->update($user, $booking);
    }

    /**
     * Determine if the user is the owner of the booking.
     */
    private function isOwner(User $user, Booking $booking): bool
    {
        return $user->id === $booking->user_id;
    }

    /**
     * Determine if the user is an admin (or higher) in the booking's congregation.
     */
    private function isAdminOfBookingCongregation(User $user, Booking $booking): bool
    {
        $role = $user->congregationRole($booking->congregation);

        return $role !== null && $role->isAtLeast(CongregationRole::Admin);
    }

    /**
     * Determine if the user is a superadmin in any congregation that shares
     * the same Kingdom Hall as the booking's congregation.
     */
    private function isSuperadminInSameHall(User $user, Booking $booking): bool
    {
        $kingdomHallId = $booking->congregation->kingdom_hall_id;

        if (! $kingdomHallId) {
            return false;
        }

        return Membership::where('user_id', $user->id)
            ->where('role', CongregationRole::Superadmin)
            ->whereHas('congregation', function ($query) use ($kingdomHallId) {
                $query->where('kingdom_hall_id', $kingdomHallId);
            })
            ->exists();
    }
}
