<?php

namespace Database\Factories;

use App\Models\StudentReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentReview>
 */
class StudentReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'institute' => fake()->randomElement(['Dhaka College', 'Notre Dame College', 'Dhaka University', 'BUET', 'Rajshahi College']),
            'roll' => (string) fake()->numberBetween(1000, 9999),
            'batch' => (string) fake()->numberBetween(2018, 2026),
            'review' => fake()->paragraph(),
            'image_path' => null,
            'video_url' => fake()->boolean(60) ? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' : null,
            'is_published' => true,
            'position' => 0,
        ];
    }

    /**
     * Indicate that the review is hidden (unpublished).
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
