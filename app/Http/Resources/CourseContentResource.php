<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CourseContent
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
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'type' => $this->type->value,
            'title' => $this->title,
            'position' => $this->position,
            'payload' => $this->payload ?? [],
            'created_at' => $this->created_at,
        ];
    }
}
