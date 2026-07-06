<?php

namespace Database\Seeders;

use App\Models\About;
use Illuminate\Database\Seeder;

class AboutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        About::singleton()->update([
            'heading' => 'About Demo Website',
            'subheading' => 'Empowering learners to reach their full potential.',
            'body' => "Demo Website is a complete learning platform built for students who want more than just videos.\n\nWe bring notes, PDFs, live classes, and auto-graded exams together in one beautiful place, so you can focus on what matters — learning and growing.",
            'mission' => 'To make high-quality education accessible, engaging and measurable for every learner.',
            'vision' => 'A world where anyone, anywhere can build the skills they need to succeed.',
            'image_path' => null,
        ]);
    }
}
