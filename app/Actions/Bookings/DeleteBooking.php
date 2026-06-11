<?php

namespace App\Actions\Bookings;

use App\Enums\CongregationRole;
use App\Enums\DeleteScope;
use App\Events\BookingDeleted;
use App\Models\Booking;
use App\Models\Membership;
use App\Models\RecurrencePattern;
use App\Models\User;
use App\Notifications\Bookings\BookingDeletedNotification;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeleteBooking
{
    /**
     * Delete a booking based on the given scope.
     *
     * - this_only: delete the single occurrence/booking
     * - all_future: delete this + all subsequent occurrences in the series;
     *   if none remain, delete the recurrence pattern
     * - all: delete a standalone (non-recurring) booking
     */
    public function handle(User $deleter, Booking $booking, DeleteScope $scope): void
    {
        // Resolve the kingdom hall ID before deletion (the relationship may not be available after)
        $kingdomHallId = $booking->congregation->kingdom_hall_id;

        match ($scope) {
            DeleteScope::ThisOnly => $this->deleteThisOnly($deleter, $booking, $kingdomHallId),
            DeleteScope::AllFuture => $this->deleteAllFuture($deleter, $booking, $kingdomHallId),
            DeleteScope::All => $this->deleteAll($deleter, $booking, $kingdomHallId),
        };
    }

    /**
     * Delete a single occurrence.
     */
    private function deleteThisOnly(User $deleter, Booking $booking, string $kingdomHallId): void
    {
        $bookingId = $booking->id;
        $bookerId = $booking->user_id;

        // Capture booking info before deletion for notification
        $bookingName = $booking->name;
        $startsAt = $booking->starts_at->copy();
        $endsAt = $booking->ends_at->copy();
        $roomNames = $booking->rooms->pluck('name')->all();
        $congregationId = $booking->congregation_id;

        $booking->delete();

        $this->dispatchEvents($deleter, $bookerId, collect([$bookingId]), $kingdomHallId);
        $this->dispatchNotification($deleter, $bookerId, $congregationId, $bookingName, $startsAt, $endsAt, $roomNames);
        $this->cleanupOrphanedPattern($booking);
    }

    /**
     * Delete the selected occurrence and all subsequent occurrences in the series.
     * If no occurrences remain, also delete the recurrence pattern.
     */
    private function deleteAllFuture(User $deleter, Booking $booking, string $kingdomHallId): void
    {
        $recurrencePatternId = $booking->recurrence_pattern_id;
        $bookerId = $booking->user_id;

        // Capture booking info before deletion for notification
        $bookingName = $booking->name;
        $startsAt = $booking->starts_at->copy();
        $endsAt = $booking->ends_at->copy();
        $roomNames = $booking->rooms->pluck('name')->all();
        $congregationId = $booking->congregation_id;

        DB::transaction(function () use ($deleter, $booking, $recurrencePatternId, $bookerId, $kingdomHallId, $bookingName, $startsAt, $endsAt, $roomNames, $congregationId) {
            $futureBookings = Booking::query()
                ->where('recurrence_pattern_id', $recurrencePatternId)
                ->where('starts_at', '>=', $booking->starts_at)
                ->get();

            $deletedIds = $futureBookings->pluck('id');

            Booking::query()
                ->whereIn('id', $deletedIds)
                ->delete();

            // If no bookings remain for the pattern, delete the pattern
            $remainingCount = Booking::query()
                ->where('recurrence_pattern_id', $recurrencePatternId)
                ->count();

            if ($remainingCount === 0 && $recurrencePatternId) {
                RecurrencePattern::where('id', $recurrencePatternId)->delete();
            }

            $this->dispatchEvents($deleter, $bookerId, $deletedIds, $kingdomHallId);
            $this->dispatchNotification($deleter, $bookerId, $congregationId, $bookingName, $startsAt, $endsAt, $roomNames);
        });
    }

    /**
     * Delete a standalone (non-recurring) booking.
     */
    private function deleteAll(User $deleter, Booking $booking, string $kingdomHallId): void
    {
        $bookingId = $booking->id;
        $bookerId = $booking->user_id;

        // Capture booking info before deletion for notification
        $bookingName = $booking->name;
        $startsAt = $booking->starts_at->copy();
        $endsAt = $booking->ends_at->copy();
        $roomNames = $booking->rooms->pluck('name')->all();
        $congregationId = $booking->congregation_id;

        $booking->delete();

        $this->dispatchEvents($deleter, $bookerId, collect([$bookingId]), $kingdomHallId);
        $this->dispatchNotification($deleter, $bookerId, $congregationId, $bookingName, $startsAt, $endsAt, $roomNames);
    }

    /**
     * Dispatch the BookingDeleted event.
     *
     * @param  Collection<int, string>  $deletedIds
     */
    private function dispatchEvents(User $deleter, string $bookerId, $deletedIds, string $kingdomHallId): void
    {
        BookingDeleted::dispatch($deletedIds, $kingdomHallId, $deleter->name);
    }

    /**
     * Dispatch notification to the original booker if the deleter is a different user.
     *
     * @param  array<int, string>  $roomNames
     */
    private function dispatchNotification(
        User $deleter,
        string $bookerId,
        string $congregationId,
        string $bookingName,
        Carbon|CarbonImmutable $startsAt,
        Carbon|CarbonImmutable $endsAt,
        array $roomNames,
    ): void {
        if ($deleter->id === $bookerId) {
            return;
        }

        $owner = User::find($bookerId);

        if (! $owner) {
            return;
        }

        $deleterRole = $this->resolveDeleterRole($deleter, $congregationId);

        $owner->notify(new BookingDeletedNotification(
            bookingName: $bookingName,
            startsAt: Carbon::instance($startsAt),
            endsAt: Carbon::instance($endsAt),
            roomNames: $roomNames,
            deleter: $deleter,
            deleterRole: $deleterRole,
            actionTimestamp: Carbon::now(),
        ));
    }

    /**
     * Resolve the deleter's congregation role for notification context.
     */
    private function resolveDeleterRole(User $deleter, string $congregationId): CongregationRole
    {
        $membership = Membership::query()
            ->where('user_id', $deleter->id)
            ->where('congregation_id', $congregationId)
            ->first();

        if ($membership) {
            return $membership->role;
        }

        return CongregationRole::Admin;
    }

    /**
     * Clean up the recurrence pattern if no bookings remain after a single delete.
     */
    private function cleanupOrphanedPattern(Booking $booking): void
    {
        $recurrencePatternId = $booking->recurrence_pattern_id;

        if (! $recurrencePatternId) {
            return;
        }

        $remainingCount = Booking::query()
            ->where('recurrence_pattern_id', $recurrencePatternId)
            ->count();

        if ($remainingCount === 0) {
            RecurrencePattern::where('id', $recurrencePatternId)->delete();
        }
    }
}
