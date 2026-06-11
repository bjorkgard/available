<?php

use App\Enums\CongregationRole;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    $this->kingdomHall = KingdomHall::factory()->create();
    $this->congregation = Congregation::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
    ]);
    $this->room = Room::factory()->create([
        'kingdom_hall_id' => $this->kingdomHall->id,
    ]);
    $this->user = User::factory()->create([
        'current_congregation_id' => $this->congregation->id,
    ]);
    Membership::create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'role' => CongregationRole::Member,
    ]);
});

// --- Recurrence generation tests ---

test('daily recurrence generates correct number of occurrences', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/{$this->congregation->slug}/bookings", [
            'name' => 'Daily Standup',
            'starts_at' => '2025-06-01 09:00:00',
            'ends_at' => '2025-06-01 09:30:00',
            'room_ids' => [$this->room->id],
            'recurrence' => [
                'frequency' => 'daily',
                'end_count' => 5,
            ],
        ]);

    $response->assertStatus(201);

    $data = $response->json('data');
    expect($data)->toHaveCount(5);

    // Verify dates are daily
    expect($data[0]['starts_at'])->toContain('2025-06-01');
    expect($data[1]['starts_at'])->toContain('2025-06-02');
    expect($data[2]['starts_at'])->toContain('2025-06-03');
    expect($data[3]['starts_at'])->toContain('2025-06-04');
    expect($data[4]['starts_at'])->toContain('2025-06-05');
});

test('weekly recurrence generates correct number of occurrences', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/{$this->congregation->slug}/bookings", [
            'name' => 'Weekly Meeting',
            'starts_at' => '2025-06-02 10:00:00',
            'ends_at' => '2025-06-02 11:00:00',
            'room_ids' => [$this->room->id],
            'recurrence' => [
                'frequency' => 'weekly',
                'end_count' => 4,
            ],
        ]);

    $response->assertStatus(201);

    $data = $response->json('data');
    expect($data)->toHaveCount(4);

    // Verify dates are weekly (7 days apart)
    expect($data[0]['starts_at'])->toContain('2025-06-02');
    expect($data[1]['starts_at'])->toContain('2025-06-09');
    expect($data[2]['starts_at'])->toContain('2025-06-16');
    expect($data[3]['starts_at'])->toContain('2025-06-23');
});

test('monthly recurrence generates correct number of occurrences', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/{$this->congregation->slug}/bookings", [
            'name' => 'Monthly Review',
            'starts_at' => '2025-01-15 14:00:00',
            'ends_at' => '2025-01-15 15:00:00',
            'room_ids' => [$this->room->id],
            'recurrence' => [
                'frequency' => 'monthly',
                'end_count' => 6,
            ],
        ]);

    $response->assertStatus(201);

    $data = $response->json('data');
    expect($data)->toHaveCount(6);

    // Verify dates are monthly
    expect($data[0]['starts_at'])->toContain('2025-01-15');
    expect($data[1]['starts_at'])->toContain('2025-02-15');
    expect($data[2]['starts_at'])->toContain('2025-03-15');
    expect($data[3]['starts_at'])->toContain('2025-04-15');
    expect($data[4]['starts_at'])->toContain('2025-05-15');
    expect($data[5]['starts_at'])->toContain('2025-06-15');
});

test('yearly recurrence generates correct number of occurrences', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/{$this->congregation->slug}/bookings", [
            'name' => 'Annual Conference',
            'starts_at' => '2025-03-01 08:00:00',
            'ends_at' => '2025-03-01 12:00:00',
            'room_ids' => [$this->room->id],
            'recurrence' => [
                'frequency' => 'yearly',
                'end_count' => 3,
            ],
        ]);

    $response->assertStatus(201);

    $data = $response->json('data');
    expect($data)->toHaveCount(3);

    // Verify dates are yearly
    expect($data[0]['starts_at'])->toContain('2025-03-01');
    expect($data[1]['starts_at'])->toContain('2026-03-01');
    expect($data[2]['starts_at'])->toContain('2027-03-01');
});

test('recurrence with end date generates only occurrences within range', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/{$this->congregation->slug}/bookings", [
            'name' => 'Limited Series',
            'starts_at' => '2025-06-01 10:00:00',
            'ends_at' => '2025-06-01 11:00:00',
            'room_ids' => [$this->room->id],
            'recurrence' => [
                'frequency' => 'weekly',
                'end_date' => '2025-06-22',
            ],
        ]);

    $response->assertStatus(201);

    $data = $response->json('data');
    // June 1, 8, 15, 22 — 4 occurrences within end_date
    expect($data)->toHaveCount(4);
});

