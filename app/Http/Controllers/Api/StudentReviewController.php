<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentReviewResource;
use App\Models\StudentReview;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StudentReviewController extends Controller
{
    /**
     * List published student reviews for the public website.
     */
    public function index(): AnonymousResourceCollection
    {
        $reviews = StudentReview::query()
            ->where('is_published', true)
            ->orderBy('position')
            ->latest()
            ->get();

        return StudentReviewResource::collection($reviews);
    }

    /**
     * Show a single published student review.
     */
    public function show(StudentReview $studentReview): StudentReviewResource
    {
        if (! $studentReview->is_published) {
            throw new NotFoundHttpException;
        }

        return new StudentReviewResource($studentReview);
    }
}
