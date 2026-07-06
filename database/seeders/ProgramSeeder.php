<?php

namespace Database\Seeders;

use App\Enums\ProgramCategory;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            [ProgramCategory::Academic, 'HSC 2026', 'Complete HSC preparation'],
            [ProgramCategory::Academic, 'HSC 2027', 'Start early, stay ahead'],
            [ProgramCategory::Academic, 'Class 9-10 (SSC)', 'Full syllabus coverage'],
            [ProgramCategory::Skills, 'Spoken English', 'Speak with confidence'],
            [ProgramCategory::Skills, 'Digital Marketing', 'Become job-ready'],
            [ProgramCategory::Admission, 'University Admission', 'Crack the admission test'],
            [ProgramCategory::Admission, 'Medical Admission', 'Targeted MCQ practice'],
            [ProgramCategory::Job, 'BCS Preparation', 'Complete BCS course'],
            [ProgramCategory::Job, 'Bank Job Prep', 'Math, English & GK'],
        ];

        foreach ($programs as $position => [$category, $title, $subtitle]) {
            Program::factory()->category($category)->create([
                'title' => $title,
                'subtitle' => $subtitle,
                'position' => $position,
            ]);
        }
    }
}
