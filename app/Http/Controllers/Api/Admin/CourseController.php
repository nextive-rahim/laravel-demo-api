<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    /**
     * List every course (published or not) for the admin.
     */
    public function index(): AnonymousResourceCollection
    {
        $courses = Course::query()
            ->withCount('contents')
            ->latest()
            ->get();

        return CourseResource::collection($courses);
    }

    /**
     * Create a new course.
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = Course::create($request->validated());

        return (new CourseResource($course))->response()->setStatusCode(201);
    }

    /**
     * Show a single course with its content items.
     */
    public function show(Course $course): CourseResource
    {
        $course->load('contents');

        return new CourseResource($course);
    }

    /**
     * Update an existing course.
     */
    public function update(UpdateCourseRequest $request, Course $course): CourseResource
    {
        $course->update($request->validated());

        return new CourseResource($course->load('contents'));
    }

    /**
     * Delete a course (and its content items via cascade).
     */
    public function destroy(Course $course): JsonResponse
    {
        $course->delete();

        return response()->json(['message' => 'Course deleted.']);
    }
}
