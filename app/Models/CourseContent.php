<?php

namespace App\Models;

use App\Enums\CourseContentType;
use Database\Factories\CourseContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_id', 'type', 'title', 'position', 'payload'])]
class CourseContent extends Model
{
    /** @use HasFactory<CourseContentFactory> */
    use HasFactory;

    /**
     * The course this content item belongs to.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CourseContentType::class,
            'payload' => 'array',
        ];
    }
}
