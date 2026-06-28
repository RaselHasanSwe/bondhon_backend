<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Interest $this */
        return [
            'id'              => $this->id,
            'status'          => $this->status,
            'expires_at'      => $this->expires_at,
            'created_at'      => $this->created_at,
            'can_message'     => (bool) ($this->can_message ?? ($this->status === 'accepted')),
            'conversation_id' => $this->conversation_id ?? null,
            'sender'          => $this->whenLoaded('sender', function () {
                return (new ProfileCardResource($this->sender))->toArray(request());
            }),
            'receiver'        => $this->whenLoaded('receiver', function () {
                return (new ProfileCardResource($this->receiver))->toArray(request());
            }),
            'connected_user'  => $this->when($request->user(), function () use ($request) {
                $authId = $request->user()->id;

                if ($this->sender_id === $authId && $this->relationLoaded('receiver') && $this->receiver) {
                    return (new ProfileCardResource($this->receiver))->toArray($request);
                }

                if ($this->receiver_id === $authId && $this->relationLoaded('sender') && $this->sender) {
                    return (new ProfileCardResource($this->sender))->toArray($request);
                }

                return null;
            }),
        ];
    }
}

