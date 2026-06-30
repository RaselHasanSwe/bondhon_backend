<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Interest;
use App\Models\MatchScore;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * MatchingService — Phase 3
 *
 * Compatibility score (total 100 points):
 *   Religion match              → 18 pts
 *   Age within preference range → 12 pts
 *   Location match              → 12 pts
 *   Education level match       →  8 pts
 *   Income range match          →  7 pts
 *   Marital status match        →  8 pts
 *   Diet compatibility          →  6 pts
 *   Height within preference    →  5 pts
 *   Lifestyle (smoke/drink)     →  4 pts
 *   Family type & values        →  5 pts
 *   Religiousness & pray        →  4 pts
 *   Body type                   →  3 pts
 *   Working status / employed   →  3 pts
 *   Mother tongue               →  3 pts
 *   Residing status             →  2 pts
 *   ─────────────────────────────────────
 *   TOTAL                       → 100 pts
 */
class MatchingService
{
    public function __construct(
        private readonly SiteSettingService $siteSettings,
    ) {}

    /** Education level rank (higher = more educated). */
    private array $educationRank = [
        'below_ssc'    => 1,
        'ssc'          => 2,
        'hsc'          => 3,
        'diploma'      => 4,
        'bachelors'    => 5,
        'masters'      => 6,
        'phd'          => 7,
        'postdoctoral' => 8,
    ];

    /** Compatible diet pairs (bidirectional). */
    private array $compatibleDiets = [
        ['vegetarian', 'vegan'],
        ['vegetarian', 'jain'],
        ['vegan', 'jain'],
    ];

    // -----------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------

    /**
     * Calculate a compatibility score (0–100) for $user vs $candidate.
     */
    public function calculateScore(User $user, User $candidate): float
    {
        return $this->calculatePairScoreData($user, $candidate)['score'];
    }

    /**
     * Calculate and persist a pair score if it does not already exist.
     * One row per unordered pair (min user id, max user id).
     * Skips storage when score is below the site minimum threshold.
     *
     * @param  bool  $ignoreMinimum  Profile view: store and show any score (matches page still filters by admin min).
     *
     * @return MatchScore|null  The existing or newly created row; null if not stored.
     */
    public function calculateAndStoreScore(
        User $user,
        User $candidate,
        ?Carbon $calculatedAt = null,
        bool $ignoreMinimum = false,
    ): ?MatchScore {
        if ($user->id === $candidate->id) {
            return null;
        }

        $existing = MatchScore::findForPair($user->id, $candidate->id);
        if ($existing) {
            return $existing;
        }

        $this->loadRelations($user);
        $this->loadRelations($candidate);

        $minScore = $this->siteSettings->minimumMatchScore();
        $pairData = $this->calculatePairScoreData(
            $user,
            $candidate,
            skipLoad: true,
            minScore: $ignoreMinimum ? null : $minScore,
        );

        if ($pairData['score'] <= 0) {
            return null;
        }

        if (! $ignoreMinimum && $pairData['score'] < $minScore) {
            Log::info('[MATCHING - Skip] User ' . $user->id . ' | Candidate ' . $candidate->id
                . ' | Score ' . round($pairData['score'], 2) . ' | below min ' . $minScore);

            return null;
        }

        [$low, $high] = MatchScore::normalizePairIds($user->id, $candidate->id);

        $matchScore = MatchScore::create([
            'user_id'         => $low,
            'candidate_id'    => $high,
            'score'           => $pairData['score'],
            'score_breakdown' => $pairData['breakdown'],
            'calculated_at'   => $calculatedAt ?? now(),
        ]);

        Log::info('[MATCHING - Created] Pair ' . $low . ':' . $high
            . ' | Score ' . round($pairData['score'], 2));

        return $matchScore;
    }

