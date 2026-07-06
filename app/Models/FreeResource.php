<?php

namespace App\Models;

use App\Enums\ResourceType;
use Database\Factories\FreeResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['type', 'title', 'description', 'thumbnail_path', 'file_url', 'is_published', 'position'])]
class FreeResource extends Model
{
    /** @use HasFactory<FreeResourceFactory> */
    use HasFactory;

    /**
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->thumbnail_path
            ? Storage::disk(config('filesystems.uploads'))->url($this->thumbnail_path)
            : null);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ResourceType::class,
            'is_published' => 'boolean',
        ];
    }
}
