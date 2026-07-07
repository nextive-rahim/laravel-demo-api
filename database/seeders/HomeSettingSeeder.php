<?php

namespace Database\Seeders;

use App\Models\HomeSetting;
use Illuminate\Database\Seeder;

class HomeSettingSeeder extends Seeder
{
    public function run(): void
    {
        HomeSetting::singleton()->update([
            'hero_badge' => '🚀 Learn at your own pace',
            'hero_title' => 'Learn anything.',
            'hero_highlight' => "It's possible.",
            'hero_subtitle' => 'Notes, PDFs, videos, live classes and exams — all in one beautiful place. Join Demo Website and start building skills that matter.',
            'stats' => [
                ['value' => '12k+', 'label' => 'Active students'],
                ['value' => '250+', 'label' => 'Expert lessons'],
                ['value' => '98%', 'label' => 'Success rate'],
                ['value' => '4.9', 'label' => 'Average rating'],
            ],
        ]);
    }
}