    /**
     * Create new pair scores for eligible candidates up to the daily limit.
     * Existing pairs are never updated. Re-running the same day creates nothing
     * unless $force is true.
     *
     * @param  array<string, true>|null  $pairCache  Shared pair keys (low:high) — skips rescoring existing pairs.
     * @param  array<int, true>|null     $usersWithNewPairs  Filled with both user IDs when a new pair is stored.
     *
     * @return int Number of new scores created.
     */
    public function calculateAndStoreAllScores(
        User $user,
        ?Carbon $calculatedAt = null,
        ?int $dailyCreateLimit = null,
        bool $force = false,
        ?array &$pairCache = null,
        ?array &$usersWithNewPairs = null,
    ): int {
        $runAt    = $calculatedAt ?? now();
        $minScore = $this->siteSettings->minimumMatchScore();

        Log::info('[MATCHING - CalculateAll] Start for User ID: ' . $user->id
            . ' | minScore=' . $minScore
            . ' | dailyLimit=' . ($dailyCreateLimit ?? 'none')
            . ' | force=' . ($force ? 'yes' : 'no'));

        if ($dailyCreateLimit !== null && $dailyCreateLimit <= 0) {
            return 0;
        }

        $createdToday = $this->countScoresCreatedOnDateForUser($user->id, $runAt);
        $remaining    = $dailyCreateLimit === null
            ? PHP_INT_MAX
            : ($force ? $dailyCreateLimit : max(0, $dailyCreateLimit - $createdToday));

        if ($remaining <= 0) {
            Log::info('[MATCHING - CalculateAll] Daily limit reached for User ID: ' . $user->id);

            return 0;
        }

        $blockedIds   = Block::where('blocker_id', $user->id)->pluck('blocked_id');
        $blockedByIds = Block::where('blocked_id', $user->id)->pluck('blocker_id');
        $excludeIds   = $blockedIds->merge($blockedByIds)->push($user->id)->unique()->values();

        if ($pairCache === null) {
            $pairCache = $this->loadPairKeysForUser($user->id);
        }

        $oppositeGender = $user->gender === 'male' ? 'female' : 'male';
        $this->loadRelations($user);

        $topProspects = [];
        $scanned      = 0;
        $skippedPairs = 0;

        $candidateQuery = $this->buildFilteredCandidateQuery($user, $excludeIds, $oppositeGender);

        $candidateQuery->chunkById(50, function ($candidates) use (
            $user,
            $minScore,
            $remaining,
            &$topProspects,
            &$scanned,
            &$skippedPairs,
            &$pairCache,
        ) {
            foreach ($candidates as $candidate) {
                $scanned++;

                $pairKey = MatchScore::pairKey($user->id, $candidate->id);
                if (isset($pairCache[$pairKey])) {
                    $skippedPairs++;

                    continue;
                }

                $pairData = $this->calculatePairScoreData($user, $candidate, skipLoad: true, minScore: $minScore);

                if ($pairData['score'] < $minScore) {
                    continue;
                }

                Log::info('[MATCHING - Score] User ' . $user->id . ' | Candidate ' . $candidate->id
                    . ' | Pair ' . $pairKey . ' | Score ' . round($pairData['score'], 2));

                $this->pushTopProspect($topProspects, [
                    'candidate' => $candidate,
                    'score'     => $pairData['score'],
                    'breakdown' => $pairData['breakdown'],
                    'pairKey'   => $pairKey,
                ], $remaining);
            }

            return true;
        });

        usort($topProspects, fn ($a, $b) => $b['score'] <=> $a['score']);

        $created = 0;

        foreach ($topProspects as $prospect) {
            if ($created >= $remaining) {
                break;
            }

            try {
                if (isset($pairCache[$prospect['pairKey']])) {
                    continue;
                }

                [$low, $high] = MatchScore::normalizePairIds($user->id, $prospect['candidate']->id);

                MatchScore::create([
                    'user_id'         => $low,
                    'candidate_id'    => $high,
                    'score'           => $prospect['score'],
                    'score_breakdown' => $prospect['breakdown'],
                    'calculated_at'   => $runAt,
                ]);

                $pairCache[$prospect['pairKey']] = true;
                if ($usersWithNewPairs !== null) {
                    $usersWithNewPairs[$low]  = true;
                    $usersWithNewPairs[$high] = true;
                }
                $created++;

                Log::info('[MATCHING - Created] User ' . $user->id . ' | Candidate ' . $prospect['candidate']->id
                    . ' | Pair ' . $prospect['pairKey'] . ' | Score ' . round($prospect['score'], 2));
            } catch (\Throwable $e) {
                Log::error('[MATCHING - CalculateScore] Failed. User: ' . $user->id
                    . ' | Candidate: ' . $prospect['candidate']->id
                    . ' | Error: ' . $e->getMessage());
            }
        }

        Log::info('[MATCHING - CalculateAll] Complete for User ID: ' . $user->id
            . ' | scanned=' . $scanned
            . ' | skippedPairs=' . $skippedPairs
            . ' | created=' . $created);

        return $created;
    }

