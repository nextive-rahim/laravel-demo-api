<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Signature('app:sync-database-to-production {--from=mysql : Source connection} {--to=mysql_production : Destination connection} {--pretend : List what would be copied without writing}')]
#[Description('Copies content data from the local database up to the production (Aiven) database, table by table.')]
class SyncDatabaseToProduction extends Command
{
    /**
     * Tables that should never be copied: framework plumbing, ephemeral state,
     * auth tokens, and the migrations ledger (already populated on the target).
     *
     * @var list<string>
     */
    private array $skip = [
        'migrations',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
    ];

    public function handle(): int
    {
        $from = DB::connection($this->option('from'));
        $to = DB::connection($this->option('to'));
        $pretend = (bool) $this->option('pretend');

        $tables = collect($from->getSchemaBuilder()->getTableListing())
            ->map(fn (string $name): string => str_contains($name, '.') ? substr(strrchr($name, '.'), 1) : $name)
            ->reject(fn (string $name): bool => in_array($name, $this->skip, true))
            ->values();

        $this->info(sprintf('Copying %d tables from [%s] to [%s]%s.', $tables->count(), $this->option('from'), $this->option('to'), $pretend ? ' (pretend)' : ''));
        $this->newLine();

        if (! $pretend && ! $this->confirm('This writes to PRODUCTION and truncates each target table first. Continue?', false)) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        if (! $pretend) {
            $to->statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $copied = 0;

        try {
            foreach ($tables as $table) {
                $count = $from->table($table)->count();

                if ($pretend) {
                    $this->line(sprintf('  %-28s %6d rows', $table, $count));

                    continue;
                }

                $to->table($table)->truncate();

                $from->table($table)->get()
                    ->map(fn ($row): array => (array) $row)
                    ->chunk(500)
                    ->each(fn ($rows) => $to->table($table)->insert($rows->all()));

                $copied += $count;
                $this->line(sprintf('  <info>✓</info> %-28s %6d rows', $table, $count));
            }
        } catch (Throwable $e) {
            $this->error('Failed on table copy: '.$e->getMessage());

            if (! $pretend) {
                $to->statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return self::FAILURE;
        }

        if (! $pretend) {
            $to->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->newLine();
        $this->info($pretend ? 'Pretend run complete.' : sprintf('Done. Copied %d rows across %d tables.', $copied, $tables->count()));

        return self::SUCCESS;
    }
}
