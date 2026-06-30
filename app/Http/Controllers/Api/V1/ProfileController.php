<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Profile\UpdatePreferenceRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\MatchScore;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\ProfileView;
use App\Models\Interest;
use App\Models\User;
use App\Jobs\SendProfileViewedEmail;
use App\Services\InterestService;
use App\Services\MatchingService;
use App\Services\ProfileCompletionService;
use App\Services\ProfilePhotoStorageService;
use App\Services\NotificationService;
use App\Services\ShortlistService;
use App\Services\SiteSettingService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Profile', description: 'Profile management endpoints')]
class ProfileController extends ApiController
{
    public function __construct(
        private readonly ProfileCompletionService $completionService,
        private readonly ProfilePhotoStorageService $photoStorage,
        private readonly SiteSettingService $siteSettings,
        private readonly NotificationService $notificationService,
        private readonly SubscriptionFeatureService $featureService,
        private readonly InterestService $interestService,
        private readonly ShortlistService $shortlistService,
        private readonly MatchingService $matchingService,
    ) {}

    #[OA\Get(
        path: '/api/v1/profile',
        summary: "Get authenticated user's own profile",
        security: [['sanctum' => []]],
        tags: ['Profile'],
        responses: [
            new OA\Response(response: 200, description: 'Profile retrieved successfully'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        Log::info('[PROFILE - Show] User ID: ' . $request->user()->id);

        try {
            $user = $request->user()->load([
                'profile', 'religiousDetail', 'familyDetail',
                'educationCareer', 'lifestyle', 'horoscopeDetail',
                'partnerPreference', 'photos',
            ]);

            return $this->successResponse($this->formatProfile($user), 'Profile retrieved successfully.');

        } catch (\Throwable $e) {
            Log::error('[PROFILE - Show] Failed for User ID: ' . $request->user()->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to retrieve profile.');
        }
    }

    #[OA\Get(
        path: '/api/v1/profile/{profileId}',
        summary: 'View another user\'s profile by profile ID (e.g. BON-000001)',
        security: [['sanctum' => []]],
        tags: ['Profile'],
        parameters: [new OA\Parameter(name: 'profileId', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'BON-000001')],
        responses: [
            new OA\Response(response: 200, description: 'Profile retrieved'),
            new OA\Response(response: 404, description: 'Profile not found'),
            new OA\Response(response: 403, description: 'This profile is not visible to you'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function showById(Request $request, string $profileId): JsonResponse
    {
        Log::info('[PROFILE - ShowById] Viewer: ' . $request->user()->id . ' | Profile ID: ' . $profileId);

        try {
            $currentUser = $request->user();

            $profile    = Profile::where('profile_id', $profileId)->firstOrFail();
            $targetUser = $profile->user()->with([
                'profile',
                'religiousDetail',
                'familyDetail',
                'educationCareer',
                'lifestyle',
                'faceScanSession',
                'photos' => fn ($q) => $q->where('is_approved', true)->where('is_private', false),
            ])->first();

            $isBlocked = $currentUser->blocks()->where('blocked_id', $targetUser->id)->exists()
                || $targetUser->blocks()->where('blocked_id', $currentUser->id)->exists();

            if ($isBlocked) {
                Log::warning('[PROFILE - ShowById] Blocked profile access. Viewer: ' . $currentUser->id . ' | Target: ' . $targetUser->id);
                return $this->errorResponse('This profile is not available.', null, 403);
            }

            $isOwnProfile = $currentUser->id === $targetUser->id;

            $hasPaidPlan = $this->featureService->hasPaidSubscription($currentUser);

            if (! $hasPaidPlan) {
                Log::warning('[PROFILE - ShowById] Free plan profile access denied. Viewer: ' . $currentUser->id);

                return $this->errorResponse(
                    'Upgrade your plan to view full profiles.',
                    ['feature' => 'full_profile_access'],
                    403
                );
            }

            $viewsToday = ProfileView::where('viewer_id', $currentUser->id)
                ->where('viewed_id', '!=', $currentUser->id)
                ->whereDate('viewed_at', today())
                ->count();

            $alreadyViewedToday = ProfileView::where('viewer_id', $currentUser->id)
                ->where('viewed_id', $targetUser->id)
                ->whereDate('viewed_at', today())
                ->exists();

            if (! $isOwnProfile && ! $alreadyViewedToday && ! $this->featureService->withinDailyLimit($currentUser, 'profile_views_per_day', $viewsToday)) {
                $limit = (int) $this->featureService->value($currentUser, 'profile_views_per_day');
                Log::warning('[PROFILE - ShowById] Daily view limit reached. Viewer: ' . $currentUser->id);

                return $this->errorResponse(
                    "You have reached your daily profile view limit ({$limit}/day). Upgrade your plan to view more profiles.",
                    ['feature' => 'profile_views_per_day', 'limit' => $limit, 'used' => $viewsToday],
                    403
                );
            }

            if (! $isOwnProfile) {
                $this->recordProfileView($currentUser, $targetUser);
            }

            $viewsTodayAfter = $isOwnProfile || $alreadyViewedToday ? $viewsToday : $viewsToday + 1;
            $accessMeta      = $this->buildProfileAccessMeta($currentUser, $viewsTodayAfter, true);

            $profileData           = $this->formatPublicProfile($targetUser, $currentUser);
            $profileData['access'] = $accessMeta;
            $profileData           = $this->attachViewerContext($profileData, $currentUser, $targetUser, $isOwnProfile);

            Log::info('[PROFILE - ShowById] Success. Viewer: ' . $currentUser->id . ' | Viewed: ' . $targetUser->id);

            return $this->successResponse($profileData, 'Profile retrieved successfully.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[PROFILE - ShowById] Profile not found: ' . $profileId);
            return $this->errorResponse('Profile not found.', null, 404);
        } catch (\Throwable $e) {
            Log::error('[PROFILE - ShowById] Failed. Profile ID: ' . $profileId . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to retrieve profile.');
        }
    }

    #[OA\Put(
        path: '/api/v1/profile',
        summary: 'Update own profile (basic info + sub-details)',
        security: [['sanctum' => []]],
        tags: ['Profile'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'Profile updated successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[PROFILE - Update] User ID: ' . $user->id);

        try {
            DB::transaction(function () use ($request, $user) {
                // Update user-level fields if provided
                $userData = array_filter([
                    'name'               => $request->input('name'),
                    'profile_created_by' => $request->input('profile_created_by'),
                ]);
                if (!empty($userData)) {
                    $user->update($userData);
                }

                $profileData = $request->only([
                    'nick_name', 'profile_created_for', 'looking_for',
                    'dob', 'height_cm', 'weight_kg', 'body_type',
                    'eye_color', 'hair_color', 'complexion', 'blood_group',
                    'marital_status', 'disability', 'mother_tongue', 'nationality',
                    'country', 'state', 'city', 'postal_code', 'residing_status',
                    'about_me', 'what_looking_for', 'privacy_settings',
                ]);

                $user->profile()->updateOrCreate(['user_id' => $user->id], $profileData);

                $religiousData = $request->only([
                    'religion', 'caste', 'sub_caste', 'gotra', 'manglik_status',
                    'religiousness', 'pray',
                ]);
                if (array_filter($religiousData)) {
                    $user->religiousDetail()->updateOrCreate(['user_id' => $user->id], $religiousData);
                }

                $familyData = $request->only([
                    'family_type', 'family_status', 'family_income_bdt_per_month',
                    'father_occupation', 'mother_occupation', 'brothers_count', 'sisters_count',
                    'has_children', 'child_living_status', 'family_values', 'sibling_position',
                ]);
                if (array_filter($familyData)) {
                    $user->familyDetail()->updateOrCreate(['user_id' => $user->id], $familyData);
                }

                $educationData = $request->only([
                    'highest_education', 'college_university', 'institution_name_year',
                    'employer_name', 'job_location', 'designation', 'experience_years',
                    'profession', 'employed_in', 'annual_income_bdt',
                ]);
                if (array_filter($educationData)) {
                    $user->educationCareer()->updateOrCreate(['user_id' => $user->id], $educationData);
                }

                $lifestyleData = $request->only(['diet', 'smoking', 'drinking', 'eye_wear', 'hobbies', 'languages_known']);
                if (array_filter($lifestyleData)) {
                    $user->lifestyle()->updateOrCreate(['user_id' => $user->id], $lifestyleData);
                }

                $horoscopeData = $request->only(['birth_place', 'birth_time', 'rashi', 'nakshatra', 'manglik']);
                if (array_filter($horoscopeData)) {
                    $user->horoscopeDetail()->updateOrCreate(['user_id' => $user->id], $horoscopeData);
                }

                $this->completionService->recalculateAndSave($user->fresh());
            });

            $user->refresh()->load([
                'profile', 'religiousDetail', 'familyDetail',
                'educationCareer', 'lifestyle', 'horoscopeDetail', 'partnerPreference',
            ]);

            Log::info('[PROFILE - Update] Success for User ID: ' . $user->id);

            return $this->successResponse($this->formatProfile($user), 'Profile updated successfully.');

        } catch (\Throwable $e) {
            Log::error('[PROFILE - Update] Failed for User ID: ' . $user->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to update profile. Please try again.');
        }
    }

    #[OA\Put(
        path: '/api/v1/preferences',
        summary: 'Update partner preferences',
        security: [['sanctum' => []]],
        tags: ['Profile'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [
            new OA\Response(response: 200, description: 'Preferences updated successfully'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function updatePreferences(UpdatePreferenceRequest $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[PROFILE - UpdatePreferences] User ID: ' . $user->id);

        try {
            DB::transaction(function () use ($request, $user) {
                $user->partnerPreference()->updateOrCreate(
                    ['user_id' => $user->id],
                    $request->validated()
                );

                $this->completionService->recalculateAndSave($user->fresh());
            });

            Log::info('[PROFILE - UpdatePreferences] Success for User ID: ' . $user->id);

            return $this->successResponse($user->fresh()->partnerPreference, 'Partner preferences updated successfully.');

        } catch (\Throwable $e) {
            Log::error('[PROFILE - UpdatePreferences] Failed for User ID: ' . $user->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to update preferences. Please try again.');
        }
    }

    #[OA\Get(
        path: '/api/v1/profile/completion',
        summary: 'Get profile completion status',
        security: [['sanctum' => []]],
        tags: ['Profile'],
        responses: [
            new OA\Response(response: 200, description: 'Profile completion status'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function completionStatus(Request $request): JsonResponse
    {
        Log::info('[PROFILE - CompletionStatus] User ID: ' . $request->user()->id);

        try {
            $user       = $request->user()->load(['profile', 'religiousDetail', 'familyDetail', 'educationCareer', 'lifestyle', 'horoscopeDetail', 'partnerPreference', 'photos']);
            $percentage = $this->completionService->calculate($user);

            // has_basic_info is true only when ALL 8 scored basic fields are filled,
            // so the completion bar accurately shows which section still needs work.
            $basicRequiredFields = ['dob', 'height_cm', 'weight_kg', 'complexion', 'marital_status', 'mother_tongue', 'country'];
            $allBasicFilled = $user->profile
                && collect($basicRequiredFields)->every(fn ($f) => ! empty($user->profile->$f));

            return $this->successResponse([
                'percentage'           => $percentage,
                'has_basic_info'       => $allBasicFilled,
                'has_religious_detail' => ! empty($user->religiousDetail?->religion),
                'has_family_detail'    => ! empty($user->familyDetail?->family_type),
                'has_education'        => ! empty($user->educationCareer?->highest_education),
                'has_lifestyle'        => ! empty($user->lifestyle?->diet),
                'has_horoscope'        => ! empty($user->horoscopeDetail?->rashi),
                'has_preferences'      => ! empty($user->partnerPreference?->age_min),
                'has_photo'            => $user->photos()->exists(),
                'has_about_me'         => ! empty($user->profile?->about_me) && mb_strlen($user->profile->about_me) >= 50,
            ], 'Profile completion status retrieved.');

        } catch (\Throwable $e) {
            Log::error('[PROFILE - CompletionStatus] Failed for User ID: ' . $request->user()->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to retrieve profile completion status.');
        }
    }

    #[OA\Post(
        path: '/api/v1/profile/photos',
        summary: 'Upload a profile photo',
        security: [['sanctum' => []]],
        tags: ['Profile'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'photo', type: 'string', format: 'binary'),
                        new OA\Property(property: 'is_private', type: 'boolean', example: false),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Photo uploaded, pending moderation'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo'      => ['required', 'image', 'mimes:jpeg,jpg,png,webp'],
            'is_private' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        Log::info('[PROFILE PHOTO - Upload] User ID: ' . $user->id);

        try {
            $image    = Image::read($request->file('photo'));
            $image->scaleDown(width: 1200);
            $encoded  = $image->toJpeg(85);

            $stored = $this->photoStorage->store((string) $encoded, $user->id);

            $autoApprove = $this->siteSettings->boolean('photo_auto_approval_enabled', false);

            $photo = ProfilePhoto::create([
                'user_id'           => $user->id,
                'file_path'         => $stored['path'],
                'is_primary'        => false,
                'is_approved'       => $autoApprove,
                'is_private'        => (bool) $request->boolean('is_private', false),
                'moderation_status' => $autoApprove ? 'approved' : 'pending',
            ]);

            if ($autoApprove) {
                $this->completionService->recalculateAndSave($user->fresh());
                $this->notificationService->notifyPhotoApproved($user);
            }

            Log::info('[PROFILE PHOTO - Upload] Success. Photo ID: ' . $photo->id . ' | File: ' . $stored['path'] . ' | User ID: ' . $user->id);

            $message = $autoApprove
                ? 'Photo uploaded and approved successfully.'
                : 'Photo uploaded successfully. It will be visible after admin approval.';

            return $this->successResponse($photo->fresh(), $message, 201);

        } catch (\Throwable $e) {
            Log::error('[PROFILE PHOTO - Upload] Failed for User ID: ' . $user->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Photo upload failed. Please try again.');
        }
    }

    #[OA\Delete(
        path: '/api/v1/profile/photos/{photoId}',
        summary: 'Delete a profile photo',
        security: [['sanctum' => []]],
        tags: ['Profile'],
        parameters: [new OA\Parameter(name: 'photoId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Photo deleted'),
            new OA\Response(response: 404, description: 'Photo not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function deletePhoto(Request $request, int $photoId): JsonResponse
    {
        $user = $request->user();
        Log::info('[PROFILE PHOTO - Delete] User ID: ' . $user->id . ' | Photo ID: ' . $photoId);

        try {
            $photo = ProfilePhoto::where('id', $photoId)->where('user_id', $user->id)->firstOrFail();

            DB::transaction(function () use ($photo, $user, $photoId) {
                $this->photoStorage->delete($photo->file_path);
                $photo->delete();
                $this->completionService->recalculateAndSave($user->fresh());
            });

            Log::info('[PROFILE PHOTO - Delete] Success. Photo ID: ' . $photoId . ' | User ID: ' . $user->id);

            return $this->successResponse(null, 'Photo deleted successfully.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[PROFILE PHOTO - Delete] Photo not found. Photo ID: ' . $photoId . ' | User ID: ' . $user->id);
            return $this->errorResponse('Photo not found.', null, 404);
        } catch (\Throwable $e) {
            Log::error('[PROFILE PHOTO - Delete] Failed. Photo ID: ' . $photoId . ' | User ID: ' . $user->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to delete photo. Please try again.');
        }
    }

    #[OA\Put(
        path: '/api/v1/profile/photos/{photoId}/primary',
        summary: 'Set a photo as primary',
        security: [['sanctum' => []]],
        tags: ['Profile'],
        parameters: [new OA\Parameter(name: 'photoId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Primary photo set'),
            new OA\Response(response: 403, description: 'Photo must be approved first'),
            new OA\Response(response: 404, description: 'Photo not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function setPrimaryPhoto(Request $request, int $photoId): JsonResponse
    {
        $user = $request->user();
        Log::info('[PROFILE PHOTO - SetPrimary] User ID: ' . $user->id . ' | Photo ID: ' . $photoId);

        try {
            $photo = ProfilePhoto::where('id', $photoId)->where('user_id', $user->id)->firstOrFail();

            if (! $photo->is_approved) {
                Log::warning('[PROFILE PHOTO - SetPrimary] Photo not approved. Photo ID: ' . $photoId . ' | User ID: ' . $user->id);
                return $this->errorResponse('Photo must be approved by admin before setting as primary.', null, 403);
            }

            DB::transaction(function () use ($user, $photo) {
                $user->photos()->update(['is_primary' => false]);
                $photo->update(['is_primary' => true]);
            });

            Log::info('[PROFILE PHOTO - SetPrimary] Success. Photo ID: ' . $photoId . ' | User ID: ' . $user->id);

            return $this->successResponse($photo->fresh(), 'Primary photo updated.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[PROFILE PHOTO - SetPrimary] Photo not found. Photo ID: ' . $photoId . ' | User ID: ' . $user->id);
            return $this->errorResponse('Photo not found.', null, 404);
        } catch (\Throwable $e) {
            Log::error('[PROFILE PHOTO - SetPrimary] Failed. Photo ID: ' . $photoId . ' | User ID: ' . $user->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to set primary photo. Please try again.');
        }
    }

    private function recordProfileView(User $viewer, User $viewed): void
    {
        if ($viewer->id === $viewed->id) {
            return;
        }

        try {
            $exists = ProfileView::where('viewer_id', $viewer->id)
                ->where('viewed_id', $viewed->id)
                ->whereDate('viewed_at', today())
                ->exists();

            if (! $exists) {
                ProfileView::create([
                    'viewer_id' => $viewer->id,
                    'viewed_id' => $viewed->id,
                    'viewed_at' => now(),
                ]);
                Log::info('[PROFILE VIEW - Record] Viewer: ' . $viewer->id . ' | Viewed: ' . $viewed->id);

                if ($this->featureService->hasPaidSubscription($viewed)) {
                    $this->notificationService->notifyProfileViewed($viewed, $viewer);
                    SendProfileViewedEmail::dispatch($viewer->id, $viewed->id);
                }
            }
        } catch (\Throwable $e) {
            // Non-critical — log but do not fail the request
            Log::error('[PROFILE VIEW - Record] Failed. Viewer: ' . $viewer->id . ' | Viewed: ' . $viewed->id . ' | Error: ' . $e->getMessage());
        }
    }

    private function formatProfile(User $user): array
    {
        $photos = $user->photos;
        $primaryPhoto = $photos->firstWhere('is_primary', true) ?? $photos->first();

        return [
            'id'                 => $user->id,
            'name'               => $user->name,
            'gender'             => $user->gender,
            'profile_created_by' => $user->profile_created_by,
            'subscription_plan'  => $user->subscription_plan,
            'email_verified_at'  => $user->email_verified_at,
            'profile'            => $user->profile ? array_merge($user->profile->toArray(), [
                'dob' => $user->profile->dob?->format('Y-m-d'),
            ]) : null,
            'religious_detail'   => $user->religiousDetail,
            'family_detail'      => $user->familyDetail,
            'education_career'   => $user->educationCareer,
            'lifestyle'          => $user->lifestyle,
            'horoscope_detail'   => $user->horoscopeDetail,
            'partner_preference' => $user->partnerPreference,
            'primary_photo'      => $primaryPhoto?->file_path,
            'photos'             => $photos,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProfileAccessMeta(User $viewer, int $viewsToday, bool $hasPaidPlan): array
    {
        $limit = (int) $this->featureService->value($viewer, 'profile_views_per_day');

        return [
            'full_profile'          => $hasPaidPlan,
            'profile_views_per_day' => [
                'limit'     => $limit,
                'used'      => $viewsToday,
                'unlimited' => $limit < 0,
                'remaining' => $limit < 0 ? null : max(0, $limit - $viewsToday),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $profileData
     * @return array<string, mixed>
     */
    private function attachViewerContext(array $profileData, User $viewer, User $target, bool $isOwnProfile): array
    {
        if ($isOwnProfile) {
            return $profileData;
        }

        $interestMeta = $this->interestService->buildStatusPayloadForUser($viewer, $target->id);

        $profileData['connection_status']  = $interestMeta['status'];
        $profileData['interest_id']        = $interestMeta['interest_id'];
        $profileData['is_interest_sender'] = $interestMeta['is_sender'];
        $profileData['can_send_interest']  = $interestMeta['can_send_interest'];

        $this->shortlistService->attachShortlistStatus($viewer, collect([$target]));
        $profileData['is_shortlisted'] = (bool) $target->getAttribute('is_shortlisted');

        if ($this->featureService->can($viewer, 'compatibility_score_visible')) {
            $matchScore = MatchScore::findForPair($viewer->id, $target->id);

            if (! $matchScore) {
                $matchScore = $this->matchingService->calculateAndStoreScore($viewer, $target, ignoreMinimum: true);
            }

            if ($matchScore) {
                $profileData['compatibility_score'] = [
                    'score'           => (float) $matchScore->score,
                    'score_breakdown' => $matchScore->score_breakdown,
                    'calculated_at'   => $matchScore->calculated_at,
                ];
            }
        }

        return $profileData;
    }

    private function formatPublicProfile(User $user, User $viewer): array
    {
        $privacySettings = $user->profile?->privacy_settings ?? [];
        $showPhoto       = $privacySettings['show_photo_to'] ?? 'all';

        $isMutualConnection = Interest::where(function ($q) use ($user, $viewer) {
            $q->where('sender_id', $user->id)->where('receiver_id', $viewer->id);
        })->orWhere(function ($q) use ($user, $viewer) {
            $q->where('sender_id', $viewer->id)->where('receiver_id', $user->id);
        })->where('status', 'accepted')->exists();

        $photosQuery = $user->photos()->where('is_approved', true);
        if ($showPhoto === 'connections_only' && ! $isMutualConnection) {
            $photosQuery->whereRaw('0=1');
        } elseif ($showPhoto === 'none') {
            $photosQuery->whereRaw('0=1');
        }

        $photos = $photosQuery->get();
        $primaryPhoto = $photos->firstWhere('is_primary', true) ?? $photos->first();

        return [
            'id'             => $user->id,
            'name'           => $user->name,
            'gender'         => $user->gender,
            'profile'        => $user->profile ? [
                'profile_id'                    => $user->profile->profile_id,
                'dob'                           => $user->profile->dob,
                'height_cm'                     => $user->profile->height_cm,
                'weight_kg'                     => $user->profile->weight_kg,
                'complexion'                    => $user->profile->complexion,
                'marital_status'                => $user->profile->marital_status,
                'mother_tongue'                 => $user->profile->mother_tongue,
                'nationality'                   => $user->profile->nationality,
                'country'                       => $user->profile->country,
                'state'                         => $user->profile->state,
                'city'                          => $user->profile->city,
                'about_me'                      => $user->profile->about_me,
                'is_verified'                   => $user->faceScanSession?->status === 'approved',
                'profile_completion_percentage' => $user->profile->profile_completion_percentage,
                'last_seen_at'                  => ($privacySettings['show_online_status'] ?? true) ? $user->profile->last_seen_at : null,
            ] : null,
            'face_scan_status'  => $user->faceScanSession?->status,
            'religious_detail'  => $user->religiousDetail ? ['religion' => $user->religiousDetail->religion, 'caste' => $user->religiousDetail->caste] : null,
            'family_detail'     => $user->familyDetail ? ['family_type' => $user->familyDetail->family_type, 'family_status' => $user->familyDetail->family_status] : null,
            'education_career'  => $user->educationCareer ? ['highest_education' => $user->educationCareer->highest_education, 'profession' => $user->educationCareer->profession, 'employed_in' => $user->educationCareer->employed_in] : null,
            'lifestyle'         => $user->lifestyle ? ['diet' => $user->lifestyle->diet, 'smoking' => $user->lifestyle->smoking, 'drinking' => $user->lifestyle->drinking, 'hobbies' => $user->lifestyle->hobbies] : null,
            'primary_photo'     => $primaryPhoto?->file_path,
            'photos'            => $photos,
            'is_connection'     => $isMutualConnection,
        ];
    }
}

