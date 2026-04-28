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
            'id'         => $this->id,
            'created_at' => $this->created_at,
            'user'       => $this->whenLoaded('shortlistedUser', function () {
                return (new ProfileCardResource($this->shortlistedUser))->toArray(request());
            }),
        ];
    }
}

