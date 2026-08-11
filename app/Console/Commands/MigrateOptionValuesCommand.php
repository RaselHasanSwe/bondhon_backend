<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateOptionValuesCommand extends Command
{
    protected $signature = 'options:migrate-values
        {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Remap stored profile/user values to updated option keys';

    /**
     * @var array<string, array<string, string>>
     */
    private array $maps = [
        'profiles.marital_status' => [
            'awaiting_divorce' => 'separated',
        ],
        'users.profile_created_by' => [
            'friend' => 'relative_and_friends',
            'relative' => 'relative_and_friends',
            'other' => 'relative_and_friends',
        ],
        'education_careers.highest_education' => [
            'diploma' => 'college_associates',
            'phd' => 'doctorate',
        ],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        foreach ($this->maps as $tableColumn => $mapping) {
            [$table, $column] = explode('.', $tableColumn);

            foreach ($mapping as $from => $to) {
                $count = DB::table($table)->where($column, $from)->count();

                if ($count === 0) {
                    continue;
                }

                $this->line(($dryRun ? '[dry-run] ' : '')."{$table}.{$column}: {$from} → {$to} ({$count} rows)");

                if (! $dryRun) {
                    DB::table($table)->where($column, $from)->update([$column => $to]);
                }

                $total += $count;
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Value migration complete. {$total} row(s) affected.");

        return self::SUCCESS;
    }
}
