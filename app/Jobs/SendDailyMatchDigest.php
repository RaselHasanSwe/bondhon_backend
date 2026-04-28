<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendDailyMatchDigest — Runs daily at 8am (cron).
 * Recalculates match scores for all active users nightly.
 */
class SendDailyMatchDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct() {}

    public function handle(MatchingService $matchingService): void
    {
        $startTime  = now();
        $totalUsers = User::where('is_active', true)->where('is_banned', false)->count();

        Log::info('[DAILY MATCH DIGEST - Start] Processing match scores for ' . $totalUsers . ' active users.');

        User::where('is_active', true)
            ->where('is_banned', false)
            ->whereNotNull('email_verified_at')
            ->chunk(50, function ($users) use ($matchingService) {
                foreach ($users as $user) {
                    try {
                        $matchingService->calculateAndStoreAllScores($user);
                    } catch (\Throwable $e) {
                        Log::error('[DAILY MATCH DIGEST - Error] User ID: ' . $user->id . ' | Error: ' . $e->getMessage());
                    }
                }
            });

        $duration = now()->diffInSeconds($startTime);
        Log::info('[DAILY MATCH DIGEST - Complete] Processed ' . $totalUsers . ' users in ' . $duration . ' seconds.');
    }
}
