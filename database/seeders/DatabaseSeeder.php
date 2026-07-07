<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DefaultAdminSeeder::class,
            CourseSeeder::class,
            ExamModuleSeeder::class,
            NoticeSeeder::class,
            StudentReviewSeeder::class,
            PostSeeder::class,
            AboutSeeder::class,
            AdvertisementSeeder::class,
            FreeResourceSeeder::class,
            ProgramSeeder::class,
            HomeSettingSeeder::class,
        ]);
    }
}
