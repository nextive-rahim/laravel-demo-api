<?php

namespace Database\Seeders;

use App\Enums\ResourceType;
use App\Models\FreeResource;
use Illuminate\Database\Seeder;

class FreeResourceSeeder extends Seeder
{
    public function run(): void
    {
        FreeResource::factory()->type(ResourceType::Note)->count(3)->create();
        FreeResource::factory()->type(ResourceType::Pdf)->count(3)->create();
        FreeResource::factory()->type(ResourceType::Book)->count(2)->create();
    }
}
