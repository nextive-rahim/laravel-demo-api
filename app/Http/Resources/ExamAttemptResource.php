<?php

namespace App\Http\Resources;

use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ExamAttempt
 */
class ExamAttemptResource extends JsonResource
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
            'status' => $this->status->value,
            'user' => new UserResource($this->whenLoaded('user')),
            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'time_taken_seconds' => $this->time_taken_seconds,
            'score' => $this->score,
            'total_marks' => $this->total_marks,
            'percentage' => $this->total_marks
                ? round($this->score / $this->total_marks * 100, 1)
                : null,
        ];
    }
}
