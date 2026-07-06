<?php

use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an admin can list every notice including drafts', function () {
    actingAsAdmin();
    Notice::factory()->create();
    Notice::factory()->draft()->create();

    $this->getJson('/api/admin/notices')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('an admin can create a notice', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/notices', [
        'title' => 'New Notice',
        'body' => 'Something important.',
        'is_published' => true,
    ])->assertCreated()
        ->assertJsonPath('data.title', 'New Notice')
        ->assertJsonPath('data.slug', 'new-notice')
        ->assertJsonPath('data.is_published', true);

    $this->assertDatabaseHas('notices', ['title' => 'New Notice', 'slug' => 'new-notice']);
});

test('publishing a notice stamps published_at', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/notices', [
        'title' => 'Live',
        'body' => 'Body',
        'is_published' => true,
    ])->assertCreated();

    $notice = Notice::first();
    expect($notice->published_at)->not->toBeNull();
});

test('a draft notice has no published_at until it is published', function () {
    actingAsAdmin();
    $notice = Notice::factory()->draft()->create();

    expect($notice->published_at)->toBeNull();

    $this->putJson("/api/admin/notices/{$notice->id}", ['is_published' => true])
        ->assertOk()
        ->assertJsonPath('data.is_published', true);

    expect($notice->fresh()->published_at)->not->toBeNull();
});

test('the slug is regenerated and kept unique when the title changes', function () {
    actingAsAdmin();
    Notice::factory()->create(['title' => 'Holiday', 'slug' => 'holiday']);
    $notice = Notice::factory()->create(['title' => 'Other', 'slug' => 'other']);

    $this->putJson("/api/admin/notices/{$notice->id}", ['title' => 'Holiday'])
        ->assertOk()
        ->assertJsonPath('data.slug', 'holiday-2');
});

test('creating a notice requires a title and body', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/notices', ['is_published' => true])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'body']);
});

test('an admin can update and delete a notice', function () {
    actingAsAdmin();
    $notice = Notice::factory()->create();

    $this->putJson("/api/admin/notices/{$notice->id}", ['title' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.title', 'Renamed');

    $this->deleteJson("/api/admin/notices/{$notice->id}")->assertOk();
    $this->assertDatabaseMissing('notices', ['id' => $notice->id]);
});

test('a non-admin cannot manage notices', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->postJson('/api/admin/notices', ['title' => 'X', 'body' => 'Y'])->assertForbidden();
});

test('guests cannot manage notices', function () {
    $this->postJson('/api/admin/notices', ['title' => 'X', 'body' => 'Y'])->assertUnauthorized();
});

test('an admin can upload a notice image and it returns a path and url', function () {
    Storage::fake(config('filesystems.uploads'));
    actingAsAdmin();

    $response = $this->postJson('/api/admin/uploads/image', [
        'file' => UploadedFile::fake()->image('banner.jpg', 800, 400),
    ])->assertCreated()->assertJsonStructure(['path', 'url']);

    Storage::disk(config('filesystems.uploads'))->assertExists($response->json('path'));
});

test('the image upload rejects non-image files', function () {
    Storage::fake(config('filesystems.uploads'));
    actingAsAdmin();

    $this->postJson('/api/admin/uploads/image', [
        'file' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
    ])->assertUnprocessable()->assertJsonValidationErrorFor('file');
});

test('a notice stores its image path and exposes an image url', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/notices', [
        'title' => 'With image',
        'body' => 'Body',
        'image_path' => 'notice-images/banner.jpg',
        'is_published' => true,
    ])->assertCreated()
        ->assertJsonPath('data.image_url', fn ($url) => is_string($url) && str_contains($url, 'notice-images/banner.jpg'));

    $this->assertDatabaseHas('notices', ['title' => 'With image', 'image_path' => 'notice-images/banner.jpg']);
});

test('a notice with no image exposes a null image url', function () {
    $notice = Notice::factory()->create(['image_path' => null]);

    $this->getJson("/api/notices/{$notice->id}")
        ->assertOk()
        ->assertJsonPath('data.image_url', null);
});
