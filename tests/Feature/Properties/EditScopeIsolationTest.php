<?php

// Feature: booking-system, Property 5: Edit scope isolation — "this occurrence only"

use App\Actions\Bookings\CreateBooking;
use App\Actions\Bookings\UpdateBooking;
use App\Enums\CongregationRole;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\RecurrencePattern;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// **Validates: Requirements 8.2**
test('editing a single occurrence leaves parent pattern and other occurrences unchanged', function () {
    Event::fake();

    // Set up kingdom hall, congregation, rooms, and user
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->create([
        'kingdom_hall_id' => $kingdomHall->id,
    ]);

    $roomCount = fake()->numberBetween(1, 3);
    $rooms = Room::factory()->count($roomCount)->create([
        'kingdom_hall_id' => $kingdomHall->id,
    ]);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($user, ['role' => CongregationRole::Member->value]);

    // Create a recurring booking series with 4 weekly occurrences
    $createAction = app(CreateBooking::class);

    // Pick a random weekday and time for the base booking
    $hour = fake()->numberBetween(8, 18);
    $minute = fake()->randomElement([0, 15, 30, 45]);
    $durationMinutes = fake()->randomElement([30, 45, 60, 90, 120]);

    $baseDate = fake()->dateTimeBetween('+1 day', '+14 days');
    $baseDate->setTime($hour, $minute, 0);

    $startsAt = clone $baseDate;
    $endsAt = (clone $startsAt)->modify("+{$durationMinutes} minutes");

    $selectedRoomIds = $rooms->random(fake()->numberBetween(1, $roomCount))->pluck('id')->all();

    $originalName = fake()->words(fake()->numberBetween(2, 4), true);

    $data = [
        'name' => $originalName,
        'starts_at' => $startsAt->format('Y-m-d H:i:s'),
        'ends_at' => $endsAt->format('Y-m-d H:i:s'),
        'room_ids' => $selectedRoomIds,
        'recurrence' => [
            'frequency' => 'weekly',
            'end_count' => 4,
        ],
    ];

    $createdBookings = $createAction->handle($user, $congregation, $data);

    expect($createdBookings)->toHaveCount(4);

    // Snapshot the parent recurrence pattern before the edit
    $patternId = $createdBookings->first()->recurrence_pattern_id;
    $patternBefore = RecurrencePattern::find($patternId);
    $patternFrequencyBefore = $patternBefore->frequency;
    $patternEndDateBefore = $patternBefore->end_date;
    $patternEndCountBefore = $patternBefore->end_count;

    // Snapshot ALL bookings before the edit
    $bookingsBefore = Booking::where('recurrence_pattern_id', $patternId)
        ->with('rooms')
        ->orderBy('starts_at')
        ->get()
        ->map(fn (Booking $b) => [
            'id' => $b->id,
            'name' => $b->name,
            'starts_at' => $b->starts_at->toDateTimeString(),
            'ends_at' => $b->ends_at->toDateTimeString(),
            'room_ids' => $b->rooms->pluck('id')->sort()->values()->all(),
            'is_exception' => $b->is_exception,
            'original_starts_at' => $b->original_starts_at?->toDateTimeString(),
        ])
        ->all();

    // Pick a random occurrence to edit (index 0-3)
    $editIndex = fake()->numberBetween(0, 3);
    $bookingToEdit = Booking::find($bookingsBefore[$editIndex]['id']);

    // Generate a new name for the edit
    $newName = fake()->words(fake()->numberBetween(2, 4), true);

    // Ensure the new name is different
    while ($newName === $originalName) {
        $newName = fake()->words(fake()->numberBetween(2, 4), true);
    }

    // Perform the "this_only" edit
    $updateAction = app(UpdateBooking::class);
    $updateAction->handle($user, $bookingToEdit, [
        'name' => $newName,
        'scope' => 'this_only',
    ]);

    // PROPERTY ASSERTIONS

    // 1. The edited occurrence is marked as is_exception = true
    $editedBooking = Booking::find($bookingToEdit->id);
    expect($editedBooking->is_exception)->toBeTrue(
        'The edited occurrence should be marked as is_exception = true'
    );

    // 2. The edited occurrence has original_starts_at set
    expect($editedBooking->original_starts_at)->not->toBeNull(
        'The edited occurrence should have original_starts_at set'
    );

    // 3. The edited occurrence has the new name
    expect($editedBooking->name)->toBe($newName);

    // 4. All OTHER occurrences remain unchanged (same name, time, rooms)
    $otherBookings = Booking::where('recurrence_pattern_id', $patternId)
        ->where('id', '!=', $bookingToEdit->id)
        ->with('rooms')
        ->orderBy('starts_at')
        ->get();

    $othersBefore = collect($bookingsBefore)->filter(fn ($b) => $b['id'] !== $bookingToEdit->id)->values();

    expect($otherBookings)->toHaveCount($othersBefore->count());

    foreach ($otherBookings as $index => $otherBooking) {
        $before = $othersBefore[$index];

        expect($otherBooking->name)->toBe($before['name'],
            "Other occurrence [{$otherBooking->id}] name should be unchanged"
        );
        expect($otherBooking->starts_at->toDateTimeString())->toBe($before['starts_at'],
            "Other occurrence [{$otherBooking->id}] starts_at should be unchanged"
        );
        expect($otherBooking->ends_at->toDateTimeString())->toBe($before['ends_at'],
            "Other occurrence [{$otherBooking->id}] ends_at should be unchanged"
        );
        expect($otherBooking->rooms->pluck('id')->sort()->values()->all())->toBe($before['room_ids'],
            "Other occurrence [{$otherBooking->id}] rooms should be unchanged"
        );
    }

    // 5. The parent RecurrencePattern is unchanged
    $patternAfter = RecurrencePattern::find($patternId);
    expect($patternAfter)->not->toBeNull('Parent RecurrencePattern should still exist');
    expect($patternAfter->frequency)->toBe($patternFrequencyBefore);
    expect($patternAfter->end_date?->toDateString())->toBe($patternEndDateBefore?->toDateString());
    expect($patternAfter->end_count)->toBe($patternEndCountBefore);

    // 6. The number of bookings in the series is unchanged
    $totalBookingsAfter = Booking::where('recurrence_pattern_id', $patternId)->count();
    expect($totalBookingsAfter)->toBe(4, 'Total bookings in series should remain 4');
})->repeat(30);
