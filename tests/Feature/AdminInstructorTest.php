<?php

use App\Models\Course;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an admin can list instructors', function () {
    actingAsAdmin();
    Instructor::factory()->count(3)->create();

    $this->getJson('/api/admin/instructors')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'title', 'image_url', 'is_published']]]);
});

test('an admin can create an instructor', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/instructors', [
        'name' => 'Ayesha Rahman',
        'title' => 'Senior Instructor',
        'bio' => 'Teaches Laravel.',
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Ayesha Rahman')
        ->assertJsonPath('data.title', 'Senior Instructor');

    $this->assertDatabaseHas('instructors', ['name' => 'Ayesha Rahman']);
});

test('creating an instructor requires a name', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/instructors', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('an admin can update and delete an instructor', function () {
    actingAsAdmin();
    $instructor = Instructor::factory()->create();

    $this->putJson("/api/admin/instructors/{$instructor->id}", ['name' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.name', 'Renamed');

    $this->deleteJson("/api/admin/instructors/{$instructor->id}")->assertOk();
    $this->assertDatabaseMissing('instructors', ['id' => $instructor->id]);
});

test('a non-admin cannot manage instructors', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->postJson('/api/admin/instructors', ['name' => 'X'])->assertForbidden();
});

test('an admin can assign multiple instructors to a course on create', function () {
    actingAsAdmin();
    $a = Instructor::factory()->create();
    $b = Instructor::factory()->create();

    $this->postJson('/api/admin/courses', [
        'title' => 'Laravel Deep Dive',
        'instructor_ids' => [$a->id, $b->id],
    ])->assertCreated()
        ->assertJsonCount(2, 'data.instructors');

    $course = Course::firstWhere('title', 'Laravel Deep Dive');
    expect($course->instructors)->toHaveCount(2);
});

test('an admin can sync a course instructors on update and order is preserved', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $a = Instructor::factory()->create();
    $b = Instructor::factory()->create();
    $c = Instructor::factory()->create();

    // Assign a, b.
    $this->putJson("/api/admin/courses/{$course->id}", ['instructor_ids' => [$a->id, $b->id]])
        ->assertOk()->assertJsonCount(2, 'data.instructors');

    // Re-sync to c, a — b removed, order c then a.
    $this->putJson("/api/admin/courses/{$course->id}", ['instructor_ids' => [$c->id, $a->id]])
        ->assertOk()
        ->assertJsonCount(2, 'data.instructors')
        ->assertJsonPath('data.instructors.0.id', $c->id)
        ->assertJsonPath('data.instructors.1.id', $a->id);

    expect($course->fresh()->instructors->pluck('id')->all())->toBe([$c->id, $a->id]);
});

test('assigning a non-existent instructor fails validation', function () {
    actingAsAdmin();
    $course = Course::factory()->create();

    $this->putJson("/api/admin/courses/{$course->id}", ['instructor_ids' => [99999]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['instructor_ids.0']);
});

test('deleting a course detaches its instructors via cascade', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $instructor = Instructor::factory()->create();
    $course->instructors()->attach($instructor->id, ['position' => 0]);

    $this->deleteJson("/api/admin/courses/{$course->id}")->assertOk();

    $this->assertDatabaseMissing('course_instructor', ['course_id' => $course->id]);
    $this->assertDatabaseHas('instructors', ['id' => $instructor->id]);
});