    /**
     * Build plain-array match rows for digest email / in-app notification.
     *
     * @param  iterable<MatchScore>  $matchScores
     * @return array<int, array<string, mixed>>
     */
    public function buildDigestMatchSummaries(User $viewer, iterable $matchScores): array
    {
        $frontend = rtrim(config('app.frontend_url', config('app.url')), '/');
        $summaries = [];

        foreach ($matchScores as $matchScore) {
            if (! $matchScore instanceof MatchScore) {
                continue;
            }

            $partner = $matchScore->partnerFor($viewer->id);
            if (! $partner) {
                continue;
            }

            $partner->loadMissing([
                'profile',
                'religiousDetail',
                'educationCareer',
                'photos' => fn ($q) => $q->where('is_approved', true)->where('is_private', false),
            ]);

            $profile      = $partner->profile;
            $primaryPhoto = $partner->photos?->firstWhere('is_primary', true)
                ?? $partner->photos?->first();
            $profileId    = $profile?->profile_id ?? $partner->id;

            $summaries[] = [
                'match_score_id' => $matchScore->id,
                'candidate_id'   => $partner->id,
                'name'           => $partner->name ?? 'A Member',
                'score'          => (int) round((float) $matchScore->score),
                'age'            => $profile?->dob?->age,
                'city'           => $profile?->city,
                'state'          => $profile?->state,
                'country'        => $profile?->country,
                'religion'       => $partner->religiousDetail?->religion,
                'education'      => $partner->educationCareer?->highest_education,
                'profession'     => $partner->educationCareer?->profession,
                'photo_url'      => profilePhotoUrl($primaryPhoto?->file_path),
                'profile_url'    => $frontend . '/profile/' . $profileId,
            ];
        }

        return $summaries;
    }

    /**
     * Get paginated match suggestions for a user sorted by compatibility score.
     */
    public function getSuggestions(
        User $user,
        int $perPage = 20,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        $blockedIds   = Block::where('blocker_id', $user->id)->pluck('blocked_id');
        $blockedByIds = Block::where('blocked_id', $user->id)->pluck('blocker_id');
        $excludeIds   = $blockedIds->merge($blockedByIds)->push($user->id)->unique()->values();

        $oppositeGender = $user->gender === 'male' ? 'female' : 'male';
        $minScore       = $this->siteSettings->minimumMatchScore();
        $userId         = $user->id;
        $search         = trim((string) $search);

        $query = MatchScore::with([
                'user',
                'user.profile',
                'user.religiousDetail',
                'user.educationCareer',
                'user.faceScanSession',
                'user.photos' => fn ($q) => $q->where('is_approved', true)->where('is_private', false)->where('is_primary', true),
                'candidate',
                'candidate.profile',
                'candidate.religiousDetail',
                'candidate.educationCareer',
                'candidate.faceScanSession',
                'candidate.photos' => fn ($q) => $q->where('is_approved', true)->where('is_private', false)->where('is_primary', true),
            ])
            ->involvingUser($userId)
            ->where('score', '>=', $minScore)
            ->where(function ($q) use ($userId, $oppositeGender, $excludeIds) {
                $q->where(function ($inner) use ($userId, $oppositeGender, $excludeIds) {
                    $inner->where('user_id', $userId)
                        ->whereHas('candidate', fn ($c) => $c
                            ->where('gender', $oppositeGender)
                            ->where('is_active', true)
                            ->where('is_banned', false)
                            ->whereNotNull('email_verified_at')
                            ->whereNotIn('id', $excludeIds)
                        );
                })->orWhere(function ($inner) use ($userId, $oppositeGender, $excludeIds) {
                    $inner->where('candidate_id', $userId)
                        ->whereHas('user', fn ($c) => $c
                            ->where('gender', $oppositeGender)
                            ->where('is_active', true)
                            ->where('is_banned', false)
                            ->whereNotNull('email_verified_at')
                            ->whereNotIn('id', $excludeIds)
                        );
                });
            });

        if ($dateFrom) {
            $query->whereDate('calculated_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('calculated_at', '<=', $dateTo);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($userId, $search) {
                $q->where(function ($inner) use ($userId, $search) {
                    $inner->where('user_id', $userId)
                        ->whereHas('candidate', fn ($c) => $this->applyPartnerKeywordSearch($c, $search));
                })->orWhere(function ($inner) use ($userId, $search) {
                    $inner->where('candidate_id', $userId)
                        ->whereHas('user', fn ($c) => $this->applyPartnerKeywordSearch($c, $search));
                });
            });
        }

