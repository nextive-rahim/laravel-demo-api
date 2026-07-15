<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    /**
     * Seed a handful of instructors and assign a couple to each course.
     */
    public function run(): void
    {
        $instructors = [
            ['name' => 'Ayesha Rahman', 'title' => 'Senior Laravel Instructor'],
            ['name' => 'Tanvir Ahmed', 'title' => 'Frontend Lead (Vue)'],
            ['name' => 'Nusrat Jahan', 'title' => 'API Architect'],
            ['name' => 'Rakib Hasan', 'title' => 'DevOps Mentor'],
        ];

        $created = collect($instructors)->map(fn (array $data): Instructor => Instructor::firstOrCreate(
            ['name' => $data['name']],
            $data + ['is_published' => true],
        ));

        // Give each course two instructors so the relationship is visible out of the box.
        Course::query()->get()->each(function (Course $course) use ($created): void {
            if ($course->instructors()->exists()) {
                return;
            }

            $pair = $created->random(min(2, $created->count()));
            $course->instructors()->syncWithoutDetaching(
                $pair->values()->mapWithKeys(fn (Instructor $i, int $position): array => [$i->id => ['position' => $position]])->all()
            );
        });
    }
}
