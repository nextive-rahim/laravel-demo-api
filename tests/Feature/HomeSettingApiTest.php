<?php

use App\Models\HomeSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('the public endpoint returns the singleton home settings', function () {
    HomeSetting::singleton()->update([
        'hero_title' => 'Hello',
        'stats' => [['value' => '10k+', 'label' => 'Students']],
    ]);

    $this->getJson('/api/home-settings')
        ->assertOk()
        ->assertJsonPath('data.hero_title', 'Hello')
        ->assertJsonPath('data.stats.0.value', '10k+');
});

test('the public endpoint works before any content is set', function () {
    $this->getJson('/api/home-settings')
        ->assertOk()
        ->assertJsonStructure(['data' => ['hero_title', 'hero_badge', 'hero_subtitle', 'stats']]);

    expect(HomeSetting::count())->toBe(1);
});

test('an admin update busts the cached public home settings', function () {
    HomeSetting::singleton()->update(['hero_title' => 'Original']);
    $this->getJson('/api/home-settings')->assertOk()->assertJsonPath('data.hero_title', 'Original');

    actingAsAdmin();
    $this->putJson('/api/admin/home-settings', [
        'hero_badge' => 'Badge',
        'hero_title' => 'Updated',
        'hero_highlight' => 'Highlight',
        'hero_subtitle' => 'Sub',
        'stats' => [['value' => '5k+', 'label' => 'Learners']],
    ])->assertOk();

    $this->getJson('/api/home-settings')->assertOk()->assertJsonPath('data.hero_title', 'Updated');
});

test('an admin can update the home settings', function () {
    actingAsAdmin();

    $this->putJson('/api/admin/home-settings', [
        'hero_badge' => 'New badge',
        'hero_title' => 'New title',
        'hero_highlight' => 'Highlight',
        'hero_subtitle' => 'Sub',
        'stats' => [
            ['value' => '5k+', 'label' => 'Learners'],
            ['value' => '4.8', 'label' => 'Rating'],
        ],
    ])->assertOk()->assertJsonPath('data.hero_title', 'New title');

    expect(HomeSetting::count())->toBe(1);
    expect(HomeSetting::singleton()->stats)->toHaveCount(2);
});

test('updating home settings requires a hero title and valid stats', function () {
    actingAsAdmin();

    $this->putJson('/api/admin/home-settings', [
        'hero_title' => '',
        'stats' => [['value' => 'x']],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['hero_title', 'stats.0.label']);
});

test('a non-admin cannot update home settings', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->putJson('/api/admin/home-settings', ['hero_title' => 'X'])->assertForbidden();
});
