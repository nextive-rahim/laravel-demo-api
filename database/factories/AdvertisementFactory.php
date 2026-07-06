<?php

namespace Database\Factories;

use App\Enums\AdPlacement;
use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Advertisement>
 */
class AdvertisementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement' => fake()->randomElement(AdPlacement::cases()),
            'title' => fake()->catchPhrase(),
            'description' => fake()->sentence(10),
            'image_path' => null,
            'link_url' => fake()->boolean(70) ? fake()->url() : null,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
            'position' => 0,
        ];
    }

    public function placement(AdPlacement $placement): static
    {
        return $this->state(fn (array $attributes) => ['placement' => $placement]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