        $paginator = $query->orderByDesc('score')->paginate($perPage);

        $paginator->getCollection()->transform(function (MatchScore $matchScore) use ($userId) {
            $partner = $matchScore->partnerFor($userId);
            if ($partner) {
                $matchScore->setRelation('candidate', $partner);
            }

            return $matchScore;
        });

        return $paginator;
    }

    /**
     * Attach stored compatibility scores to profile users for list views.
     *
     * @param  iterable<User>  $users
     */
    public function attachCompatibilityScoresToUsers(User $viewer, iterable $users): void
    {
        $collection = collect($users)->filter(fn ($user) => $user instanceof User);
        if ($collection->isEmpty()) {
            return;
        }

        $otherIds = $collection->pluck('id')->unique()->values();
        $viewerId = $viewer->id;

        $scores = MatchScore::query()
            ->involvingUser($viewerId)
            ->where(function ($q) use ($viewerId, $otherIds) {
                $q->where(function ($inner) use ($viewerId, $otherIds) {
                    $inner->where('user_id', $viewerId)
                        ->whereIn('candidate_id', $otherIds);
                })->orWhere(function ($inner) use ($viewerId, $otherIds) {
                    $inner->where('candidate_id', $viewerId)
                        ->whereIn('user_id', $otherIds);
                });
            })
            ->get();

        $scoreMap = [];
        foreach ($scores as $matchScore) {
            $partnerId = $matchScore->user_id === $viewerId
                ? $matchScore->candidate_id
                : $matchScore->user_id;
            $scoreMap[$partnerId] = $matchScore;
        }

        $collection->each(function (User $user) use ($scoreMap) {
            $matchScore = $scoreMap[$user->id] ?? null;
            if (! $matchScore) {
                return;
            }

            $user->setAttribute('compatibility_score', [
                'score'           => (float) $matchScore->score,
                'score_breakdown'   => $matchScore->score_breakdown,
                'calculated_at'     => $matchScore->calculated_at,
            ]);
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\User>  $userQuery
     */
    private function applyPartnerKeywordSearch($userQuery, string $search): void
    {
        $userQuery->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
                ->orWhereHas('profile', function ($profileQuery) use ($search) {
                    $profileQuery->where('profile_id', 'like', '%' . $search . '%')
                        ->orWhere('city', 'like', '%' . $search . '%')
                        ->orWhere('state', 'like', '%' . $search . '%')
                        ->orWhere('country', 'like', '%' . $search . '%');
                })
                ->orWhereHas('religiousDetail', function ($religionQuery) use ($search) {
                    $religionQuery->where('religion', 'like', '%' . $search . '%');
                })
                ->orWhereHas('educationCareer', function ($careerQuery) use ($search) {
                    $careerQuery->where('profession', 'like', '%' . $search . '%')
                        ->orWhere('highest_education', 'like', '%' . $search . '%');
                });
        });
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * @return array{score: float, breakdown: array<string, float>}
     */
    private function calculatePairScoreData(
        User $userA,
        User $userB,
        bool $skipLoad = false,
        ?float $minScore = null,
    ): array {
        if (! $skipLoad) {
            $this->loadRelations($userA);
            $this->loadRelations($userB);
        }

        $breakdownA = $this->buildBreakdown($userA, $userB);
        $sumA       = array_sum($breakdownA);

        if ($minScore !== null && ($sumA + 100.0) / 2 < $minScore) {
            return ['score' => 0.0, 'breakdown' => []];
        }

        $breakdownB = $this->buildBreakdown($userB, $userA);
        $sumB       = array_sum($breakdownB);
        $total      = ($sumA + $sumB) / 2;

        if ($minScore !== null && $total < $minScore) {
            return ['score' => $total, 'breakdown' => []];
        }

        $breakdown = [];
        foreach (array_keys($breakdownA) as $key) {
            $breakdown[$key] = ($breakdownA[$key] + $breakdownB[$key]) / 2;
        }

        return ['score' => min(100.0, max(0.0, $total)), 'breakdown' => $breakdown];
    }

    /**
     * @return array<string, float>
     */
    private function buildBreakdown(User $user, User $candidate): array
    {
        $pref = $user->partnerPreference;

        return [
            'religion'        => $this->scoreReligion($pref, $candidate),
            'age'             => $this->scoreAge($pref, $candidate),
            'location'        => $this->scoreLocation($user, $candidate),
            'education'       => $this->scoreEducation($pref, $candidate),
            'income'          => $this->scoreIncome($pref, $candidate),
            'marital_status'  => $this->scoreMaritalStatus($pref, $candidate),
            'diet'            => $this->scoreDiet($pref, $candidate),
            'height'          => $this->scoreHeight($pref, $candidate),
            'lifestyle'       => $this->scoreLifestyle($pref, $candidate),
            'family'          => $this->scoreFamilyPrefs($pref, $candidate),
            'religiousness'   => $this->scoreReligiousness($pref, $candidate),
            'body_type'       => $this->scoreBodyType($pref, $candidate),
            'working_status'  => $this->scoreWorkingStatus($pref, $candidate),
            'mother_tongue'   => $this->scoreMotherTongue($pref, $candidate),
            'residing_status' => $this->scoreResidingStatus($pref, $candidate),
        ];
    }

    /**
     * @return array<string, true>
     */
    public function loadPairKeysForUser(int $userId): array
    {
        $keys = [];

        MatchScore::query()
            ->involvingUser($userId)
            ->select(['user_id', 'candidate_id'])
            ->each(function (MatchScore $match) use (&$keys) {
                $keys[MatchScore::pairKey($match->user_id, $match->candidate_id)] = true;
            });

        return $keys;
    }

    /**
     * Load all pair keys touching any of the given user IDs (one query per batch).
     *
     * @param  int[]  $userIds
     * @return array<string, true>
     */
    public function loadPairKeysForUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $keys = [];

        MatchScore::query()
            ->where(function ($q) use ($userIds) {
                $q->whereIn('user_id', $userIds)->orWhereIn('candidate_id', $userIds);
            })
            ->select(['user_id', 'candidate_id'])
            ->each(function (MatchScore $match) use (&$keys) {
                $keys[MatchScore::pairKey($match->user_id, $match->candidate_id)] = true;
            });

        return $keys;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>|array<int, int>  $excludeIds
     */
    private function buildFilteredCandidateQuery(User $user, $excludeIds, string $oppositeGender)
    {
        $pref = $user->partnerPreference;

        $query = User::query()
            ->where('gender', $oppositeGender)
            ->where('is_active', true)
            ->where('is_banned', false)
            ->whereNotIn('id', $excludeIds)
            ->whereNotNull('email_verified_at')
            ->with($this->scoringRelations());

        if ($pref?->age_min) {
            $query->whereHas('profile', fn ($q) => $q->whereDate(
                'dob',
                '<=',
                now()->subYears((int) $pref->age_min)->format('Y-m-d')
            ));
        }

        if ($pref?->age_max) {
            $query->whereHas('profile', fn ($q) => $q->whereDate(
                'dob',
                '>=',
                now()->subYears((int) $pref->age_max + 1)->addDay()->format('Y-m-d')
            ));
        }

        $religions = $this->normalizedPreferenceList($pref?->religion);
        if ($religions !== []) {
            $query->whereHas('religiousDetail', fn ($q) => $q->whereIn('religion', $religions));
        }

        $maritalStatuses = $this->normalizedPreferenceList($pref?->marital_status);
        if ($maritalStatuses !== []) {
            $query->whereHas('profile', fn ($q) => $q->whereIn('marital_status', $maritalStatuses));
        }

        return $query->orderBy('id');
    }

    /**
     * @return string[]
     */
    private function normalizedPreferenceList(mixed $value): array
    {
        if (empty($value)) {
            return [];
        }

        return array_values(array_filter(is_array($value) ? $value : [$value]));
    }

    private function countScoresCreatedOnDateForUser(int $userId, Carbon $date): int
    {
        return MatchScore::query()
            ->involvingUser($userId)
            ->whereDate('calculated_at', $date->toDateString())
            ->count();
    }

    /**
     * @return string[]
     */
    private function scoringRelations(): array
    {
        return [
            'profile', 'religiousDetail', 'educationCareer',
            'lifestyle', 'familyDetail', 'partnerPreference',
        ];
    }

    /**
     * @param  array<int, array{candidate: User, score: float, breakdown: array<string, float>}>  $top
     */
    private function pushTopProspect(array &$top, array $prospect, int $limit): void
    {
        $top[] = $prospect;

        if (count($top) <= $limit) {
            if (count($top) === $limit) {
                usort($top, fn ($a, $b) => $b['score'] <=> $a['score']);
            }

            return;
        }

        usort($top, fn ($a, $b) => $b['score'] <=> $a['score']);
        array_pop($top);
    }

    private function loadRelations(User $user): void
    {
        $user->loadMissing($this->scoringRelations());
    }

    /** 18 pts — religion */
    private function scoreReligion(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || empty($pref->religion)) {
            return 9.0;
        }
        $candidateReligion = $candidate->religiousDetail?->religion;
        if (! $candidateReligion) {
            return 0.0;
        }
        $religions = is_array($pref->religion) ? $pref->religion : [$pref->religion];
        return in_array($candidateReligion, $religions) ? 18.0 : 0.0;
    }

    /** 12 pts — age */
    private function scoreAge(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || ! $pref->age_min || ! $pref->age_max) {
            return 6.0;
        }
        $dob = $candidate->profile?->dob;
        if (! $dob) {
            return 0.0;
        }
        $age = $dob->age;
        if ($age >= $pref->age_min && $age <= $pref->age_max) {
            return 12.0;
        }
        if ($age >= ($pref->age_min - 3) && $age <= ($pref->age_max + 3)) {
            return 5.0;
        }
        return 0.0;
    }

    /** 12 pts — location */
    private function scoreLocation(User $user, User $candidate): float
    {
        $up = $user->profile;
        $cp = $candidate->profile;
        if (! $up || ! $cp) {
            return 0.0;
        }
        if ($up->city && $cp->city && strtolower($up->city) === strtolower($cp->city)) {
            return 12.0;
        }
        if ($up->state && $cp->state && strtolower($up->state) === strtolower($cp->state)) {
            return 6.0;
        }
        if ($up->country && $cp->country && strtolower($up->country) === strtolower($cp->country)) {
            return 3.0;
        }
        return 0.0;
    }

    /** 8 pts — education */
    private function scoreEducation(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || empty($pref->education)) {
            return 4.0;
        }
        $candidateEdu  = strtolower($candidate->educationCareer?->highest_education ?? '');
        if (! $candidateEdu) {
            return 0.0;
        }
        $preferredEdus = array_map('strtolower', is_array($pref->education) ? $pref->education : [$pref->education]);
        if (in_array($candidateEdu, $preferredEdus)) {
            return 8.0;
        }
        $candidateRank = $this->educationRank[$candidateEdu] ?? 0;
        foreach ($preferredEdus as $prefEdu) {
            $prefRank = $this->educationRank[$prefEdu] ?? 0;
            if ($prefRank > 0 && $candidateRank > 0 && abs($prefRank - $candidateRank) === 1) {
                return 4.0;
            }
        }
        return 0.0;
    }

    /** 7 pts — income */
    private function scoreIncome(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || (! $pref->income_min_bdt && ! $pref->income_max_bdt)) {
            return 3.0;
        }
        $income = $candidate->educationCareer?->annual_income_bdt;
        if (! $income) {
            return 0.0;
        }
        $min = $pref->income_min_bdt ?? 0;
        $max = $pref->income_max_bdt ?? PHP_INT_MAX;
        return ($income >= $min && $income <= $max) ? 7.0 : 0.0;
    }

    /** 8 pts — marital status */
    private function scoreMaritalStatus(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || empty($pref->marital_status)) {
            return 4.0;
        }
        $candidateStatus = $candidate->profile?->marital_status;
        if (! $candidateStatus) {
            return 0.0;
        }
        $statuses = is_array($pref->marital_status) ? $pref->marital_status : [$pref->marital_status];
        return in_array($candidateStatus, $statuses) ? 8.0 : 0.0;
    }

    /** 6 pts — diet */
    private function scoreDiet(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || empty($pref->diet)) {
            return 3.0;
        }
        $candidateDiet  = $candidate->lifestyle?->diet;
        if (! $candidateDiet) {
            return 0.0;
        }
        $preferredDiets = is_array($pref->diet) ? $pref->diet : [$pref->diet];
        if (in_array($candidateDiet, $preferredDiets)) {
            return 6.0;
        }
        foreach ($this->compatibleDiets as $pair) {
            foreach ($preferredDiets as $prefDiet) {
                if (in_array($prefDiet, $pair) && in_array($candidateDiet, $pair)) {
                    return 3.0;
                }
            }
        }
        return 0.0;
    }

    /** 5 pts — height */
    private function scoreHeight(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || (! $pref->height_min_cm && ! $pref->height_max_cm)) {
            return 2.0;
        }
        $height = $candidate->profile?->height_cm;
        if (! $height) {
            return 0.0;
        }
        $min = $pref->height_min_cm ?? 0;
        $max = $pref->height_max_cm ?? 999;
        if ($height >= $min && $height <= $max) {
            return 5.0;
        }
        if ($height >= ($min - 5) && $height <= ($max + 5)) {
            return 2.0;
        }
        return 0.0;
    }

    /** 4 pts — lifestyle (smoking / drinking) */
    private function scoreLifestyle(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref) {
            return 2.0;
        }
        $lifestyle = $candidate->lifestyle;
        if (! $lifestyle) {
            return 0.0;
        }
        $smokingOk        = $pref->smoking_acceptable  ?? true;
        $drinkingOk       = $pref->drinking_acceptable ?? true;
        $candidateSmoker  = in_array($lifestyle->smoking,  ['smoker', 'occasionally']);
        $candidateDrinker = in_array($lifestyle->drinking, ['drinker', 'occasionally']);
        $smokingMatch     = $smokingOk  || ! $candidateSmoker;
        $drinkingMatch    = $drinkingOk || ! $candidateDrinker;

        if ($smokingMatch && $drinkingMatch) {
            return 4.0;
        }
        if ($smokingMatch || $drinkingMatch) {
            return 2.0;
        }
        return 0.0;
    }

    /** 5 pts — family type (2.5) + family values (2.5) */
    private function scoreFamilyPrefs(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        $score = 0.0;

        if (! $pref || empty($pref->family_type)) {
            $score += 1.25;
        } else {
            $val = $candidate->familyDetail?->family_type;
            if ($val) {
                $types  = is_array($pref->family_type) ? $pref->family_type : [$pref->family_type];
                $score += in_array($val, $types) ? 2.5 : 0.0;
            }
        }

        if (! $pref || empty($pref->family_values)) {
            $score += 1.25;
        } else {
            $val = $candidate->familyDetail?->family_values;
            if ($val) {
                $values = is_array($pref->family_values) ? $pref->family_values : [$pref->family_values];
                $score += in_array($val, $values) ? 2.5 : 0.0;
            }
        }

        return $score;
    }

    /** 4 pts — religiousness (2) + pray (2) */
    private function scoreReligiousness(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        $score = 0.0;

        if (! $pref || empty($pref->religiousness)) {
            $score += 1.0;
        } else {
            $val = $candidate->religiousDetail?->religiousness;
            if ($val) {
                $levels = is_array($pref->religiousness) ? $pref->religiousness : [$pref->religiousness];
                $score += in_array($val, $levels) ? 2.0 : 0.0;
            }
        }

        if (! $pref || empty($pref->pray)) {
            $score += 1.0;
        } else {
            $val = $candidate->religiousDetail?->pray;
            if ($val) {
                $prays = is_array($pref->pray) ? $pref->pray : [$pref->pray];
                $score += in_array($val, $prays) ? 2.0 : 0.0;
            }
        }

        return $score;
    }

    /** 3 pts — body type */
    private function scoreBodyType(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || empty($pref->body_type)) {
            return 1.5;
        }
        $val = $candidate->profile?->body_type;
        if (! $val) {
            return 0.0;
        }
        $types = is_array($pref->body_type) ? $pref->body_type : [$pref->body_type];
        return in_array($val, $types) ? 3.0 : 0.0;
    }

    /** 3 pts — working status (1.5) + employed_in (1.5) */
    private function scoreWorkingStatus(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        $score = 0.0;

        if (! $pref || empty($pref->working_status)) {
            $score += 0.75;
        } else {
            $profession      = $candidate->educationCareer?->profession ?? '';
            $workingStatuses = is_array($pref->working_status) ? $pref->working_status : [$pref->working_status];
            $derived         = $this->deriveWorkingStatus($profession);
            $score += in_array($derived, $workingStatuses) ? 1.5 : 0.0;
        }

        if (! $pref || empty($pref->employed_in)) {
            $score += 0.75;
        } else {
            $val = $candidate->educationCareer?->employed_in;
            if ($val) {
                $employedIns = is_array($pref->employed_in) ? $pref->employed_in : [$pref->employed_in];
                $score += in_array($val, $employedIns) ? 1.5 : 0.0;
            }
        }

        return $score;
    }

    /** 3 pts — mother tongue */
    private function scoreMotherTongue(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || empty($pref->mother_tongue)) {
            return 1.5;
        }
        $val = $candidate->profile?->mother_tongue;
        if (! $val) {
            return 0.0;
        }
        $tongues = is_array($pref->mother_tongue) ? $pref->mother_tongue : [$pref->mother_tongue];
        return in_array($val, $tongues) ? 3.0 : 0.0;
    }

    /** 2 pts — residing status */
    private function scoreResidingStatus(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || empty($pref->pref_residing_status)) {
            return 1.0;
        }
        $val = $candidate->profile?->residing_status;
        if (! $val) {
            return 0.0;
        }
        $statuses = is_array($pref->pref_residing_status) ? $pref->pref_residing_status : [$pref->pref_residing_status];
        return in_array($val, $statuses) ? 2.0 : 0.0;
    }

    private function deriveWorkingStatus(string $profession): string
    {
        if ($profession === 'homemaker') {
            return 'homemaker';
        }
        if ($profession === 'student') {
            return 'student';
        }
        if ($profession === 'not_working') {
            return 'not_working';
        }
        return 'working';
    }
}