// --- Edit scope tests ---

test('update with scope this_only changes only that booking', function () {
    // Create a recurring series via the store endpoint
    $storeResponse = $this->actingAs($this->user)
        ->postJson("/{$this->congregation->slug}/bookings", [
            'name' => 'Series Meeting',
            'starts_at' => '2025-07-01 09:00:00',
            'ends_at' => '2025-07-01 10:00:00',
            'room_ids' => [$this->room->id],
            'recurrence' => [
                'frequency' => 'weekly',
                'end_count' => 4,
            ],
        ]);

    $storeResponse->assertStatus(201);
    $bookings = $storeResponse->json('data');
    $secondBookingId = $bookings[1]['id'];

    // Update only the second occurrence
    $updateResponse = $this->actingAs($this->user)
        ->putJson("/{$this->congregation->slug}/bookings/{$secondBookingId}", [
            'name' => 'Renamed Occurrence',
            'scope' => 'this_only',
        ]);

    $updateResponse->assertStatus(200);

    // Verify the second booking was updated
    $updatedBooking = Booking::find($secondBookingId);
    expect($updatedBooking->name)->toBe('Renamed Occurrence');
    expect($updatedBooking->is_exception)->toBeTrue();

    // Verify other bookings remain unchanged
    $firstBooking = Booking::find($bookings[0]['id']);
    expect($firstBooking->name)->toBe('Series Meeting');
    expect($firstBooking->is_exception)->toBeFalse();

    $thirdBooking = Booking::find($bookings[2]['id']);
    expect($thirdBooking->name)->toBe('Series Meeting');
    expect($thirdBooking->is_exception)->toBeFalse();
});

test('update with scope this_and_future splits the recurrence pattern', function () {
    // Create a recurring series
    $storeResponse = $this->actingAs($this->user)
        ->postJson("/{$this->congregation->slug}/bookings", [
            'name' => 'Original Series',
            'starts_at' => '2025-07-01 09:00:00',
            'ends_at' => '2025-07-01 10:00:00',
            'room_ids' => [$this->room->id],
            'recurrence' => [
                'frequency' => 'weekly',
                'end_count' => 5,
            ],
        ]);

    $storeResponse->assertStatus(201);
    $bookings = $storeResponse->json('data');
    $originalPatternId = $bookings[0]['recurrence_pattern_id'];
    $thirdBookingId = $bookings[2]['id'];

    // Update "this and future" starting from the 3rd occurrence
    $updateResponse = $this->actingAs($this->user)
        ->putJson("/{$this->congregation->slug}/bookings/{$thirdBookingId}", [
            'name' => 'New Series Name',
            'starts_at' => '2025-07-15 10:00:00',
            'ends_at' => '2025-07-15 11:00:00',
            'scope' => 'this_and_future',
        ]);

    $updateResponse->assertStatus(200);

    // The original pattern should still have the first 2 bookings
    $remainingOriginal = Booking::where('recurrence_pattern_id', $originalPatternId)->get();
    expect($remainingOriginal)->toHaveCount(2);
    expect($remainingOriginal->every(fn ($b) => $b->name === 'Original Series'))->toBeTrue();

    // A new pattern should be created for the future bookings
    $newBookings = $updateResponse->json('data');
    expect($newBookings)->not->toBeEmpty();
    expect($newBookings[0]['name'])->toBe('New Series Name');

    // The new pattern should be different from the original
    $newPatternId = $newBookings[0]['recurrence_pattern_id'];
    expect($newPatternId)->not->toBe($originalPatternId);
});

