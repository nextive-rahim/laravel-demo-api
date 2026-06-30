<?php

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Question
 */
class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Admin/authoring view: exposes the correct option via the option resource.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subcategory_id' => $this->subcategory_id,
            'subcategory' => new SubcategoryResource($this->whenLoaded('subcategory')),
            'body' => $this->body,
            'marks' => $this->marks,
            'options' => QuestionOptionResource::collection($this->whenLoaded('options')),
            'options_count' => $this->whenCounted('options'),
            'pivot' => $this->whenPivotLoaded('content_question', fn () => [
                'position' => $this->pivot->position,
                'marks' => $this->pivot->marks,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
