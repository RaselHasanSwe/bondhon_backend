<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\User;
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
            $query = User::with('profile')->withTrashed();

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
    public function ban(Request $request, int $id): JsonResponse
    {
        $request->validate(['is_banned' => ['required', 'boolean']]);

        Log::info('[ADMIN USER - Ban] Admin ID: ' . $request->user()->id . ' | Target User ID: ' . $id . ' | is_banned: ' . ($request->is_banned ? 'true' : 'false'));

        try {
            $user = User::withTrashed()->findOrFail($id);

            DB::transaction(function () use ($user, $request) {
                $user->update(['is_banned' => $request->is_banned]);

                // Revoke all tokens when banning
                if ($request->is_banned) {
                    $user->tokens()->delete();
                }
            });

            $action = $request->is_banned ? 'banned' : 'unbanned';
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
}
