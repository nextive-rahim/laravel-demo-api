<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an admin can create a section in a course', function () {
    actingAsAdmin();
    $course = Course::factory()->create();

    $this->postJson("/api/admin/courses/{$course->id}/sections", ['title' => 'Introduction'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Introduction')
        ->assertJsonPath('data.position', 1);

    $this->assertDatabaseHas('course_sections', ['course_id' => $course->id, 'title' => 'Introduction']);
});

test('creating a section requires a title', function () {
    actingAsAdmin();
    $course = Course::factory()->create();

    $this->postJson("/api/admin/courses/{$course->id}/sections", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});

test('an admin can list a course sections with their content', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $section = CourseSection::factory()->for($course)->create(['title' => 'Module 1']);

    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'note',
        'title' => 'Lesson 1',
        'course_section_id' => $section->id,
        'payload' => ['body' => 'Hello'],
    ])->assertCreated()->assertJsonPath('data.course_section_id', $section->id);

    $this->getJson("/api/admin/courses/{$course->id}/sections")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Module 1')
        ->assertJsonCount(1, 'data.0.contents')
        ->assertJsonPath('data.0.contents.0.title', 'Lesson 1');
});

test('an admin can rename and delete a section', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $section = CourseSection::factory()->for($course)->create();

    $this->putJson("/api/admin/courses/{$course->id}/sections/{$section->id}", ['title' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.title', 'Renamed');

    $this->deleteJson("/api/admin/courses/{$course->id}/sections/{$section->id}")->assertOk();
    $this->assertDatabaseMissing('course_sections', ['id' => $section->id]);
});

test('deleting a section keeps its content but ungroups it', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $section = CourseSection::factory()->for($course)->create();

    $content = $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'link',
        'title' => 'Resource',
        'course_section_id' => $section->id,
        'payload' => ['url' => 'https://example.com'],
    ])->json('data.id');

    $this->deleteJson("/api/admin/courses/{$course->id}/sections/{$section->id}")->assertOk();

    $this->assertDatabaseHas('course_contents', ['id' => $content, 'course_section_id' => null]);
});

test('content cannot be assigned to a section from another course', function () {
    actingAsAdmin();
    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();
    $foreignSection = CourseSection::factory()->for($courseB)->create();

    $this->postJson("/api/admin/courses/{$courseA->id}/contents", [
        'type' => 'note',
        'title' => 'X',
        'course_section_id' => $foreignSection->id,
        'payload' => ['body' => 'x'],
    ])->assertUnprocessable()->assertJsonValidationErrors(['course_section_id']);
});

test('a section cannot be updated through the wrong course', function () {
    actingAsAdmin();
    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();
    $section = CourseSection::factory()->for($courseA)->create();

    $this->putJson("/api/admin/courses/{$courseB->id}/sections/{$section->id}", ['title' => 'Nope'])
        ->assertNotFound();
});

test('the course show endpoint returns sections with their content', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $section = CourseSection::factory()->for($course)->create(['title' => 'Part 1']);
    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'note', 'title' => 'Intro', 'course_section_id' => $section->id, 'payload' => ['body' => 'hi'],
    ])->assertCreated();

    $this->getJson("/api/admin/courses/{$course->id}")
        ->assertOk()
        ->assertJsonPath('data.sections.0.title', 'Part 1')
        ->assertJsonPath('data.sections.0.contents.0.title', 'Intro');
});

test('an admin can create a sub-section under a section', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $parent = CourseSection::factory()->for($course)->create(['title' => 'Module 1']);

    $this->postJson("/api/admin/courses/{$course->id}/sections", [
        'title' => 'Lesson group A',
        'parent_id' => $parent->id,
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Lesson group A')
        ->assertJsonPath('data.parent_id', $parent->id);

    $this->assertDatabaseHas('course_sections', ['parent_id' => $parent->id, 'title' => 'Lesson group A']);
});

test('a sub-section cannot be nested under another sub-section', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $parent = CourseSection::factory()->for($course)->create();
    $sub = CourseSection::factory()->for($course)->create(['parent_id' => $parent->id]);

    $this->postJson("/api/admin/courses/{$course->id}/sections", [
        'title' => 'Too deep',
        'parent_id' => $sub->id,
    ])->assertUnprocessable()->assertJsonValidationErrors(['parent_id']);
});

test('content can be added to a sub-section', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $parent = CourseSection::factory()->for($course)->create();
    $sub = CourseSection::factory()->for($course)->create(['parent_id' => $parent->id]);

    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'note',
        'title' => 'Sub lesson',
        'course_section_id' => $sub->id,
        'payload' => ['body' => 'hi'],
    ])->assertCreated()->assertJsonPath('data.course_section_id', $sub->id);
});

test('the course show endpoint nests sub-sections under their parent', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $parent = CourseSection::factory()->for($course)->create(['title' => 'Module 1']);
    $sub = CourseSection::factory()->for($course)->create(['parent_id' => $parent->id, 'title' => 'Part A']);
    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'note', 'title' => 'Sub note', 'course_section_id' => $sub->id, 'payload' => ['body' => 'x'],
    ])->assertCreated();

    $this->getJson("/api/admin/courses/{$course->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.sections')                        // only the top-level section at root
        ->assertJsonPath('data.sections.0.title', 'Module 1')
        ->assertJsonPath('data.sections.0.children.0.title', 'Part A')
        ->assertJsonPath('data.sections.0.children.0.contents.0.title', 'Sub note');
});

test('deleting a parent section cascades to its sub-sections', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $parent = CourseSection::factory()->for($course)->create();
    $sub = CourseSection::factory()->for($course)->create(['parent_id' => $parent->id]);

    $this->deleteJson("/api/admin/courses/{$course->id}/sections/{$parent->id}")->assertOk();

    $this->assertDatabaseMissing('course_sections', ['id' => $parent->id]);
    $this->assertDatabaseMissing('course_sections', ['id' => $sub->id]);
});

test('a content item carries active, paid and available_from fields', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $section = CourseSection::factory()->for($course)->create();

    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'video',
        'title' => 'lec 1',
        'course_section_id' => $section->id,
        'is_active' => true,
        'is_paid' => true,
        'available_from' => '2025-10-13 22:53:58',
        'payload' => ['url' => 'https://youtu.be/x', 'provider' => 'youtube'],
    ])->assertCreated()
        ->assertJsonPath('data.is_paid', true)
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('course_contents', ['title' => 'lec 1', 'is_paid' => true]);
});

test('a section can be toggled inactive', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $section = CourseSection::factory()->for($course)->create(['is_active' => true]);

    $this->putJson("/api/admin/courses/{$course->id}/sections/{$section->id}", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);
});

test('a non-admin cannot manage sections', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));
    $course = Course::factory()->create();

    $this->postJson("/api/admin/courses/{$course->id}/sections", ['title' => 'X'])->assertForbidden();
});
