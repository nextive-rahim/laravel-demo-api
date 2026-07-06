<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NoticeResource;
use App\Models\Notice;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NoticeController extends Controller
{
    /**
     * List published notices for the public website, newest first.
     */
    public function index(): AnonymousResourceCollection
    {
        $notices = Notice::query()
            ->where('is_published', true)
            ->latest('published_at')
            ->get();

        return NoticeResource::collection($notices);
    }

    /**
     * Show a single published notice's details.
     */
    public function show(Notice $notice): NoticeResource
    {
        if (! $notice->is_published) {
            throw new NotFoundHttpException;
        }

        return new NoticeResource($notice);
    }
}
