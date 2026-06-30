<?php

namespace App\Jobs;

use App\Models\MatchScore;
use App\Models\User;
use App\Notifications\MatchDigestNotification;
use App\Services\MatchingService;
use App\Services\SiteSettingService;
use App\Services\SubscriptionFeatureService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Processes match score creation (and optional digest notifications) for a small batch of users.
 */
class ProcessMatchDigestBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** How many users each batch job processes. */
    public const USERS_PER_JOB = 10;

    public int $tries = 1;

    private const UNLIMITED_CAP = 50;

    /**
     * @param  int[]        $userIds
     * @param  bool|null    $sendNotifications
     * @param  bool|null    $sendEmails
     */
    public function __construct(
        public readonly array $userIds,
        public readonly ?string $runAt = null,
        public readonly ?bool $sendNotifications = null,
        public readonly ?bool $sendEmails = null,
        public readonly bool $force = false,
    ) {}

    public function handle(
        MatchingService $matchingService,
        SubscriptionFeatureService $featureService,
        SiteSettingService $siteSettings,
    ): void {
        $runAt     = $this->runAt ? Carbon::parse($this->runAt) : now();
        $isMonday  = $runAt->isMonday();
        $minScore  = $siteSettings->minimumMatchScore();
        $matchDate = $runAt->toDateString();
        $notify    = $this->sendNotifications ?? true;
        $startTime = now();

        Log::info('[MATCH DIGEST BATCH - Start] Users: ' . implode(',', $this->userIds)
            . ' | runAt=' . $runAt->toDateTimeString()
            . ' | minScore=' . $minScore
            . ' | notify=' . ($notify ? 'yes' : 'no')
            . ' | force=' . ($this->force ? 'yes' : 'no'));

        $users = User::query()
            ->whereIn('id', $this->userIds)
            ->where('is_active', true)
            ->where('is_banned', false)
            ->whereNotNull('email_verified_at')
            ->orderBy('id')
            ->get();

        $pairCache           = $matchingService->loadPairKeysForUsers($this->userIds);
        $usersWithNewPairs   = [];

        foreach ($users as $user) {
            try {
                $rawLimit    = (int) $featureService->value($user, 'daily_matches');
                $createLimit = $rawLimit < 0 ? self::UNLIMITED_CAP : $rawLimit;

                $created = $matchingService->calculateAndStoreAllScores(
                    $user,
                    $runAt,
                    $createLimit,
                    $this->force,
                    $pairCache,
                    $usersWithNewPairs,
                );

                Log::info('[MATCH DIGEST BATCH - User] User ID: ' . $user->id . ' | newScores=' . $created);

                if (! $notify || ! $featureService->hasPaidSubscription($user) || $rawLimit === 0) {
                    continue;
                }

                if (! isset($usersWithNewPairs[$user->id])) {
                    continue;
                }

                $topMatchScores = MatchScore::with([
                        'user.profile',
                        'user.religiousDetail',
                        'user.educationCareer',
                        'user.photos' => fn ($q) => $q->where('is_approved', true)->where('is_private', false),
                        'candidate.profile',
                        'candidate.religiousDetail',
                        'candidate.educationCareer',
                        'candidate.photos' => fn ($q) => $q->where('is_approved', true)->where('is_private', false),
                    ])
                    ->involvingUser($user->id)
                    ->whereDate('calculated_at', $matchDate)
                    ->where('score', '>=', $minScore)
                    ->orderByDesc('score')
                    ->limit($createLimit)
                    ->get();

                $matchSummaries = $matchingService->buildDigestMatchSummaries($user, $topMatchScores);

                if ($matchSummaries === []) {
                    continue;
                }

                $sendEmail = $this->resolveSendEmail($featureService, $user, $isMonday);

                // One notification + one email per user with all matches combined.
                $user->notify(new MatchDigestNotification($matchSummaries, $sendEmail));

                Log::info('[MATCH DIGEST BATCH - Notify] User ID: ' . $user->id
                    . ' | email=' . $user->email
                    . ' | matches=' . count($matchSummaries)
                    . ' | sendEmail=' . ($sendEmail ? 'yes' : 'no')
                    . ' | names=' . implode(', ', array_column($matchSummaries, 'name')));
            } catch (\Throwable $e) {
                Log::error('[MATCH DIGEST BATCH - Error] User ID: ' . $user->id . ' | ' . $e->getMessage());
            }
        }

        $duration = now()->diffInSeconds($startTime);
        Log::info('[MATCH DIGEST BATCH - Complete] Users: ' . count($this->userIds) . ' | duration=' . $duration . 's');
    }

    private function resolveSendEmail(
        SubscriptionFeatureService $featureService,
        User $user,
        bool $isMonday,
    ): bool {
        if ($this->sendEmails === false) {
            return false;
        }

        if ($this->sendEmails === true) {
            return true;
        }

        $digestFrequency = (string) $featureService->value($user, 'email_digest_frequency');

        return match ($digestFrequency) {
            'daily'  => true,
            'weekly' => $isMonday,
            default  => false,
        };
    }
}
