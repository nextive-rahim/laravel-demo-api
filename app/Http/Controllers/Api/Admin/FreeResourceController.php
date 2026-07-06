<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFreeResourceRequest;
use App\Http\Requests\UpdateFreeResourceRequest;
use App\Http\Resources\FreeResourceResource;
use App\Models\FreeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class FreeResourceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return FreeResourceResource::collection(FreeResource::query()->orderBy('position')->latest()->get());
    }

    public function store(StoreFreeResourceRequest $request): JsonResponse
    {
        $resource = FreeResource::create($request->validated());

        return (new FreeResourceResource($resource))->response()->setStatusCode(201);
    }

    public function show(FreeResource $freeResource): FreeResourceResource
    {
        return new FreeResourceResource($freeResource);
    }

    public function update(UpdateFreeResourceRequest $request, FreeResource $freeResource): FreeResourceResource
    {
        $data = $request->validated();

        if (array_key_exists('thumbnail_path', $data) && $freeResource->thumbnail_path && $data['thumbnail_path'] !== $freeResource->thumbnail_path) {
            Storage::disk(config('filesystems.uploads'))->delete($freeResource->thumbnail_path);
        }

        $freeResource->update($data);

        return new FreeResourceResource($freeResource);
    }

    public function destroy(FreeResource $freeResource): JsonResponse
    {
        if ($freeResource->thumbnail_path) {
            Storage::disk(config('filesystems.uploads'))->delete($freeResource->thumbnail_path);
        }

        $freeResource->delete();

        return response()->json(['message' => 'Resource deleted.']);
    }
}
