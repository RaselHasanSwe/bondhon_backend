<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends ApiController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * GET /api/v1/conversations/{conversationId}/messages
     * Paginated message history (cursor-based via ?before_id=X).
     */
    public function index(Request $request, int $conversationId): JsonResponse
    {
        $user         = $request->user();
        $beforeId     = $request->integer('before_id') ?: null;
        $perPage      = min($request->integer('per_page', 40), 100);

        $conversation = Conversation::findOrFail($conversationId);

        if ($conversation->user_one_id !== $user->id && $conversation->user_two_id !== $user->id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        $messages = $this->chatService->getMessages($conversation, $perPage, $beforeId);

        // Mark as delivered for the requesting user
        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('delivered_at')
            ->update(['delivered_at' => now()]);

        return $this->successResponse([
            'data'       => MessageResource::collection($messages->getCollection()),
            'pagination' => [
                'has_more'     => $messages->hasMorePages(),
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'per_page'     => $messages->perPage(),
                'total'        => $messages->total(),
            ],
        ], 'Messages retrieved.');
    }

    /**
     * POST /api/v1/conversations/{conversationId}/messages
     * Send a new message.
     */
    public function send(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $conversation = Conversation::findOrFail($conversationId);

        if ($conversation->user_one_id !== $user->id && $conversation->user_two_id !== $user->id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        try {
            $message = $this->chatService->sendMessage(
                conversation:       $conversation,
                sender:             $user,
                type:               $data['type'],
                body:               $data['body'] ?? null,
                file:               $request->file('file'),
                replyToMessageId:   $data['reply_to_message_id'] ?? null,
            );
        } catch (\Exception $e) {
            Log::error('[MESSAGE - Send] Error: ' . $e->getMessage() . ' | User: ' . $user->id);
            return $this->errorResponse($e->getMessage(), null, $e->getCode() ?: 500);
        }

        // Send in-app notification to receiver (if offline > 10 min, email is done by queued job)
        $receiverId = $conversation->user_one_id === $user->id
            ? $conversation->user_two_id
            : $conversation->user_one_id;

        $receiver = \App\Models\User::find($receiverId);
        if ($receiver) {
            $preview = $data['type'] === 'text'
                ? mb_substr($data['body'] ?? '', 0, 80)
                : '[' . ucfirst($data['type']) . ']';

            $this->notificationService->notifyNewMessage($receiver, $user, $preview, $conversation->id);
        }

        return $this->successResponse(
            new MessageResource($message),
            'Message sent.',
            201
        );
    }

    /**
     * PUT /api/v1/messages/{id}/read
     * Mark all messages in the conversation as read.
     */
    public function markRead(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();

        $conversation = Conversation::findOrFail($conversationId);

        if ($conversation->user_one_id !== $user->id && $conversation->user_two_id !== $user->id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        $count = $this->chatService->markAsRead($conversation, $user);

        Log::info('[MESSAGE - MarkRead] Conv: ' . $conversationId . ' | User: ' . $user->id . ' | Count: ' . $count);

        return $this->successResponse(['updated' => $count], 'Messages marked as read.');
    }

    /**
     * DELETE /api/v1/messages/{id}
     * Soft delete a message (sender only).
     */
    public function delete(Request $request, int $messageId): JsonResponse
    {
        $user    = $request->user();
        $message = Message::findOrFail($messageId);

        try {
            $this->chatService->deleteMessage($message, $user);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, $e->getCode() ?: 403);
        }

        return $this->successResponse(null, 'Message deleted.');
    }

    /**
     * POST /api/v1/conversations/{conversationId}/typing
     * Broadcast typing status.
     */
    public function typing(Request $request, int $conversationId): JsonResponse
    {
        $request->validate(['is_typing' => ['required', 'boolean']]);

        $user = $request->user();
        $conversation = Conversation::findOrFail($conversationId);

        if ($conversation->user_one_id !== $user->id && $conversation->user_two_id !== $user->id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        $this->chatService->sendTypingEvent($conversationId, $user, (bool) $request->input('is_typing'));

        return $this->successResponse(null, 'Typing status sent.');
    }
}

