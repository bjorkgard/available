<?php

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\CongregationInvitation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('superadmin can delete a congregation', function () {
    $kh = KingdomHall::factory()->create();
    $actingCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $targetCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);

    $superadmin = User::factory()->create(['current_congregation_id' => $actingCongregation->id]);
    Membership::create([
        'congregation_id' => $actingCongregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->delete("/{$actingCongregation->slug}/kingdom-hall/congregations/{$targetCongregation->slug}");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertSoftDeleted('congregations', ['id' => $targetCongregation->id]);
});

test('deletion cascades memberships', function () {
    $kh = KingdomHall::factory()->create();
    $actingCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $targetCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);

    $superadmin = User::factory()->create(['current_congregation_id' => $actingCongregation->id]);
    Membership::create([
        'congregation_id' => $actingCongregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    // Add members to the target congregation
    $member1 = User::factory()->create(['current_congregation_id' => $actingCongregation->id]);
    $member2 = User::factory()->create(['current_congregation_id' => $actingCongregation->id]);

    Membership::create([
        'congregation_id' => $actingCongregation->id,
        'user_id' => $member1->id,
        'role' => CongregationRole::Member,
    ]);
    Membership::create([
        'congregation_id' => $actingCongregation->id,
        'user_id' => $member2->id,
        'role' => CongregationRole::Member,
    ]);

    Membership::create([
        'congregation_id' => $targetCongregation->id,
        'user_id' => $member1->id,
        'role' => CongregationRole::Admin,
    ]);
    Membership::create([
        'congregation_id' => $targetCongregation->id,
        'user_id' => $member2->id,
        'role' => CongregationRole::Member,
    ]);

    $response = $this->actingAs($superadmin)
        ->delete("/{$actingCongregation->slug}/kingdom-hall/congregations/{$targetCongregation->slug}");

    $response->assertRedirect();

    // Memberships in target congregation should be removed
    $this->assertDatabaseMissing('congregation_members', ['congregation_id' => $targetCongregation->id]);

    // Memberships in acting congregation should remain
    $this->assertDatabaseHas('congregation_members', ['congregation_id' => $actingCongregation->id, 'user_id' => $member1->id]);
    $this->assertDatabaseHas('congregation_members', ['congregation_id' => $actingCongregation->id, 'user_id' => $member2->id]);
});

test('deletion cascades invitations', function () {
    $kh = KingdomHall::factory()->create();
    $actingCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $targetCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);

    $superadmin = User::factory()->create(['current_congregation_id' => $actingCongregation->id]);
    Membership::create([
        'congregation_id' => $actingCongregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    // Create invitations for the target congregation
    $invitation1 = CongregationInvitation::factory()->create([
        'congregation_id' => $targetCongregation->id,
        'invited_by' => $superadmin->id,
    ]);
    $invitation2 = CongregationInvitation::factory()->create([
        'congregation_id' => $targetCongregation->id,
        'invited_by' => $superadmin->id,
    ]);

    $response = $this->actingAs($superadmin)
        ->delete("/{$actingCongregation->slug}/kingdom-hall/congregations/{$targetCongregation->slug}");

    $response->assertRedirect();

    // Invitations for target congregation should be removed
    $this->assertDatabaseMissing('congregation_invitations', ['id' => $invitation1->id]);
    $this->assertDatabaseMissing('congregation_invitations', ['id' => $invitation2->id]);
});

test('non-superadmin cannot delete congregation', function () {
    $kh = KingdomHall::factory()->create();
    $actingCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);
    $targetCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kh->id]);

    $admin = User::factory()->create(['current_congregation_id' => $actingCongregation->id]);
    Membership::create([
        'congregation_id' => $actingCongregation->id,
        'user_id' => $admin->id,
        'role' => CongregationRole::Admin,
    ]);

    $response = $this->actingAs($admin)
        ->delete("/{$actingCongregation->slug}/kingdom-hall/congregations/{$targetCongregation->slug}");

    $response->assertForbidden();

    // Target congregation should NOT be soft-deleted
    $this->assertDatabaseHas('congregations', [
        'id' => $targetCongregation->id,
        'deleted_at' => null,
    ]);
});

test('cannot delete congregation from another kingdom hall', function () {
    $kh1 = KingdomHall::factory()->create();
    $kh2 = KingdomHall::factory()->create();

    $actingCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kh1->id]);
    $foreignCongregation = Congregation::factory()->create(['kingdom_hall_id' => $kh2->id]);

    $superadmin = User::factory()->create(['current_congregation_id' => $actingCongregation->id]);
    Membership::create([
        'congregation_id' => $actingCongregation->id,
        'user_id' => $superadmin->id,
        'role' => CongregationRole::Superadmin,
    ]);

    $response = $this->actingAs($superadmin)
        ->delete("/{$actingCongregation->slug}/kingdom-hall/congregations/{$foreignCongregation->slug}");

    $response->assertForbidden();

    // Foreign congregation should NOT be soft-deleted
    $this->assertDatabaseHas('congregations', [
        'id' => $foreignCongregation->id,
        'deleted_at' => null,
    ]);
});
