<?php

namespace App\Http\Resources;

use App\Models\Course;
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
            'contents_count' => $this->whenCounted('contents'),
            'contents' => CourseContentResource::collection($this->whenLoaded('contents')),
            // Access + the current student's enrollment, only on the detail endpoint.
            'is_enrolled' => $this->when($this->relationLoaded('contents'), fn (): bool => $request->attributes->get('course_unlocked') === true),
            'enrollment' => $this->when($this->relationLoaded('contents'), fn () => $this->currentEnrollment($request)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * The authenticated student's enrollment for this course, if any.
     *
     * @return array{status: string, amount: int|null, created_at: mixed}|null
     */
    private function currentEnrollment(Request $request): ?array
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if ($user === null) {
            return null;
        }

        $enrollment = $this->enrollments()->where('user_id', $user->id)->first();

        if ($enrollment === null) {
            return null;
        }

        return [
            'status' => $enrollment->status->value,
            'amount' => $enrollment->amount,
            'created_at' => $enrollment->created_at,
        ];
    }
}
