<?php

namespace Database\Factories;

use App\Models\Instructor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Instructor>
 */
class InstructorFactory extends Factory
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
            'title' => fake()->randomElement(['Senior Instructor', 'Lecturer', 'Lead Mentor', 'Subject Expert', 'Guest Faculty']),
            'bio' => fake()->paragraph(),
            'image_path' => null,
            'is_published' => true,
            'position' => 0,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => ['is_published' => false]);
    }
}
