<?php

namespace App\Http\Resources;

use App\Models\CourseSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CourseSection
 */
class CourseSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'parent_id' => $this->parent_id,
            'title' => $this->title,
            'is_active' => $this->is_active,
            'position' => $this->position,
            'contents_count' => $this->whenCounted('contents'),
            'contents' => CourseContentResource::collection($this->whenLoaded('contents')),
            'children' => CourseSectionResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at,
        ];
    }
}
