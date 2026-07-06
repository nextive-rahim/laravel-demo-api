<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ProgramController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProgramResource::collection(Program::query()->orderBy('position')->latest()->get());
    }

    public function store(StoreProgramRequest $request): JsonResponse
    {
        $program = Program::create($request->validated());

        return (new ProgramResource($program))->response()->setStatusCode(201);
    }

    public function show(Program $program): ProgramResource
    {
        return new ProgramResource($program);
    }

    public function update(UpdateProgramRequest $request, Program $program): ProgramResource
    {
        $data = $request->validated();

        if (array_key_exists('thumbnail_path', $data) && $program->thumbnail_path && $data['thumbnail_path'] !== $program->thumbnail_path) {
            Storage::disk(config('filesystems.uploads'))->delete($program->thumbnail_path);
        }

        $program->update($data);

        return new ProgramResource($program);
    }

    public function destroy(Program $program): JsonResponse
    {
        if ($program->thumbnail_path) {
            Storage::disk(config('filesystems.uploads'))->delete($program->thumbnail_path);
        }

        $program->delete();

        return response()->json(['message' => 'Program deleted.']);
    }
}
