<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message,
        public readonly Conversation $conversation
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'                  => $this->message->id,
            'conversation_id'     => $this->message->conversation_id,
            'sender_id'           => $this->message->sender_id,
            'type'                => $this->message->type,
            'body'                => $this->message->body,
            'file_path'           => $this->message->file_path,
            'file_name'           => $this->message->file_name,
            'file_size'           => $this->message->file_size,
            'file_mime_type'      => $this->message->file_mime_type,
            'duration_seconds'    => $this->message->duration_seconds,
            'thumbnail_path'      => $this->message->thumbnail_path,
            'is_deleted'          => $this->message->is_deleted,
            'reply_to_message_id' => $this->message->reply_to_message_id,
            'delivered_at'        => $this->message->delivered_at?->toISOString(),
            'read_at'             => $this->message->read_at?->toISOString(),
            'created_at'          => $this->message->created_at->toISOString(),
            'status'              => $this->message->status,
            'sender'              => [
                'id'   => $this->message->sender->id,
                'name' => $this->message->sender->name,
            ],
        ];
    }
}
