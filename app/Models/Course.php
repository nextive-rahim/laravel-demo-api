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
    'title', 'description', 'overview', 'thumbnail_path', 'instructor_name', 'instructor_title',
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
     * The enrollments (paid access requests) made against this course.
     *
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Whether the course is free (no payment required to enroll).
     */
    public function isFree(): bool
    {
        return $this->effectivePrice() === 0;
    }

    /**
     * The price a student actually pays: the discounted price when set,
     * otherwise the base price (0 when neither is set).
     */
    public function effectivePrice(): int
    {
        return (int) ($this->discount_price ?? $this->price ?? 0);
    }

    /**
     * Whether the given user may access this course's locked content.
     */
    public function isAccessibleBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->enrollments()
            ->where('user_id', $user->id)
            ->approved()
            ->exists();
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
