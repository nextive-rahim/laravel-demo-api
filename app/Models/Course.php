<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'title', 'description', 'thumbnail_path', 'instructor_name', 'instructor_title',
    'instructor_image_path', 'price', 'discount_price', 'rating', 'rating_count', 'is_published',
])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * The content items belonging to the course, ordered for display.
     *
     * @return HasMany<CourseContent, $this>
     */
    public function contents(): HasMany
    {
        return $this->hasMany(CourseContent::class)->orderBy('position');
    }

    /**
     * The full public URL to the course thumbnail, or null when none is set.
     *
     * @return Attribute<string|null, never>
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image('thumbnail_path'));
    }

    /**
     * The full public URL to the instructor's image, or null when none is set.
     *
     * @return Attribute<string|null, never>
     */
    protected function instructorImageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image('instructor_image_path'));
    }

    /**
     * Resolve a stored path on the uploads disk to a public URL.
     */
    private function image(string $column): ?string
    {
        return $this->{$column}
            ? Storage::disk(config('filesystems.uploads'))->url($this->{$column})
            : null;
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
            'price' => 'integer',
            'discount_price' => 'integer',
            'rating' => 'float',
            'rating_count' => 'integer',
        ];
    }
}
