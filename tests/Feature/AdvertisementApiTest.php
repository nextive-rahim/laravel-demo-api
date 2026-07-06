<?php

use App\Enums\AdPlacement;
use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('the public index lists only active ads', function () {
    Advertisement::factory()->create(['title' => 'Live ad']);
    Advertisement::factory()->inactive()->create(['title' => 'Off ad']);

    $this->getJson('/api/advertisements')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Live ad');
});

test('the public index can filter by placement', function () {
    Advertisement::factory()->placement(AdPlacement::Banner)->create(['title' => 'Banner ad']);
    Advertisement::factory()->placement(AdPlacement::Popup)->create(['title' => 'Popup ad']);

    $this->getJson('/api/advertisements?placement=banner')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.placement', 'banner');
});

test('scheduled ads outside their window are hidden', function () {
    Advertisement::factory()->create(['title' => 'Future', 'starts_at' => now()->addDay()]);
    Advertisement::factory()->create(['title' => 'Expired', 'ends_at' => now()->subDay()]);
    Advertisement::factory()->create(['title' => 'Now', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);

    $this->getJson('/api/advertisements')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Now');
});

test('an invalid placement filter is rejected', function () {
    $this->getJson('/api/advertisements?placement=bogus')->assertUnprocessable();
});

test('an admin can create an advertisement', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/advertisements', [
        'placement' => 'banner',
        'title' => 'Big Sale',
        'description' => 'Everything 20% off',
        'link_url' => 'https://example.com/sale',
        'is_active' => true,
    ])->assertCreated()->assertJsonPath('data.title', 'Big Sale');

    $this->assertDatabaseHas('advertisements', ['title' => 'Big Sale', 'placement' => 'banner']);
});

test('creating an ad requires a placement and title', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/advertisements', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['placement', 'title']);
});

test('the link url must be a valid url and end date after start', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/advertisements', [
        'placement' => 'banner',
        'title' => 'X',
        'link_url' => 'not-a-url',
        'starts_at' => '2026-07-10',
        'ends_at' => '2026-07-01',
    ])->assertUnprocessable()->assertJsonValidationErrors(['link_url', 'ends_at']);
});

test('an admin can update and delete an advertisement', function () {
    actingAsAdmin();
    $ad = Advertisement::factory()->create();

    $this->putJson("/api/admin/advertisements/{$ad->id}", ['title' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.title', 'Renamed');

    $this->deleteJson("/api/admin/advertisements/{$ad->id}")->assertOk();
    $this->assertDatabaseMissing('advertisements', ['id' => $ad->id]);
});

test('a non-admin cannot manage advertisements', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->postJson('/api/admin/advertisements', ['placement' => 'banner', 'title' => 'X'])->assertForbidden();
});
