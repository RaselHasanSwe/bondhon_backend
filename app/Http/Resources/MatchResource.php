<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\MatchScore $this */
        return [
            'id'              => $this->id,
            'score'           => (float) $this->score,
            'score_breakdown' => $this->score_breakdown,
            'calculated_at'   => $this->calculated_at,
            'candidate'       => $this->whenLoaded('candidate', function () {
                return (new ProfileCardResource($this->candidate))->toArray(request());
            }),
        ];
    }
}

