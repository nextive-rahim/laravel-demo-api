<?php

use App\Enums\CourseContentType;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function actingAsAdmin(): User
{
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);

    return $admin;
}

test('an admin can create a course', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/courses', [
        'title' => 'New Course',
        'description' => 'A description',
        'is_published' => true,
    ])->assertCreated()->assertJsonPath('data.title', 'New Course');

    $this->assertDatabaseHas('courses', ['title' => 'New Course']);
});

test('an admin can update and delete a course', function () {
    actingAsAdmin();
    $course = Course::factory()->create();

    $this->putJson("/api/admin/courses/{$course->id}", ['title' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.title', 'Renamed');

    $this->deleteJson("/api/admin/courses/{$course->id}")->assertOk();
    $this->assertDatabaseMissing('courses', ['id' => $course->id]);
});

test('a non-admin cannot manage courses', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->postJson('/api/admin/courses', ['title' => 'X'])->assertForbidden();
});

test('an admin can add a typed content item to a course', function () {
    actingAsAdmin();
    $course = Course::factory()->create();

    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'video',
        'title' => 'Lesson 1',
        'payload' => ['url' => 'https://youtube.com/watch?v=abc', 'provider' => 'youtube'],
    ])->assertCreated()
        ->assertJsonPath('data.type', 'video')
        ->assertJsonPath('data.payload.url', 'https://youtube.com/watch?v=abc');

    $this->assertDatabaseHas('course_contents', ['course_id' => $course->id, 'type' => 'video']);
});

test('content data is validated per type', function () {
    actingAsAdmin();
    $course = Course::factory()->create();

    // A video without a url is invalid.
    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'video',
        'title' => 'Bad video',
        'payload' => [],
    ])->assertUnprocessable()->assertJsonValidationErrorFor('payload.url');

    // A note requires a body.
    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'note',
        'title' => 'Bad note',
        'payload' => [],
    ])->assertUnprocessable()->assertJsonValidationErrorFor('payload.body');

    // An unknown type is rejected.
    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'audio',
        'title' => 'Nope',
    ])->assertUnprocessable()->assertJsonValidationErrorFor('type');
});

test('position auto-increments when omitted', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    CourseContent::factory()->for($course)->ofType(CourseContentType::Note)->create(['position' => 5]);

    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'link',
        'title' => 'A link',
        'payload' => ['url' => 'https://example.com'],
    ])->assertCreated()->assertJsonPath('data.position', 6);
});

test('an admin can update and delete a content item', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $content = CourseContent::factory()->for($course)->ofType(CourseContentType::Link)->create();

    $this->putJson("/api/admin/courses/{$course->id}/contents/{$content->id}", [
        'title' => 'Updated title',
    ])->assertOk()->assertJsonPath('data.title', 'Updated title');

    $this->deleteJson("/api/admin/courses/{$course->id}/contents/{$content->id}")->assertOk();
    $this->assertDatabaseMissing('course_contents', ['id' => $content->id]);
});

test('an admin can show a single content item with its data', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $content = CourseContent::factory()->for($course)->ofType(CourseContentType::Note)
        ->create(['title' => 'Lesson notes', 'payload' => ['body' => 'Full body text']]);

    $this->getJson("/api/admin/courses/{$course->id}/contents/{$content->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $content->id)
        ->assertJsonPath('data.title', 'Lesson notes')
        ->assertJsonPath('data.payload.body', 'Full body text');
});

test('a content item cannot be shown through the wrong course', function () {
    actingAsAdmin();
    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();
    $content = CourseContent::factory()->for($courseA)->ofType(CourseContentType::Link)->create();

    $this->getJson("/api/admin/courses/{$courseB->id}/contents/{$content->id}")->assertNotFound();
});

test('an admin can edit a content item title and payload', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $content = CourseContent::factory()->for($course)->ofType(CourseContentType::Video)
        ->create(['title' => 'Old', 'payload' => ['url' => 'https://old.test/v', 'provider' => 'youtube']]);

    $this->putJson("/api/admin/courses/{$course->id}/contents/{$content->id}", [
        'title' => 'New title',
        'payload' => ['url' => 'https://new.test/v', 'provider' => 'vimeo'],
    ])->assertOk()
        ->assertJsonPath('data.title', 'New title')
        ->assertJsonPath('data.payload.url', 'https://new.test/v')
        ->assertJsonPath('data.payload.provider', 'vimeo');

    $this->assertDatabaseHas('course_contents', ['id' => $content->id, 'title' => 'New title']);
});

test('editing a content item revalidates the payload for its type', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $content = CourseContent::factory()->for($course)->ofType(CourseContentType::Video)->create();

    // Clearing the required video url must fail validation.
    $this->putJson("/api/admin/courses/{$course->id}/contents/{$content->id}", [
        'payload' => [],
    ])->assertUnprocessable()->assertJsonValidationErrorFor('payload.url');
});

test('a content item cannot be edited through the wrong course', function () {
    actingAsAdmin();
    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();
    $content = CourseContent::factory()->for($courseA)->ofType(CourseContentType::Link)->create();

    $this->deleteJson("/api/admin/courses/{$courseB->id}/contents/{$content->id}")->assertNotFound();
});
