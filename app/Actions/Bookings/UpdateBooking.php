<?php

namespace App\Actions\Bookings;

use App\Enums\CongregationRole;
use App\Enums\RecurrenceFrequency;
use App\Events\BookingUpdated;
use App\Models\Booking;
use App\Models\Membership;
use App\Models\RecurrencePattern;
use App\Models\User;
use App\Notifications\Bookings\BookingModifiedNotification;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateBooking
{
    /**
     * Update a booking with the specified edit scope.
     *
     * @param  array{
     *     name?: string,
     *     starts_at?: string,
     *     ends_at?: string,
     *     room_ids?: list<string>,
     *     scope: 'this_only'|'this_and_future',
     * }  $data
     * @return Collection<int, Booking>
     */
    public function handle(User $modifier, Booking $booking, array $data): Collection
    {
        $this->validate($data, $booking);

        $scope = $data['scope'];

        return match ($scope) {
            'this_only' => $this->updateThisOnly($modifier, $booking, $data),
            'this_and_future' => $this->updateThisAndFuture($modifier, $booking, $data),
        };
    }

    /**
     * Edit "this occurrence only": mark as exception, update fields.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, Booking>
     */
    private function updateThisOnly(User $modifier, Booking $booking, array $data): Collection
    {
        return DB::transaction(function () use ($modifier, $booking, $data) {
            // Snapshot old values before update for notification
            $oldStartsAt = $booking->starts_at->copy();
            $oldEndsAt = $booking->ends_at->copy();
            $oldRooms = $booking->rooms->pluck('name')->all();

            $updates = [];

            if (isset($data['name'])) {
                $updates['name'] = $data['name'];
            }

            if (isset($data['starts_at'])) {
                $updates['starts_at'] = Carbon::parse($data['starts_at'])->setTimezone('Europe/Stockholm');
            }

            if (isset($data['ends_at'])) {
                $updates['ends_at'] = Carbon::parse($data['ends_at'])->setTimezone('Europe/Stockholm');
            }

            // Mark as exception if part of a recurrence pattern
            if ($booking->recurrence_pattern_id) {
                $updates['is_exception'] = true;
                $updates['original_starts_at'] = $booking->starts_at;
            }

            // Determine the effective start/end for conflict detection
            $effectiveStart = $updates['starts_at'] ?? $booking->starts_at;
            $effectiveEnd = $updates['ends_at'] ?? $booking->ends_at;
            $effectiveRoomIds = $data['room_ids'] ?? $booking->rooms->pluck('id')->all();

            // Conflict detection (exclude the current booking)
            $this->detectConflicts(
                [[$effectiveStart, $effectiveEnd]],
                $effectiveRoomIds,
                [$booking->id]
            );

            $booking->update($updates);

            // Update room assignments if provided
            if (isset($data['room_ids'])) {
                $booking->rooms()->sync($data['room_ids']);
            }

            $booking->load('rooms');

            $bookings = collect([$booking]);

            BookingUpdated::dispatch($bookings);

            // Dispatch notification if modifier is not the booking owner
            if ($modifier->id !== $booking->user_id) {
                $owner = User::find($booking->user_id);
                $modifierRole = $this->resolveModifierRole($modifier, $booking);

                $owner->notify(new BookingModifiedNotification(
                    bookingName: $booking->name,
                    oldStartsAt: Carbon::instance($oldStartsAt),
                    oldEndsAt: Carbon::instance($oldEndsAt),
                    newStartsAt: Carbon::instance($booking->starts_at),
                    newEndsAt: Carbon::instance($booking->ends_at),
                    oldRooms: $oldRooms,
                    newRooms: $booking->rooms->pluck('name')->all(),
                    modifier: $modifier,
                    modifierRole: $modifierRole,
                    actionTimestamp: Carbon::now(),
                ));
            }

            return $bookings;
        });
    }

    /**
     * Edit "this and all future": end original pattern, create new pattern, regenerate.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, Booking>
     */
    private function updateThisAndFuture(User $modifier, Booking $booking, array $data): Collection
    {
        return DB::transaction(function () use ($modifier, $booking, $data) {
            $recurrencePattern = $booking->recurrencePattern;

            if (! $recurrencePattern) {
                // Non-recurring booking: just update directly (same as this_only but without exception flag)
                return $this->updateSingleBooking($modifier, $booking, $data);
            }

            // Get the new values
            $newName = $data['name'] ?? $booking->name;
            $newStartsAt = isset($data['starts_at'])
                ? Carbon::parse($data['starts_at'])->setTimezone('Europe/Stockholm')
                : Carbon::parse($booking->starts_at)->setTimezone('Europe/Stockholm');
            $newEndsAt = isset($data['ends_at'])
                ? Carbon::parse($data['ends_at'])->setTimezone('Europe/Stockholm')
                : Carbon::parse($booking->ends_at)->setTimezone('Europe/Stockholm');
            $newRoomIds = $data['room_ids'] ?? $booking->rooms->pluck('id')->all();

            // Delete this booking and all future bookings in the series
            $futureBookings = Booking::query()
                ->where('recurrence_pattern_id', $recurrencePattern->id)
                ->where('starts_at', '>=', $booking->starts_at)
                ->get();

            $futureBookingIds = $futureBookings->pluck('id')->all();

            // Delete future bookings (this removes pivot records via cascadeOnDelete)
            Booking::whereIn('id', $futureBookingIds)->delete();

            // Check if the original pattern has any remaining bookings
            $remainingCount = Booking::where('recurrence_pattern_id', $recurrencePattern->id)->count();

            if ($remainingCount === 0) {
                // No bookings remain in original pattern — delete it
                $recurrencePattern->delete();
            }

            // Create a new recurrence pattern from the edit point forward
            $newPattern = RecurrencePattern::create([
                'congregation_id' => $booking->congregation_id,
                'frequency' => $recurrencePattern->frequency->value,
                'end_date' => $recurrencePattern->end_date,
                'end_count' => $recurrencePattern->end_count,
            ]);

            // Generate new occurrence dates from the new start point
            $duration = $newStartsAt->diffInMinutes($newEndsAt);
            $occurrenceDates = $this->generateOccurrenceDates(
                $newStartsAt,
                $newEndsAt,
                $newPattern->frequency,
                $newPattern->end_date,
                $newPattern->end_count
            );

            // Conflict detection on all regenerated occurrences
            $this->detectConflicts($occurrenceDates, $newRoomIds);

            // Create new bookings for the regenerated occurrences
            $newBookings = collect();

            foreach ($occurrenceDates as [$occurrenceStart, $occurrenceEnd]) {
                $newBooking = Booking::create([
                    'congregation_id' => $booking->congregation_id,
                    'user_id' => $booking->user_id,
                    'name' => $newName,
                    'starts_at' => $occurrenceStart,
                    'ends_at' => $occurrenceEnd,
                    'recurrence_pattern_id' => $newPattern->id,
                ]);

                $newBooking->rooms()->attach($newRoomIds);
                $newBookings->push($newBooking);
            }

            // If no occurrences were generated, clean up the pattern
            if ($newBookings->isEmpty()) {
                $newPattern->delete();
            }

            BookingUpdated::dispatch($newBookings);

            return $newBookings;
        });
    }

    /**
     * Update a single (non-recurring) booking directly.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, Booking>
     */
    private function updateSingleBooking(User $modifier, Booking $booking, array $data): Collection
    {
        // Snapshot old values before update for notification
        $oldStartsAt = $booking->starts_at->copy();
        $oldEndsAt = $booking->ends_at->copy();
        $oldRooms = $booking->rooms->pluck('name')->all();

        $updates = [];

        if (isset($data['name'])) {
            $updates['name'] = $data['name'];
        }

        if (isset($data['starts_at'])) {
            $updates['starts_at'] = Carbon::parse($data['starts_at'])->setTimezone('Europe/Stockholm');
        }

        if (isset($data['ends_at'])) {
            $updates['ends_at'] = Carbon::parse($data['ends_at'])->setTimezone('Europe/Stockholm');
        }

        $effectiveStart = $updates['starts_at'] ?? $booking->starts_at;
        $effectiveEnd = $updates['ends_at'] ?? $booking->ends_at;
        $effectiveRoomIds = $data['room_ids'] ?? $booking->rooms->pluck('id')->all();

        $this->detectConflicts(
            [[$effectiveStart, $effectiveEnd]],
            $effectiveRoomIds,
            [$booking->id]
        );

        $booking->update($updates);

        if (isset($data['room_ids'])) {
            $booking->rooms()->sync($data['room_ids']);
        }

        $booking->load('rooms');

        $bookings = collect([$booking]);

        BookingUpdated::dispatch($bookings);

        // Dispatch notification if modifier is not the booking owner
        if ($modifier->id !== $booking->user_id) {
            $owner = User::find($booking->user_id);
            $modifierRole = $this->resolveModifierRole($modifier, $booking);

            $owner->notify(new BookingModifiedNotification(
                bookingName: $booking->name,
                oldStartsAt: Carbon::instance($oldStartsAt),
                oldEndsAt: Carbon::instance($oldEndsAt),
                newStartsAt: Carbon::instance($booking->starts_at),
                newEndsAt: Carbon::instance($booking->ends_at),
                oldRooms: $oldRooms,
                newRooms: $booking->rooms->pluck('name')->all(),
                modifier: $modifier,
                modifierRole: $modifierRole,
                actionTimestamp: Carbon::now(),
            ));
        }

        return $bookings;
    }

    /**
     * Validate the incoming update data.
     *
     * @param  array<string, mixed>  $data
     */
    private function validate(array $data, Booking $booking): void
    {
        $kingdomHallId = $booking->congregation->kingdom_hall_id;

        Validator::make($data, [
            'scope' => ['required', 'string', Rule::in(['this_only', 'this_and_future'])],
            'name' => ['sometimes', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
            'room_ids' => ['sometimes', 'array', 'min:1'],
            'room_ids.*' => [
                'required',
                'string',
                Rule::exists('rooms', 'id')->where('kingdom_hall_id', $kingdomHallId),
            ],
        ])->validate();

        // Validate 15-minute alignment on provided times
        if (isset($data['starts_at'])) {
            $startsAt = Carbon::parse($data['starts_at']);

            if ($startsAt->minute % 15 !== 0 || $startsAt->second !== 0) {
                throw ValidationException::withMessages([
                    'starts_at' => ['The start time must be aligned to a 15-minute interval.'],
                ]);
            }
        }

        if (isset($data['ends_at'])) {
            $endsAt = Carbon::parse($data['ends_at']);

            if ($endsAt->minute % 15 !== 0 || $endsAt->second !== 0) {
                throw ValidationException::withMessages([
                    'ends_at' => ['The end time must be aligned to a 15-minute interval.'],
                ]);
            }
        }

        // If both are provided, ensure ends_at > starts_at
        if (isset($data['starts_at']) && isset($data['ends_at'])) {
            $startsAt = Carbon::parse($data['starts_at']);
            $endsAt = Carbon::parse($data['ends_at']);

            if ($endsAt->lte($startsAt)) {
                throw ValidationException::withMessages([
                    'ends_at' => ['The end time must be after the start time.'],
                ]);
            }
        }
    }

    /**
     * Generate occurrence date pairs based on recurrence rules.
     *
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function generateOccurrenceDates(
        Carbon $startsAt,
        Carbon $endsAt,
        RecurrenceFrequency $frequency,
        Carbon|CarbonImmutable|null $endDate,
        ?int $endCount,
    ): array {
        $duration = $startsAt->diffInMinutes($endsAt);
        $occurrences = [];
        $currentStart = $startsAt->copy();
        $maxOccurrences = 365;

        while (count($occurrences) < $maxOccurrences) {
            $currentEnd = $currentStart->copy()->addMinutes($duration);

            if ($endDate && $currentStart->toDateString() > $endDate->toDateString()) {
                break;
            }

            if ($endCount && count($occurrences) >= $endCount) {
                break;
            }

            $occurrences[] = [$currentStart->copy(), $currentEnd->copy()];

            $currentStart = $this->advanceDate($currentStart, $frequency);
        }

        return $occurrences;
    }

    /**
     * Advance a date by the given frequency.
     */
    private function advanceDate(Carbon $date, RecurrenceFrequency $frequency): Carbon
    {
        return match ($frequency) {
            RecurrenceFrequency::Daily => $date->copy()->addDay(),
            RecurrenceFrequency::Weekly => $date->copy()->addWeek(),
            RecurrenceFrequency::Monthly => $date->copy()->addMonth(),
            RecurrenceFrequency::Yearly => $date->copy()->addYear(),
        };
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

    /**
     * Resolve the modifier's congregation role for notification context.
     */
    private function resolveModifierRole(User $modifier, Booking $booking): string
    {
        $membership = Membership::query()
            ->where('user_id', $modifier->id)
            ->where('congregation_id', $booking->congregation_id)
            ->first();

        if ($membership) {
            return $membership->role->label();
        }

        return CongregationRole::Admin->label();
    }
}
