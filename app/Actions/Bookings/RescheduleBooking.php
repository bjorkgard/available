<?php

namespace App\Actions\Bookings;

use App\Events\BookingUpdated;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RescheduleBooking
{
    /**
     * Reschedule a booking via drag-and-drop.
     *
     * Preserves the original duration and snaps to the nearest 15-minute boundary.
     *
     * @param  'this_only'|'this_and_future'  $scope
     * @return Collection<int, Booking>
     */
    public function handle(User $modifier, Booking $booking, Carbon $newStartsAt, string $scope): Collection
    {
        // Snap to nearest 15-minute boundary
        $newStartsAt = $this->snapTo15Minutes($newStartsAt);

        // Compute new ends_at preserving original duration
        $durationInMinutes = $booking->starts_at->diffInMinutes($booking->ends_at);
        $newEndsAt = $newStartsAt->copy()->addMinutes($durationInMinutes);

        return match ($scope) {
            'this_only' => $this->rescheduleThisOnly($modifier, $booking, $newStartsAt, $newEndsAt),
            'this_and_future' => $this->rescheduleThisAndFuture($modifier, $booking, $newStartsAt, $newEndsAt),
        };
    }

    /**
     * Reschedule only this occurrence.
     *
     * @return Collection<int, Booking>
     */
    private function rescheduleThisOnly(User $modifier, Booking $booking, Carbon $newStartsAt, Carbon $newEndsAt): Collection
    {
        return DB::transaction(function () use ($modifier, $booking, $newStartsAt, $newEndsAt) {
            $roomIds = $booking->rooms->pluck('id')->all();

            // Conflict detection (exclude the current booking)
            $this->detectConflicts(
                [[$newStartsAt, $newEndsAt]],
                $roomIds,
                [$booking->id]
            );

            $updates = [
                'starts_at' => $newStartsAt,
                'ends_at' => $newEndsAt,
            ];

            // Mark as exception if part of a recurrence pattern
            if ($booking->recurrence_pattern_id) {
                $updates['is_exception'] = true;
                $updates['original_starts_at'] = $booking->starts_at;
            }

            $booking->update($updates);
            $booking->load('rooms');

            BookingUpdated::dispatch($booking);

            // Notify original booker if rescheduled by a different user
            if ($modifier->id !== $booking->user_id) {
                // Notification will be implemented in a later task
            }

            return collect([$booking]);
        });
    }

    /**
     * Reschedule this occurrence and all future occurrences in the series.
     *
     * Computes the time delta and applies it to all future occurrences.
     *
     * @return Collection<int, Booking>
     */
    private function rescheduleThisAndFuture(User $modifier, Booking $booking, Carbon $newStartsAt, Carbon $newEndsAt): Collection
    {
        return DB::transaction(function () use ($modifier, $booking, $newStartsAt, $newEndsAt) {
            $recurrencePattern = $booking->recurrencePattern;

            if (! $recurrencePattern) {
                // Non-recurring booking: just reschedule this one
                return $this->rescheduleThisOnly($modifier, $booking, $newStartsAt, $newEndsAt);
            }

            // Compute the time delta (difference between new start and old start)
            $deltaInMinutes = $booking->starts_at->diffInMinutes($newStartsAt, false);
            $durationInMinutes = $booking->starts_at->diffInMinutes($booking->ends_at);

            // Get all future bookings in the series (this booking + all subsequent)
            $futureBookings = Booking::query()
                ->where('recurrence_pattern_id', $recurrencePattern->id)
                ->where('starts_at', '>=', $booking->starts_at)
                ->orderBy('starts_at')
                ->get();

            $roomIds = $booking->rooms->pluck('id')->all();

            // Compute the new time slots for all affected bookings
            $occurrenceDates = [];
            $bookingMap = [];

            foreach ($futureBookings as $futureBooking) {
                $adjustedStart = Carbon::parse($futureBooking->starts_at)->addMinutes($deltaInMinutes);
                $adjustedStart = $this->snapTo15Minutes($adjustedStart);
                $adjustedEnd = $adjustedStart->copy()->addMinutes($durationInMinutes);

                $occurrenceDates[] = [$adjustedStart, $adjustedEnd];
                $bookingMap[] = [
                    'booking' => $futureBooking,
                    'starts_at' => $adjustedStart,
                    'ends_at' => $adjustedEnd,
                ];
            }

            // Conflict detection for all affected bookings (exclude all of them)
            $excludeIds = $futureBookings->pluck('id')->all();
            $this->detectConflicts($occurrenceDates, $roomIds, $excludeIds);

            // Apply the delta to all future bookings
            $updatedBookings = collect();

            foreach ($bookingMap as $entry) {
                $entry['booking']->update([
                    'starts_at' => $entry['starts_at'],
                    'ends_at' => $entry['ends_at'],
                ]);

                $entry['booking']->load('rooms');
                $updatedBookings->push($entry['booking']);
            }

            BookingUpdated::dispatch($updatedBookings->first());

            // Notify original booker if rescheduled by a different user
            if ($modifier->id !== $booking->user_id) {
                // Notification will be implemented in a later task
            }

            return $updatedBookings;
        });
    }

    /**
     * Snap a timestamp to the nearest 15-minute boundary.
     */
    private function snapTo15Minutes(Carbon|CarbonImmutable $time): Carbon
    {
        $time = Carbon::parse($time);
        $minutes = $time->minute;
        $remainder = $minutes % 15;

        if ($remainder === 0) {
            return $time->copy()->second(0);
        }

        // Round to nearest 15 minutes
        if ($remainder < 8) {
            return $time->copy()->minute($minutes - $remainder)->second(0);
        }

        return $time->copy()->minute($minutes - $remainder + 15)->second(0);
    }

    /**
     * Detect conflicts across all occurrence dates and rooms.
     *
     * @param  list<array{0: Carbon, 1: Carbon}>  $occurrenceDates
     * @param  list<string>  $roomIds
     * @param  list<string>  $excludeIds
     *
     * @throws ValidationException
     */
    private function detectConflicts(array $occurrenceDates, array $roomIds, array $excludeIds = []): void
    {
        $conflicts = [];

        foreach ($occurrenceDates as [$start, $end]) {
            $query = Booking::query()
                ->whereHas('rooms', function ($query) use ($roomIds) {
                    $query->whereIn('rooms.id', $roomIds);
                })
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start);

            if (! empty($excludeIds)) {
                $query->whereNotIn('id', $excludeIds);
            }

            if ($query->exists()) {
                $conflicts[] = $start->toDateTimeString();
            }
        }

        if (! empty($conflicts)) {
            throw ValidationException::withMessages([
                'conflicts' => ['Booking conflicts detected on the following dates: '.implode(', ', $conflicts)],
            ]);
        }
    }
}
