<?php

namespace App\Http\Resources;

use App\Enums\CourseContentType;
use App\Models\CourseContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A course content item (exam or live class) that starts today.
 *
 * @mixin CourseContent
 */
class TodaysLiveResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isExam = $this->type === CourseContentType::Exam;

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course_title' => $this->course?->title,
            'title' => $this->title,
            'type' => $this->type->value,
            'start_at' => $isExam ? ($this->payload['start_time'] ?? null) : ($this->payload['scheduled_at'] ?? null),
            'end_at' => $this->payload['end_time'] ?? null,
            'duration_minutes' => $this->payload['duration_minutes'] ?? null,
            'url' => $isExam ? null : ($this->payload['url'] ?? null),
        ];
    }
}
