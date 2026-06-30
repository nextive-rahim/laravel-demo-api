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
        ];
    }
}
