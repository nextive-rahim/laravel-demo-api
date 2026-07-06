<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('the public index lists only published posts', function () {
    Post::factory()->create(['title' => 'Live post']);
    Post::factory()->draft()->create(['title' => 'Draft post']);

    $this->getJson('/api/posts')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Live post');
});

test('the public index can filter by type', function () {
    Post::factory()->blog()->create(['title' => 'A blog']);
    Post::factory()->news()->create(['title' => 'A news']);

    $this->getJson('/api/posts?type=news')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'news');
});

test('an invalid type filter is rejected', function () {
    $this->getJson('/api/posts?type=bogus')->assertUnprocessable();
});

test('a published post is shown with its body', function () {
    $post = Post::factory()->create(['title' => 'Hello', 'body' => 'World body']);

    $this->getJson("/api/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Hello')
        ->assertJsonPath('data.body', 'World body')
        ->assertJsonStructure(['data' => ['id', 'type', 'title', 'slug', 'excerpt', 'body', 'image_url', 'published_at']]);
});

test('a draft post returns 404 from the public endpoint', function () {
    $post = Post::factory()->draft()->create();

    $this->getJson("/api/posts/{$post->id}")->assertNotFound();
});

test('an admin can create a post and it gets a slug and published_at', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/posts', [
        'type' => 'news',
        'title' => 'Big Announcement',
        'excerpt' => 'Short teaser',
        'body' => 'Full article body.',
        'is_published' => true,
    ])->assertCreated()
        ->assertJsonPath('data.type', 'news')
        ->assertJsonPath('data.slug', 'big-announcement');

    $post = Post::first();
    expect($post->published_at)->not->toBeNull();
});

test('post slugs stay unique', function () {
    actingAsAdmin();
    Post::factory()->create(['title' => 'Same', 'slug' => 'same']);

    $this->postJson('/api/admin/posts', ['type' => 'blog', 'title' => 'Same', 'body' => 'x'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'same-2');
});

test('creating a post requires type, title and body', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/posts', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'title', 'body']);
});

test('an admin can update and delete a post', function () {
    actingAsAdmin();
    $post = Post::factory()->create();

    $this->putJson("/api/admin/posts/{$post->id}", ['title' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.title', 'Renamed')->assertJsonPath('data.slug', 'renamed');

    $this->deleteJson("/api/admin/posts/{$post->id}")->assertOk();
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});

test('a non-admin cannot manage posts', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->postJson('/api/admin/posts', ['type' => 'blog', 'title' => 'X', 'body' => 'Y'])->assertForbidden();
});
