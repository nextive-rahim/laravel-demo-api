<?php

namespace App\Http\Resources;

use App\Models\FreeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FreeResource
 */
class FreeResourceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->title,
            'description' => $this->description,
            'thumbnail_path' => $this->thumbnail_path,
            'thumbnail_url' => $this->thumbnail_url,
            'file_url' => $this->file_url,
            'is_published' => $this->is_published,
            'position' => $this->position,
            'created_at' => $this->created_at,
        ];
    }
}
