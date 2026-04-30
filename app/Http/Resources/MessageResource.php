<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'conversation_id'     => $this->conversation_id,
            'sender_id'           => $this->sender_id,
            'type'                => $this->type,
            'body'                => $this->is_deleted ? null : $this->body,
            'file_path'           => $this->is_deleted ? null : $this->file_path,
            'file_name'           => $this->is_deleted ? null : $this->file_name,
            'file_size'           => $this->is_deleted ? null : $this->file_size,
            'file_mime_type'      => $this->is_deleted ? null : $this->file_mime_type,
            'duration_seconds'    => $this->duration_seconds,
            'thumbnail_path'      => $this->is_deleted ? null : $this->thumbnail_path,
            'reactions'           => $this->reactions ?? [],
            'reply_to_message_id' => $this->reply_to_message_id,
            'reply_to'            => $this->whenLoaded('replyTo', function () {
                if (!$this->replyTo) return null;
                return [
                    'id'       => $this->replyTo->id,
                    'body'     => $this->replyTo->is_deleted ? null : $this->replyTo->body,
                    'type'     => $this->replyTo->type,
                    'sender_id'=> $this->replyTo->sender_id,
                ];
            }),
            'is_deleted'          => $this->is_deleted,
            'delivered_at'        => $this->delivered_at?->toISOString(),
            'read_at'             => $this->read_at?->toISOString(),
            'created_at'          => $this->created_at->toISOString(),
            'status'              => $this->status,
            'sender'              => $this->whenLoaded('sender', fn () => [
                'id'         => $this->sender->id,
                'name'       => $this->sender->name,
                'profile_id' => $this->sender->profile?->profile_id,
            ]),
        ];
    }
}

