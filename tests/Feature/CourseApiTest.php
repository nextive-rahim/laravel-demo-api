<?php

use App\Enums\CourseContentType;
use App\Models\Course;
use App\Models\CourseContent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the public index lists only published courses', function () {
    Course::factory()->create(['title' => 'Published']);
    Course::factory()->draft()->create(['title' => 'Draft']);

    $this->getJson('/api/courses')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Published');
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
