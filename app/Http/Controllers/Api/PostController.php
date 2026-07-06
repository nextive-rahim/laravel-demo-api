<?php

namespace App\Http\Controllers\Api;

use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PostController extends Controller
{
    /**
     * List published blog/news posts for the public website.
     *
     * Optionally filter by `?type=blog` or `?type=news`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'type' => ['nullable', new Enum(PostType::class)],
        ]);

        $posts = Post::query()
            ->where('is_published', true)
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->latest('published_at')
            ->get();

        return PostResource::collection($posts);
    }

    /**
     * Show a single published post.
     */
    public function show(Post $post): PostResource
    {
        if (! $post->is_published) {
            throw new NotFoundHttpException;
        }

        return new PostResource($post);
    }
}
