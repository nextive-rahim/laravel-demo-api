<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseContentResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourseController extends Controller
{
    /**
     * List published courses for the public website. The signed-in student's own
     * enrollments are eager-loaded so each card can show its access state.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->currentUser($request);

        $courses = Course::query()
            ->where('is_published', true)
            ->withCount('contents')
            ->when($user !== null, fn ($query) => $query->with([
                'enrollments' => fn ($enrollments) => $enrollments->where('user_id', $user->id),
            ]))
            ->latest()
            ->get();

        return CourseResource::collection($courses);
    }

    /**
     * Show a single published course. Content payloads are only unlocked for
     * students with an approved enrollment; everyone else sees a curriculum
     * preview (titles + types) alongside their own enrollment status.
     */
    public function show(Request $request, Course $course): CourseResource
    {
        if (! $course->is_published) {
            throw new NotFoundHttpException;
        }

        $course->load('contents');

        // Signal to the resources whether locked payloads may be exposed.
        $request->attributes->set('course_unlocked', $course->isAccessibleBy($this->currentUser($request)));

        return new CourseResource($course);
    }

    /**
     * Show a single content item's data — requires an approved enrollment.
     */
    public function content(Request $request, Course $course, CourseContent $content): CourseContentResource
    {
        if (! $course->is_published || $content->course_id !== $course->id) {
            throw new NotFoundHttpException;
        }

        abort_unless($course->isAccessibleBy($this->currentUser($request)), 403, 'Enroll in this course to access its lessons.');

        $request->attributes->set('course_unlocked', true);

        return new CourseContentResource($content);
    }

    /**
     * Resolve the user from the bearer token even on these public routes
     * (they carry no auth middleware, so we authenticate manually).
     */
    private function currentUser(Request $request): ?User
    {
        return $request->user() ?? auth('sanctum')->user();
    }
}
