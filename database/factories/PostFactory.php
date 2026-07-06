<?php

namespace Database\Factories;

use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'type' => fake()->randomElement(PostType::cases()),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 1000000),
            'excerpt' => fake()->sentence(12),
            'body' => fake()->paragraphs(5, true),
            'image_path' => null,
            'is_published' => true,
            'published_at' => now(),
        ];
    }

    /**
     * Indicate the post is a news article.
     */
    public function news(): static
    {
        return $this->state(fn (array $attributes) => ['type' => PostType::News]);
    }

    /**
     * Indicate the post is a blog article.
     */
    public function blog(): static
    {
        return $this->state(fn (array $attributes) => ['type' => PostType::Blog]);
    }

    /**
     * Indicate the post is a draft (unpublished).
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
