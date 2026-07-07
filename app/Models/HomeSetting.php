<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['hero_badge', 'hero_title', 'hero_highlight', 'hero_subtitle', 'stats'])]
class HomeSetting extends Model
{
    /**
     * Fetch the single home-settings record, creating a blank one if needed.
     *
     * Treated as pre-existing so an API Resource response does not auto-set a
     * 201 status the first time the row is created.
     */
    public static function singleton(): self
    {
        $setting = static::query()->firstOrCreate([]);
        $setting->wasRecentlyCreated = false;

        return $setting;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stats' => 'array',
        ];
    }
}
