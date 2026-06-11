<?php

namespace App\Actions\Congregations;

use App\Models\Booking;
use App\Models\Congregation;
use App\Models\Membership;
use App\Models\RecurrencePattern;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteCongregation
{
    /**
     * Delete a congregation, removing exclusive users and switching multi-congregation users.
     */
    public function handle(Congregation $congregation): void
    {
        DB::transaction(function () use ($congregation) {
            // Get all user IDs belonging to this congregation
            $memberUserIds = Membership::where('congregation_id', $congregation->id)
                ->pluck('user_id');

            // Find exclusive users (users who only belong to this congregation)
            $exclusiveUserIds = $memberUserIds->filter(function (string $userId) use ($congregation) {
                return Membership::where('user_id', $userId)
                    ->where('congregation_id', '!=', $congregation->id)
                    ->doesntExist();
            });

            // For multi-congregation users, switch their current_congregation_id if needed
            $multiCongregationUserIds = $memberUserIds->diff($exclusiveUserIds);

            foreach ($multiCongregationUserIds as $userId) {
                $user = User::find($userId);

                if ($user && $user->current_congregation_id === $congregation->id) {
                    $fallback = $user->fallbackCongregation($congregation);

                    if ($fallback) {
                        $user->update(['current_congregation_id' => $fallback->id]);
                    }
                }
            }

            // Delete all bookings for this congregation (before recurrence patterns due to FK)
            Booking::where('congregation_id', $congregation->id)->delete();

            // Delete all recurrence patterns for this congregation
            RecurrencePattern::where('congregation_id', $congregation->id)->delete();

            // Cancel pending invitations
            $congregation->invitations()->delete();

            // Remove all memberships for this congregation
            Membership::where('congregation_id', $congregation->id)->delete();

            // Delete exclusive users from the system
            if ($exclusiveUserIds->isNotEmpty()) {
                User::whereIn('id', $exclusiveUserIds)->delete();
            }

            // Soft-delete the congregation
            $congregation->delete();
        });
    }
}
