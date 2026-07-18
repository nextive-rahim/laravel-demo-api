<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseContentResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourseController extends Controller
{
    /**
     * List published courses for the public website. The signed-in student's own
     * enrollments are eager-loaded so each card can show its access state.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $catalog = $this->publishedCatalog();
        $user = $this->currentUser($request);

        // Guests all see the same catalog, so serve it straight from cache.
        if ($user === null) {
            return CourseResource::collection($catalog);
        }

        // Signed-in students reuse that same cached catalog — the only per-user
        // work is one indexed lookup of their own enrollments, which we graft
        // onto clones so the shared cached models are never mutated.
        return CourseResource::collection($this->withEnrollmentsFor($catalog, $user));
    }

    /**
     * The published-course catalog shared by every visitor, cached so guests
     * (and the shared portion of signed-in requests) never touch the database.
     * Model events bust the cache on any course or content change.
     *
     * @return Collection<int, Course>
     */
    private function publishedCatalog(): Collection
    {
        return Cache::remember(
            Course::PUBLIC_CACHE_KEY,
            now()->addDay(),
            fn () => Course::query()
                ->where('is_published', true)
                ->withCount('contents')
                ->latest()
                ->get(),
        );
    }

    /**
     * Return clones of the cached courses with the given user's enrollments
     * attached, fetched in a single query keyed by course.
     *
     * @param  Collection<int, Course>  $catalog
     * @return Collection<int, Course>
     */
    private function withEnrollmentsFor(Collection $catalog, User $user): Collection
    {
        $enrollments = Enrollment::query()
            ->where('user_id', $user->id)
            ->whereIn('course_id', $catalog->modelKeys())
            ->get()
            ->groupBy('course_id');

        return $catalog->map(function (Course $course) use ($enrollments): Course {
            $clone = clone $course;
            $clone->setRelation('enrollments', $enrollments->get($course->getKey(), new Collection));

            return $clone;
        });
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
