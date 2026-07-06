<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LiveCourseResource;
use App\Models\LiveCourse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LiveCourseController extends Controller
{
    /**
     * List published live courses for the public website, soonest first.
     */
    public function index(): AnonymousResourceCollection
    {
        $lives = LiveCourse::query()
            ->where('is_published', true)
            ->orderByRaw('scheduled_at is null, scheduled_at asc')
            ->get();

        return LiveCourseResource::collection($lives);
    }

    public function show(LiveCourse $liveCourse): LiveCourseResource
    {
        if (! $liveCourse->is_published) {
            throw new NotFoundHttpException;
        }

        return new LiveCourseResource($liveCourse);
    }
}
