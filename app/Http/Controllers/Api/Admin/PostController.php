<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * List every post (published or not) for the admin.
     */
    public function index(): AnonymousResourceCollection
    {
        $posts = Post::query()->latest()->get();

        return PostResource::collection($posts);
    }

    /**
     * Create a new post.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Post::uniqueSlug($data['title']);
        $data = $this->applyPublishedAt($data);

        $post = Post::create($data);

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    /**
     * Show a single post.
     */
    public function show(Post $post): PostResource
    {
        return new PostResource($post);
    }

    /**
     * Update an existing post.
     */
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $data = $request->validated();

        if (isset($data['title'])) {
            $data['slug'] = Post::uniqueSlug($data['title'], $post->id);
        }

        $data = $this->applyPublishedAt($data, $post);

        // Remove the previous image from S3 when it is replaced or cleared.
        if (array_key_exists('image_path', $data) && $post->image_path && $data['image_path'] !== $post->image_path) {
            Storage::disk(config('filesystems.uploads'))->delete($post->image_path);
        }

        $post->update($data);

        return new PostResource($post);
    }

    /**
     * Delete a post (and its cover image from S3).
     */
    public function destroy(Post $post): JsonResponse
    {
        if ($post->image_path) {
            Storage::disk(config('filesystems.uploads'))->delete($post->image_path);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    /**
     * Stamp published_at the first time a post becomes published.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyPublishedAt(array $data, ?Post $post = null): array
    {
        $willPublish = $data['is_published'] ?? $post?->is_published ?? false;
        $alreadyPublished = $post?->published_at !== null;

        if ($willPublish && ! $alreadyPublished) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
