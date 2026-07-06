<?php

namespace App\Http\Resources;

use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Advertisement
 */
class AdvertisementResource extends JsonResource
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
            'placement' => $this->placement->value,
            'title' => $this->title,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'link_url' => $this->link_url,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'position' => $this->position,
            'created_at' => $this->created_at,
        ];
    }
}
