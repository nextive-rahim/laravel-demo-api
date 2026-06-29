<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an authenticated user can upload an avatar to s3', function () {
    Storage::fake('s3');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/auth/avatar', [
        'avatar' => UploadedFile::fake()->image('me.jpg'),
    ])->assertOk();

    $path = $user->fresh()->avatar_path;

    expect($path)->not->toBeNull();
    Storage::disk('s3')->assertExists($path);
    $response->assertJsonPath('user.avatar_url', fn ($url) => is_string($url) && str_contains($url, $path));
});

test('uploading a new avatar removes the old one', function () {
    Storage::fake('s3');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/auth/avatar', ['avatar' => UploadedFile::fake()->image('first.jpg')]);
    $first = $user->fresh()->avatar_path;

    $this->postJson('/api/auth/avatar', ['avatar' => UploadedFile::fake()->image('second.jpg')]);
    $second = $user->fresh()->avatar_path;

    expect($second)->not->toBe($first);
    Storage::disk('s3')->assertMissing($first);
    Storage::disk('s3')->assertExists($second);
});

test('the avatar must be an image', function () {
    Storage::fake('s3');
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/auth/avatar', [
        'avatar' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
    ])->assertUnprocessable()->assertJsonValidationErrorFor('avatar');
});

test('a guest cannot upload an avatar', function () {
    $this->postJson('/api/auth/avatar', [
        'avatar' => UploadedFile::fake()->image('me.jpg'),
    ])->assertUnauthorized();
});

test('an admin can upload a pdf to s3', function () {
    Storage::fake('s3');
    Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

    $response = $this->postJson('/api/admin/uploads/pdf', [
        'file' => UploadedFile::fake()->create('lesson.pdf', 200, 'application/pdf'),
    ])->assertCreated()->assertJsonStructure(['path', 'url']);

    Storage::disk('s3')->assertExists($response->json('path'));
});

test('a non-admin cannot upload a pdf', function () {
    Storage::fake('s3');
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->postJson('/api/admin/uploads/pdf', [
        'file' => UploadedFile::fake()->create('lesson.pdf', 200, 'application/pdf'),
    ])->assertForbidden();
});

test('the pdf upload rejects non-pdf files', function () {
    Storage::fake('s3');
    Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

    $this->postJson('/api/admin/uploads/pdf', [
        'file' => UploadedFile::fake()->image('photo.png'),
    ])->assertUnprocessable()->assertJsonValidationErrorFor('file');
});
