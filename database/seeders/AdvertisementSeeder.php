<?php

namespace Database\Seeders;

use App\Enums\AdPlacement;
use App\Models\Advertisement;
use Illuminate\Database\Seeder;

class AdvertisementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Advertisement::factory()->placement(AdPlacement::Banner)->create([
            'title' => '🎉 New batch admission open — get 20% off this week!',
            'description' => 'Enroll now and start learning with a special launch discount.',
            'link_url' => 'https://example.com/admission',
        ]);

        Advertisement::factory()->placement(AdPlacement::Popup)->create([
            'title' => 'Free live class this Friday',
            'description' => 'Join our free masterclass on exam preparation. Limited seats available!',
            'link_url' => 'https://example.com/live',
        ]);

        Advertisement::factory()->placement(AdPlacement::Home)->count(2)->create();
    }
}
