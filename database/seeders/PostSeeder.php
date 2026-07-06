<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Post::factory()->blog()->count(4)->create();
        Post::factory()->news()->count(4)->create();
        Post::factory()->draft()->count(2)->create();
    }
}
