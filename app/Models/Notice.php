<?php

namespace App\Models;

use Database\Factories\NoticeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'body', 'image_path', 'is_published', 'published_at'])]
class Notice extends Model
{
    /** @use HasFactory<NoticeFactory> */
    use HasFactory;

    /**
     * The full public URL to the notice's image, or null when none is set.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image_path
            ? Storage::disk(config('filesystems.uploads'))->url($this->image_path)
            : null);
    }

    /**
     * Build a URL-friendly slug from a title that is unique across notices.
     */
    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'notice';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
