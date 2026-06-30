<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subcategory_id' => Subcategory::factory(),
            'body' => fake()->sentence().'?',
            'marks' => 1,
        ];
    }

    /**
     * Give the question four options with the given index marked correct.
     */
    public function withOptions(int $correctIndex = 0): static
    {
        return $this->afterCreating(function (Question $question) use ($correctIndex) {
            foreach (range(0, 3) as $i) {
                QuestionOption::factory()->for($question)->create([
                    'body' => fake()->words(3, true),
                    'is_correct' => $i === $correctIndex,
                    'position' => $i,
                ]);
            }
        });
    }
}