test('this_and_future discards existing exceptions on future dates', function () {
    // Create a recurring series
    $storeResponse = $this->actingAs($this->user)
        ->postJson("/{$this->congregation->slug}/bookings", [
            'name' => 'Weekly Event',
            'starts_at' => '2025-08-04 09:00:00',
            'ends_at' => '2025-08-04 10:00:00',
            'room_ids' => [$this->room->id],
            'recurrence' => [
                'frequency' => 'weekly',
                'end_count' => 5,
            ],
        ]);

    $storeResponse->assertStatus(201);
    $bookings = $storeResponse->json('data');

    // First, create an exception on the 4th occurrence
    $fourthId = $bookings[3]['id'];
    $this->actingAs($this->user)
        ->putJson("/{$this->congregation->slug}/bookings/{$fourthId}", [
            'name' => 'Exception Name',
            'scope' => 'this_only',
        ]);

    // Now edit "this and future" from the 3rd occurrence
    $thirdId = $bookings[2]['id'];
    $updateResponse = $this->actingAs($this->user)
        ->putJson("/{$this->congregation->slug}/bookings/{$thirdId}", [
            'name' => 'Fresh Start',
            'starts_at' => '2025-08-18 09:00:00',
            'ends_at' => '2025-08-18 10:00:00',
            'scope' => 'this_and_future',
        ]);

    $updateResponse->assertStatus(200);

    // The exception on the 4th occurrence should be gone — regenerated from new pattern
    $newBookings = $updateResponse->json('data');
    $allNames = collect($newBookings)->pluck('name')->unique()->all();
    expect($allNames)->toBe(['Fresh Start']);
});

// --- Conflict rejection tests ---

test('recurring booking creation rejected when occurrence conflicts with existing booking', function () {
    // Create an existing standalone booking on what would be the 3rd week
    $existing = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-09-15 09:00:00',
        'ends_at' => '2025-09-15 10:00:00',
    ]);
    $existing->rooms()->attach($this->room->id);

    // Attempt to create a weekly recurring that would conflict on the 3rd occurrence
    $response = $this->actingAs($this->user)
        ->postJson("/{$this->congregation->slug}/bookings", [
            'name' => 'Conflicting Weekly',
            'starts_at' => '2025-09-01 09:00:00',
            'ends_at' => '2025-09-01 10:00:00',
            'room_ids' => [$this->room->id],
            'recurrence' => [
                'frequency' => 'weekly',
                'end_count' => 4,
            ],
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('conflicts');

    // Verify no bookings were created (entire creation rejected)
    expect(Booking::where('name', 'Conflicting Weekly')->count())->toBe(0);
});

test('edit with scope this_only rejected on conflict', function () {
    // Create a recurring series
    $storeResponse = $this->actingAs($this->user)
        ->postJson("/{$this->congregation->slug}/bookings", [
            'name' => 'Moveable Meeting',
            'starts_at' => '2025-10-06 09:00:00',
            'ends_at' => '2025-10-06 10:00:00',
            'room_ids' => [$this->room->id],
            'recurrence' => [
                'frequency' => 'weekly',
                'end_count' => 3,
            ],
        ]);

    $storeResponse->assertStatus(201);
    $bookings = $storeResponse->json('data');

    // Create a conflicting standalone booking at the target time
    $blocker = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-10-10 14:00:00',
        'ends_at' => '2025-10-10 15:00:00',
    ]);
    $blocker->rooms()->attach($this->room->id);

    // Try to move the first occurrence to the conflicting time
    $firstId = $bookings[0]['id'];
    $response = $this->actingAs($this->user)
        ->putJson("/{$this->congregation->slug}/bookings/{$firstId}", [
            'starts_at' => '2025-10-10 14:00:00',
            'ends_at' => '2025-10-10 15:00:00',
            'scope' => 'this_only',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('conflicts');
});

test('edit with scope this_and_future rejected on conflict', function () {
    // Create a recurring series
    $storeResponse = $this->actingAs($this->user)
        ->postJson("/{$this->congregation->slug}/bookings", [
            'name' => 'Future Conflict',
            'starts_at' => '2025-11-03 09:00:00',
            'ends_at' => '2025-11-03 10:00:00',
            'room_ids' => [$this->room->id],
            'recurrence' => [
                'frequency' => 'weekly',
                'end_count' => 4,
            ],
        ]);

    $storeResponse->assertStatus(201);
    $bookings = $storeResponse->json('data');

    // Create a conflicting booking at a future regenerated date
    $blocker = Booking::factory()->create([
        'congregation_id' => $this->congregation->id,
        'user_id' => $this->user->id,
        'starts_at' => '2025-11-17 14:00:00',
        'ends_at' => '2025-11-17 15:00:00',
    ]);
    $blocker->rooms()->attach($this->room->id);

    // Try to move the 2nd occurrence and all future to conflicting time
    $secondId = $bookings[1]['id'];
    $response = $this->actingAs($this->user)
        ->putJson("/{$this->congregation->slug}/bookings/{$secondId}", [
            'starts_at' => '2025-11-10 14:00:00',
            'ends_at' => '2025-11-10 15:00:00',
            'scope' => 'this_and_future',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('conflicts');
});
