<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseContentRequest;
use App\Http\Requests\UpdateCourseContentRequest;
use App\Http\Resources\CourseContentResource;
use App\Models\Course;
use App\Models\CourseContent;
use Illuminate\Http\JsonResponse;

class CourseContentController extends Controller
{
    /**
     * Add a content item (note, pdf, exam, video, live or link) to a course.
     */
    public function store(StoreCourseContentRequest $request, Course $course): JsonResponse
    {
        $validated = $request->validated();
        $validated['position'] ??= (int) $course->contents()->max('position') + 1;

        $content = $course->contents()->create($validated);

        return (new CourseContentResource($content))->response()->setStatusCode(201);
    }

    /**
     * Update a content item belonging to a course.
     */
    public function update(UpdateCourseContentRequest $request, Course $course, CourseContent $content): CourseContentResource
    {
        $this->ensureContentBelongsToCourse($course, $content);

        $content->update($request->validated());

        return new CourseContentResource($content);
    }

    /**
     * Delete a content item from a course.
     */
    public function destroy(Course $course, CourseContent $content): JsonResponse
    {
        $this->ensureContentBelongsToCourse($course, $content);

        $content->delete();

        return response()->json(['message' => 'Content deleted.']);
    }

    /**
     * Guard against editing a content item through the wrong course.
     */
    private function ensureContentBelongsToCourse(Course $course, CourseContent $content): void
    {
        abort_unless($content->course_id === $course->id, 404);
    }
}
