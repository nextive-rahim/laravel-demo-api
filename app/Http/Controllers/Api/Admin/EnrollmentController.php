<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rules\Enum;

class EnrollmentController extends Controller
{
    /**
     * List enrollments for the admin, newest first, optionally filtered by status.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', new Enum(EnrollmentStatus::class)],
        ]);

        $enrollments = Enrollment::query()
            ->with(['user', 'course'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->get();

        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Approve an enrollment, granting the student access to the course.
     */
    public function approve(Request $request, Enrollment $enrollment): EnrollmentResource
    {
        $enrollment->update([
            'status' => EnrollmentStatus::Approved,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return new EnrollmentResource($enrollment->load(['user', 'course']));
    }

    /**
     * Reject (or cancel) an enrollment, revoking course access.
     */
    public function reject(Request $request, Enrollment $enrollment): EnrollmentResource
    {
        $enrollment->update([
            'status' => EnrollmentStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return new EnrollmentResource($enrollment->load(['user', 'course']));
    }
}
