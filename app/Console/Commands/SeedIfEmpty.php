<?php

namespace App\Console\Commands;

use App\Models\Course;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:seed-if-empty')]
#[Description('Seed the database only when it has no courses yet — safe to run on every deploy.')]
class SeedIfEmpty extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (Course::query()->exists()) {
            $this->info('Database already seeded; skipping.');

            return self::SUCCESS;
        }

        $this->info('Empty database detected — running seeders.');
        $this->call('db:seed', ['--force' => true]);

        return self::SUCCESS;
    }
}
