<?php

namespace App\Jobs;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrator for scheduled cron: splits users into batch jobs (10 users each).
 */
class SendDailyMatchDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly ?string $runAt = null,
        public readonly ?bool $sendNotifications = null,
        public readonly ?bool $sendEmails = null,
        public readonly bool $force = false,
        public readonly bool $sync = false,
    ) {}

    public function handle(): void
    {
        self::dispatchBatches(
            runAt: $this->runAt,
            sendNotifications: $this->sendNotifications,
            sendEmails: $this->sendEmails,
            force: $this->force,
            sync: $this->sync,
        );
    }

    /**
     * Queue (or run) one ProcessMatchDigestBatch job per USERS_PER_JOB users.
     *
     * @return int Number of batch jobs dispatched.
     */
    public static function dispatchBatches(
        ?string $runAt = null,
        ?bool $sendNotifications = null,
        ?bool $sendEmails = null,
        bool $force = false,
        bool $sync = false,
    ): int {
        $runAtParsed = $runAt ? Carbon::parse($runAt) : now();

        $userIds = User::query()
            ->where('is_active', true)
            ->where('is_banned', false)
            ->whereNotNull('email_verified_at')
            ->orderBy('id')
            ->pluck('id');

        $chunks = $userIds->chunk(ProcessMatchDigestBatch::USERS_PER_JOB);
        $batch  = 0;

        Log::info('[DAILY MATCH DIGEST - Dispatch] Total users: ' . $userIds->count()
            . ' | batches: ' . $chunks->count()
            . ' | usersPerJob=' . ProcessMatchDigestBatch::USERS_PER_JOB
            . ' | runAt=' . $runAtParsed->toDateTimeString()
            . ' | force=' . ($force ? 'yes' : 'no')
            . ' | sync=' . ($sync ? 'yes' : 'no'));

        if ($chunks->isEmpty()) {
            Log::info('[DAILY MATCH DIGEST - Dispatch] No eligible users found.');

            return 0;
        }

        foreach ($chunks as $chunk) {
            $batch++;
            $ids = $chunk->values()->all();

            $job = new ProcessMatchDigestBatch(
                userIds: $ids,
                runAt: $runAt,
                sendNotifications: $sendNotifications,
                sendEmails: $sendEmails,
                force: $force,
            );

            if ($sync) {
                dispatch_sync($job);
                Log::info('[DAILY MATCH DIGEST - Batch Done] #' . $batch . ' | users: ' . implode(',', $ids));
            } else {
                dispatch($job);
                Log::info('[DAILY MATCH DIGEST - Batch Queued] #' . $batch . ' | users: ' . implode(',', $ids));
            }
        }

        Log::info('[DAILY MATCH DIGEST - Dispatch Complete] Dispatched ' . $chunks->count() . ' batch job(s).');

        return $chunks->count();
    }
}
