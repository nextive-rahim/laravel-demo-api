<?php

namespace App\Models;

use Database\Factories\StudentReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'institute', 'roll', 'batch', 'review', 'image_path', 'video_url', 'is_published', 'position'])]
class StudentReview extends Model
{
    /** @use HasFactory<StudentReviewFactory> */
    use HasFactory;

    /**
     * The full public URL to the student's image, or null when none is set.
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
            'is_published' => 'boolean',
        ];
    }
}
