<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdvertisementRequest;
use App\Http\Requests\UpdateAdvertisementRequest;
use App\Http\Resources\AdvertisementResource;
use App\Models\Advertisement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class AdvertisementController extends Controller
{
    /**
     * List every advertisement (active or not) for the admin.
     */
    public function index(): AnonymousResourceCollection
    {
        $ads = Advertisement::query()->orderBy('position')->latest()->get();

        return AdvertisementResource::collection($ads);
    }

    /**
     * Create a new advertisement.
     */
    public function store(StoreAdvertisementRequest $request): JsonResponse
    {
        $ad = Advertisement::create($request->validated());

        return (new AdvertisementResource($ad))->response()->setStatusCode(201);
    }

    /**
     * Show a single advertisement.
     */
    public function show(Advertisement $advertisement): AdvertisementResource
    {
        return new AdvertisementResource($advertisement);
    }

    /**
     * Update an existing advertisement.
     */
    public function update(UpdateAdvertisementRequest $request, Advertisement $advertisement): AdvertisementResource
    {
        $data = $request->validated();

        // Remove the previous image from S3 when it is replaced or cleared.
        if (array_key_exists('image_path', $data) && $advertisement->image_path && $data['image_path'] !== $advertisement->image_path) {
            Storage::disk(config('filesystems.uploads'))->delete($advertisement->image_path);
        }

        $advertisement->update($data);

        return new AdvertisementResource($advertisement);
    }

    /**
     * Delete an advertisement (and its image from S3).
     */
    public function destroy(Advertisement $advertisement): JsonResponse
    {
        if ($advertisement->image_path) {
            Storage::disk(config('filesystems.uploads'))->delete($advertisement->image_path);
        }

        $advertisement->delete();

        return response()->json(['message' => 'Advertisement deleted.']);
    }
}
