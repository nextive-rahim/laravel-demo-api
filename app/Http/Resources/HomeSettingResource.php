<?php

namespace App\Http\Resources;

use App\Models\HomeSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HomeSetting
 */
class HomeSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hero_badge' => $this->hero_badge,
            'hero_title' => $this->hero_title,
            'hero_highlight' => $this->hero_highlight,
            'hero_subtitle' => $this->hero_subtitle,
            'stats' => $this->stats ?? [],
            'updated_at' => $this->updated_at,
        ];
    }
}
