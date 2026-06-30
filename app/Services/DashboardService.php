<?php

namespace App\Services;

use App\Models\Interest;
use App\Models\ProfileView;
use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly ProfileCompletionService $completionService,
        private readonly MatchingService $matchingService,
        private readonly ShortlistService $shortlistService,
        private readonly InterestService $interestService,
        private readonly SubscriptionFeatureService $featureService,
    ) {}

    /**
     * @return array{
     *     completion: array<string, mixed>,
     *     stats: array<string, mixed>,
     *     matches: \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * }
     */
    public function getSummary(User $user): array
    {
        $user->load([
            'profile',
            'religiousDetail',
            'familyDetail',
            'educationCareer',
            'lifestyle',
            'horoscopeDetail',
            'partnerPreference',
            'photos',
        ]);

        $userId = $user->id;

        $pendingInterests = Interest::query()
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->count();

        $contactsCount = Interest::query()
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->count();

        $shortlistCount = Shortlist::query()
            ->where('user_id', $userId)
            ->where('shortlisted_user_id', '!=', $userId)
            ->count();

        $canSeeViewers = $this->featureService->can($user, 'see_who_viewed_profile');
        $profileViewers = $canSeeViewers
            ? ProfileView::query()
                ->where('viewed_id', $userId)
                ->where('viewer_id', '!=', $userId)
                ->count()
            : null;

        $matches = $this->matchingService->getSuggestions($user, 6);
        $totalMatches = $matches->total();

        $candidates = $matches->getCollection()->pluck('candidate')->filter();
        $this->attachCandidateMeta($user, $candidates);

        return [
            'completion' => $this->buildCompletionPayload($user),
            'stats'      => [
                'pending_interests'        => $pendingInterests,
                'total_matches'            => $totalMatches,
                'profile_viewers'          => $profileViewers,
                'profile_viewers_locked'   => ! $canSeeViewers,
                'shortlist_count'          => $shortlistCount,
                'contacts_count'           => $contactsCount,
            ],
            'matches' => $matches,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCompletionPayload(User $user): array
    {
        $percentage = $this->completionService->calculate($user);

        $basicRequiredFields = ['dob', 'height_cm', 'weight_kg', 'complexion', 'marital_status', 'mother_tongue', 'country'];
        $allBasicFilled = $user->profile
            && collect($basicRequiredFields)->every(fn ($f) => ! empty($user->profile->$f));

        return [
            'percentage'           => $percentage,
            'has_basic_info'       => $allBasicFilled,
            'has_religious_detail' => ! empty($user->religiousDetail?->religion),
            'has_family_detail'    => ! empty($user->familyDetail?->family_type),
            'has_education'        => ! empty($user->educationCareer?->highest_education),
            'has_lifestyle'        => ! empty($user->lifestyle?->diet),
            'has_horoscope'        => ! empty($user->horoscopeDetail?->rashi),
            'has_preferences'      => ! empty($user->partnerPreference?->age_min),
            'has_photo'            => $user->photos->isNotEmpty(),
            'has_about_me'         => ! empty($user->profile?->about_me) && mb_strlen($user->profile->about_me) >= 50,
        ];
    }

    /**
     * @param  Collection<int, User>|iterable<User>  $candidates
     */
    private function attachCandidateMeta(User $viewer, iterable $candidates): void
    {
        $collection = collect($candidates);
        if ($collection->isEmpty()) {
            return;
        }

        $this->shortlistService->attachShortlistStatus($viewer, $collection);
        $this->interestService->attachConnectionMetaToItems(
            $viewer,
            $collection,
            fn (User $user) => $user->id
        );
    }
}
