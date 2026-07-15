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
            ->withCount(['enrollments as students_count' => fn ($query) => $query->approved()])
            ->withCount('enrollments as enrollments_count')
            ->with('instructors')
            ->latest()
            ->get();

        return CourseResource::collection($courses);
    }

    /**
     * Create a new course.
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $instructorIds = $data['instructor_ids'] ?? null;
        unset($data['instructor_ids']);

        $course = Course::create($data);

        if ($instructorIds !== null) {
            $this->syncInstructors($course, $instructorIds);
        }

        return (new CourseResource($course->load('instructors')))->response()->setStatusCode(201);
    }

    /**
     * Show a single course with its content items and instructors.
     */
    public function show(Course $course): CourseResource
    {
        $course->load(['contents', 'rootSections.contents', 'rootSections.children.contents', 'instructors']);

        return new CourseResource($course);
    }

    /**
     * Update an existing course.
     */
    public function update(UpdateCourseRequest $request, Course $course): CourseResource
    {
        $data = $request->validated();
        $hasInstructors = array_key_exists('instructor_ids', $data);
        $instructorIds = $data['instructor_ids'] ?? [];
        unset($data['instructor_ids']);

        $course->update($data);

        if ($hasInstructors) {
            $this->syncInstructors($course, $instructorIds);
        }

        return new CourseResource($course->load(['contents', 'instructors']));
    }

    /**
     * Delete a course (and its content items via cascade).
     */
    public function destroy(Course $course): JsonResponse
    {
        $course->delete();

        return response()->json(['message' => 'Course deleted.']);
    }

    /**
     * Sync the course's instructors, preserving the given order as pivot position.
     *
     * @param  array<int, int>  $instructorIds
     */
    private function syncInstructors(Course $course, array $instructorIds): void
    {
        $pivot = [];
        foreach (array_values($instructorIds) as $position => $id) {
            $pivot[$id] = ['position' => $position];
        }

        $course->instructors()->sync($pivot);
    }
}
