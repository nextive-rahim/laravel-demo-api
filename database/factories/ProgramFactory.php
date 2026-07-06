<?php

namespace Database\Factories;

use App\Enums\ProgramCategory;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(ProgramCategory::cases()),
            'title' => fake()->unique()->sentence(3),
            'subtitle' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'thumbnail_path' => null,
            'price' => fake()->randomElement([2000, 3000, 5000, 8000]),
            'discount_price' => fake()->boolean(60) ? fake()->randomElement([1500, 2500, 4000]) : null,
            'is_published' => true,
            'position' => 0,
        ];
    }

    public function category(ProgramCategory $category): static
    {
        return $this->state(fn (array $attributes) => ['category' => $category]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }
}
