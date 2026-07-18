<?php

use App\Enums\CourseContentType;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('the public index lists only published courses', function () {
    Course::factory()->create(['title' => 'Published']);
    Course::factory()->draft()->create(['title' => 'Draft']);

    $this->getJson('/api/courses')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Published');
});

test('the guest course list is served from cache without touching the database', function () {
    Course::factory()->create();

    // First request warms the cache.
    $this->getJson('/api/courses')->assertOk()->assertJsonCount(1, 'data');

    DB::enableQueryLog();
    $this->getJson('/api/courses')->assertOk()->assertJsonCount(1, 'data');
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toBeEmpty();
});

test('the signed-in course list reuses the cached catalog and only queries enrollments', function () {
    $user = User::factory()->create();
    Course::factory()->create();
    Sanctum::actingAs($user);

    // Warm the shared catalog cache.
    $this->getJson('/api/courses')->assertOk()->assertJsonCount(1, 'data');

    DB::enableQueryLog();
    $this->getJson('/api/courses')->assertOk()->assertJsonCount(1, 'data');
    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    // The catalog comes from cache; the only DB work is the user's enrollments.
    expect($queries->filter(fn ($q) => str_contains($q, 'from "courses"')))->toBeEmpty();
    expect($queries->filter(fn ($q) => str_contains($q, 'from "enrollments"')))->not->toBeEmpty();
});

test('publishing a new course busts the cached public list', function () {
    Course::factory()->create(['title' => 'First']);
    $this->getJson('/api/courses')->assertOk()->assertJsonCount(1, 'data');

    Course::factory()->create(['title' => 'Second']);
    $this->getJson('/api/courses')->assertOk()->assertJsonCount(2, 'data');
});

test('adding content updates the cached contents_count on the public list', function () {
    $course = Course::factory()->create();
    $this->getJson('/api/courses')->assertOk()->assertJsonPath('data.0.contents_count', 0);

    CourseContent::factory()->for($course)->ofType(CourseContentType::Note)->create();
    $this->getJson('/api/courses')->assertOk()->assertJsonPath('data.0.contents_count', 1);
});

test('a published course is shown with its typed contents', function () {
    $course = Course::factory()->create();
    CourseContent::factory()->for($course)->ofType(CourseContentType::Video)->create(['title' => 'Intro video', 'position' => 1]);
    CourseContent::factory()->for($course)->ofType(CourseContentType::Note)->create(['title' => 'Notes', 'position' => 2]);

    $response = $this->getJson("/api/courses/{$course->id}")->assertOk();

    $response->assertJsonPath('data.id', $course->id)
        ->assertJsonCount(2, 'data.contents')
        ->assertJsonPath('data.contents.0.type', 'video')
        ->assertJsonStructure(['data' => ['contents' => [['type', 'title', 'payload']]]]);
});

test('a draft course returns 404 from the public endpoint', function () {
    $course = Course::factory()->draft()->create();

    $this->getJson("/api/courses/{$course->id}")->assertNotFound();
});

test('the website can show a single content item of a published course', function () {
    $course = Course::factory()->create();
    $content = CourseContent::factory()->for($course)->ofType(CourseContentType::Note)
        ->create(['title' => 'Lesson notes', 'payload' => ['body' => 'Full body text']]);

    // Content is gated behind an approved enrollment.
    $student = User::factory()->create();
    Enrollment::factory()->for($student)->for($course)->approved()->create();
    Sanctum::actingAs($student);

    $this->getJson("/api/courses/{$course->id}/contents/{$content->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $content->id)
        ->assertJsonPath('data.title', 'Lesson notes')
        ->assertJsonPath('data.payload.body', 'Full body text');
});

test('content of a draft course is not exposed to the website', function () {
    $course = Course::factory()->draft()->create();
    $content = CourseContent::factory()->for($course)->ofType(CourseContentType::Link)->create();

    $this->getJson("/api/courses/{$course->id}/contents/{$content->id}")->assertNotFound();
});

test('content cannot be shown through the wrong published course', function () {
    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();
    $content = CourseContent::factory()->for($courseA)->ofType(CourseContentType::Link)->create();

    $this->getJson("/api/courses/{$courseB->id}/contents/{$content->id}")->assertNotFound();
});
