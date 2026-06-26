<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\CallLog $this */
        $caller   = $this->whenLoaded('caller');
        $receiver = $this->whenLoaded('receiver');

        $callerPhoto   = $this->caller?->photos()->where('is_primary', true)->where('is_approved', true)->first();
        $receiverPhoto = $this->receiver?->photos()->where('is_primary', true)->where('is_approved', true)->first();

        return [
            'id'               => $this->id,
            'type'             => $this->type,
            'status'           => $this->status,
            'started_at'       => $this->started_at?->toISOString(),
            'ended_at'         => $this->ended_at?->toISOString(),
            'duration_seconds' => $this->duration_seconds,
            'created_at'       => $this->created_at->toISOString(),
            'caller' => $caller ? [
                'id'         => $caller->id,
                'name'       => $caller->name,
                'avatar'     => profilePhotoUrl($callerPhoto?->file_path),
                'profile_id' => $caller->profile?->profile_id,
            ] : null,
            'receiver' => $receiver ? [
                'id'         => $receiver->id,
                'name'       => $receiver->name,
                'avatar'     => profilePhotoUrl($receiverPhoto?->file_path),
                'profile_id' => $receiver->profile?->profile_id,
            ] : null,
        ];
    }
}

