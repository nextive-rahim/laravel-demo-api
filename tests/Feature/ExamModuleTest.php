<?php

use App\Enums\CourseContentType;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Build a published course with an exam content item carrying the given payload.
 */
function makeExam(array $payload = []): CourseContent
{
    $course = Course::factory()->create(['is_published' => true]);

    return CourseContent::factory()
        ->for($course)
        ->ofType(CourseContentType::Exam)
        ->create(['payload' => array_merge(['duration_minutes' => 30], $payload)]);
}

/**
 * Create a student with an approved enrollment for the exam's course and act as them.
 *
 * @param  array<string, mixed>  $attributes
 */
function actingAsEnrolledStudent(CourseContent $exam, array $attributes = []): User
{
    $student = User::factory()->create($attributes);
    Enrollment::factory()->for($student)->for($exam->course)->approved()->create();
    Sanctum::actingAs($student);

    return $student;
}

/**
 * Attach freshly-created questions to an exam and return them.
 *
 * @param  array<int, int>  $correctIndexes
 * @return Collection<int, Question>
 */
function attachQuestions(CourseContent $exam, array $correctIndexes): Collection
{
    return collect($correctIndexes)->map(function (int $correctIndex, int $i) use ($exam) {
        $question = Question::factory()->withOptions($correctIndex)->create(['marks' => 2]);
        $exam->questions()->attach($question->id, ['position' => $i + 1]);

        return $question->load('options');
    });
}

// --- Store: categories, subcategories, questions ---------------------------

