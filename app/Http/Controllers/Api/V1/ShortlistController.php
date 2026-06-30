<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ShortlistResource;
use App\Models\Block;
use App\Models\Shortlist;
use App\Models\User;
use App\Services\InterestService;
use App\Services\MatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShortlistController extends ApiController
{
    public function __construct(
        private readonly InterestService $interestService,
        private readonly MatchingService $matchingService,
    ) {}

    /**
     * POST /api/v1/shortlist/{userId}
     * Toggle shortlist for a user (add if not exists, remove if exists).
     */
    public function toggle(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();
        Log::info('[SHORTLIST - Toggle] User ID: ' . $user->id . ' | Target: ' . $userId);

        if ($user->id === $userId) {
            return $this->errorResponse('You cannot shortlist yourself.', null, 422);
        }

        $target = User::where('id', $userId)->where('is_active', true)->where('is_banned', false)->firstOrFail();

        // Check blocked
        $isBlocked = Block::where('blocker_id', $user->id)->where('blocked_id', $userId)->exists()
            || Block::where('blocker_id', $userId)->where('blocked_id', $user->id)->exists();

        if ($isBlocked) {
            return $this->errorResponse('You cannot shortlist this user.', null, 403);
        }

        $existing = Shortlist::where('user_id', $user->id)->where('shortlisted_user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
            Log::info('[SHORTLIST - Toggle] Removed. User: ' . $user->id . ' | Target: ' . $userId);
            return $this->successResponse(['shortlisted' => false], 'Removed from shortlist.');
        }

        Shortlist::create([
            'user_id'             => $user->id,
            'shortlisted_user_id' => $userId,
        ]);

        Log::info('[SHORTLIST - Toggle] Added. User: ' . $user->id . ' | Target: ' . $userId);

        return $this->successResponse(['shortlisted' => true], 'Added to shortlist.', 201);
    }

    /**
     * GET /api/v1/shortlist
     * List the authenticated user's shortlisted profiles.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[SHORTLIST - Index] User ID: ' . $user->id);

        $query = Shortlist::with([
                'shortlistedUser',
                'shortlistedUser.profile',
                'shortlistedUser.religiousDetail',
                'shortlistedUser.educationCareer',
                'shortlistedUser.faceScanSession',
                'shortlistedUser.photos' => fn ($q) => $q->where('is_approved', true)->where('is_primary', true),
            ])
            ->where('user_id', $user->id)
            ->where('shortlisted_user_id', '!=', $user->id);

        $this->applyShortlistedUserSearch($query, $request->input('search'));

        $items = $query->orderByDesc('created_at')->paginate(20);

        $this->interestService->attachConnectionMetaToItems(
            $user,
            $items->getCollection(),
            fn (Shortlist $item) => $item->shortlisted_user_id
        );

        $shortlistedUsers = $items->getCollection()->pluck('shortlistedUser')->filter();
        $this->matchingService->attachCompatibilityScoresToUsers($user, $shortlistedUsers);

        return $this->successResponse(
            ShortlistResource::collection($items)->response()->getData(true),
            'Shortlist retrieved.'
        );
    }

    private function applyShortlistedUserSearch($query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $query->whereHas('shortlistedUser', function ($userQuery) use ($search) {
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
