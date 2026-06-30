<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Match\SearchRequest;
use App\Http\Resources\MatchResource;
use App\Http\Resources\ProfileCardResource;
use App\Models\Block;
use App\Models\MatchScore;
use App\Models\Profile;
use App\Models\ProfileView;
use App\Models\User;
use App\Services\InterestService;
use App\Services\MatchingService;
use App\Services\ShortlistService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MatchController extends ApiController
{
    public function __construct(
        private readonly MatchingService $matchingService,
        private readonly InterestService $interestService,
        private readonly ShortlistService $shortlistService,
        private readonly SubscriptionFeatureService $featureService,
    ) {}

    /**
     * GET /api/v1/matches
     * Return paginated daily match suggestions sorted by compatibility score.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[MATCH - Index] User ID: ' . $user->id);

        $validated = $request->validate([
            'search'    => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'page'      => ['nullable', 'integer', 'min:1'],
        ]);

        // Enforce daily_matches limit
        $dailyLimit = (int) $this->featureService->value($user, 'daily_matches');
        $perPage    = ($dailyLimit > 0) ? min(20, $dailyLimit) : 20;

        $paginator = $this->matchingService->getSuggestions(
            $user,
            $perPage,
            $validated['search'] ?? null,
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
        );

        $this->attachViewerMeta(
            $user,
            $paginator->getCollection()->pluck('candidate')->filter()
        );

        return $this->successResponse(
            MatchResource::collection($paginator)->response()->getData(true),
            'Match suggestions retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/matches/search
     * Search profiles with optional filters.
     */
    public function search(SearchRequest $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[MATCH - Search] User ID: ' . $user->id . ' | Filters: ' . json_encode($request->validated()));

        // Guard: basic search_access
        if (! $this->featureService->can($user, 'search_access')) {
            return $this->errorResponse(
                'Search is not available on your current subscription plan.',
                ['feature' => 'search_access'],
                403
            );
        }

        // Guard: advanced filters require search_filters_advanced
        $advancedFields = ['income_min', 'income_max', 'caste', 'diet', 'smoking', 'drinking', 'employed_in'];
        $hasAdvanced    = collect($advancedFields)->contains(fn ($f) => $request->filled($f));

        if ($hasAdvanced && ! $this->featureService->can($user, 'search_filters_advanced')) {
            return $this->errorResponse(
                'Advanced search filters (income, caste, etc.) require an upgraded plan.',
                ['feature' => 'search_filters_advanced'],
                403
            );
        }

        // Guard: BON-XXXXXX profile ID search
        if ($request->filled('profile_id') && ! $this->featureService->can($user, 'profile_id_search')) {
            return $this->errorResponse(
                'Search by Profile ID requires an upgraded subscription plan.',
                ['feature' => 'profile_id_search'],
                403
            );
        }

        // IDs the auth user has blocked or been blocked by
        $blockedIds   = Block::where('blocker_id', $user->id)->pluck('blocked_id');
        $blockedByIds = Block::where('blocked_id', $user->id)->pluck('blocker_id');
        $excludeIds   = $blockedIds->merge($blockedByIds)->push($user->id)->unique()->values();

        // Profile ID (BON-XXXXXX) direct lookup
        if ($request->filled('profile_id')) {
            $profile = Profile::where('profile_id', $request->profile_id)->first();

            if (! $profile || in_array($profile->user_id, $excludeIds->toArray())) {
                return $this->successResponse([
                    'data'         => [],
                    'current_page' => 1,
                    'per_page'     => 20,
                    'total'        => 0,
                    'last_page'    => 1,
                ], 'Search results retrieved.');
            }

            $targetUser = User::with([
                    'profile',
                    'religiousDetail',
                    'educationCareer',
                    'lifestyle',
                    'faceScanSession',
                    'photos' => fn ($q) => $q->where('is_approved', true)->where('is_private', false)->where('is_primary', true),
                ])
                ->where('id', $profile->user_id)
                ->where('role', 'user')
                ->where('is_active', true)
                ->where('is_banned', false)
                ->whereNotNull('email_verified_at')
                ->first();

            if (! $targetUser) {
                return $this->successResponse(['data' => [], 'total' => 0], 'No profile found.');
            }

            $this->attachViewerMeta($user, collect([$targetUser]));

            return $this->successResponse([
                'data'      => [ProfileCardResource::make($targetUser)],
                'total'     => 1,
                'last_page' => 1,
            ], 'Search results retrieved.');
        }

        // Full text keyword + structured filter search
        $oppositeGender = $user->gender === 'male' ? 'female' : 'male';
        $gender         = $request->input('gender', $oppositeGender);

        $query = User::with([
                'profile',
                'religiousDetail',
                'educationCareer',
                'lifestyle',
                'faceScanSession',
                'photos' => fn ($q) => $q->where('is_approved', true)->where('is_private', false)->where('is_primary', true),
            ])
            ->join('profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('religious_details', 'religious_details.user_id', '=', 'users.id')
            ->leftJoin('education_careers', 'education_careers.user_id', '=', 'users.id')
            ->leftJoin('lifestyles', 'lifestyles.user_id', '=', 'users.id')
            ->leftJoin('family_details', 'family_details.user_id', '=', 'users.id')
            ->select('users.*')
            ->where('users.gender', $gender)
            ->where('users.role', 'user')
            ->where('users.is_active', true)
            ->where('users.is_banned', false)
            ->whereNotNull('users.email_verified_at')
            ->whereNotIn('users.id', $excludeIds);

        // Age range (calculated from dob)
        if ($request->filled('age_min')) {
            $query->whereDate('profiles.dob', '<=', now()->subYears((int) $request->age_min)->format('Y-m-d'));
        }
        if ($request->filled('age_max')) {
            $query->whereDate('profiles.dob', '>=', now()->subYears((int) $request->age_max)->format('Y-m-d'));
        }

        // Height
        if ($request->filled('height_min')) {
            $query->where('profiles.height_cm', '>=', $request->height_min);
        }
        if ($request->filled('height_max')) {
            $query->where('profiles.height_cm', '<=', $request->height_max);
        }

        // Location — use exact match for structured country values, LIKE for free text state/city
        if ($request->filled('country')) {
            $query->where('profiles.country', $request->country);
        }
        if ($request->filled('state')) {
            $query->where('profiles.state', 'like', '%' . $request->state . '%');
        }
        if ($request->filled('city')) {
            $query->where('profiles.city', 'like', '%' . $request->city . '%');
        }
        if ($request->filled('nationality')) {
            $query->where('profiles.nationality', $request->nationality);
        }
        if ($request->filled('residing_status')) {
            $query->where('profiles.residing_status', $request->residing_status);
        }

        // Marital status
        if ($request->filled('marital_status')) {
            $query->where('profiles.marital_status', $request->marital_status);
        }

        // Physical attributes
        if ($request->filled('body_type')) {
            $query->where('profiles.body_type', $request->body_type);
        }
        if ($request->filled('complexion')) {
            $query->where('profiles.complexion', $request->complexion);
        }
        if ($request->filled('blood_group')) {
            $query->where('profiles.blood_group', $request->blood_group);
        }
        if ($request->filled('mother_tongue')) {
            $query->where('profiles.mother_tongue', $request->mother_tongue);
        }

        // Religion / caste — LIKE so partial seeder values still match
        if ($request->filled('religion')) {
            $query->where('religious_details.religion', $request->religion);
        }
        if ($request->filled('caste')) {
            $query->where('religious_details.caste', $request->caste);
        }

        // Education / career
        if ($request->filled('education')) {
            $query->where('education_careers.highest_education', $request->education);
        }
        if ($request->filled('profession')) {
            $query->where('education_careers.profession', $request->profession);
        }
        if ($request->filled('employed_in')) {
            $query->where('education_careers.employed_in', $request->employed_in);
        }

        // Income
        if ($request->filled('income_min')) {
            $query->where('education_careers.annual_income_bdt', '>=', $request->income_min);
        }
        if ($request->filled('income_max')) {
            $query->where('education_careers.annual_income_bdt', '<=', $request->income_max);
        }

        // Lifestyle
        if ($request->filled('diet')) {
            $query->where('lifestyles.diet', $request->diet);
        }
        if ($request->filled('smoking')) {
            $query->where('lifestyles.smoking', $request->smoking);
        }
        if ($request->filled('drinking')) {
            $query->where('lifestyles.drinking', $request->drinking);
        }

        // Family
        if ($request->filled('has_children')) {
            $query->where('family_details.has_children', $request->has_children);
        }

        // Global full-text keyword search — searches across all meaningful text fields
        if ($request->filled('query')) {
            $kw = (string) $request->input('query');
            $query->where(function ($q) use ($kw, $user) {
                $q->where('users.name', 'like', '%' . $kw . '%')
                  ->orWhere('profiles.about_me', 'like', '%' . $kw . '%')
                  ->orWhere('profiles.city', 'like', '%' . $kw . '%')
                  ->orWhere('profiles.state', 'like', '%' . $kw . '%')
                  ->orWhere('profiles.country', 'like', '%' . $kw . '%')
                  ->orWhere('profiles.nationality', 'like', '%' . $kw . '%')
                  ->orWhere('religious_details.religion', 'like', '%' . $kw . '%')
                  ->orWhere('education_careers.profession', 'like', '%' . $kw . '%')
                  ->orWhere('education_careers.highest_education', 'like', '%' . $kw . '%')
                  ->orWhere('education_careers.employer_name', 'like', '%' . $kw . '%');

                if ($this->featureService->can($user, 'profile_id_search')) {
                    $q->orWhere('profiles.profile_id', 'like', '%' . $kw . '%');
                }
            });
        }

        // Sorting
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'age_asc'    => $query->orderByRaw('profiles.dob DESC'),   // youngest → oldest
            'age_desc'   => $query->orderByRaw('profiles.dob ASC'),    // oldest → youngest
            'completion' => $query->orderBy('profiles.profile_completion_percentage', 'desc'),
            default      => $query->orderBy('users.id', 'desc'),        // latest registrations
        };

        $paginator = $query->paginate(20);

        $this->attachViewerMeta($user, $paginator->getCollection());

        return $this->successResponse(
            ProfileCardResource::collection($paginator)->response()->getData(true),
            'Search results retrieved.'
        );
    }

    /**
     * GET /api/v1/matches/{userId}/score
     * Get compatibility score between auth user and another user.
     */
    public function compatibilityScore(Request $request, int $userId): JsonResponse
    {
        $user      = $request->user();
        $candidate = User::where('id', $userId)
            ->where('is_active', true)
            ->where('is_banned', false)
            ->firstOrFail();

        Log::info('[MATCH - CompatibilityScore] User ID: ' . $user->id . ' | Candidate ID: ' . $userId);

        // Guard: compatibility_score_visible feature
        if (! $this->featureService->can($user, 'compatibility_score_visible')) {
            return $this->errorResponse(
                'Compatibility score visibility requires an upgraded subscription plan.',
                ['feature' => 'compatibility_score_visible'],
                403
            );
        }

        // Check blocked
        $isBlocked = Block::where('blocker_id', $user->id)->where('blocked_id', $userId)->exists()
            || Block::where('blocker_id', $userId)->where('blocked_id', $user->id)->exists();

        if ($isBlocked) {
            return $this->errorResponse('Cannot view compatibility score for this user.', null, 403);
        }

        // Try stored pair score first
        $matchScore = MatchScore::findForPair($user->id, $userId);

        // If not stored, calculate on-demand (one-off)
        if (! $matchScore) {
            $matchScore = $this->matchingService->calculateAndStoreScore($user, $candidate, ignoreMinimum: true);
        }

        if (! $matchScore) {
            return $this->errorResponse(
                'Compatibility score is not available for this profile.',
                null,
                404
            );
        }

        return $this->successResponse([
            'score'          => (float) $matchScore->score,
            'score_breakdown' => $matchScore->score_breakdown,
            'calculated_at'  => $matchScore->calculated_at,
        ], 'Compatibility score retrieved.');
    }

    /**
     * Attach shortlist + interest connection metadata for the authenticated viewer.
     *
     * @param  iterable<User>  $users
     */
    private function attachViewerMeta(User $viewer, iterable $users): void
    {
        $collection = collect($users);
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

