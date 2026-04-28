<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Interest;
use App\Models\MatchScore;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MatchingService — Phase 2
 *
 * Calculates compatibility scores between users based on weighted analytics.
 * Score breakdown (total 100 points):
 *   Religion match              → 20
 *   Age within preference range → 15
 *   Location match              → 15
 *   Education level match       → 10
 *   Income range match          → 10
 *   Marital status match        → 10
 *   Diet compatibility          → 8
 *   Height within preference    → 7
 *   Lifestyle match             → 5
 */
class MatchingService
{
    /**
     * Education level rank mapping (higher = more educated).
     */
    private array $educationRank = [
        'below_ssc'        => 1,
        'ssc'              => 2,
        'hsc'              => 3,
        'diploma'          => 4,
        'bachelors'        => 5,
        'masters'          => 6,
        'phd'              => 7,
        'postdoctoral'     => 8,
    ];

    /**
     * Compatible diet pairs (bidirectional).
     */
    private array $compatibleDiets = [
        ['vegetarian', 'vegan'],
        ['vegetarian', 'jain'],
        ['vegan', 'jain'],
    ];

    /**
     * Calculate a compatibility score (0–100) for $user vs $candidate.
     */
    public function calculateScore(User $user, User $candidate): float
    {
        $user->loadMissing(['profile', 'religiousDetail', 'educationCareer', 'lifestyle', 'partnerPreference']);
        $candidate->loadMissing(['profile', 'religiousDetail', 'educationCareer', 'lifestyle', 'partnerPreference']);

        $pref  = $user->partnerPreference;
        $score = 0.0;

        // 1. Religion match (20 pts)
        $score += $this->scoreReligion($pref, $candidate);

        // 2. Age within preference range (15 pts)
        $score += $this->scoreAge($pref, $candidate);

        // 3. Location match (15 pts)
        $score += $this->scoreLocation($user, $candidate);

        // 4. Education level match (10 pts)
        $score += $this->scoreEducation($pref, $candidate);

        // 5. Income range match (10 pts)
        $score += $this->scoreIncome($pref, $candidate);

        // 6. Marital status match (10 pts)
        $score += $this->scoreMaritalStatus($pref, $candidate);

        // 7. Diet compatibility (8 pts)
        $score += $this->scoreDiet($pref, $candidate);

        // 8. Height within preference (7 pts)
        $score += $this->scoreHeight($pref, $candidate);

        // 9. Lifestyle match (5 pts)
        $score += $this->scoreLifestyle($pref, $candidate);

        return min(100.0, max(0.0, $score));
    }

    /**
     * Calculate score with breakdown and persist to match_scores table.
     */
    public function calculateAndStoreScore(User $user, User $candidate): void
    {
        $user->loadMissing(['profile', 'religiousDetail', 'educationCareer', 'lifestyle', 'partnerPreference']);
        $candidate->loadMissing(['profile', 'religiousDetail', 'educationCareer', 'lifestyle', 'partnerPreference']);

        $pref      = $user->partnerPreference;
        $breakdown = [];

        $religion      = $this->scoreReligion($pref, $candidate);
        $age           = $this->scoreAge($pref, $candidate);
        $location      = $this->scoreLocation($user, $candidate);
        $education     = $this->scoreEducation($pref, $candidate);
        $income        = $this->scoreIncome($pref, $candidate);
        $maritalStatus = $this->scoreMaritalStatus($pref, $candidate);
        $diet          = $this->scoreDiet($pref, $candidate);
        $height        = $this->scoreHeight($pref, $candidate);
        $lifestyle     = $this->scoreLifestyle($pref, $candidate);

        $breakdown = [
            'religion'       => $religion,
            'age'            => $age,
            'location'       => $location,
            'education'      => $education,
            'income'         => $income,
            'marital_status' => $maritalStatus,
            'diet'           => $diet,
            'height'         => $height,
            'lifestyle'      => $lifestyle,
        ];

        $total = min(100.0, max(0.0,
            $religion + $age + $location + $education + $income +
            $maritalStatus + $diet + $height + $lifestyle
        ));

        MatchScore::updateOrCreate(
            ['user_id' => $user->id, 'candidate_id' => $candidate->id],
            [
                'score'          => $total,
                'score_breakdown' => $breakdown,
                'calculated_at'  => now(),
            ]
        );
    }

