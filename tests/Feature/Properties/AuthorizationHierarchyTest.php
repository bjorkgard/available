<?php

// Feature: booking-system, Property 4: Authorization hierarchy enforcement

use App\Enums\CongregationRole;
use App\Models\Booking;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// **Validates: Requirements 6.1, 6.2, 6.3, 6.4, 7.1, 7.2, 7.3, 7.4, 7.5**
test('authorization hierarchy enforces correct edit/delete permissions based on role and ownership', function () {
    $roles = [CongregationRole::Superadmin, CongregationRole::Admin, CongregationRole::Member];

    $actingRole = fake()->randomElement($roles);
    $isOwner = fake()->boolean();
    $isSameCongregation = fake()->boolean();

    // Create Kingdom Hall with two congregations
    $kingdomHall = KingdomHall::factory()->create();
    $congregationA = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $congregationB = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    // Create the booking owner
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $bookingCongregation = fake()->randomElement([$congregationA, $congregationB]);
    $bookingCongregation->members()->attach($owner, ['role' => CongregationRole::Member->value]);

    // Create the booking
    $booking = Booking::factory()->create([
        'congregation_id' => $bookingCongregation->id,
        'user_id' => $owner->id,
    ]);

    // Create the acting user
    if ($isOwner) {
        $actingUser = $owner;
        // Ensure the owner has the acting role in the booking's congregation
        $bookingCongregation->members()->updateExistingPivot($owner, ['role' => $actingRole->value]);
    } else {
        $actingUser = User::factory()->create(['email_verified_at' => now()]);

        // Determine which congregation the acting user belongs to
        $actingCongregation = $isSameCongregation ? $bookingCongregation : (
            $bookingCongregation->id === $congregationA->id ? $congregationB : $congregationA
        );

        $actingCongregation->members()->attach($actingUser, ['role' => $actingRole->value]);
    }

    // Evaluate the policy
    $canUpdate = $actingUser->can('update', $booking);
    $canDelete = $actingUser->can('delete', $booking);

    // Determine expected permission
    $expectedPermission = false;

    if ($isOwner) {
        // Owner can always edit/delete their own booking
        $expectedPermission = true;
    } elseif ($actingRole === CongregationRole::Admin && $isSameCongregation) {
        // Admin in the booking's congregation can edit/delete
        $expectedPermission = true;
    } elseif ($actingRole === CongregationRole::Superadmin) {
        // Superadmin in any congregation sharing the same Kingdom Hall can edit/delete
        $expectedPermission = true;
    }

    $context = "Role [{$actingRole->value}], Owner [{$isOwner}], Same Congregation [{$isSameCongregation}]";

    expect($canUpdate)->toBe($expectedPermission, "Update permission mismatch: {$context}")
        ->and($canDelete)->toBe($expectedPermission, "Delete permission mismatch: {$context}");
})->repeat(30);

// **Validates: Requirements 6.1, 6.4**
test('member who is not the owner cannot update or delete a booking', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($owner, ['role' => CongregationRole::Member->value]);

    $nonOwner = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($nonOwner, ['role' => CongregationRole::Member->value]);

    $booking = Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $owner->id,
    ]);

    expect($nonOwner->can('update', $booking))->toBeFalse()
        ->and($nonOwner->can('delete', $booking))->toBeFalse();
});

// **Validates: Requirements 6.1, 7.1**
test('member who is the owner can update and delete their own booking', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($owner, ['role' => CongregationRole::Member->value]);

    $booking = Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $owner->id,
    ]);

    expect($owner->can('update', $booking))->toBeTrue()
        ->and($owner->can('delete', $booking))->toBeTrue();
});

// **Validates: Requirements 6.2, 7.3**
test('admin in the booking congregation can update and delete regardless of ownership', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($owner, ['role' => CongregationRole::Member->value]);

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($admin, ['role' => CongregationRole::Admin->value]);

    $booking = Booking::factory()->create([
        'congregation_id' => $congregation->id,
        'user_id' => $owner->id,
    ]);

    expect($admin->can('update', $booking))->toBeTrue()
        ->and($admin->can('delete', $booking))->toBeTrue();
});

// **Validates: Requirements 6.3, 7.4**
test('superadmin in a congregation sharing the same Kingdom Hall can update and delete', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregationA = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $congregationB = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $congregationA->members()->attach($owner, ['role' => CongregationRole::Member->value]);

    $superadmin = User::factory()->create(['email_verified_at' => now()]);
    $congregationB->members()->attach($superadmin, ['role' => CongregationRole::Superadmin->value]);

    $booking = Booking::factory()->create([
        'congregation_id' => $congregationA->id,
        'user_id' => $owner->id,
    ]);

    expect($superadmin->can('update', $booking))->toBeTrue()
        ->and($superadmin->can('delete', $booking))->toBeTrue();
});

// **Validates: Requirements 6.4, 7.5**
test('admin in a different congregation cannot update or delete', function () {
    $kingdomHall = KingdomHall::factory()->create();
    $congregationA = Congregation::factory()->withKingdomHall($kingdomHall)->create();
    $congregationB = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $congregationA->members()->attach($owner, ['role' => CongregationRole::Member->value]);

    $adminOtherCongregation = User::factory()->create(['email_verified_at' => now()]);
    $congregationB->members()->attach($adminOtherCongregation, ['role' => CongregationRole::Admin->value]);

    $booking = Booking::factory()->create([
        'congregation_id' => $congregationA->id,
        'user_id' => $owner->id,
    ]);

    expect($adminOtherCongregation->can('update', $booking))->toBeFalse()
        ->and($adminOtherCongregation->can('delete', $booking))->toBeFalse();
});

// **Validates: Requirements 1.8**
test('any congregation member can create bookings for their congregation', function () {
    $roles = [CongregationRole::Superadmin, CongregationRole::Admin, CongregationRole::Member];
    $role = fake()->randomElement($roles);

    $kingdomHall = KingdomHall::factory()->create();
    $congregation = Congregation::factory()->withKingdomHall($kingdomHall)->create();

    $user = User::factory()->create(['email_verified_at' => now()]);
    $congregation->members()->attach($user, ['role' => $role->value]);

    expect($user->can('create', [Booking::class, $congregation]))->toBeTrue(
        "User with role [{$role->value}] should be able to create bookings"
    );
})->repeat(30);