test('an admin can create a category and subcategory', function () {
    actingAsAdmin();

    $this->postJson('/api/admin/categories', ['name' => 'Mathematics'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mathematics')
        ->assertJsonPath('data.slug', 'mathematics');

    $category = Category::first();

    $this->postJson('/api/admin/subcategories', ['category_id' => $category->id, 'name' => 'Algebra'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Algebra')
        ->assertJsonPath('data.category_id', $category->id);
});

test('an admin can create an MCQ question with one correct option', function () {
    actingAsAdmin();
    $subcategory = Subcategory::factory()->create();

    $this->postJson('/api/admin/questions', [
        'subcategory_id' => $subcategory->id,
        'body' => 'What is 2 + 2?',
        'marks' => 1,
        'options' => [
            ['body' => '3', 'is_correct' => false],
            ['body' => '4', 'is_correct' => true],
            ['body' => '5', 'is_correct' => false],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.body', 'What is 2 + 2?')
        ->assertJsonCount(3, 'data.options');

    $this->assertDatabaseHas('question_options', ['body' => '4', 'is_correct' => true]);
});

test('a question must have exactly one correct option', function () {
    actingAsAdmin();
    $subcategory = Subcategory::factory()->create();

    $this->postJson('/api/admin/questions', [
        'subcategory_id' => $subcategory->id,
        'body' => 'Pick',
        'options' => [
            ['body' => 'a', 'is_correct' => true],
            ['body' => 'b', 'is_correct' => true],
        ],
    ])->assertUnprocessable()->assertJsonValidationErrorFor('options');
});

// --- Attaching questions to an exam ----------------------------------------

test('an admin can attach store questions to an exam', function () {
    actingAsAdmin();
    $exam = makeExam();
    $questions = Question::factory()->withOptions()->count(2)->create();

    $this->postJson("/api/admin/courses/{$exam->course_id}/contents/{$exam->id}/questions", [
        'question_ids' => $questions->pluck('id')->all(),
    ])->assertOk()->assertJsonCount(2, 'data');

    expect($exam->questions()->count())->toBe(2);
});

test('questions cannot be attached to a non-exam content item', function () {
    actingAsAdmin();
    $course = Course::factory()->create();
    $note = CourseContent::factory()->for($course)->ofType(CourseContentType::Note)->create();
    $question = Question::factory()->withOptions()->create();

    $this->postJson("/api/admin/courses/{$course->id}/contents/{$note->id}/questions", [
        'question_ids' => [$question->id],
    ])->assertStatus(422);
});

// --- Taking the exam --------------------------------------------------------

test('exam endpoints require authentication', function () {
    $exam = makeExam();

    $this->getJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam")
        ->assertUnauthorized();
});

test('the taking payload never leaks which option is correct', function () {
    $exam = makeExam();
    attachQuestions($exam, [0, 1]);
    actingAsEnrolledStudent($exam);

    $response = $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/start")
        ->assertOk();

    $options = $response->json('data.questions.0.options');
    expect($options)->not->toBeEmpty();
    foreach ($options as $option) {
        expect($option)->not->toHaveKey('is_correct');
    }
});

test('a student can take an exam and it is auto-graded', function () {
    $exam = makeExam(); // no result_publish_time -> results shown immediately
    $questions = attachQuestions($exam, [0, 1]); // each worth 2 marks, total 4
    actingAsEnrolledStudent($exam);

    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/start")->assertOk();

    // Answer the first question correctly, the second incorrectly.
    $q1 = $questions[0];
    $q2 = $questions[1];
    $answers = [
        ['question_id' => $q1->id, 'question_option_id' => $q1->options->firstWhere('is_correct', true)->id],
        ['question_id' => $q2->id, 'question_option_id' => $q2->options->firstWhere('is_correct', false)->id],
    ];

    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/submit", ['answers' => $answers])
        ->assertOk()
        ->assertJsonPath('data.score', 2)
        ->assertJsonPath('data.total_marks', 4)
        ->assertJsonPath('data.results_published', true);

    $this->assertDatabaseHas('exam_attempts', ['course_content_id' => $exam->id, 'status' => 'submitted', 'score' => 2]);
});

test('an admin-set pass mark decides pass/fail on the server', function () {
    $exam = makeExam(['pass_mark' => 60]); // 2 of 4 marks = 50% -> below 60
    $questions = attachQuestions($exam, [0, 1]);
    actingAsEnrolledStudent($exam);

    $base = "/api/courses/{$exam->course_id}/contents/{$exam->id}/exam";
    $this->postJson("{$base}/start")->assertOk();

    $this->postJson("{$base}/submit", ['answers' => [
        ['question_id' => $questions[0]->id, 'question_option_id' => $questions[0]->options->firstWhere('is_correct', true)->id],
        ['question_id' => $questions[1]->id, 'question_option_id' => $questions[1]->options->firstWhere('is_correct', false)->id],
    ]])
        ->assertOk()
        ->assertJsonPath('data.pass_mark', 60)
        ->assertJsonPath('data.passed', false)
        ->assertJsonPath('data.submitted', true)
        ->assertJsonPath('data.percentage', fn ($v) => (int) round($v) === 50);
});

test('a lower pass mark lets the same score pass', function () {
    $exam = makeExam(['pass_mark' => 50]); // exactly 50% passes
    $questions = attachQuestions($exam, [0, 1]);
    actingAsEnrolledStudent($exam);

    $base = "/api/courses/{$exam->course_id}/contents/{$exam->id}/exam";
    $this->postJson("{$base}/start")->assertOk();

    $this->postJson("{$base}/submit", ['answers' => [
        ['question_id' => $questions[0]->id, 'question_option_id' => $questions[0]->options->firstWhere('is_correct', true)->id],
        ['question_id' => $questions[1]->id, 'question_option_id' => $questions[1]->options->firstWhere('is_correct', false)->id],
    ]])
        ->assertOk()
        ->assertJsonPath('data.pass_mark', 50)
        ->assertJsonPath('data.passed', true);
});

test('the exam meta exposes a default 40% pass mark when none is set', function () {
    $exam = makeExam(); // no pass_mark
    attachQuestions($exam, [0]);
    actingAsEnrolledStudent($exam);

    $this->getJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam")
        ->assertOk()
        ->assertJsonPath('data.pass_mark', 40);
});

test('an admin can set the pass mark when creating an exam content item', function () {
    actingAsAdmin();
    $course = Course::factory()->create();

    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'exam',
        'title' => 'Graded exam',
        'payload' => ['duration_minutes' => 30, 'pass_mark' => 70],
    ])->assertCreated()->assertJsonPath('data.payload.pass_mark', 70);
});

test('the pass mark must be between 0 and 100', function () {
    actingAsAdmin();
    $course = Course::factory()->create();

    $this->postJson("/api/admin/courses/{$course->id}/contents", [
        'type' => 'exam',
        'title' => 'Bad exam',
        'payload' => ['pass_mark' => 150],
    ])->assertUnprocessable()->assertJsonValidationErrorFor('payload.pass_mark');
});

test('a student gets only one attempt', function () {
    $exam = makeExam();
    $questions = attachQuestions($exam, [0]);
    actingAsEnrolledStudent($exam);

    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/start")->assertOk();
    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/submit", [
        'answers' => [['question_id' => $questions[0]->id, 'question_option_id' => $questions[0]->options->first()->id]],
    ])->assertOk();

    // A second start after submitting is rejected.
    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/start")
        ->assertStatus(409);
});

test('results are withheld until the publish time', function () {
    $exam = makeExam(['result_publish_time' => now()->addDay()->toIso8601String()]);
    $questions = attachQuestions($exam, [0]);
    actingAsEnrolledStudent($exam);

    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/start")->assertOk();

    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/submit", [
        'answers' => [['question_id' => $questions[0]->id, 'question_option_id' => $questions[0]->options->first()->id]],
    ])->assertOk()
        ->assertJsonPath('data.results_published', false)
        ->assertJsonPath('data.score', null);

    // The score is still recorded server-side for the admin.
    $this->assertDatabaseHas('exam_attempts', ['course_content_id' => $exam->id, 'score' => 2]);
});

// --- Analytics --------------------------------------------------------------

test('students see a ranking ordered by score then time once results are published', function () {
    $exam = makeExam();
    $questions = attachQuestions($exam, [0]); // one question, 2 marks

    // Two students: one correct (winner), one wrong.
    $winner = actingAsEnrolledStudent($exam, ['name' => 'Top Scorer']);
    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/start")->assertOk();
    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/submit", [
        'answers' => [['question_id' => $questions[0]->id, 'question_option_id' => $questions[0]->options->firstWhere('is_correct', true)->id]],
    ])->assertOk();

    $loser = actingAsEnrolledStudent($exam, ['name' => 'Lower Scorer']);
    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/start")->assertOk();
    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/submit", [
        'answers' => [['question_id' => $questions[0]->id, 'question_option_id' => $questions[0]->options->firstWhere('is_correct', false)->id]],
    ])->assertOk();

    Sanctum::actingAs($winner);
    $this->getJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/ranking")
        ->assertOk()
        ->assertJsonPath('data.0.rank', 1)
        ->assertJsonPath('data.0.user_name', 'Top Scorer')
        ->assertJsonPath('data.0.is_you', true)
        ->assertJsonPath('data.1.user_name', 'Lower Scorer')
        ->assertJsonPath('data.1.is_you', false);
});

test('ranking is hidden until results are published', function () {
    $exam = makeExam(['result_publish_time' => now()->addDay()->toIso8601String()]);
    attachQuestions($exam, [0]);
    actingAsEnrolledStudent($exam);

    $this->getJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/ranking")
        ->assertStatus(403);
});

test('an admin can see attempts and analysis for an exam', function () {
    $exam = makeExam();
    $questions = attachQuestions($exam, [0]);

    actingAsEnrolledStudent($exam);
    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/start")->assertOk();
    $this->postJson("/api/courses/{$exam->course_id}/contents/{$exam->id}/exam/submit", [
        'answers' => [['question_id' => $questions[0]->id, 'question_option_id' => $questions[0]->options->firstWhere('is_correct', true)->id]],
    ])->assertOk();

    actingAsAdmin();

    $this->getJson("/api/admin/courses/{$exam->course_id}/contents/{$exam->id}/attempts")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.score', 2);

    $this->getJson("/api/admin/courses/{$exam->course_id}/contents/{$exam->id}/analysis")
        ->assertOk()
        ->assertJsonPath('data.participation.submitted', 1)
        ->assertJsonPath('data.scores.average_percentage', 100)
        ->assertJsonPath('data.questions.0.correct', 1);
});
