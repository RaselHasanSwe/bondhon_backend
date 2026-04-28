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
            'viewer'    => $this->whenLoaded('viewer', function () {
                return (new ProfileCardResource($this->viewer))->toArray(request());
            }),
        ];
    }
}

