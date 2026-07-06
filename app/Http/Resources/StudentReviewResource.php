<?php

namespace App\Http\Resources;

use App\Models\StudentReview;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentReview
 */
class StudentReviewResource extends JsonResource
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
            'name' => $this->name,
            'institute' => $this->institute,
            'roll' => $this->roll,
            'batch' => $this->batch,
            'review' => $this->review,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'video_url' => $this->video_url,
            'is_published' => $this->is_published,
            'position' => $this->position,
            'created_at' => $this->created_at,
        ];
    }
}
