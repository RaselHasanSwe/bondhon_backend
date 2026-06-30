<?php

namespace App\Console\Commands;

use App\Jobs\SendDailyMatchDigest;
use Carbon\Carbon;
use Illuminate\Console\Command;


/*
# Run instantly (queued — needs queue:work)
php artisan matches:run-digest

# Run instantly in this terminal (best for manual/testing)
php artisan matches:run-digest --sync

# Only calculate & store scores, no notifications
php artisan matches:run-digest --sync --no-notify

# Calculate + notify, but no email
php artisan matches:run-digest --sync --notify --no-email

# Calculate + notify + force email
php artisan matches:run-digest --sync --notify --email

# Force another batch of daily match scores (testing)
php artisan matches:run-digest --sync --no-notify --force
php artisan matches:run-digest --no-notify --no-email
**/

class RunMatchDigestCommand extends Command
{



    protected $signature = 'matches:run-digest
        {--at= : Simulate run datetime (Y-m-d or Y-m-d H:i:s)}
        {--notify : Send in-app notifications to qualifying paid users}
        {--no-notify : Calculate and store scores only; skip notifications}
        {--email : Force email digest when notifying}
        {--no-email : Never send email digest}
        {--force : Ignore daily score creation limit and create another batch}
        {--sync : Run the job immediately in this process (no queue worker)}';

    protected $description = 'Calculate match scores for all users and optionally send match digest notifications';

    public function handle(): int
    {
        if ($this->option('notify') && $this->option('no-notify')) {
            $this->error('Use either --notify or --no-notify, not both.');

            return self::FAILURE;
        }

        if ($this->option('email') && $this->option('no-email')) {
            $this->error('Use either --email or --no-email, not both.');

            return self::FAILURE;
        }

        $at = $this->option('at');
        if ($at !== null && $at !== '') {
            try {
                Carbon::parse($at);
            } catch (\Throwable) {
                $this->error('Invalid --at value. Use Y-m-d or Y-m-d H:i:s (e.g. 2026-06-30 or "2026-06-30 08:00:00").');

                return self::FAILURE;
            }
        } else {
            $at = null;
        }

        $sendNotifications = $this->option('no-notify')
            ? false
            : ($this->option('notify') ? true : null);

        $sendEmails = $this->option('no-email')
            ? false
            : ($this->option('email') ? true : null);

        $sync   = (bool) $this->option('sync');
        $count  = SendDailyMatchDigest::dispatchBatches(
            runAt: $at,
            sendNotifications: $sendNotifications,
            sendEmails: $sendEmails,
            force: (bool) $this->option('force'),
            sync: $sync,
        );

        if ($count === 0) {
            $this->warn('No eligible users found (active, not banned, email verified).');

            return self::SUCCESS;
        }

        if ($sync) {
            $this->info("Match digest completed — {$count} batch(es), "
                . \App\Jobs\ProcessMatchDigestBatch::USERS_PER_JOB . ' users per batch.');

            return self::SUCCESS;
        }

        $this->info("Dispatched {$count} batch job(s) to the queue ("
            . \App\Jobs\ProcessMatchDigestBatch::USERS_PER_JOB . ' users each).');
        $this->line('Run `php artisan queue:work` to process them.');

        return self::SUCCESS;
    }
}
