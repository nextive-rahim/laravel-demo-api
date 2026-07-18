<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['hero_badge', 'hero_title', 'hero_highlight', 'hero_subtitle', 'stats'])]
class HomeSetting extends Model
{
    /**
     * Cache key holding the public home-settings payload. Busted on every
     * write so an admin edit is reflected on the website immediately.
     */
    public const PUBLIC_CACHE_KEY = 'public.home_settings.v1';

    /**
     * Flush the cached public payload whenever the record is written.
     */
    protected static function booted(): void
    {
        $flush = static fn (): bool => Cache::forget(self::PUBLIC_CACHE_KEY);

        static::saved($flush);
        static::deleted($flush);
    }

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
