<?php

namespace App\Console\Commands;

use App\Services\OptionsDataLoader;
use Illuminate\Console\Command;
use Throwable;

class SyncOptionsCommand extends Command
{
    protected $signature = 'options:sync
        {--group= : Sync a single flat option group by name}
        {--only= : Sync a specific data file (e.g. locations/bangladesh.json)}
        {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Upsert select_options from JSON data files (safe for production)';

    public function handle(OptionsDataLoader $loader): int
    {
        try {
            $stats = $loader->syncAll(
                group: $this->option('group'),
                only: $this->option('only'),
                dryRun: (bool) $this->option('dry-run'),
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $prefix = $this->option('dry-run') ? '[dry-run] ' : '';

        $this->info("{$prefix}Options sync complete.");
        $this->line("  Created: {$stats['created']}");
        $this->line("  Updated: {$stats['updated']}");
        $this->line("  Deactivated: {$stats['deactivated']}");

        return self::SUCCESS;
    }
}
