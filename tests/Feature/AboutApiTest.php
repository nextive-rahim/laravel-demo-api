<?php

use App\Models\About;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('the public about endpoint returns the singleton content', function () {
    About::singleton()->update(['heading' => 'About Us Heading', 'body' => 'Some body']);

    $this->getJson('/api/about')
        ->assertOk()
        ->assertJsonPath('data.heading', 'About Us Heading')
        ->assertJsonPath('data.body', 'Some body');
});

test('the public about endpoint works even before any content is set', function () {
    $this->getJson('/api/about')
        ->assertOk()
        ->assertJsonStructure(['data' => ['heading', 'subheading', 'body', 'mission', 'vision', 'image_url']]);

    // Only ever one row exists.
    expect(About::count())->toBe(1);
});

test('an admin can update the about content', function () {
    actingAsAdmin();

    $this->putJson('/api/admin/about', [
        'heading' => 'New Heading',
        'subheading' => 'A subheading',
        'body' => 'New body',
        'mission' => 'Our mission',
        'vision' => 'Our vision',
    ])->assertOk()->assertJsonPath('data.heading', 'New Heading');

    expect(About::count())->toBe(1);
    expect(About::singleton()->mission)->toBe('Our mission');
});

test('updating about requires a heading', function () {
    actingAsAdmin();

    $this->putJson('/api/admin/about', ['heading' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('heading');
});

test('a non-admin cannot update about content', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->putJson('/api/admin/about', ['heading' => 'X'])->assertForbidden();
});
