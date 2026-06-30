<?php

namespace Database\Seeders;

use App\Enums\CourseContentType;
use App\Models\Category;
use App\Models\CourseContent;
use App\Models\Question;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class ExamModuleSeeder extends Seeder
{
    /**
     * Seed the MCQ store (categories, subcategories, questions) and populate
     * the exam content items created by the CourseSeeder.
     */
    public function run(): void
    {
        $bank = [
            'General Knowledge' => [
                'Geography' => [
                    ['What is the capital of France?', ['Paris', 'London', 'Berlin', 'Madrid'], 0],
                    ['Which is the largest ocean?', ['Atlantic', 'Indian', 'Pacific', 'Arctic'], 2],
                ],
                'Science' => [
                    ['What gas do plants absorb?', ['Oxygen', 'Carbon dioxide', 'Nitrogen', 'Hydrogen'], 1],
                    ['How many planets are in the solar system?', ['7', '8', '9', '10'], 1],
                ],
            ],
            'Programming' => [
                'PHP' => [
                    ['Which keyword declares a constant?', ['var', 'const', 'let', 'define only'], 1],
                    ['What does PHP stand for?', ['Personal Home Page', 'PHP: Hypertext Preprocessor', 'Private Hypertext Page', 'Preprocessed Hyper Pages'], 1],
                ],
                'Laravel' => [
                    ['Which command starts a dev server?', ['php artisan serve', 'php run', 'artisan start', 'laravel up'], 0],
                    ['What is Eloquent?', ['A template engine', 'An ORM', 'A queue driver', 'A router'], 1],
                ],
            ],
        ];

        foreach ($bank as $categoryName => $subcategories) {
            $category = Category::firstOrCreate(
                ['slug' => str($categoryName)->slug()->toString()],
                ['name' => $categoryName],
            );

            foreach ($subcategories as $subName => $questions) {
                $subcategory = Subcategory::firstOrCreate(
                    ['category_id' => $category->id, 'slug' => str($subName)->slug()->toString()],
                    ['name' => $subName],
                );

                foreach ($questions as [$body, $options, $correctIndex]) {
                    $this->makeQuestion($subcategory, $body, $options, $correctIndex);
                }
            }
        }

        $this->populateExams();
    }

    /**
     * Create a question with its options unless it already exists.
     *
     * @param  array<int, string>  $options
     */
    private function makeQuestion(Subcategory $subcategory, string $body, array $options, int $correctIndex): void
    {
        if (Question::where('subcategory_id', $subcategory->id)->where('body', $body)->exists()) {
            return;
        }

        $question = Question::create([
            'subcategory_id' => $subcategory->id,
            'body' => $body,
            'marks' => 1,
        ]);

        foreach ($options as $position => $optionBody) {
            $question->options()->create([
                'body' => $optionBody,
                'is_correct' => $position === $correctIndex,
                'position' => $position,
            ]);
        }
    }

    /**
     * Attach a sample of questions to every exam content item and give it a
     * sensible payload (results already published so the demo is end-to-end).
     */
    private function populateExams(): void
    {
        $exams = CourseContent::where('type', CourseContentType::Exam)->get();
        $questionIds = Question::pluck('id');

        foreach ($exams as $exam) {
            if ($exam->questions()->exists()) {
                continue;
            }

            $selected = $questionIds->shuffle()->take(5)->values();
            $attach = [];
            foreach ($selected as $i => $questionId) {
                $attach[$questionId] = ['position' => $i + 1];
            }
            $exam->questions()->attach($attach);

            $exam->update([
                'payload' => array_merge($exam->payload ?? [], [
                    'duration_minutes' => 20,
                    'start_time' => now()->subDay()->toIso8601String(),
                    'end_time' => now()->addWeek()->toIso8601String(),
                    'result_publish_time' => now()->subHour()->toIso8601String(),
                ]),
            ]);
        }
    }
}
