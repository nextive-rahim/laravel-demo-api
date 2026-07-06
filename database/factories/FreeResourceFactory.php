<?php

namespace Database\Factories;

use App\Enums\ResourceType;
use App\Models\FreeResource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FreeResource>
 */
class FreeResourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(ResourceType::cases()),
            'title' => fake()->unique()->sentence(4),
            'description' => fake()->sentence(12),
            'thumbnail_path' => null,
            'file_url' => 'https://example.com/'.fake()->slug().'.pdf',
            'is_published' => true,
            'position' => 0,
        ];
    }

    public function type(ResourceType $type): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }
}
