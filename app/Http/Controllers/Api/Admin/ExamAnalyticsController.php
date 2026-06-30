<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ExamAttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamAttemptResource;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\ExamAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExamAnalyticsController extends Controller
{
    /**
     * Pass mark as a percentage of total marks.
     */
    private const PASS_PERCENTAGE = 50;

    /**
     * List every attempt on an exam — who attended, who submitted, score and time taken.
     */
    public function attempts(Course $course, CourseContent $content): AnonymousResourceCollection
    {
        $this->ensureExamContent($course, $content);

        $attempts = $content->attempts()
            ->with('user')
            ->latest('started_at')
            ->get();

        return ExamAttemptResource::collection($attempts);
    }

    /**
     * Aggregate analysis for an exam: participation, scores and per-question accuracy.
     */
    public function analysis(Course $course, CourseContent $content): JsonResponse
    {
        $this->ensureExamContent($course, $content);

        $attempts = $content->attempts()->get();
        $submitted = $attempts->where('status', ExamAttemptStatus::Submitted);

        $percentages = $submitted
            ->filter(fn ($attempt) => $attempt->total_marks > 0)
            ->map(fn ($attempt) => $attempt->score / $attempt->total_marks * 100);

        $passed = $percentages->filter(fn ($percentage) => $percentage >= self::PASS_PERCENTAGE)->count();

        return response()->json([
            'data' => [
                'participation' => [
                    'attended' => $attempts->count(),
                    'submitted' => $submitted->count(),
                    'in_progress' => $attempts->where('status', ExamAttemptStatus::InProgress)->count(),
                ],
                'scores' => [
                    'average_percentage' => $percentages->isNotEmpty() ? round($percentages->avg(), 1) : null,
                    'highest_score' => $submitted->max('score'),
                    'lowest_score' => $submitted->min('score'),
                    'pass_percentage' => self::PASS_PERCENTAGE,
                    'pass_rate' => $submitted->isNotEmpty() ? round($passed / $submitted->count() * 100, 1) : null,
                    'passed' => $passed,
                    'failed' => $submitted->count() - $passed,
                ],
                'timing' => [
                    'average_seconds' => $submitted->whereNotNull('time_taken_seconds')->isNotEmpty()
                        ? (int) round($submitted->avg('time_taken_seconds'))
                        : null,
                ],
                'questions' => $this->questionBreakdown($content),
            ],
        ]);
    }

    /**
     * Per-question correctness across all submitted answers.
     *
     * @return array<int, array<string, mixed>>
     */
    private function questionBreakdown(CourseContent $content): array
    {
        return $content->questions()->get()->map(function ($question) use ($content) {
            // Answers recorded for this question within submitted attempts of this exam.
            $base = ExamAnswer::query()
                ->where('question_id', $question->id)
                ->whereHas('attempt', fn ($query) => $query
                    ->where('course_content_id', $content->id)
                    ->where('status', ExamAttemptStatus::Submitted));

            $answered = (clone $base)->count();
            $correct = (clone $base)->where('is_correct', true)->count();

            return [
                'id' => $question->id,
                'body' => $question->body,
                'answered' => $answered,
                'correct' => $correct,
                'correct_rate' => $answered > 0 ? round($correct / $answered * 100, 1) : null,
            ];
        })->values()->all();
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
