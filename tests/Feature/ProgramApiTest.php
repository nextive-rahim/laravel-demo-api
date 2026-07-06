<?php

use App\Enums\ProgramCategory;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('the public index lists only published programs', function () {
    Program::factory()->create(['title' => 'HSC 2026']);
    Program::factory()->draft()->create(['title' => 'Hidden']);

    $this->getJson('/api/programs')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'HSC 2026');
});

test('the public index can filter by category', function () {
    Program::factory()->category(ProgramCategory::Academic)->create();
    Program::factory()->category(ProgramCategory::Job)->create();

    $this->getJson('/api/programs?category=job')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.category', 'job');
});

test('a draft program returns 404', function () {
    $program = Program::factory()->draft()->create();
    $this->getJson("/api/programs/{$program->id}")->assertNotFound();
});

test('an admin can create a program', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/programs', [
        'category' => 'academic',
        'title' => 'HSC 2027',
        'subtitle' => 'Start early',
        'price' => 3000,
        'is_published' => true,
    ])->assertCreated()->assertJsonPath('data.category', 'academic');

    $this->assertDatabaseHas('programs', ['title' => 'HSC 2027']);
});

test('creating a program requires category and title', function () {
    actingAsAdmin();
    $this->postJson('/api/admin/programs', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category', 'title']);
});

test('an admin can update and delete a program', function () {
    actingAsAdmin();
    $program = Program::factory()->create();

    $this->putJson("/api/admin/programs/{$program->id}", ['title' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.title', 'Renamed');
    $this->deleteJson("/api/admin/programs/{$program->id}")->assertOk();
    $this->assertDatabaseMissing('programs', ['id' => $program->id]);
});

test('a non-admin cannot manage programs', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));
    $this->postJson('/api/admin/programs', ['category' => 'academic', 'title' => 'X'])->assertForbidden();
});
