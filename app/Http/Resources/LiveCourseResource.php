<?php

namespace App\Http\Resources;

use App\Models\LiveCourse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LiveCourse
 */
class LiveCourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'instructor_name' => $this->instructor_name,
            'thumbnail_path' => $this->thumbnail_path,
            'thumbnail_url' => $this->thumbnail_url,
            'scheduled_at' => $this->scheduled_at,
            'join_url' => $this->join_url,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at,
        ];
    }
}
