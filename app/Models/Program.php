<?php

namespace App\Models;

use App\Enums\ProgramCategory;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['category', 'title', 'subtitle', 'description', 'thumbnail_path', 'price', 'discount_price', 'is_published', 'position'])]
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
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
            'category' => ProgramCategory::class,
            'price' => 'integer',
            'discount_price' => 'integer',
            'is_published' => 'boolean',
        ];
    }
}
