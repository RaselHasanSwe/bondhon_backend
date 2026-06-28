<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\User;
use App\Services\FaceScanReviewService;
use App\Services\UserBanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Users', description: 'Admin user management')]
class AdminUserController extends ApiController
{
    #[OA\Get(
        path: '/api/v1/admin/users',
        summary: 'List all users with pagination',
        security: [['sanctum' => []]],
        tags: ['Admin - Users'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Users list'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        Log::info('[ADMIN USER - Index] Request by Admin ID: ' . $request->user()->id);

        try {
            $query = User::with(['profile', 'faceScanSession.latestCapture'])->withTrashed();

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            if ($request->filled('is_banned')) {
                $query->where('is_banned', (bool) $request->is_banned);
            }

            $users = $query->orderByDesc('created_at')->paginate(20);

            Log::info('[ADMIN USER - Index] Retrieved ' . $users->total() . ' users for Admin ID: ' . $request->user()->id);

            return $this->successResponse($users, 'Users retrieved successfully.');

        } catch (\Throwable $e) {
            Log::error('[ADMIN USER - Index] Failed for Admin ID: ' . $request->user()->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to retrieve users.');
        }
    }

    #[OA\Get(
        path: '/api/v1/admin/users/{id}',
        summary: 'Get a full user profile for admin review',
        security: [['sanctum' => []]],
        tags: ['Admin - Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'User details'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            /** @var User $user */
            $user = User::withTrashed()->with([
                'profile',
                'religiousDetail',
                'familyDetail',
                'educationCareer',
                'lifestyle',
                'horoscopeDetail',
                'partnerPreference',
                'photos',
                'faceScanSession.captures',
                'faceScanSession.latestCapture',
            ])->findOrFail($id);

            return $this->successResponse($this->formatUserDetail($user), 'User details retrieved successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('User not found.', null, 404);
        } catch (\Throwable $e) {
            Log::error('[ADMIN USER - Show] Failed for User ID: ' . $id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to retrieve user details.');
        }
    }

    #[OA\Put(
        path: '/api/v1/admin/users/{id}/ban',
        summary: 'Ban or unban a user',
        security: [['sanctum' => []]],
        tags: ['Admin - Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'is_banned', type: 'boolean', example: true),
                new OA\Property(property: 'reason', type: 'string', example: 'Violation of terms'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'User ban status updated'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function ban(Request $request, int $id, UserBanService $banService): JsonResponse
    {
        $request->validate([
            'is_banned'               => ['required', 'boolean'],
            'reason'                  => ['required_if:is_banned,true', 'nullable', 'string', 'min:10', 'max:2000'],
            'send_email_notification' => ['nullable', 'boolean'],
        ]);

        Log::info('[ADMIN USER - Ban] Admin ID: ' . $request->user()->id . ' | Target User ID: ' . $id . ' | is_banned: ' . ($request->is_banned ? 'true' : 'false'));

        try {
            $user = User::withTrashed()->findOrFail($id);

            if ($request->user()->id === $user->id) {
                return $this->errorResponse('You cannot ban yourself.', null, 422);
            }

            DB::transaction(function () use ($user, $request, $banService) {
                if ($request->boolean('is_banned')) {
                    $banService->ban(
                        $user,
                        $request->input('reason', 'Violation of terms of service.'),
                        $request->boolean('send_email_notification'),
                    );
                } else {
                    $banService->reactivate($user);
                }
            });

            $action = $request->boolean('is_banned') ? 'banned' : 'reactivated';
            Log::info('[ADMIN USER - ' . strtoupper($action) . '] Admin: ' . $request->user()->id . ' | User: ' . $id);

            return $this->successResponse($user->fresh(), 'User has been ' . $action . '.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[ADMIN USER - Ban] User not found. User ID: ' . $id);
            return $this->errorResponse('User not found.', null, 404);
        } catch (\Throwable $e) {
            Log::error('[ADMIN USER - Ban] Failed. Admin: ' . $request->user()->id . ' | User: ' . $id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to update user ban status.');
        }
    }

    #[OA\Put(
        path: '/api/v1/admin/users/{id}/verify',
        summary: 'Verify a user profile',
        security: [['sanctum' => []]],
        tags: ['Admin - Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'User verified'),
            new OA\Response(response: 400, description: 'User has no profile'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function verify(Request $request, int $id): JsonResponse
    {
        Log::info('[ADMIN USER - Verify] Admin ID: ' . $request->user()->id . ' | Target User ID: ' . $id);

        try {
            $user = User::findOrFail($id);

            if (! $user->profile) {
                Log::warning('[ADMIN USER - Verify] User has no profile. User ID: ' . $id);
                return $this->errorResponse('User does not have a profile.', null, 400);
            }

            DB::transaction(function () use ($user) {
                $user->profile->update(['is_verified' => true]);
            });

            Log::info('[ADMIN USER - Verify] Successfully verified User ID: ' . $id . ' by Admin: ' . $request->user()->id);

            return $this->successResponse($user->profile->fresh(), 'User profile has been verified.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[ADMIN USER - Verify] User not found. User ID: ' . $id);
            return $this->errorResponse('User not found.', null, 404);
        } catch (\Throwable $e) {
            Log::error('[ADMIN USER - Verify] Failed. Admin: ' . $request->user()->id . ' | User: ' . $id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to verify user profile.');
        }
    }

    #[OA\Put(
        path: '/api/v1/admin/users/{id}/face-scan',
        summary: 'Review a user face-scan submission',
        security: [['sanctum' => []]],
        tags: ['Admin - Users'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            required: ['decision'],
            properties: [
                new OA\Property(property: 'decision', type: 'string', enum: ['approved', 'rejected', 'ban'], example: 'approved'),
                new OA\Property(property: 'review_note', type: 'string', example: 'Face verified successfully.'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Face scan reviewed'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 400, description: 'Face scan missing'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function reviewFaceScan(Request $request, int $id, FaceScanReviewService $reviewService): JsonResponse
    {
        $request->validate([
            'decision' => ['required', 'in:approved,rejected,ban'],
            'review_note' => ['required_if:decision,rejected', 'nullable', 'string', 'max:2000'],
            'send_email_notification' => ['nullable', 'boolean'],
        ]);

        try {
            $user = User::withTrashed()->with('faceScanSession')->findOrFail($id);

            if (! $user->faceScanSession) {
                return $this->errorResponse('Face scan session not found.', null, 400);
            }

            $reviewService->review(
                $user,
                $user->faceScanSession,
                $request->string('decision')->toString(),
                $request->input('review_note'),
                $request->user()->id,
                $request->boolean('send_email_notification'),
            );

            return $this->successResponse($user->fresh(['faceScanSession.captures']), 'Face scan review updated successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('User not found.', null, 404);
        } catch (\Throwable $e) {
            Log::error('[ADMIN USER - FaceScanReview] Failed for User ID: ' . $id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to review face scan.');
        }
    }

    private function formatUserDetail($user): array
    {
        $session = $user->faceScanSession;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'gender' => $user->gender,
                'profile_created_by' => $user->profile_created_by,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'is_banned' => $user->is_banned,
                'ban_reason' => $user->ban_reason,
                'banned_at' => $user->banned_at,
                'subscription_plan' => $user->subscription_plan,
                'subscription_expires_at' => $user->subscription_expires_at,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'deleted_at' => $user->deleted_at,
                'profile' => $user->profile,
                'religious_detail' => $user->religiousDetail,
                'family_detail' => $user->familyDetail,
                'education_career' => $user->educationCareer,
                'lifestyle' => $user->lifestyle,
                'horoscope_detail' => $user->horoscopeDetail,
                'partner_preference' => $user->partnerPreference,
                'photos' => $user->photos,
            ],
            'face_scan' => $session ? [
                'id' => $session->id,
                'status' => $session->status,
                'completed_at' => $session->completed_at,
                'reviewed_at' => $session->reviewed_at,
                'review_note' => $session->review_note,
                'reviewed_by' => $session->reviewed_by,
                'captures' => $session->captures->map(fn ($capture) => [
                    'id' => $capture->id,
                    'capture_key' => $capture->capture_key,
                    'image_path' => $capture->image_path,
                    'metadata' => $capture->metadata,
                    'captured_at' => $capture->captured_at,
                ])->values(),
            ] : null,
        ];
    }
}
