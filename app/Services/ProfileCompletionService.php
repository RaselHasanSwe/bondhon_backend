<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProfileCompletionService
{
    /**
     * Calculate the profile completion percentage for a user.
     *
     * Breakdown:
     *   Basic Info (Profile table)   → 15%
     *   Religious Details            → 10%
     *   Family Details               → 10%
     *   Education & Career           → 15%
     *   Lifestyle                    → 10%
     *   Horoscope Details            →  5%
     *   Partner Preferences          → 15%
     *   At least 1 approved photo    → 15%
     *   About Me (min 50 chars)      →  5%
     *   TOTAL                        → 100%
     */
    public function calculate(User $user): int
    {
        $score = 0;

        $profile = $user->profile;

        if (! $profile) {
            return 0;
        }

        // Basic Info (15%)
        $basicFields = ['dob', 'height_cm', 'weight_kg', 'complexion', 'marital_status', 'mother_tongue', 'country', 'city'];
        $filledBasic = collect($basicFields)->filter(fn ($f) => ! empty($profile->$f))->count();
        $score += (int) round(($filledBasic / count($basicFields)) * 15);

        // Religious Details (10%)
        $religious = $user->religiousDetail;
        if ($religious && ! empty($religious->religion)) {
            $relFields  = ['religion', 'caste', 'manglik_status'];
            $filledRel  = collect($relFields)->filter(fn ($f) => ! empty($religious->$f))->count();
            $score     += (int) round(($filledRel / count($relFields)) * 10);
        }

        // Family Details (10%)
        $family = $user->familyDetail;
        if ($family) {
            $famFields  = ['family_type', 'family_status'];
            $filledFam  = collect($famFields)->filter(fn ($f) => ! empty($family->$f))->count();
            $score     += (int) round(($filledFam / count($famFields)) * 10);
        }

        // Education & Career (15%)
        $education = $user->educationCareer;
        if ($education && ! empty($education->highest_education)) {
            $eduFields  = ['highest_education', 'profession', 'employed_in'];
            $filledEdu  = collect($eduFields)->filter(fn ($f) => ! empty($education->$f))->count();
            $score     += (int) round(($filledEdu / count($eduFields)) * 15);
        }

        // Lifestyle (10%)
        $lifestyle = $user->lifestyle;
        if ($lifestyle) {
            $lifeFields  = ['diet', 'smoking', 'drinking'];
            $filledLife  = collect($lifeFields)->filter(fn ($f) => ! empty($lifestyle->$f))->count();
            $score      += (int) round(($filledLife / count($lifeFields)) * 10);
        }

        // Horoscope Details (5%)
        $horoscope = $user->horoscopeDetail;
        if ($horoscope && (! empty($horoscope->rashi) || ! empty($horoscope->birth_place))) {
            $score += 5;
        }

        // Partner Preferences (15%)
        $preferences = $user->partnerPreference;
        if ($preferences) {
            $prefFields  = ['age_min', 'age_max', 'height_min_cm', 'height_max_cm', 'religion'];
            $filledPref  = collect($prefFields)->filter(fn ($f) => ! empty($preferences->$f))->count();
            $score      += (int) round(($filledPref / count($prefFields)) * 15);
        }

        // At least 1 approved photo (15%)
        $hasApprovedPhoto = $user->photos()->where('is_approved', true)->exists();
        if ($hasApprovedPhoto) {
            $score += 15;
        }

        // About Me — min 50 chars (5%)
        if (! empty($profile->about_me) && mb_strlen($profile->about_me) >= 50) {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Recalculate and persist the profile completion percentage.
     */
    public function recalculateAndSave(User $user): int
    {
        $percentage = $this->calculate($user);

        if ($user->profile) {
            $user->profile->update(['profile_completion_percentage' => $percentage]);
            Log::info('[PROFILE COMPLETION - Recalculated] User ID: ' . $user->id . ' | Score: ' . $percentage . '%');
        }

        return $percentage;
    }
}

