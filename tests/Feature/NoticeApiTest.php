<?php

use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the public index lists only published notices', function () {
    Notice::factory()->create(['title' => 'Published notice']);
    Notice::factory()->draft()->create(['title' => 'Draft notice']);

    $this->getJson('/api/notices')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Published notice');
});

test('published notices are ordered newest first', function () {
    Notice::factory()->create(['title' => 'Older', 'published_at' => now()->subDay()]);
    Notice::factory()->create(['title' => 'Newer', 'published_at' => now()]);

    $this->getJson('/api/notices')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Newer')
        ->assertJsonPath('data.1.title', 'Older');
});

test('a published notice is shown with its details', function () {
    $notice = Notice::factory()->create([
        'title' => 'Exam schedule',
        'body' => 'The final exam is on Monday.',
    ]);

    $this->getJson("/api/notices/{$notice->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $notice->id)
        ->assertJsonPath('data.title', 'Exam schedule')
        ->assertJsonPath('data.body', 'The final exam is on Monday.')
        ->assertJsonStructure(['data' => ['id', 'title', 'slug', 'body', 'is_published', 'published_at']]);
});

test('a draft notice returns 404 from the public endpoint', function () {
    $notice = Notice::factory()->draft()->create();

    $this->getJson("/api/notices/{$notice->id}")->assertNotFound();
});
