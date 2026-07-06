<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLiveCourseRequest;
use App\Http\Requests\UpdateLiveCourseRequest;
use App\Http\Resources\LiveCourseResource;
use App\Models\LiveCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class LiveCourseController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return LiveCourseResource::collection(LiveCourse::query()->latest()->get());
    }

    public function store(StoreLiveCourseRequest $request): JsonResponse
    {
        $live = LiveCourse::create($request->validated());

        return (new LiveCourseResource($live))->response()->setStatusCode(201);
    }

    public function show(LiveCourse $liveCourse): LiveCourseResource
    {
        return new LiveCourseResource($liveCourse);
    }

    public function update(UpdateLiveCourseRequest $request, LiveCourse $liveCourse): LiveCourseResource
    {
        $data = $request->validated();

        if (array_key_exists('thumbnail_path', $data) && $liveCourse->thumbnail_path && $data['thumbnail_path'] !== $liveCourse->thumbnail_path) {
            Storage::disk(config('filesystems.uploads'))->delete($liveCourse->thumbnail_path);
        }

        $liveCourse->update($data);

        return new LiveCourseResource($liveCourse);
    }

    public function destroy(LiveCourse $liveCourse): JsonResponse
    {
        if ($liveCourse->thumbnail_path) {
            Storage::disk(config('filesystems.uploads'))->delete($liveCourse->thumbnail_path);
        }

        $liveCourse->delete();

        return response()->json(['message' => 'Live course deleted.']);
    }
}
