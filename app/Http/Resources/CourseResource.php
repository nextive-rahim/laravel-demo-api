<?php

namespace App\Http\Resources;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Course
 */
class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // The detail endpoint loads `contents`; the public list eager-loads the
        // student's own `enrollments`. Either way we can report their access
        // state without a query per course.
        $exposesEnrollment = $this->relationLoaded('contents') || $this->relationLoaded('enrollments');
        $enrollment = $exposesEnrollment ? $this->currentEnrollment($request) : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'overview' => $this->overview,
            'thumbnail_path' => $this->thumbnail_path,
            'thumbnail_url' => $this->thumbnail_url,
            'instructor_name' => $this->instructor_name,
            'instructor_title' => $this->instructor_title,
            'instructor_image_path' => $this->instructor_image_path,
            'instructor_image_url' => $this->instructor_image_url,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'effective_price' => $this->effectivePrice(),
            'is_free' => $this->isFree(),
            'rating' => $this->rating,
            'rating_count' => $this->rating_count,
            'is_published' => $this->is_published,
            'is_featured' => $this->is_featured,
            'contents_count' => $this->whenCounted('contents'),
            // Approved (paying) students, and total enrollment requests, for the admin list.
            'students_count' => $this->whenCounted('students'),
            'enrollments_count' => $this->whenCounted('enrollments'),
            'contents' => CourseContentResource::collection($this->whenLoaded('contents')),
            'sections' => CourseSectionResource::collection($this->whenLoaded('rootSections')),
            'instructors' => InstructorResource::collection($this->whenLoaded('instructors')),
            // Access + the current student's enrollment, for the website.
            'is_enrolled' => $this->when($exposesEnrollment, fn (): bool => $enrollment?->status === EnrollmentStatus::Approved),
            'enrollment' => $this->when($exposesEnrollment, fn () => $enrollment === null ? null : [
                'status' => $enrollment->status->value,
                'amount' => $enrollment->amount,
                'created_at' => $enrollment->created_at,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * The authenticated student's enrollment for this course, if any. Reads the
     * eager-loaded relation when the caller scoped it to the current user
     * (the list endpoint), and falls back to a lookup on the detail endpoint.
     */
    private function currentEnrollment(Request $request): ?Enrollment
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if ($user === null) {
            return null;
        }

        if ($this->relationLoaded('enrollments')) {
            return $this->enrollments->firstWhere('user_id', $user->id);
        }

        return $this->enrollments()->where('user_id', $user->id)->first();
    }
}
