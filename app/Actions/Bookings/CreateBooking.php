<?php

namespace App\Actions\Bookings;

use App\Enums\RecurrenceFrequency;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\RecurrencePattern;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateBooking
{
    /**
     * Create a booking (and optional recurrence occurrences).
     *
     * @param  array{
     *     name: string,
     *     starts_at: string,
     *     ends_at: string,
     *     room_ids: list<string>,
     *     recurrence?: array{frequency: string, end_date?: string, end_count?: int}|null,
     * }  $data
     * @return Collection<int, Booking>
     */
    public function handle(User $user, Congregation $congregation, array $data): Collection
    {
        $this->validate($data, $congregation);

        $startsAt = Carbon::parse($data['starts_at'])->setTimezone('Europe/Stockholm');
        $endsAt = Carbon::parse($data['ends_at'])->setTimezone('Europe/Stockholm');
        $roomIds = $data['room_ids'];

        $occurrenceDates = $this->generateOccurrenceDates($startsAt, $endsAt, $data['recurrence'] ?? null);

        return DB::transaction(function () use ($user, $congregation, $data, $roomIds, $occurrenceDates) {
            $recurrencePattern = null;

            if (! empty($data['recurrence'])) {
                $recurrencePattern = RecurrencePattern::create([
                    'congregation_id' => $congregation->id,
                    'frequency' => $data['recurrence']['frequency'],
                    'end_date' => $data['recurrence']['end_date'] ?? null,
                    'end_count' => $data['recurrence']['end_count'] ?? null,
                ]);
            }

            // Detect conflicts for all occurrences before persisting
            $this->detectConflicts($occurrenceDates, $roomIds);

            $bookings = collect();

            foreach ($occurrenceDates as [$occurrenceStart, $occurrenceEnd]) {
                $booking = Booking::create([
                    'congregation_id' => $congregation->id,
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'starts_at' => $occurrenceStart,
                    'ends_at' => $occurrenceEnd,
                    'recurrence_pattern_id' => $recurrencePattern?->id,
                ]);

                $booking->rooms()->attach($roomIds);
                $bookings->push($booking);
            }

            BookingCreated::dispatch($bookings->first());

            return $bookings;
        });
    }

    /**
     * Validate the incoming booking data.
     *
     * @param  array<string, mixed>  $data
     */
    private function validate(array $data, Congregation $congregation): void
    {
        $kingdomHallId = $congregation->kingdom_hall_id;

        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'room_ids' => ['required', 'array', 'min:1'],
            'room_ids.*' => [
                'required',
                'string',
                Rule::exists('rooms', 'id')->where('kingdom_hall_id', $kingdomHallId),
            ],
            'recurrence' => ['nullable', 'array'],
            'recurrence.frequency' => ['required_with:recurrence', Rule::enum(RecurrenceFrequency::class)],
            'recurrence.end_date' => ['nullable', 'date', 'after:starts_at'],
            'recurrence.end_count' => ['nullable', 'integer', 'min:1', 'max:365'],
        ])->validate();

        // Validate 15-minute alignment
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);

        if ($startsAt->minute % 15 !== 0 || $startsAt->second !== 0) {
            throw ValidationException::withMessages([
                'starts_at' => ['The start time must be aligned to a 15-minute interval.'],
            ]);
        }

        if ($endsAt->minute % 15 !== 0 || $endsAt->second !== 0) {
            throw ValidationException::withMessages([
                'ends_at' => ['The end time must be aligned to a 15-minute interval.'],
            ]);
        }
    }

    /**
     * Generate occurrence date pairs based on recurrence rules.
     *
     * @param  array{frequency: string, end_date?: string|null, end_count?: int|null}|null  $recurrence
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function generateOccurrenceDates(Carbon $startsAt, Carbon $endsAt, ?array $recurrence): array
    {
        if (empty($recurrence)) {
            return [[$startsAt, $endsAt]];
        }

        $frequency = RecurrenceFrequency::from($recurrence['frequency']);
        $endDate = isset($recurrence['end_date']) ? Carbon::parse($recurrence['end_date'])->setTimezone('Europe/Stockholm') : null;
        $endCount = $recurrence['end_count'] ?? null;
        $duration = $startsAt->diffInMinutes($endsAt);

        $occurrences = [];
        $currentStart = $startsAt->copy();
        $maxOccurrences = 365;

        while (count($occurrences) < $maxOccurrences) {
            $currentEnd = $currentStart->copy()->addMinutes($duration);

            // Check end conditions
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
     *
     * @throws ValidationException
     */
    private function detectConflicts(array $occurrenceDates, array $roomIds): void
    {
        $conflicts = [];

        foreach ($occurrenceDates as [$start, $end]) {
            $hasConflict = Booking::query()
                ->whereHas('rooms', function ($query) use ($roomIds) {
                    $query->whereIn('rooms.id', $roomIds);
                })
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->exists();

            if ($hasConflict) {
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
