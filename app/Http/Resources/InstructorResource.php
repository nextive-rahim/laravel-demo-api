<?php

namespace App\Http\Resources;

use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Instructor
 */
class InstructorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'bio' => $this->bio,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'is_published' => $this->is_published,
            'position' => $this->position,
            'courses_count' => $this->whenCounted('courses'),
            'created_at' => $this->created_at,
        ];
    }
}
