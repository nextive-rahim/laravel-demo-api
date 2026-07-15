<?php

namespace App\Models;

use Database\Factories\CourseSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'parent_id', 'title', 'is_active', 'position'])]
class CourseSection extends Model
{
    /** @use HasFactory<CourseSectionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * The course this section belongs to.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * The parent section, when this is a sub-section (null for top-level sections).
     *
     * @return BelongsTo<CourseSection, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'parent_id');
    }

    /**
     * The sub-sections nested under this section, ordered for display.
     *
     * @return HasMany<CourseSection, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(CourseSection::class, 'parent_id')->orderBy('position');
    }

    /**
     * The content items inside this section, ordered for display.
     *
     * @return HasMany<CourseContent, $this>
     */
    public function contents(): HasMany
    {
        return $this->hasMany(CourseContent::class)->orderBy('position');
    }
}
