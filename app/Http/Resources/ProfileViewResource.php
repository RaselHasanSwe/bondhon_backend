<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileViewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\ProfileView $this */
        return [
            'viewer_id' => $this->viewer_id,
            'viewed_at' => $this->viewed_at,
            'connection_status' => $this->connection_status ?? 'none',
            'interest_id' => $this->interest_id ?? null,
            'is_interest_sender' => (bool) ($this->is_interest_sender ?? false),
            'conversation_id' => $this->conversation_id ?? null,
            'send_count' => (int) ($this->send_count ?? 0),
            'can_send_interest' => (bool) ($this->can_send_interest ?? true),
            'remaining_send_attempts' => (int) ($this->remaining_send_attempts ?? 0),
            'viewer'    => $this->whenLoaded('viewer', function () {
                return (new ProfileCardResource($this->viewer))->toArray(request());
            }),
        ];
    }
}

