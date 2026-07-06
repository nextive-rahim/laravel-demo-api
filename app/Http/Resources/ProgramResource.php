<?php

namespace App\Http\Resources;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Program
 */
class ProgramResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category->value,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'thumbnail_path' => $this->thumbnail_path,
            'thumbnail_url' => $this->thumbnail_url,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'is_published' => $this->is_published,
            'position' => $this->position,
            'created_at' => $this->created_at,
        ];
    }
}
