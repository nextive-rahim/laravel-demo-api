<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'description' => fake()->paragraph(),
            'instructor_name' => fake()->name(),
            'instructor_title' => fake()->randomElement(['Senior Instructor', 'Lead Mentor', 'Subject Expert', 'Course Instructor']),
            'price' => fake()->randomElement([500, 800, 1000, 1500, 2000, 2500]),
            'discount_price' => fake()->boolean(60) ? fake()->randomElement([400, 700, 900, 1200]) : null,
            'rating' => fake()->randomFloat(1, 4.0, 5.0),
            'rating_count' => fake()->numberBetween(20, 1200),
            'is_published' => true,
        ];
    }

    /**
     * Indicate that the course is a draft (unpublished).
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
