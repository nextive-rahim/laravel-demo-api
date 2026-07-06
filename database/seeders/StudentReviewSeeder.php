<?php

namespace Database\Seeders;

use App\Models\StudentReview;
use Illuminate\Database\Seeder;

class StudentReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StudentReview::factory()->count(6)->create();
        StudentReview::factory()->draft()->count(2)->create();
    }
}
