<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ProfileViewResource;
use App\Models\ProfileView;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileViewController extends ApiController
{
    public function __construct(private readonly SubscriptionFeatureService $featureService) {}

    /**
     * GET /api/v1/profile-views
     * Get a paginated list of users who have viewed the authenticated user's profile.
     */
    public function myViewers(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[PROFILE VIEW - MyViewers] User ID: ' . $user->id);

        // Feature check: see_who_viewed_profile
        if (! $this->featureService->can($user, 'see_who_viewed_profile')) {
            return $this->errorResponse(
                'Viewing your profile visitors requires an upgraded subscription plan.',
                ['feature' => 'see_who_viewed_profile'],
                403
            );
        }

        $views = ProfileView::with([
                'viewer',
                'viewer.profile',
                'viewer.religiousDetail',
                'viewer.educationCareer',
                'viewer.faceScanSession',
                'viewer.photos' => fn ($q) => $q->where('is_approved', true)->where('is_primary', true),
            ])
            ->where('viewed_id', $user->id)
            ->orderByDesc('viewed_at')
            ->paginate(20);

        return $this->successResponse(
            ProfileViewResource::collection($views)->response()->getData(true),
            'Profile viewers retrieved.'
        );
    }
}

