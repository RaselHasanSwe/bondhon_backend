<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\ProfilePhoto;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Dashboard', description: 'Admin dashboard statistics')]
class AdminDashboardController extends ApiController
{
    #[OA\Get(
        path: '/api/v1/admin/dashboard',
        summary: 'Get admin dashboard statistics',
        security: [['sanctum' => []]],
        tags: ['Admin - Dashboard'],
        responses: [
            new OA\Response(response: 200, description: 'Dashboard statistics'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function stats(Request $request): JsonResponse
    {
        Log::info('[ADMIN DASHBOARD - Stats] Request by Admin ID: ' . $request->user()->id);

        try {
            $stats = [
                'total_users'          => User::count(),
                'active_today'         => User::whereHas('profile', fn ($q) => $q->whereDate('last_seen_at', today()))->count(),
                'pending_photos'       => ProfilePhoto::where('moderation_status', 'pending')->count(),
                'pending_reports'      => Report::where('status', 'pending')->count(),
                'new_users_today'      => User::whereDate('created_at', today())->count(),
                'verified_users'       => User::whereHas('profile', fn ($q) => $q->where('is_verified', true))->count(),
                'banned_users'         => User::where('is_banned', true)->count(),
                'active_subscriptions' => \App\Models\Subscription::where('status', 'active')->where('expires_at', '>', now())->count(),
            ];

            Log::info('[ADMIN DASHBOARD - Stats] Successfully retrieved stats for Admin ID: ' . $request->user()->id);

            return $this->successResponse($stats, 'Dashboard statistics retrieved.');

        } catch (\Throwable $e) {
            Log::error('[ADMIN DASHBOARD - Stats] Failed for Admin ID: ' . $request->user()->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to retrieve dashboard statistics.');
        }
    }
}
