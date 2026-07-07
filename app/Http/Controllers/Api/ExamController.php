<?php

namespace App\Http\Controllers\Api;

use App\Enums\ExamAttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitExamRequest;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\ExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    /**
     * Exam overview for the authenticated student: meta + their attempt state.
     * Does not expose questions or correct answers.
     */
    public function show(Request $request, Course $course, CourseContent $content): JsonResponse
    {
        $this->ensureExamContent($course, $content);

        $attempt = $this->attemptFor($request, $content);

        return response()->json([
            'data' => array_merge($this->examMeta($content), [
                'attempt' => $attempt ? $this->attemptSummary($attempt) : null,
            ]),
        ]);
    }

    /**
     * Start (or resume) the single attempt and return the questions to answer.
     * Correct answers are never included here.
     */
    public function start(Request $request, Course $course, CourseContent $content): JsonResponse
    {
        $this->ensureExamContent($course, $content);

        abort_unless($content->questions()->exists(), 422, 'This exam has no questions yet.');
        abort_if($this->isBeforeWindow($content), 422, 'This exam has not opened yet.');
        abort_if($this->isAfterWindow($content), 422, 'This exam has closed.');

        $user = $request->user();
        $attempt = $content->attempts()->where('user_id', $user->id)->first();

        abort_if($attempt?->isSubmitted() ?? false, 409, 'You have already submitted this exam.');

        $attempt ??= $content->attempts()->create([
            'user_id' => $user->id,
            'status' => ExamAttemptStatus::InProgress,
            'started_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'attempt' => $this->attemptSummary($attempt),
                'duration_minutes' => $content->payload['duration_minutes'] ?? null,
                'questions' => $this->questionsForTaking($content),
            ],
        ]);
    }

    /**
     * Submit answers, auto-grade, and record the score and time taken.
     */
    public function submit(SubmitExamRequest $request, Course $course, CourseContent $content): JsonResponse
    {
        $this->ensureExamContent($course, $content);

        $user = $request->user();
        $attempt = $content->attempts()->where('user_id', $user->id)->first();

        abort_if($attempt === null, 422, 'Start the exam before submitting.');
        abort_if($attempt->isSubmitted(), 409, 'You have already submitted this exam.');

        // Map submitted answers by question id for quick lookup.
        $submitted = collect($request->validated('answers'))
            ->keyBy('question_id')
            ->map(fn ($answer) => $answer['question_option_id'] ?? null);

        $questions = $content->questions()->with('options')->get();

        $score = 0;
        $totalMarks = 0;

        DB::transaction(function () use ($attempt, $questions, $submitted, &$score, &$totalMarks) {
            foreach ($questions as $question) {
                $marks = $question->pivot->marks ?? $question->marks;
                $totalMarks += $marks;

                $selectedId = $submitted->get($question->id);
                // Only accept an option that actually belongs to this question.
                $selected = $question->options->firstWhere('id', $selectedId);
                $isCorrect = $selected !== null && $selected->is_correct;

                if ($isCorrect) {
                    $score += $marks;
                }

                $attempt->answers()->create([
                    'question_id' => $question->id,
                    'question_option_id' => $selected?->id,
                    'is_correct' => $isCorrect,
                ]);
            }

            $attempt->update([
                'status' => ExamAttemptStatus::Submitted,
                'submitted_at' => now(),
                'time_taken_seconds' => max(0, now()->diffInSeconds($attempt->started_at, true)),
                'score' => $score,
                'total_marks' => $totalMarks,
            ]);
        });

        return response()->json([
            'data' => $this->resultPayload($content, $attempt->fresh()),
        ]);
    }

    /**
     * The student's result, gated by the exam's result publish time.
     */
    public function result(Request $request, Course $course, CourseContent $content): JsonResponse
    {
        $this->ensureExamContent($course, $content);

        $attempt = $this->attemptFor($request, $content);

        abort_if($attempt === null || ! $attempt->isSubmitted(), 404, 'No submitted attempt found.');

        return response()->json([
            'data' => $this->resultPayload($content, $attempt),
        ]);
    }

    /**
     * Leaderboard of everyone who submitted this exam, best score first
     * (ties broken by the faster time). Available once results are published.
     */
    public function ranking(Request $request, Course $course, CourseContent $content): JsonResponse
    {
        $this->ensureExamContent($course, $content);
        abort_unless($this->resultsPublished($content), 403, 'Rankings are available once results are published.');

        $userId = $request->user()->id;

        $attempts = $content->attempts()
            ->where('status', ExamAttemptStatus::Submitted)
            ->with('user')
            ->orderByDesc('score')
            ->orderBy('time_taken_seconds')
            ->get();

        $data = $attempts->values()->map(fn (ExamAttempt $attempt, int $index) => [
            'rank' => $index + 1,
            'user_name' => $attempt->user->name,
            'score' => $attempt->score,
            'total_marks' => $attempt->total_marks,
            'percentage' => $attempt->total_marks
                ? round($attempt->score / $attempt->total_marks * 100, 1)
                : null,
            'time_taken_seconds' => $attempt->time_taken_seconds,
            'is_you' => $attempt->user_id === $userId,
        ]);

        return response()->json(['data' => $data]);
    }

    /**
     * Build the result body, hiding the detailed breakdown until the publish time.
     *
     * @return array<string, mixed>
     */
    private function resultPayload(CourseContent $content, ExamAttempt $attempt): array
    {
        $published = $this->resultsPublished($content);
        $publishAt = $this->payloadDate($content, 'result_publish_time');
        $passMark = (int) ($content->payload['pass_mark'] ?? 40);

        $base = [
            'attempt' => $this->attemptSummary($attempt),
            'submitted' => $attempt->isSubmitted(),
            'results_published' => $published,
            'result_publish_time' => $publishAt?->toIso8601String(),
            'pass_mark' => $passMark,
        ];

        if (! $published) {
            // Score is withheld until results are published.
            return array_merge($base, ['score' => null, 'total_marks' => null, 'percentage' => null, 'passed' => null, 'questions' => null]);
        }

        $percentage = $attempt->total_marks
            ? round($attempt->score / $attempt->total_marks * 100, 1)
            : 0.0;

        $answers = $attempt->answers()->with(['question.options', 'option'])->get()->keyBy('question_id');

        $questions = $content->questions()->with('options')->get()->map(function ($question) use ($answers) {
            $answer = $answers->get($question->id);
            $correct = $question->options->firstWhere('is_correct', true);

            return [
                'id' => $question->id,
                'body' => $question->body,
                'marks' => $question->pivot->marks ?? $question->marks,
                'your_option_id' => $answer?->question_option_id,
                'correct_option_id' => $correct?->id,
                'is_correct' => (bool) $answer?->is_correct,
                'options' => $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'body' => $option->body,
                    'is_correct' => $option->is_correct,
                ])->values(),
            ];
        })->values();

        return array_merge($base, [
            'score' => $attempt->score,
            'total_marks' => $attempt->total_marks,
            'percentage' => $percentage,
            'passed' => $percentage >= $passMark,
            'questions' => $questions,
        ]);
    }

    /**
     * Exam meta derived from the content item and its attached questions.
     *
     * @return array<string, mixed>
     */
    private function examMeta(CourseContent $content): array
    {
        $payload = $content->payload ?? [];

        return [
            'id' => $content->id,
            'course_id' => $content->course_id,
            'title' => $content->title,
            'duration_minutes' => $payload['duration_minutes'] ?? null,
            'pass_mark' => (int) ($payload['pass_mark'] ?? 40),
            'total_marks' => (int) $content->questions()->sum(DB::raw('coalesce(content_question.marks, questions.marks)')),
            'question_count' => $content->questions()->count(),
            'start_time' => $this->payloadDate($content, 'start_time')?->toIso8601String(),
            'end_time' => $this->payloadDate($content, 'end_time')?->toIso8601String(),
            'result_publish_time' => $this->payloadDate($content, 'result_publish_time')?->toIso8601String(),
            'is_open' => ! $this->isBeforeWindow($content) && ! $this->isAfterWindow($content),
        ];
    }

    /**
     * Questions for taking the exam — options carry no `is_correct` flag.
     *
     * @return array<int, array<string, mixed>>
     */
    private function questionsForTaking(CourseContent $content): array
    {
        return $content->questions()->with('options')->get()->map(fn ($question) => [
            'id' => $question->id,
            'body' => $question->body,
            'marks' => $question->pivot->marks ?? $question->marks,
            'options' => $question->options->map(fn ($option) => [
                'id' => $option->id,
                'body' => $option->body,
            ])->values(),
        ])->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptSummary(ExamAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'status' => $attempt->status->value,
            'submitted' => $attempt->isSubmitted(),
            'started_at' => $attempt->started_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'time_taken_seconds' => $attempt->time_taken_seconds,
        ];
    }

    /**
     * The authenticated user's attempt for this exam, if any.
     */
    private function attemptFor(Request $request, CourseContent $content): ?ExamAttempt
    {
        return $content->attempts()->where('user_id', $request->user()->id)->first();
    }

    private function resultsPublished(CourseContent $content): bool
    {
        $publishAt = $this->payloadDate($content, 'result_publish_time');

        return $publishAt === null || $publishAt->isPast();
    }

    private function isBeforeWindow(CourseContent $content): bool
    {
        $start = $this->payloadDate($content, 'start_time');

        return $start !== null && $start->isFuture();
    }

    private function isAfterWindow(CourseContent $content): bool
    {
        $end = $this->payloadDate($content, 'end_time');

        return $end !== null && $end->isPast();
    }

    private function payloadDate(CourseContent $content, string $key): ?Carbon
    {
        $value = $content->payload[$key] ?? null;

        return $value ? Carbon::parse($value) : null;
    }

    /**
     * Guard: content must belong to the course and be an exam.
     */
    private function ensureExamContent(Course $course, CourseContent $content): void
    {
        abort_unless($content->course_id === $course->id, 404);
        abort_unless($content->isExam(), 404);
    }
}
