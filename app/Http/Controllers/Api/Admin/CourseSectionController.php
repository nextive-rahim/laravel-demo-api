<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseSectionRequest;
use App\Http\Requests\UpdateCourseSectionRequest;
use App\Http\Resources\CourseSectionResource;
use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseSectionController extends Controller
{
    /**
     * List the course's top-level sections, each with its sub-sections and content.
     */
    public function index(Course $course): AnonymousResourceCollection
    {
        return CourseSectionResource::collection(
            $course->rootSections()->with(['contents', 'children.contents'])->get()
        );
    }

    /**
     * Add a section (or sub-section, via parent_id) to the course.
     */
    public function store(StoreCourseSectionRequest $request, Course $course): JsonResponse
    {
        $data = $request->validated();
        $data['position'] ??= (int) $course->sections()
            ->where('parent_id', $data['parent_id'] ?? null)
            ->max('position') + 1;

        $section = $course->sections()->create($data);

        return (new CourseSectionResource($section->load(['contents', 'children.contents'])))->response()->setStatusCode(201);
    }

    /**
     * Rename or reorder a section.
     */
    public function update(UpdateCourseSectionRequest $request, Course $course, CourseSection $section): CourseSectionResource
    {
        $this->ensureSectionBelongsToCourse($course, $section);

        $section->update($request->validated());

        return new CourseSectionResource($section->load(['contents', 'children.contents']));
    }

    /**
     * Delete a section. Its content items are detached (kept, ungrouped), not destroyed.
     */
    public function destroy(Course $course, CourseSection $section): JsonResponse
    {
        $this->ensureSectionBelongsToCourse($course, $section);

        $section->delete();

        return response()->json(['message' => 'Section deleted.']);
    }

    /**
     * Guard against touching a section through the wrong course.
     */
    private function ensureSectionBelongsToCourse(Course $course, CourseSection $section): void
    {
        abort_unless($section->course_id === $course->id, 404);
    }
}
