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

        // Home-section carousel slides.
        $homeAds = [
            ['title' => 'Admission 2026 is open', 'description' => 'Secure your seat for the new batch and save 20% this week.', 'link_url' => 'https://example.com/admission'],
            ['title' => 'Free model test this week', 'description' => 'Test your preparation with a full-length free model exam.', 'link_url' => 'https://example.com/model-test'],
            ['title' => 'New video lessons added', 'description' => 'Fresh HD lessons from top instructors — now streaming.', 'link_url' => 'https://example.com/videos'],
            ['title' => 'Live class marathon', 'description' => 'Non-stop live classes all weekend. Do not miss out!', 'link_url' => 'https://example.com/marathon'],
            ['title' => 'Refer a friend, get 1 month free', 'description' => 'Invite friends and unlock premium access for free.', 'link_url' => 'https://example.com/refer'],
            ['title' => 'Premium notes bundle', 'description' => 'Complete note bundle at a limited-time launch price.', 'link_url' => 'https://example.com/notes'],
        ];

        foreach ($homeAds as $position => $ad) {
            Advertisement::factory()->placement(AdPlacement::Home)->create([
                ...$ad,
                'position' => $position,
            ]);
        }
    }
}
