<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Services\BroadcastNotificationService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Notifications', description: 'Admin notification management & broadcast')]
class AdminNotificationController extends ApiController
{
    public function __construct(
        private readonly BroadcastNotificationService $broadcastService,
    ) {}

    /**
     * POST /api/v1/admin/notifications/broadcast
     * Send a broadcast notification to all (or filtered) users.
     */
    #[OA\Post(
        path: '/api/v1/admin/notifications/broadcast',
        summary: 'Broadcast a notification to users',
        security: [['sanctum' => []]],
        tags: ['Admin - Notifications'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'message'],
                properties: [
                    new OA\Property(property: 'title',   type: 'string',  example: 'System Maintenance'),
                    new OA\Property(property: 'message', type: 'string',  example: 'We will have maintenance tonight.'),
                    new OA\Property(property: 'target',  type: 'string',  enum: ['all', 'free', 'silver', 'gold', 'platinum'], example: 'all'),
                    new OA\Property(property: 'channel', type: 'string',  enum: ['application', 'email', 'both'], example: 'application'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Notification broadcast sent'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function broadcast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'target'  => ['nullable', 'string', 'in:all,free,silver,gold,platinum'],
            'channel' => ['nullable', 'string', 'in:application,email,both'],
        ]);

        $target  = $validated['target']  ?? 'all';
        $channel = $validated['channel'] ?? 'application';

        Log::info('[ADMIN BROADCAST] Admin: ' . $request->user()->id
            . ' | Target: ' . $target . ' | Channel: ' . $channel
            . ' | Title: ' . $validated['title']);

        try {
            $count = $this->broadcastService->broadcast(
                $validated['title'],
                $validated['message'],
                $target,
                $channel,
            );

            return $this->successResponse(
                ['notified_count' => $count],
                "Notification broadcast to {$count} user(s)."
            );
        } catch (\Throwable $e) {
            Log::error('[ADMIN BROADCAST] Failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to broadcast notification.');
        }
    }

    /**
     * GET /api/v1/admin/notifications
     * Paginated list of all system notifications (all users).
     */
    #[OA\Get(
        path: '/api/v1/admin/notifications',
        summary: 'List all notifications (admin view)',
        security: [['sanctum' => []]],
        tags: ['Admin - Notifications'],
        parameters: [
            new OA\Parameter(name: 'page',     in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type',     in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notifications list'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->integer('per_page', 20), 100);
        $query   = \App\Models\Notification::with('notifiable')
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('user_id')) {
            $query->where('notifiable_type', \App\Models\User::class)
                  ->where('notifiable_id', $request->integer('user_id'));
        }

        $notifications = $query->paginate($perPage);

        return $this->successResponse([
            'data'       => \App\Http\Resources\NotificationResource::collection($notifications->getCollection()),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ], 'Notifications retrieved.');
    }
}

