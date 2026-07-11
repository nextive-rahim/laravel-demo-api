<?php

namespace App\Http\Controllers\Api;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnrollmentController extends Controller
{
    /**
     * The authenticated student's enrollments, newest first, with course data.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $enrollments = $request->user()->enrollments()
            ->with('course')
            ->latest()
            ->get();

        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Submit (or resubmit) an enrollment with manual payment details for a course.
     * Free courses are approved instantly; paid courses start as pending review.
     */
    public function store(StoreEnrollmentRequest $request, Course $course): JsonResponse
    {
        if (! $course->is_published) {
            throw new NotFoundHttpException;
        }

        $user = $request->user();
        $existing = $course->enrollments()->where('user_id', $user->id)->first();

        // Block duplicate submissions while one is pending or already approved.
        if ($existing !== null && $existing->status !== EnrollmentStatus::Rejected) {
            abort(409, $existing->isApproved()
                ? 'You are already enrolled in this course.'
                : 'Your enrollment is already awaiting review.');
        }

        $free = $course->isFree();

        $attributes = [
            'user_id' => $user->id,
            'status' => $free ? EnrollmentStatus::Approved : EnrollmentStatus::Pending,
            'payment_method' => $free ? null : $request->validated('payment_method'),
            'sender_number' => $free ? null : $request->validated('sender_number'),
            'receiver_number' => $free ? null : $request->validated('receiver_number'),
            'transaction_id' => $free ? null : $request->validated('transaction_id'),
            'amount' => $free ? 0 : $course->effectivePrice(),
            'reviewed_at' => $free ? now() : null,
            'reviewed_by' => null,
        ];

        // Resubmit in place after a rejection, otherwise create a fresh row.
        $enrollment = $existing
            ? tap($existing)->update($attributes)
            : $course->enrollments()->create($attributes);

        return (new EnrollmentResource($enrollment->fresh()))->response()->setStatusCode(201);
    }
}
