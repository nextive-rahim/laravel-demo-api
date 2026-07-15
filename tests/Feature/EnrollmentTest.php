<?php

use App\Enums\CourseContentType;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Attach a note content item to a course and return it.
 */
function noteContent(Course $course): CourseContent
{
    return CourseContent::factory()->for($course)->create([
        'type' => CourseContentType::Note,
        'payload' => ['body' => 'Secret lesson body'],
    ]);
}

test('a student can submit a paid enrollment which starts pending', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $course = Course::factory()->create(['price' => 1000, 'discount_price' => 700]);

    $this->postJson("/api/courses/{$course->id}/enroll", [
        'payment_method' => 'bkash',
        'sender_number' => '01711111111',
        'receiver_number' => '01822222222',
        'transaction_id' => 'TRX12345',
    ])->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.amount', 700)
        ->assertJsonPath('data.payment_method', 'bkash');

    $this->assertDatabaseHas('enrollments', [
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'pending',
        'transaction_id' => 'TRX12345',
    ]);
});

test('paid enrollment requires payment details', function () {
    Sanctum::actingAs(User::factory()->create());
    $course = Course::factory()->create(['price' => 1000, 'discount_price' => null]);

    $this->postJson("/api/courses/{$course->id}/enroll", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['payment_method', 'sender_number', 'receiver_number', 'transaction_id']);
});

test('a free course enrolls instantly as approved', function () {
    Sanctum::actingAs(User::factory()->create());
    $course = Course::factory()->create(['price' => 0, 'discount_price' => null]);

    $this->postJson("/api/courses/{$course->id}/enroll", [])
        ->assertCreated()
        ->assertJsonPath('data.status', 'approved');
});

test('a student cannot submit twice while pending', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $course = Course::factory()->create(['price' => 1000]);
    Enrollment::factory()->for($user)->for($course)->create();

    $this->postJson("/api/courses/{$course->id}/enroll", [
        'payment_method' => 'nagad',
        'sender_number' => '01711111111',
        'receiver_number' => '01822222222',
        'transaction_id' => 'TRX999',
    ])->assertStatus(409);
});

test('a student can resubmit after a rejection', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $course = Course::factory()->create(['price' => 1000]);
    $rejected = Enrollment::factory()->for($user)->for($course)->rejected()->create();

    $this->postJson("/api/courses/{$course->id}/enroll", [
        'payment_method' => 'rocket',
        'sender_number' => '01711111111',
        'receiver_number' => '01822222222',
        'transaction_id' => 'TRXNEW',
    ])->assertCreated()->assertJsonPath('data.status', 'pending');

    // Resubmitted in place — still one row for this user + course.
    expect(Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->count())->toBe(1);
    expect($rejected->fresh()->transaction_id)->toBe('TRXNEW');
});

test('course show hides lesson payloads until enrolled', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $course = Course::factory()->create(['price' => 1000]);
    noteContent($course);

    $this->getJson("/api/courses/{$course->id}")
        ->assertOk()
        ->assertJsonPath('data.is_enrolled', false)
        ->assertJsonPath('data.contents.0.locked', true)
        ->assertJsonPath('data.contents.0.payload', []);
});

test('course show unlocks lesson payloads once approved', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $course = Course::factory()->create(['price' => 1000]);
    noteContent($course);
    Enrollment::factory()->for($user)->for($course)->approved()->create();

    $this->getJson("/api/courses/{$course->id}")
        ->assertOk()
        ->assertJsonPath('data.is_enrolled', true)
        ->assertJsonPath('data.contents.0.locked', false)
        ->assertJsonPath('data.contents.0.payload.body', 'Secret lesson body');
});

test('the course list reports the student enrollment state per course', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $approved = Course::factory()->create(['title' => 'Approved', 'is_published' => true]);
    $pending = Course::factory()->create(['title' => 'Pending', 'is_published' => true]);
    $untouched = Course::factory()->create(['title' => 'Untouched', 'is_published' => true]);

    Enrollment::factory()->for($user)->for($approved)->approved()->create();
    Enrollment::factory()->for($user)->for($pending)->create(['status' => EnrollmentStatus::Pending]);
    // Another student's enrollment must not leak into this student's cards.
    Enrollment::factory()->for(User::factory())->for($untouched)->approved()->create();

    $response = $this->getJson('/api/courses')->assertOk();

    $byTitle = collect($response->json('data'))->keyBy('title');

    expect($byTitle['Approved']['is_enrolled'])->toBeTrue();
    expect($byTitle['Pending']['is_enrolled'])->toBeFalse();
    expect($byTitle['Pending']['enrollment']['status'])->toBe('pending');
    expect($byTitle['Untouched']['is_enrolled'])->toBeFalse();
    expect($byTitle['Untouched']['enrollment'])->toBeNull();
});

test('the course list never leaks an enrollment to a guest', function () {
    $course = Course::factory()->create(['is_published' => true]);
    Enrollment::factory()->for(User::factory())->for($course)->approved()->create();

    // No user, so the field is simply not exposed — the card falls back to "Enroll".
    $this->getJson('/api/courses')
        ->assertOk()
        ->assertJsonMissingPath('data.0.is_enrolled')
        ->assertJsonMissingPath('data.0.enrollment');
});

test('the single content endpoint is blocked without an approved enrollment', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $course = Course::factory()->create(['price' => 1000]);
    $content = noteContent($course);

    $this->getJson("/api/courses/{$course->id}/contents/{$content->id}")
        ->assertForbidden();

    Enrollment::factory()->for($user)->for($course)->approved()->create();

    $this->getJson("/api/courses/{$course->id}/contents/{$content->id}")
        ->assertOk()
        ->assertJsonPath('data.payload.body', 'Secret lesson body');
});

test('an admin can approve an enrollment and grant access', function () {
    $student = User::factory()->create();
    $course = Course::factory()->create(['price' => 1000]);
    $enrollment = Enrollment::factory()->for($student)->for($course)->create();

    actingAsAdmin();

    $this->postJson("/api/admin/enrollments/{$enrollment->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    expect($course->fresh()->isAccessibleBy($student))->toBeTrue();
});

test('an admin can reject an enrollment and revoke access', function () {
    $student = User::factory()->create();
    $course = Course::factory()->create(['price' => 1000]);
    $enrollment = Enrollment::factory()->for($student)->for($course)->approved()->create();

    actingAsAdmin();

    $this->postJson("/api/admin/enrollments/{$enrollment->id}/reject")
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    expect($course->fresh()->isAccessibleBy($student))->toBeFalse();
});

test('admin enrollment listing can filter by status', function () {
    $course = Course::factory()->create();
    Enrollment::factory()->for($course)->count(2)->create();
    Enrollment::factory()->for($course)->approved()->count(3)->create();

    actingAsAdmin();

    $this->getJson('/api/admin/enrollments?status=pending')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('admin enrollment listing can filter by course', function () {
    $courseA = Course::factory()->create();
    $courseB = Course::factory()->create();
    Enrollment::factory()->for($courseA)->count(2)->create();
    Enrollment::factory()->for($courseB)->count(3)->create();

    actingAsAdmin();

    $this->getJson("/api/admin/enrollments?course_id={$courseA->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('a non-admin cannot review enrollments', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));
    $enrollment = Enrollment::factory()->create();

    $this->postJson("/api/admin/enrollments/{$enrollment->id}/approve")->assertForbidden();
});
