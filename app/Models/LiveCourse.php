<?php

namespace App\Models;

use Database\Factories\LiveCourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'description', 'instructor_name', 'thumbnail_path', 'scheduled_at', 'join_url', 'is_published'])]
class LiveCourse extends Model
{
    /** @use HasFactory<LiveCourseFactory> */
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
            'scheduled_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }
}
