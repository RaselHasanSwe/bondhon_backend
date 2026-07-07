<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ChatService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends ApiController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly SubscriptionFeatureService $featureService,
    ) {}

    /**
     * GET /api/v1/conversations
     * List all conversations for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[CHAT - Index] User: ' . $user->id);

        $conversations = $this->chatService->getConversations($user);

        $resources = $conversations->getCollection()->map(function ($conv) use ($user) {
            $resource = new ConversationResource($conv);
            $resource->currentUserId = $user->id;
            return $resource;
        });

        return $this->successResponse([
            'data'       => $resources->values(),
            'pagination' => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'per_page'     => $conversations->perPage(),
                'total'        => $conversations->total(),
            ],
        ], 'Conversations retrieved.');
    }

    /**
     * GET /api/v1/conversations/unread-count
     * Total unread messages across all conversations.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->chatService->getTotalUnreadCount($request->user());

        return $this->successResponse(['unread_count' => $count], 'Unread message count retrieved.');
    }

    /**
     * POST /api/v1/conversations
     * Get or create a conversation with another user (mutual interest required).
     */
    public function getOrCreate(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[CHAT - GetOrCreate] User: ' . $user->id);

        // Guard: chat_access feature
        if (! $this->featureService->can($user, 'chat_access')) {
            return $this->errorResponse(
                'Chat is not available on your current subscription plan.',
                ['feature' => 'chat_access'],
                403
            );
        }

        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        $otherId = (int) $request->input('user_id');

        if ($user->id === $otherId) {
            return $this->errorResponse('You cannot start a conversation with yourself.', null, 422);
        }

        try {
            $conversation = $this->chatService->getOrCreateConversation($user, $otherId);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, $e->getCode() ?: 403);
        }

        $conversation->load([
            'userOne.profile',
            'userOne.photos' => fn ($q) => $q->where('is_primary', true)->where('is_approved', true),
            'userTwo.profile',
            'userTwo.photos' => fn ($q) => $q->where('is_primary', true)->where('is_approved', true),
            'lastMessage',
        ]);

        $resource = new ConversationResource($conversation);
        $resource->currentUserId = $user->id;

        return $this->successResponse($resource, 'Conversation ready.', 200);
    }

    /**
     * GET /api/v1/conversations/{conversationId}
     * Get a single conversation.
     */
    public function show(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::with([
            'userOne.profile',
            'userOne.photos' => fn ($q) => $q->where('is_primary', true)->where('is_approved', true),
            'userTwo.profile',
            'userTwo.photos' => fn ($q) => $q->where('is_primary', true)->where('is_approved', true),
            'lastMessage',
        ])->findOrFail($conversationId);

        if ($conversation->user_one_id !== $user->id && $conversation->user_two_id !== $user->id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        $resource = new ConversationResource($conversation);
        $resource->currentUserId = $user->id;

        return $this->successResponse($resource, 'Conversation retrieved.');
    }
}

