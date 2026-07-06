<?php

namespace Database\Seeders;

use App\Models\LiveCourse;
use Illuminate\Database\Seeder;

class LiveCourseSeeder extends Seeder
{
    public function run(): void
    {
        LiveCourse::factory()->count(5)->create();
        LiveCourse::factory()->draft()->count(1)->create();
    }
}
