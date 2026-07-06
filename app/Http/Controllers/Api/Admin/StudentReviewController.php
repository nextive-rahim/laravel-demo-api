<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentReviewRequest;
use App\Http\Requests\UpdateStudentReviewRequest;
use App\Http\Resources\StudentReviewResource;
use App\Models\StudentReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class StudentReviewController extends Controller
{
    /**
     * List every student review (published or not) for the admin.
     */
    public function index(): AnonymousResourceCollection
    {
        $reviews = StudentReview::query()->orderBy('position')->latest()->get();

        return StudentReviewResource::collection($reviews);
    }

    /**
     * Create a new student review.
     */
    public function store(StoreStudentReviewRequest $request): JsonResponse
    {
        $review = StudentReview::create($request->validated());

        return (new StudentReviewResource($review))->response()->setStatusCode(201);
    }

    /**
     * Show a single student review.
     */
    public function show(StudentReview $studentReview): StudentReviewResource
    {
        return new StudentReviewResource($studentReview);
    }

    /**
     * Update an existing student review.
     */
    public function update(UpdateStudentReviewRequest $request, StudentReview $studentReview): StudentReviewResource
    {
        $data = $request->validated();

        // Remove the previous image from S3 when it is replaced or cleared.
        if (array_key_exists('image_path', $data) && $studentReview->image_path && $data['image_path'] !== $studentReview->image_path) {
            Storage::disk(config('filesystems.uploads'))->delete($studentReview->image_path);
        }

        $studentReview->update($data);

        return new StudentReviewResource($studentReview);
    }

    /**
     * Delete a student review (and its image from S3).
     */
    public function destroy(StudentReview $studentReview): JsonResponse
    {
        if ($studentReview->image_path) {
            Storage::disk(config('filesystems.uploads'))->delete($studentReview->image_path);
        }

        $studentReview->delete();

        return response()->json(['message' => 'Review deleted.']);
    }
}
