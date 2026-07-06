<?php

use App\Enums\ResourceType;
use App\Models\FreeResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('the public index lists only published resources', function () {
    FreeResource::factory()->create(['title' => 'Free note']);
    FreeResource::factory()->draft()->create(['title' => 'Hidden']);

    $this->getJson('/api/free-resources')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Free note');
});

test('the public index can filter by type', function () {
    FreeResource::factory()->type(ResourceType::Note)->create();
    FreeResource::factory()->type(ResourceType::Book)->create();

    $this->getJson('/api/free-resources?type=book')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'book');
});

test('an invalid type filter is rejected', function () {
    $this->getJson('/api/free-resources?type=bogus')->assertUnprocessable();
});

test('an admin can create a free resource', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/free-resources', [
        'type' => 'pdf',
        'title' => 'Physics Formula Sheet',
        'file_url' => 'https://example.com/formula.pdf',
        'is_published' => true,
    ])->assertCreated()->assertJsonPath('data.type', 'pdf');

    $this->assertDatabaseHas('free_resources', ['title' => 'Physics Formula Sheet']);
});

test('creating a resource requires type and title', function () {
    actingAsAdmin();
    $this->postJson('/api/admin/free-resources', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'title']);
});

test('an admin can update and delete a resource', function () {
    actingAsAdmin();
    $resource = FreeResource::factory()->create();

    $this->putJson("/api/admin/free-resources/{$resource->id}", ['title' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.title', 'Renamed');
    $this->deleteJson("/api/admin/free-resources/{$resource->id}")->assertOk();
    $this->assertDatabaseMissing('free_resources', ['id' => $resource->id]);
});

test('a non-admin cannot manage resources', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));
    $this->postJson('/api/admin/free-resources', ['type' => 'note', 'title' => 'X'])->assertForbidden();
});
