<?php

namespace Database\Factories;

use App\Models\LiveCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveCourse>
 */
class LiveCourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(4),
            'description' => fake()->paragraph(),
            'instructor_name' => fake()->name(),
            'thumbnail_path' => null,
            'scheduled_at' => fake()->dateTimeBetween('now', '+2 weeks'),
            'join_url' => 'https://meet.example.com/'.fake()->uuid(),
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }
}
