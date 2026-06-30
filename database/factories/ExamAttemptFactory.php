<?php

namespace Database\Factories;

use App\Enums\ExamAttemptStatus;
use App\Models\CourseContent;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_content_id' => CourseContent::factory(),
            'user_id' => User::factory(),
            'status' => ExamAttemptStatus::InProgress,
            'started_at' => now(),
            'submitted_at' => null,
            'time_taken_seconds' => null,
            'score' => null,
            'total_marks' => null,
        ];
    }

    /**
     * A submitted attempt with a recorded score.
     */
    public function submitted(int $score = 1, int $totalMarks = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExamAttemptStatus::Submitted,
            'submitted_at' => now(),
            'time_taken_seconds' => fake()->numberBetween(60, 3600),
            'score' => $score,
            'total_marks' => $totalMarks,
        ]);
    }
}
