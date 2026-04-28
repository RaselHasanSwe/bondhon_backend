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
            'id'         => $this->id,
            'status'     => $this->status,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'sender'     => $this->whenLoaded('sender', function () {
                return (new ProfileCardResource($this->sender))->toArray(request());
            }),
            'receiver'   => $this->whenLoaded('receiver', function () {
                return (new ProfileCardResource($this->receiver))->toArray(request());
            }),
        ];
    }
}

