<?php

use App\Enums\CourseContentType;
use App\Models\Course;
use App\Models\CourseContent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function todayAt(string $time): string
{
    return now()->format('Y-m-d').'T'.$time;
}

test('todays-live returns exams and classes starting today', function () {
    $course = Course::factory()->create(); // published

    CourseContent::factory()->for($course)->ofType(CourseContentType::Exam)
        ->create(['title' => 'Physics Exam', 'payload' => ['start_time' => todayAt('10:00'), 'duration_minutes' => 60]]);

    CourseContent::factory()->for($course)->ofType(CourseContentType::Live)
        ->create(['title' => 'Math Live', 'payload' => ['url' => 'https://meet.test/x', 'scheduled_at' => todayAt('18:00')]]);

    $this->getJson('/api/todays-live')
        ->assertOk()
        ->assertJsonCount(1, 'exams')
        ->assertJsonCount(1, 'classes')
        ->assertJsonPath('exams.0.title', 'Physics Exam')
        ->assertJsonPath('exams.0.course_title', $course->title)
        ->assertJsonPath('exams.0.duration_minutes', 60)
        ->assertJsonPath('classes.0.title', 'Math Live')
        ->assertJsonPath('classes.0.url', 'https://meet.test/x');
});

test('content starting on another day is excluded', function () {
    $course = Course::factory()->create();

    CourseContent::factory()->for($course)->ofType(CourseContentType::Exam)
        ->create(['payload' => ['start_time' => now()->addDays(3)->format('Y-m-d').'T10:00']]);
    CourseContent::factory()->for($course)->ofType(CourseContentType::Live)
        ->create(['payload' => ['url' => 'https://x', 'scheduled_at' => now()->subDays(2)->format('Y-m-d').'T10:00']]);

    $this->getJson('/api/todays-live')
        ->assertOk()
        ->assertJsonCount(0, 'exams')
        ->assertJsonCount(0, 'classes');
});

test('content from unpublished courses is excluded', function () {
    $course = Course::factory()->draft()->create();
    CourseContent::factory()->for($course)->ofType(CourseContentType::Exam)
        ->create(['payload' => ['start_time' => todayAt('10:00')]]);

    $this->getJson('/api/todays-live')
        ->assertOk()
        ->assertJsonCount(0, 'exams');
});

test('non exam/live content is never returned', function () {
    $course = Course::factory()->create();
    CourseContent::factory()->for($course)->ofType(CourseContentType::Note)
        ->create(['payload' => ['body' => 'today']]);

    $this->getJson('/api/todays-live')
        ->assertOk()
        ->assertJsonCount(0, 'exams')
        ->assertJsonCount(0, 'classes');
});
