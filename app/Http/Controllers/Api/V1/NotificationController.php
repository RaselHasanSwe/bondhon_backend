<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends ApiController
{
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * GET /api/v1/notifications
     * Paginated notification list for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user          = $request->user();
        $perPage       = min($request->integer('per_page', 20), 50);
        $unreadOnly    = $request->boolean('unread_only', false);

        $query = $user->notifications()->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        $notifications = $query->paginate($perPage);
        $unreadCount   = $this->notificationService->unreadCount($user);

        return $this->successResponse([
            'data'         => NotificationResource::collection($notifications->getCollection()),
            'unread_count' => $unreadCount,
            'pagination'   => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ], 'Notifications retrieved.');
    }

    /**
     * PUT /api/v1/notifications/{id}/read
     * Mark a single notification as read.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        Log::info('[NOTIFICATION - MarkRead] User: ' . $user->id . ' | ID: ' . $id);

        $success = $this->notificationService->markRead($user, $id);

        if (!$success) {
            return $this->errorResponse('Notification not found.', null, 404);
        }

        return $this->successResponse(null, 'Notification marked as read.');
    }

    /**
     * PUT /api/v1/notifications/read-all
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user    = $request->user();
        $updated = $this->notificationService->markAllRead($user);

        Log::info('[NOTIFICATION - MarkAllRead] User: ' . $user->id . ' | Count: ' . $updated);

        return $this->successResponse(['updated' => $updated], 'All notifications marked as read.');
    }

    /**
     * DELETE /api/v1/notifications/{id}
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user    = $request->user();
        $deleted = $this->notificationService->delete($user, $id);

        if (!$deleted) {
            return $this->errorResponse('Notification not found.', null, 404);
        }

        Log::info('[NOTIFICATION - Destroy] User: ' . $user->id . ' | ID: ' . $id);

        return $this->successResponse(null, 'Notification deleted.');
    }

    /**
     * GET /api/v1/notifications/unread-count
     * Get unread notification count (for the bell badge).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->unreadCount($request->user());

        return $this->successResponse(['count' => $count], 'Unread count retrieved.');
    }
}

