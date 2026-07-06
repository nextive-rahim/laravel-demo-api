<?php

namespace App\Http\Resources;

use App\Models\About;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin About
 */
class AboutResource extends JsonResource
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
            'heading' => $this->heading,
            'subheading' => $this->subheading,
            'body' => $this->body,
            'mission' => $this->mission,
            'vision' => $this->vision,
            'image_path' => $this->image_path,
            'image_url' => $this->image_url,
            'updated_at' => $this->updated_at,
        ];
    }
}
