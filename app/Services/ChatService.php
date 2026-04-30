<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Events\TypingEvent;
use App\Models\Block;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\Message;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ChatService
{
    /**
     * Allowed MIME types and their message types
     */
    private const TYPE_MAP = [
        'image/jpeg'       => 'image',
        'image/png'        => 'image',
        'image/gif'        => 'image',
        'image/webp'       => 'image',
        'video/mp4'        => 'video',
        'video/quicktime'  => 'video',
        'video/avi'        => 'video',
        'video/webm'       => 'video',
        'audio/mpeg'       => 'audio',
        'audio/ogg'        => 'audio',
        'audio/wav'        => 'audio',
        'audio/mp4'        => 'audio',
        'audio/x-m4a'      => 'audio',
        'application/pdf'  => 'document',
        'application/msword'=> 'document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
        'application/vnd.ms-excel' => 'document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'      => 'document',
        'text/csv'         => 'document',
    ];

    /**
     * Get or create conversation between two users (mutual interest required).
     * Always stores lower user ID as user_one_id.
     *
     * @throws \Exception when not allowed to chat
     */
    public function getOrCreateConversation(User $authUser, int $otherUserId): Conversation
    {
        // Check block (either direction)
        $blocked = Block::where(function ($q) use ($authUser, $otherUserId) {
            $q->where('blocker_id', $authUser->id)->where('blocked_id', $otherUserId);
        })->orWhere(function ($q) use ($authUser, $otherUserId) {
            $q->where('blocker_id', $otherUserId)->where('blocked_id', $authUser->id);
        })->exists();

        if ($blocked) {
            throw new \Exception('Chat is not available with this user.', 403);
        }

        // Require mutual accepted interest
        $mutualInterest = Interest::where('status', 'accepted')
            ->where(function ($q) use ($authUser, $otherUserId) {
                $q->where('sender_id', $authUser->id)->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($q) use ($authUser, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('receiver_id', $authUser->id)->where('status', 'accepted');
            })
            ->exists();

        if (!$mutualInterest) {
            throw new \Exception('Chat is only available between users with mutually accepted interests.', 403);
        }

        // Canonical ordering
        [$userOneId, $userTwoId] = $authUser->id < $otherUserId
            ? [$authUser->id, $otherUserId]
            : [$otherUserId, $authUser->id];

        return Conversation::firstOrCreate(
            ['user_one_id' => $userOneId, 'user_two_id' => $userTwoId]
        );
    }

    /**
     * Get all conversations for a user, sorted by last message.
     */
    public function getConversations(User $user)
    {
        return Conversation::with([
                'userOne.profile',
                'userOne.photos' => fn ($q) => $q->where('is_primary', true)->where('is_approved', true),
                'userTwo.profile',
                'userTwo.photos' => fn ($q) => $q->where('is_primary', true)->where('is_approved', true),
                'lastMessage',
            ])
            ->where(function ($q) use ($user) {
                $q->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id);
            })
            ->orderByDesc('last_message_at')
            ->paginate(30);
    }

    /**
     * Get paginated messages for a conversation.
     */
    public function getMessages(Conversation $conversation, int $perPage = 40, ?int $beforeId = null)
    {
        $query = $conversation->messages()
            ->with(['sender.profile', 'replyTo'])
            ->orderByDesc('id');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->paginate($perPage);

        // Reverse to chronological order
        $messages->setCollection($messages->getCollection()->reverse()->values());

        return $messages;
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(
        Conversation $conversation,
        User $sender,
        string $type,
        ?string $body,
        ?UploadedFile $file,
        ?int $replyToMessageId
    ): Message {
        $receiverId = $conversation->user_one_id === $sender->id
            ? $conversation->user_two_id
            : $conversation->user_one_id;

        $fileData = [];
        if ($file) {
            $fileData = $this->processFile($file, $type);
        }

        $message = DB::transaction(function () use (
            $conversation, $sender, $type, $body, $fileData, $replyToMessageId, $receiverId
        ) {
            $msg = Message::create([
                'conversation_id'     => $conversation->id,
                'sender_id'           => $sender->id,
                'type'                => $type,
                'body'                => $body,
                'file_path'           => $fileData['file_path'] ?? null,
                'file_name'           => $fileData['file_name'] ?? null,
                'file_size'           => $fileData['file_size'] ?? null,
                'file_mime_type'      => $fileData['file_mime_type'] ?? null,
                'duration_seconds'    => $fileData['duration_seconds'] ?? null,
                'thumbnail_path'      => $fileData['thumbnail_path'] ?? null,
                'reply_to_message_id' => $replyToMessageId,
                'delivered_at'        => now(),
            ]);

            // Update conversation
            $conversation->update([
                'last_message_at' => now(),
                'last_message_id' => $msg->id,
            ]);

            // Increment receiver's unread count
            $conversation->incrementUnreadFor($receiverId);

            return $msg;
        });

        $message->load(['sender.profile', 'replyTo']);

        // Broadcast real-time event
        broadcast(new MessageSent($message, $conversation))->toOthers();

        Log::info('[CHAT - SendMessage] Message ID: ' . $message->id . ' | Conv: ' . $conversation->id . ' | Sender: ' . $sender->id);

        return $message;
    }

    /**
     * Mark all messages in a conversation as read for the given user.
     */
    public function markAsRead(Conversation $conversation, User $user): int
    {
        $updated = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Reset unread count
        $conversation->resetUnreadFor($user->id);

        return $updated;
    }

    /**
     * Soft-delete a message (only sender can delete).
     */
    public function deleteMessage(Message $message, User $user): bool
    {
        if ($message->sender_id !== $user->id) {
            throw new \Exception('You can only delete your own messages.', 403);
        }

        $message->update(['is_deleted' => true, 'body' => null, 'file_path' => null]);

        Log::info('[CHAT - DeleteMessage] Message ID: ' . $message->id . ' deleted by User: ' . $user->id);
        return true;
    }

    /**
     * Dispatch typing event.
     */
    public function sendTypingEvent(int $conversationId, User $user, bool $isTyping): void
    {
        broadcast(new TypingEvent($conversationId, $user->id, $user->name, $isTyping))->toOthers();
    }

    /**
     * Process uploaded file — store and return metadata.
     */
    private function processFile(UploadedFile $file, string $type): array
    {
        $mime = $file->getMimeType();
        $originalName = $file->getClientOriginalName();
        $size = $file->getSize();

        $dir = match ($type) {
            'image'    => 'chat/images',
            'video'    => 'chat/videos',
            'audio'    => 'chat/audio',
            'voice'    => 'chat/audio',
            'document' => 'chat/documents',
            default    => 'chat/files',
        };

        $path = $file->store($dir, 'public');

        $result = [
            'file_path'      => $path,
            'file_name'      => $originalName,
            'file_size'      => $size,
            'file_mime_type' => $mime,
        ];

        // For images: optionally resize if too large
        if ($type === 'image') {
            try {
                $fullPath = Storage::disk('public')->path($path);
                $img = Image::read($fullPath);
                if ($img->width() > 1200) {
                    $img->scale(width: 1200);
                    $img->save($fullPath);
                    // Update size after resize
                    $result['file_size'] = filesize($fullPath);
                }
            } catch (\Throwable $e) {
                Log::warning('[CHAT - processFile] Image resize failed: ' . $e->getMessage());
            }
        }

        return $result;
    }
}

