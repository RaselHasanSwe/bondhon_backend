<?php

namespace App\Jobs;

use App\Models\MatchScore;
use App\Models\User;
use App\Notifications\MatchDigestNotification;
use App\Services\MatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendDailyMatchDigest — Runs daily at 8am (cron).
 * 1. Recalculates match scores for all active users.
 * 2. Sends in-app + email notification with top 5 matches to each user.
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
                        // 1. Recalculate scores
                        $matchingService->calculateAndStoreAllScores($user);

                        // 2. Fetch today's top 5 recalculated matches (score ≥ 40)
                        $topMatches = MatchScore::with(['candidate', 'candidate.profile', 'candidate.religiousDetail', 'candidate.educationCareer'])
                            ->where('user_id', $user->id)
                            ->whereDate('calculated_at', today())
                            ->where('score', '>=', 40)
                            ->orderByDesc('score')
                            ->limit(5)
                            ->get()
                            ->all();

                        // 3. Send notification only if there are matches
                        if (! empty($topMatches)) {
                            $user->notify(new MatchDigestNotification($topMatches));
                        }
                    } catch (\Throwable $e) {
                        Log::error('[DAILY MATCH DIGEST - Error] User ID: ' . $user->id . ' | Error: ' . $e->getMessage());
                    }
                }
            });

        $duration = now()->diffInSeconds($startTime);
        Log::info('[DAILY MATCH DIGEST - Complete] Processed ' . $totalUsers . ' users in ' . $duration . ' seconds.');
    }
}


