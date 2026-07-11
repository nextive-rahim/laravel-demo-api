<?php

namespace App\Http\Resources;

use App\Models\CourseContent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CourseContent
 */
class CourseContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Locked only when the public course endpoint explicitly says so; admin
        // and single-content responses leave the flag unset and stay unlocked.
        $locked = $request->attributes->get('course_unlocked') === false;

        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'type' => $this->type->value,
            'title' => $this->title,
            'position' => $this->position,
            'locked' => $locked,
            'payload' => $locked ? [] : ($this->payload ?? []),
            'created_at' => $this->created_at,
        ];
    }
}
