<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachQuestionsRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExamQuestionController extends Controller
{
    /**
     * List the questions attached to an exam content item.
     */
    public function index(Course $course, CourseContent $content): AnonymousResourceCollection
    {
        $this->ensureExamContent($course, $content);

        return QuestionResource::collection(
            $content->questions()->with(['options', 'subcategory.category'])->get()
        );
    }

    /**
     * Attach one or more store questions to the exam, appended after existing ones.
     */
    public function store(AttachQuestionsRequest $request, Course $course, CourseContent $content): AnonymousResourceCollection
    {
        $this->ensureExamContent($course, $content);

        $position = (int) $content->questions()->max('content_question.position');

        $attach = [];
        foreach ($request->validated('question_ids') as $questionId) {
            $attach[$questionId] = ['position' => ++$position];
        }

        // syncWithoutDetaching ignores questions already attached (idempotent).
        $content->questions()->syncWithoutDetaching($attach);

        return QuestionResource::collection(
            $content->questions()->with(['options', 'subcategory.category'])->get()
        );
    }

    /**
     * Detach a question from the exam.
     */
    public function destroy(Course $course, CourseContent $content, Question $question): JsonResponse
    {
        $this->ensureExamContent($course, $content);

        $content->questions()->detach($question->id);

        return response()->json(['message' => 'Question removed from exam.']);
    }

    /**
     * Guard: the content must belong to the course and be an exam.
     */
    private function ensureExamContent(Course $course, CourseContent $content): void
    {
        abort_unless($content->course_id === $course->id, 404);
        abort_unless($content->isExam(), 422, 'This content item is not an exam.');
    }
}
