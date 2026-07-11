<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentMethod;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'course_id', 'status', 'payment_method', 'sender_number',
    'receiver_number', 'transaction_id', 'amount', 'reviewed_at', 'reviewed_by',
])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    /**
     * The student who submitted the enrollment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The course the student wants to access.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * The admin who reviewed (approved/rejected) the enrollment, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Whether the enrollment has been approved (grants course access).
     */
    public function isApproved(): bool
    {
        return $this->status === EnrollmentStatus::Approved;
    }

    /**
     * Limit the query to approved enrollments.
     *
     * @param  Builder<Enrollment>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', EnrollmentStatus::Approved);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }
}
