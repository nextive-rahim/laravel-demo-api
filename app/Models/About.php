<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['heading', 'subheading', 'body', 'mission', 'vision', 'image_path'])]
class About extends Model
{
    /**
     * Fetch the single About record, creating a blank one if it does not exist.
     *
     * The record is always treated as pre-existing so an API Resource response
     * does not auto-set a 201 status the first time the row is created.
     */
    public static function singleton(): self
    {
        $about = static::query()->firstOrCreate([]);
        $about->wasRecentlyCreated = false;

        return $about;
    }

    /**
     * The full public URL to the About image, or null when none is set.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image_path
            ? Storage::disk(config('filesystems.uploads'))->url($this->image_path)
            : null);
    }
}
