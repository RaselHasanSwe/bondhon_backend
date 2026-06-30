<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ProfileViewResource;
use App\Models\ProfileView;
use App\Services\InterestService;
use App\Services\MatchingService;
use App\Services\ShortlistService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileViewController extends ApiController
{
    public function __construct(
        private readonly SubscriptionFeatureService $featureService,
        private readonly InterestService $interestService,
        private readonly ShortlistService $shortlistService,
        private readonly MatchingService $matchingService,
    ) {}

    /**
     * GET /api/v1/profile-views
     * Get a paginated list of users who have viewed the authenticated user's profile.
     */
    public function myViewers(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[PROFILE VIEW - MyViewers] User ID: ' . $user->id);

        if (! $this->featureService->can($user, 'see_who_viewed_profile')) {
            return $this->errorResponse(
                'Viewing your profile visitors requires an upgraded subscription plan.',
                ['feature' => 'see_who_viewed_profile'],
                403
            );
        }

        $query = ProfileView::with([
                'viewer',
                'viewer.profile',
                'viewer.religiousDetail',
                'viewer.educationCareer',
                'viewer.faceScanSession',
                'viewer.photos' => fn ($q) => $q->where('is_approved', true)->where('is_primary', true),
            ])
            ->where('viewed_id', $user->id)
            ->where('viewer_id', '!=', $user->id);

        $this->applyViewerSearch($query, $request->input('search'));

        $views = $query->orderByDesc('viewed_at')->paginate(20);

        $this->interestService->attachConnectionMetaToItems(
            $user,
            $views->getCollection(),
            fn (ProfileView $view) => $view->viewer_id
        );

        $viewers = $views->getCollection()->pluck('viewer')->filter();
        $this->shortlistService->attachShortlistStatus($user, $viewers);
        $this->matchingService->attachCompatibilityScoresToUsers($user, $viewers);

        return $this->successResponse(
            ProfileViewResource::collection($views)->response()->getData(true),
            'Profile viewers retrieved.'
        );
    }

    private function applyViewerSearch($query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $query->whereHas('viewer', function ($userQuery) use ($search) {
            $this->applyProfileKeywordSearch($userQuery, $search);
        });
    }

    private function applyProfileKeywordSearch($userQuery, string $search): void
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
}
