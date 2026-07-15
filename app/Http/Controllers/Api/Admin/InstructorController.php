<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstructorRequest;
use App\Http\Requests\UpdateInstructorRequest;
use App\Http\Resources\InstructorResource;
use App\Models\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class InstructorController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return InstructorResource::collection(
            Instructor::query()->withCount('courses')->orderBy('position')->latest()->get()
        );
    }

    public function store(StoreInstructorRequest $request): JsonResponse
    {
        $instructor = Instructor::create($request->validated());

        return (new InstructorResource($instructor))->response()->setStatusCode(201);
    }

    public function show(Instructor $instructor): InstructorResource
    {
        return new InstructorResource($instructor->loadCount('courses'));
    }

    public function update(UpdateInstructorRequest $request, Instructor $instructor): InstructorResource
    {
        $data = $request->validated();

        if (array_key_exists('image_path', $data) && $instructor->image_path && $data['image_path'] !== $instructor->image_path) {
            Storage::disk(config('filesystems.uploads'))->delete($instructor->image_path);
        }

        $instructor->update($data);

        return new InstructorResource($instructor->loadCount('courses'));
    }

    public function destroy(Instructor $instructor): JsonResponse
    {
        if ($instructor->image_path) {
            Storage::disk(config('filesystems.uploads'))->delete($instructor->image_path);
        }

        $instructor->delete();

        return response()->json(['message' => 'Instructor deleted.']);
    }
}
