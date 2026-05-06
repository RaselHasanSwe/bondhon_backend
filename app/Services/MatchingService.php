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
        $this->loadRelations($user);
        $this->loadRelations($candidate);

        $pref  = $user->partnerPreference;
        $score = 0.0;

        $score += $this->scoreReligion($pref, $candidate);
        $score += $this->scoreAge($pref, $candidate);
        $score += $this->scoreLocation($user, $candidate);
        $score += $this->scoreEducation($pref, $candidate);
        $score += $this->scoreIncome($pref, $candidate);
        $score += $this->scoreMaritalStatus($pref, $candidate);
        $score += $this->scoreDiet($pref, $candidate);
        $score += $this->scoreHeight($pref, $candidate);
        $score += $this->scoreLifestyle($pref, $candidate);
        $score += $this->scoreFamilyPrefs($pref, $candidate);
        $score += $this->scoreReligiousness($pref, $candidate);
        $score += $this->scoreBodyType($pref, $candidate);
        $score += $this->scoreWorkingStatus($pref, $candidate);
        $score += $this->scoreMotherTongue($pref, $candidate);
        $score += $this->scoreResidingStatus($pref, $candidate);

        return min(100.0, max(0.0, $score));
    }

    /**
     * Calculate score with breakdown and persist to match_scores table.
     */
    public function calculateAndStoreScore(User $user, User $candidate): void
    {
        $this->loadRelations($user);
        $this->loadRelations($candidate);

        $pref = $user->partnerPreference;

        $breakdown = [
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

        $total = min(100.0, max(0.0, array_sum($breakdown)));

        MatchScore::updateOrCreate(
            ['user_id' => $user->id, 'candidate_id' => $candidate->id],
            [
                'score'           => $total,
                'score_breakdown' => $breakdown,
                'calculated_at'   => now(),
            ]
        );
    }

    /**
     * Calculate and store scores for a user against all eligible candidates.
     */
    public function calculateAndStoreAllScores(User $user): void
    {
        Log::info('[MATCHING - CalculateAll] Start for User ID: ' . $user->id);

        $blockedIds   = Block::where('blocker_id', $user->id)->pluck('blocked_id');
        $blockedByIds = Block::where('blocked_id', $user->id)->pluck('blocker_id');
        $excludeIds   = $blockedIds->merge($blockedByIds)->push($user->id)->unique()->values();

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
                        Log::error('[MATCHING - CalculateScore] Failed. User: ' . $user->id
                            . ' | Candidate: ' . $candidate->id
                            . ' | Error: ' . $e->getMessage());
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
    // Private helpers
    // -----------------------------------------------------------------------

    private function loadRelations(User $user): void
    {
        $user->loadMissing([
            'profile', 'religiousDetail', 'educationCareer',
            'lifestyle', 'familyDetail', 'horoscopeDetail', 'partnerPreference',
        ]);
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