    /**
     * Calculate and store scores for a user against all eligible candidates.
     */
    public function calculateAndStoreAllScores(User $user): void
    {
        Log::info('[MATCHING - CalculateAll] Start for User ID: ' . $user->id);

        $blockedIds    = Block::where('blocker_id', $user->id)->pluck('blocked_id');
        $blockedByIds  = Block::where('blocked_id', $user->id)->pluck('blocker_id');
        $excludeIds    = $blockedIds->merge($blockedByIds)->push($user->id)->unique()->values();

        $oppositeGender = $user->gender === 'male' ? 'female' : 'male';

        User::where('gender', $oppositeGender)
            ->where('is_active', true)
            ->where('is_banned', false)
            ->whereNotIn('id', $excludeIds)
            ->whereNotNull('email_verified_at')
            ->chunk(100, function ($candidates) use ($user) {
                foreach ($candidates as $candidate) {
                    try {
                        $this->calculateAndStoreScore($user, $candidate);
                    } catch (\Throwable $e) {
                        Log::error('[MATCHING - CalculateScore] Failed. User: ' . $user->id . ' | Candidate: ' . $candidate->id . ' | Error: ' . $e->getMessage());
                    }
                }
            });

        Log::info('[MATCHING - CalculateAll] Complete for User ID: ' . $user->id);
    }

    /**
     * Get paginated match suggestions for a user sorted by compatibility score.
     */
    public function getSuggestions(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $blockedIds   = Block::where('blocker_id', $user->id)->pluck('blocked_id');
        $blockedByIds = Block::where('blocked_id', $user->id)->pluck('blocker_id');
        $excludeIds   = $blockedIds->merge($blockedByIds)->push($user->id)->unique()->values();

        // Exclude already-accepted interest pairs
        $acceptedCandidateIds = Interest::where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->where('status', 'accepted')
            ->get()
            ->map(fn ($i) => $i->sender_id === $user->id ? $i->receiver_id : $i->sender_id)
            ->unique()
            ->values();

        $excludeIds = $excludeIds->merge($acceptedCandidateIds)->unique()->values();

        $oppositeGender = $user->gender === 'male' ? 'female' : 'male';

        return MatchScore::with([
                'candidate',
                'candidate.profile',
                'candidate.religiousDetail',
                'candidate.educationCareer',
                'candidate.photos' => fn ($q) => $q->where('is_approved', true)->where('is_private', false)->where('is_primary', true),
            ])
            ->where('user_id', $user->id)
            ->whereHas('candidate', fn ($q) => $q
                ->where('gender', $oppositeGender)
                ->where('is_active', true)
                ->where('is_banned', false)
                ->whereNotNull('email_verified_at')
                ->whereNotIn('id', $excludeIds)
            )
            ->orderByDesc('score')
            ->paginate($perPage);
    }

    // -----------------------------------------------------------------------
    // Private scoring helpers
    // -----------------------------------------------------------------------

