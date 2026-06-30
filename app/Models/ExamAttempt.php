<?php

namespace App\Models;

use App\Enums\ExamAttemptStatus;
use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_content_id', 'user_id', 'status', 'started_at',
    'submitted_at', 'time_taken_seconds', 'score', 'total_marks',
])]
class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    /**
     * The exam (course content item) this attempt is for.
     *
     * @return BelongsTo<CourseContent, $this>
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(CourseContent::class, 'course_content_id');
    }

    /**
     * The user who took the exam.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The per-question answers recorded for this attempt.
     *
     * @return HasMany<ExamAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }

    /**
     * Whether this attempt has been submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->status === ExamAttemptStatus::Submitted;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ExamAttemptStatus::class,
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'time_taken_seconds' => 'integer',
            'score' => 'integer',
            'total_marks' => 'integer',
        ];
    }
}
