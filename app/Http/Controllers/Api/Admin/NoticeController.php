<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoticeRequest;
use App\Http\Requests\UpdateNoticeRequest;
use App\Http\Resources\NoticeResource;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class NoticeController extends Controller
{
    /**
     * List every notice (published or not) for the admin.
     */
    public function index(): AnonymousResourceCollection
    {
        $notices = Notice::query()->latest()->get();

        return NoticeResource::collection($notices);
    }

    /**
     * Create a new notice.
     */
    public function store(StoreNoticeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Notice::uniqueSlug($data['title']);
        $data = $this->applyPublishedAt($data);

        $notice = Notice::create($data);

        return (new NoticeResource($notice))->response()->setStatusCode(201);
    }

    /**
     * Show a single notice.
     */
    public function show(Notice $notice): NoticeResource
    {
        return new NoticeResource($notice);
    }

    /**
     * Update an existing notice.
     */
    public function update(UpdateNoticeRequest $request, Notice $notice): NoticeResource
    {
        $data = $request->validated();

        if (isset($data['title'])) {
            $data['slug'] = Notice::uniqueSlug($data['title'], $notice->id);
        }

        $data = $this->applyPublishedAt($data, $notice);

        // Remove the previous image from S3 when it is replaced or cleared.
        if (array_key_exists('image_path', $data) && $notice->image_path && $data['image_path'] !== $notice->image_path) {
            Storage::disk(config('filesystems.uploads'))->delete($notice->image_path);
        }

        $notice->update($data);

        return new NoticeResource($notice);
    }

    /**
     * Delete a notice (and its image from S3).
     */
    public function destroy(Notice $notice): JsonResponse
    {
        if ($notice->image_path) {
            Storage::disk(config('filesystems.uploads'))->delete($notice->image_path);
        }

        $notice->delete();

        return response()->json(['message' => 'Notice deleted.']);
    }

    /**
     * Stamp published_at the first time a notice becomes published.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyPublishedAt(array $data, ?Notice $notice = null): array
    {
        $willPublish = $data['is_published'] ?? $notice?->is_published ?? false;
        $alreadyPublished = $notice?->published_at !== null;

        if ($willPublish && ! $alreadyPublished) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
