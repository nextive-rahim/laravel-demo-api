<?php

namespace Database\Factories;

use App\Enums\CourseContentType;
use App\Models\Course;
use App\Models\CourseContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseContent>
 */
class CourseContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(CourseContentType::cases());

        return [
            'course_id' => Course::factory(),
            'type' => $type,
            'title' => fake()->sentence(3),
            'position' => fake()->numberBetween(0, 20),
            'payload' => $this->dataFor($type),
        ];
    }

    /**
     * Force the content item to a specific type with matching data.
     */
    public function ofType(CourseContentType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'payload' => $this->dataFor($type),
        ]);
    }

    /**
     * Build a realistic `data` payload for the given content type.
     *
     * @return array<string, mixed>
     */
    private function dataFor(CourseContentType $type): array
    {
        return match ($type) {
            CourseContentType::Note => ['body' => fake()->paragraphs(2, true)],
            CourseContentType::Pdf => ['url' => fake()->url().'/document.pdf'],
            CourseContentType::Exam => [
                'url' => fake()->url().'/exam',
                'duration_minutes' => fake()->numberBetween(15, 120),
                'total_marks' => fake()->numberBetween(20, 100),
            ],
            CourseContentType::Video => ['url' => 'https://youtube.com/watch?v='.fake()->lexify('???????????'), 'provider' => 'youtube'],
            CourseContentType::Live => ['url' => fake()->url().'/live', 'scheduled_at' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d H:i:s')],
            CourseContentType::Link => ['url' => fake()->url()],
        };
    }
}
