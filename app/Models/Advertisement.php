<?php

namespace App\Models;

use App\Enums\AdPlacement;
use Database\Factories\AdvertisementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['placement', 'title', 'description', 'image_path', 'link_url', 'is_active', 'starts_at', 'ends_at', 'position'])]
class Advertisement extends Model
{
    /** @use HasFactory<AdvertisementFactory> */
    use HasFactory;

    /**
     * Limit the query to ads that are live right now: active and within their
     * optional scheduled window.
     *
     * @param  Builder<Advertisement>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * The full public URL to the advertisement image, or null when none is set.
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'placement' => AdPlacement::class,
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