    private function scoreReligion(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || empty($pref->religion)) {
            return 10.0; // no preference set → give partial points
        }
        $candidateReligion = $candidate->religiousDetail?->religion;
        if (! $candidateReligion) {
            return 0.0;
        }
        $religions = is_array($pref->religion) ? $pref->religion : [$pref->religion];
        return in_array($candidateReligion, $religions) ? 20.0 : 0.0;
    }

    private function scoreAge(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || ! $pref->age_min || ! $pref->age_max) {
            return 7.0; // no preference set
        }
        $dob = $candidate->profile?->dob;
        if (! $dob) {
            return 0.0;
        }
        $age = $dob->age;
        if ($age >= $pref->age_min && $age <= $pref->age_max) {
            return 15.0;
        }
        // Within 3 years outside range
        if ($age >= ($pref->age_min - 3) && $age <= ($pref->age_max + 3)) {
            return 7.0;
        }
        return 0.0;
    }

    private function scoreLocation(User $user, User $candidate): float
    {
        $userProfile      = $user->profile;
        $candidateProfile = $candidate->profile;

        if (! $userProfile || ! $candidateProfile) {
            return 0.0;
        }

        if ($userProfile->city && $candidateProfile->city && strtolower($userProfile->city) === strtolower($candidateProfile->city)) {
            return 15.0;
        }
        if ($userProfile->state && $candidateProfile->state && strtolower($userProfile->state) === strtolower($candidateProfile->state)) {
            return 8.0;
        }
        if ($userProfile->country && $candidateProfile->country && strtolower($userProfile->country) === strtolower($candidateProfile->country)) {
            return 4.0;
        }
        return 0.0;
    }

    private function scoreEducation(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || empty($pref->education)) {
            return 5.0; // no preference
        }
        $candidateEdu = strtolower($candidate->educationCareer?->highest_education ?? '');
        if (! $candidateEdu) {
            return 0.0;
        }
        $preferredEdus = is_array($pref->education) ? array_map('strtolower', $pref->education) : [strtolower($pref->education)];

        if (in_array($candidateEdu, $preferredEdus)) {
            return 10.0;
        }
        // Check if within 1 rank
        $candidateRank = $this->educationRank[$candidateEdu] ?? 0;
        foreach ($preferredEdus as $prefEdu) {
            $prefRank = $this->educationRank[$prefEdu] ?? 0;
            if ($prefRank > 0 && $candidateRank > 0 && abs($prefRank - $candidateRank) === 1) {
                return 5.0;
            }
        }
        return 0.0;
    }

    private function scoreIncome(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || (! $pref->income_min_bdt && ! $pref->income_max_bdt)) {
            return 5.0; // no preference
        }
        $income = $candidate->educationCareer?->annual_income_bdt;
        if (! $income) {
            return 0.0;
        }
        $min = $pref->income_min_bdt ?? 0;
        $max = $pref->income_max_bdt ?? PHP_INT_MAX;
        return ($income >= $min && $income <= $max) ? 10.0 : 0.0;
    }

    private function scoreMaritalStatus(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || empty($pref->marital_status)) {
            return 5.0; // no preference
        }
        $candidateStatus = $candidate->profile?->marital_status;
        if (! $candidateStatus) {
            return 0.0;
        }
        $statuses = is_array($pref->marital_status) ? $pref->marital_status : [$pref->marital_status];
        return in_array($candidateStatus, $statuses) ? 10.0 : 0.0;
    }

    private function scoreDiet(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || empty($pref->diet)) {
            return 4.0; // no preference
        }
        $candidateDiet = $candidate->lifestyle?->diet;
        if (! $candidateDiet) {
            return 0.0;
        }
        $preferredDiets = is_array($pref->diet) ? $pref->diet : [$pref->diet];

        if (in_array($candidateDiet, $preferredDiets)) {
            return 8.0;
        }
        // Check compatible diet pairs
        foreach ($this->compatibleDiets as $pair) {
            foreach ($preferredDiets as $prefDiet) {
                if (in_array($prefDiet, $pair) && in_array($candidateDiet, $pair)) {
                    return 4.0;
                }
            }
        }
        return 0.0;
    }

    private function scoreHeight(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref || (! $pref->height_min_cm && ! $pref->height_max_cm)) {
            return 3.0; // no preference
        }
        $height = $candidate->profile?->height_cm;
        if (! $height) {
            return 0.0;
        }
        $min = $pref->height_min_cm ?? 0;
        $max = $pref->height_max_cm ?? 999;

        if ($height >= $min && $height <= $max) {
            return 7.0;
        }
        // Within 5 cm of range
        if ($height >= ($min - 5) && $height <= ($max + 5)) {
            return 3.0;
        }
        return 0.0;
    }

    private function scoreLifestyle(?\App\Models\PartnerPreference $pref, User $candidate): float
    {
        if (! $pref) {
            return 2.0;
        }
        $lifestyle = $candidate->lifestyle;
        if (! $lifestyle) {
            return 0.0;
        }

        $smokingOk  = $pref->smoking_acceptable  ?? true;
        $drinkingOk = $pref->drinking_acceptable ?? true;

        $candidateSmoker  = in_array($lifestyle->smoking, ['smoker', 'occasionally']);
        $candidateDrinker = in_array($lifestyle->drinking, ['drinker', 'occasionally']);

        $smokingMatch  = $smokingOk  || ! $candidateSmoker;
        $drinkingMatch = $drinkingOk || ! $candidateDrinker;

        if ($smokingMatch && $drinkingMatch) {
            return 5.0;
        }
        if ($smokingMatch || $drinkingMatch) {
            return 2.0;
        }
        return 0.0;
    }
}
