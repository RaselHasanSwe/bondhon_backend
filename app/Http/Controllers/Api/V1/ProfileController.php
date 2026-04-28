<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Profile\UpdatePreferenceRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\ProfileView;
use App\Models\Interest;
use App\Models\User;
use App\Services\ProfileCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Profile', description: 'Profile management endpoints')]
class ProfileController extends ApiController
{
    public function __construct(private readonly ProfileCompletionService $completionService) {}

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
                'photos' => fn ($q) => $q->where('is_approved', true)->where('is_private', false),
            ])->first();

            $isBlocked = $currentUser->blocks()->where('blocked_id', $targetUser->id)->exists()
                || $targetUser->blocks()->where('blocked_id', $currentUser->id)->exists();

            if ($isBlocked) {
                Log::warning('[PROFILE - ShowById] Blocked profile access. Viewer: ' . $currentUser->id . ' | Target: ' . $targetUser->id);
                return $this->errorResponse('This profile is not available.', null, 403);
            }

            $this->recordProfileView($currentUser, $targetUser);

            Log::info('[PROFILE - ShowById] Success. Viewer: ' . $currentUser->id . ' | Viewed: ' . $targetUser->id);

            return $this->successResponse($this->formatPublicProfile($targetUser, $currentUser), 'Profile retrieved successfully.');

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
                $profileData = $request->only([
                    'dob', 'height_cm', 'weight_kg', 'complexion', 'blood_group',
                    'marital_status', 'mother_tongue', 'nationality', 'country', 'state',
                    'city', 'about_me', 'privacy_settings',
                ]);

                $user->profile()->updateOrCreate(['user_id' => $user->id], $profileData);

                $religiousData = $request->only(['religion', 'caste', 'sub_caste', 'gotra', 'manglik_status']);
                if (array_filter($religiousData)) {
                    $user->religiousDetail()->updateOrCreate(['user_id' => $user->id], $religiousData);
                }

                $familyData = $request->only([
                    'family_type', 'family_status', 'family_income_bdt_per_month',
                    'father_occupation', 'mother_occupation', 'brothers_count', 'sisters_count',
                ]);
                if (array_filter($familyData)) {
                    $user->familyDetail()->updateOrCreate(['user_id' => $user->id], $familyData);
                }

                $educationData = $request->only([
                    'highest_education', 'college_university', 'profession', 'employed_in', 'annual_income_bdt',
                ]);
                if (array_filter($educationData)) {
                    $user->educationCareer()->updateOrCreate(['user_id' => $user->id], $educationData);
                }

                $lifestyleData = $request->only(['diet', 'smoking', 'drinking', 'hobbies', 'languages_known']);
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

            return $this->successResponse([
                'percentage'           => $percentage,
                'has_basic_info'       => ! empty($user->profile?->dob),
                'has_religious_detail' => ! empty($user->religiousDetail?->religion),
                'has_family_detail'    => ! empty($user->familyDetail?->family_type),
                'has_education'        => ! empty($user->educationCareer?->highest_education),
                'has_lifestyle'        => ! empty($user->lifestyle?->diet),
                'has_horoscope'        => ! empty($user->horoscopeDetail?->rashi),
                'has_preferences'      => ! empty($user->partnerPreference?->age_min),
                'has_photo'            => $user->photos()->where('is_approved', true)->exists(),
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
            'photo'      => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'is_private' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        Log::info('[PROFILE PHOTO - Upload] User ID: ' . $user->id);

        try {
            $image    = Image::read($request->file('photo'));
            $image->scaleDown(width: 1200);

            $filename = 'photos/' . $user->id . '/' . uniqid('photo_', true) . '.jpg';
            $encoded  = $image->toJpeg(85);

            Storage::disk(config('filesystems.default', 'public'))->put($filename, $encoded);

            $photo = ProfilePhoto::create([
                'user_id'           => $user->id,
                'file_path'         => $filename,
                'is_primary'        => false,
                'is_approved'       => false,
                'is_private'        => (bool) $request->boolean('is_private', false),
                'moderation_status' => 'pending',
            ]);

            Log::info('[PROFILE PHOTO - Upload] Success. Photo ID: ' . $photo->id . ' | File: ' . $filename . ' | User ID: ' . $user->id);

            return $this->successResponse($photo, 'Photo uploaded successfully. It will be visible after admin approval.', 201);

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
                Storage::disk(config('filesystems.default', 'public'))->delete($photo->file_path);
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
            }
        } catch (\Throwable $e) {
            // Non-critical — log but do not fail the request
            Log::error('[PROFILE VIEW - Record] Failed. Viewer: ' . $viewer->id . ' | Viewed: ' . $viewed->id . ' | Error: ' . $e->getMessage());
        }
    }

    private function formatProfile(User $user): array
    {
        return [
            'id'                 => $user->id,
            'name'               => $user->name,
            'gender'             => $user->gender,
            'profile_created_by' => $user->profile_created_by,
            'subscription_plan'  => $user->subscription_plan,
            'email_verified_at'  => $user->email_verified_at,
            'profile'            => $user->profile,
            'religious_detail'   => $user->religiousDetail,
            'family_detail'      => $user->familyDetail,
            'education_career'   => $user->educationCareer,
            'lifestyle'          => $user->lifestyle,
            'horoscope_detail'   => $user->horoscopeDetail,
            'partner_preference' => $user->partnerPreference,
            'photos'             => $user->photos,
        ];
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
                'is_verified'                   => $user->profile->is_verified,
                'profile_completion_percentage' => $user->profile->profile_completion_percentage,
                'last_seen_at'                  => ($privacySettings['show_online_status'] ?? true) ? $user->profile->last_seen_at : null,
            ] : null,
            'religious_detail'  => $user->religiousDetail ? ['religion' => $user->religiousDetail->religion, 'caste' => $user->religiousDetail->caste] : null,
            'family_detail'     => $user->familyDetail ? ['family_type' => $user->familyDetail->family_type, 'family_status' => $user->familyDetail->family_status] : null,
            'education_career'  => $user->educationCareer ? ['highest_education' => $user->educationCareer->highest_education, 'profession' => $user->educationCareer->profession, 'employed_in' => $user->educationCareer->employed_in] : null,
            'lifestyle'         => $user->lifestyle ? ['diet' => $user->lifestyle->diet, 'smoking' => $user->lifestyle->smoking, 'drinking' => $user->lifestyle->drinking, 'hobbies' => $user->lifestyle->hobbies] : null,
            'photos'            => $photosQuery->get(),
            'is_connection'     => $isMutualConnection,
        ];
    }
}

