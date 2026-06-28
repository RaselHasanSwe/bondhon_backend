<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Shortlist $this */
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'connection_status' => $this->connection_status ?? 'none',
            'interest_id' => $this->interest_id ?? null,
            'is_interest_sender' => (bool) ($this->is_interest_sender ?? false),
            'conversation_id' => $this->conversation_id ?? null,
            'send_count' => (int) ($this->send_count ?? 0),
            'can_send_interest' => (bool) ($this->can_send_interest ?? true),
            'remaining_send_attempts' => (int) ($this->remaining_send_attempts ?? 0),
            'user'       => $this->whenLoaded('shortlistedUser', function () {
                return (new ProfileCardResource($this->shortlistedUser))->toArray(request());
            }),
        ];
    }
}

