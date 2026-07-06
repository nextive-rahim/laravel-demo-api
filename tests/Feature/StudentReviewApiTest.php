<?php

use App\Models\StudentReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('the public index lists only published reviews', function () {
    StudentReview::factory()->create(['name' => 'Visible']);
    StudentReview::factory()->draft()->create(['name' => 'Hidden']);

    $this->getJson('/api/student-reviews')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Visible');
});

test('a published review exposes its fields and image url', function () {
    $review = StudentReview::factory()->create([
        'name' => 'Ayesha',
        'institute' => 'Dhaka College',
        'roll' => '1234',
        'batch' => '2025',
        'image_path' => 'student-reviews/a.jpg',
        'video_url' => 'https://www.youtube.com/watch?v=abc',
    ]);

    $this->getJson("/api/student-reviews/{$review->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Ayesha')
        ->assertJsonPath('data.institute', 'Dhaka College')
        ->assertJsonPath('data.roll', '1234')
        ->assertJsonPath('data.batch', '2025')
        ->assertJsonPath('data.video_url', 'https://www.youtube.com/watch?v=abc')
        ->assertJsonPath('data.image_url', fn ($url) => str_contains($url, 'student-reviews/a.jpg'));
});

test('a draft review returns 404 from the public endpoint', function () {
    $review = StudentReview::factory()->draft()->create();

    $this->getJson("/api/student-reviews/{$review->id}")->assertNotFound();
});

test('an admin can create a student review', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/student-reviews', [
        'name' => 'Tanvir',
        'institute' => 'BUET',
        'roll' => '4321',
        'batch' => '2024',
        'review' => 'Great platform!',
        'video_url' => 'https://www.youtube.com/watch?v=xyz',
        'is_published' => true,
    ])->assertCreated()->assertJsonPath('data.name', 'Tanvir');

    $this->assertDatabaseHas('student_reviews', ['name' => 'Tanvir', 'institute' => 'BUET']);
});

test('creating a review requires a name and institute', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/student-reviews', ['roll' => '1'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'institute']);
});

test('the video url must be a valid url', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/student-reviews', [
        'name' => 'X',
        'institute' => 'Y',
        'video_url' => 'not-a-url',
    ])->assertUnprocessable()->assertJsonValidationErrorFor('video_url');
});

test('an admin can update and delete a review', function () {
    actingAsAdmin();
    $review = StudentReview::factory()->create();

    $this->putJson("/api/admin/student-reviews/{$review->id}", ['name' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.name', 'Renamed');

    $this->deleteJson("/api/admin/student-reviews/{$review->id}")->assertOk();
    $this->assertDatabaseMissing('student_reviews', ['id' => $review->id]);
});

test('a non-admin cannot manage reviews', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->postJson('/api/admin/student-reviews', ['name' => 'X', 'institute' => 'Y'])->assertForbidden();
});
