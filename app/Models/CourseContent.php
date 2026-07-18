<?php

namespace App\Models;

use App\Enums\CourseContentType;
use Database\Factories\CourseContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable(['course_id', 'course_section_id', 'type', 'title', 'is_active', 'is_paid', 'available_from', 'position', 'payload'])]
class CourseContent extends Model
{
    /** @use HasFactory<CourseContentFactory> */
    use HasFactory;

    /**
     * Adding or removing content changes each course's `contents_count` on the
     * public catalog, so flush that cache alongside the course's own events.
     */
    protected static function booted(): void
    {
        $flush = static fn (): bool => Cache::forget(Course::PUBLIC_CACHE_KEY);

        static::saved($flush);
        static::deleted($flush);
    }

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
     * The section this content item belongs to (null for ungrouped content).
     *
     * @return BelongsTo<CourseSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    /**
     * The store questions attached to this content item (when it is an exam).
     *
     * @return BelongsToMany<Question, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'content_question')
            ->withPivot(['position', 'marks'])
            ->withTimestamps()
            ->orderBy('content_question.position');
    }

    /**
     * The exam attempts users have made on this content item.
     *
     * @return HasMany<ExamAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Whether this content item is an exam.
     */
    public function isExam(): bool
    {
        return $this->type === CourseContentType::Exam;
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
            'is_active' => 'boolean',
            'is_paid' => 'boolean',
            'available_from' => 'datetime',
        ];
    }
}
