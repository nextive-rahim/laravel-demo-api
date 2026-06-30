<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseContentResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\CourseContent;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourseController extends Controller
{
    /**
     * List published courses for the public website.
     */
    public function index(): AnonymousResourceCollection
    {
        $courses = Course::query()
            ->where('is_published', true)
            ->withCount('contents')
            ->latest()
            ->get();

        return CourseResource::collection($courses);
    }

    /**
     * Show a single published course with all of its content items.
     */
    public function show(Course $course): CourseResource
    {
        if (! $course->is_published) {
            throw new NotFoundHttpException;
        }

        $course->load('contents');

        return new CourseResource($course);
    }

    /**
     * Show a single content item's data for a published course (step 3: click a content item).
     */
    public function content(Course $course, CourseContent $content): CourseContentResource
    {
        if (! $course->is_published || $content->course_id !== $course->id) {
            throw new NotFoundHttpException;
        }

        return new CourseContentResource($content);
    }
}
