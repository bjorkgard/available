<?php

// Feature: booking-system, Property 3: Recurrence occurrence count limit

use App\Actions\Bookings\CreateBooking;
use App\Enums\CongregationRole;
use App\Enums\RecurrenceFrequency;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// **Validates: Requirements 2.6**
test('recurrence generates at most 365 occurrences regardless of frequency and end conditions', function () {
    Event::fake();

    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $room = Room::factory()->create(['kingdom_hall_id' => $kingdomHall->id]);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($user, ['role' => CongregationRole::Member->value]);

    $frequency = fake()->randomElement(RecurrenceFrequency::cases());

    // Generate end conditions that could potentially exceed 365 occurrences
    $endConditionType = fake()->randomElement(['end_date', 'end_count', 'neither']);

    $recurrence = ['frequency' => $frequency->value];

    match ($endConditionType) {
        'end_date' => $recurrence['end_date'] = now()->addYears(fake()->numberBetween(2, 10))->toDateString(),
        'end_count' => $recurrence['end_count'] = fake()->numberBetween(1, 365),
        'neither' => null, // No end condition — relies on the 365 hard limit
    };

    // Use 15-minute aligned times
    $hour = fake()->numberBetween(6, 20);
    $minute = fake()->randomElement([0, 15, 30, 45]);
    $startsAt = now()->setTime($hour, $minute, 0)->format('Y-m-d H:i:s');
    $endsAt = now()->setTime($hour, $minute, 0)->addMinutes(fake()->randomElement([15, 30, 45, 60]))->format('Y-m-d H:i:s');

    $data = [
        'name' => fake()->words(3, true),
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'room_ids' => [$room->id],
        'recurrence' => $recurrence,
    ];

    $action = app(CreateBooking::class);
    $bookings = $action->handle($user, $congregation, $data);

    $context = "Frequency [{$frequency->value}], End condition [{$endConditionType}]"
        .($endConditionType === 'end_date' ? ", End date [{$recurrence['end_date']}]" : '')
        .($endConditionType === 'end_count' ? ", End count [{$recurrence['end_count']}]" : '');

    expect($bookings->count())->toBeLessThanOrEqual(365, "Generated more than 365 occurrences: {$context}")
        ->and($bookings->count())->toBeGreaterThan(0, "Should generate at least 1 occurrence: {$context}");
})->repeat(30);
