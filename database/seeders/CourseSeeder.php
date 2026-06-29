<?php

namespace Database\Seeders;

use App\Enums\CourseContentType;
use App\Models\Course;
use App\Models\CourseContent;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Seed a few sample courses, each containing one item of every content type.
     */
    public function run(): void
    {
        $courses = [
            ['title' => 'Laravel for Beginners', 'description' => 'Build modern web apps with Laravel.'],
            ['title' => 'Vue 3 Essentials', 'description' => 'Reactive frontends with Vue 3 and Vite.'],
            ['title' => 'REST API Design', 'description' => 'Design clean, versioned HTTP APIs.'],
        ];

        foreach ($courses as $attributes) {
            $course = Course::firstOrCreate(
                ['title' => $attributes['title']],
                ['description' => $attributes['description'], 'is_published' => true],
            );

            if ($course->contents()->exists()) {
                continue;
            }

            $position = 1;

            foreach (CourseContentType::cases() as $type) {
                CourseContent::factory()->for($course)->ofType($type)->create([
                    'title' => ucfirst($type->value).' lesson',
                    'position' => $position++,
                ]);
            }
        }
    }
}
