<?php

namespace App\Jobs;

use App\Models\MatchScore;
use App\Models\User;
use App\Notifications\MatchDigestNotification;
use App\Services\MatchingService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendDailyMatchDigest — Runs daily at midnight (00:00).
 *
 * 1. Recalculates match scores for all active users.
 * 2. Fetches top N matches per user where N comes from their subscription's
 *    `daily_matches` permission (-1 = unlimited → capped at 50).
 * 3. Sends an in-app notification to every eligible user.
 * 4. Sends the email digest only when the user's `email_digest_frequency`
 *    subscription permission allows it:
 *      - 'daily'  → every night
 *      - 'weekly' → only on Monday nights
 *      - 'none'   → in-app only, no email
 */
class SendDailyMatchDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** Absolute cap for "unlimited" plans so we don't send 1000 cards. */
    private const UNLIMITED_CAP = 50;

    public function __construct() {}

    public function handle(MatchingService $matchingService, SubscriptionFeatureService $featureService): void
    {
        $startTime  = now();
        $isMonday   = now()->isMonday(); // Weekly digest goes out on Mondays
        $totalUsers = User::where('is_active', true)->where('is_banned', false)->count();

        Log::info('[DAILY MATCH DIGEST - Start] Processing ' . $totalUsers . ' active users. isMonday=' . ($isMonday ? 'yes' : 'no'));

        User::where('is_active', true)
            ->where('is_banned', false)
            ->whereNotNull('email_verified_at')
            ->chunk(50, function ($users) use ($matchingService, $featureService, $isMonday) {
                foreach ($users as $user) {
                    try {
                        // ── 1. Recalculate match scores ──────────────────────────
                        $matchingService->calculateAndStoreAllScores($user);

                        // ── 2. Resolve daily_matches limit from subscription ─────
                        $rawLimit = (int) $featureService->value($user, 'daily_matches');
                        // 0 means the feature is disabled for this plan
                        if ($rawLimit === 0) {
                            continue;
                        }
                        // -1 means unlimited → cap at UNLIMITED_CAP
                        $limit = $rawLimit < 0 ? self::UNLIMITED_CAP : $rawLimit;

                        // ── 3. Fetch today's top matches (score ≥ 40) ────────────
                        $topMatches = MatchScore::with([
                                'candidate',
                                'candidate.profile',
                                'candidate.religiousDetail',
                                'candidate.educationCareer',
                            ])
                            ->where('user_id', $user->id)
                            ->whereDate('calculated_at', today())
                            ->where('score', '>=', 40)
                            ->orderByDesc('score')
                            ->limit($limit)
                            ->get()
                            ->all();

                        if (empty($topMatches)) {
                            continue;
                        }

                        // ── 4. Resolve email_digest_frequency from subscription ──
                        $digestFrequency = (string) $featureService->value($user, 'email_digest_frequency');

                        $sendEmail = match ($digestFrequency) {
                            'daily'  => true,
                            'weekly' => $isMonday,   // email only on Monday nights
                            default  => false,        // 'none' or unknown → no email
                        };

                        // ── 5. Send notification ────────────────────────────────
                        $user->notify(new MatchDigestNotification($topMatches, $sendEmail));

                    } catch (\Throwable $e) {
                        Log::error('[DAILY MATCH DIGEST - Error] User ID: ' . $user->id . ' | ' . $e->getMessage());
                    }
                }
            });

        $duration = now()->diffInSeconds($startTime);
        Log::info('[DAILY MATCH DIGEST - Complete] Processed ' . $totalUsers . ' users in ' . $duration . 's.');
    }
}
