<?php

use App\Models\LiveCourse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('the public index lists only published live courses', function () {
    LiveCourse::factory()->create(['title' => 'Live now']);
    LiveCourse::factory()->draft()->create(['title' => 'Hidden']);

    $this->getJson('/api/live-courses')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Live now');
});

test('a draft live course returns 404', function () {
    $live = LiveCourse::factory()->draft()->create();
    $this->getJson("/api/live-courses/{$live->id}")->assertNotFound();
});

test('an admin can create a live course', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/live-courses', [
        'title' => 'HSC Physics Live',
        'instructor_name' => 'Sadman',
        'scheduled_at' => '2026-08-01T19:00:00',
        'join_url' => 'https://meet.example.com/abc',
        'is_published' => true,
    ])->assertCreated()->assertJsonPath('data.title', 'HSC Physics Live');

    $this->assertDatabaseHas('live_courses', ['title' => 'HSC Physics Live']);
});

test('creating a live course requires a title and valid join url', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/live-courses', ['join_url' => 'nope'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'join_url']);
});

test('an admin can update and delete a live course', function () {
    actingAsAdmin();
    $live = LiveCourse::factory()->create();

    $this->putJson("/api/admin/live-courses/{$live->id}", ['title' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.title', 'Renamed');
    $this->deleteJson("/api/admin/live-courses/{$live->id}")->assertOk();
    $this->assertDatabaseMissing('live_courses', ['id' => $live->id]);
});

test('a non-admin cannot manage live courses', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));
    $this->postJson('/api/admin/live-courses', ['title' => 'X'])->assertForbidden();
});
