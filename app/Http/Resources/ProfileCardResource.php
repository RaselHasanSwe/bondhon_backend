<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight profile card resource shared across features.
 * Never exposes email or phone.
 */
class ProfileCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User $this */
        $primaryPhoto = $this->photos?->firstWhere('is_primary', true) ?? $this->photos?->first();

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'gender'          => $this->gender,
            'subscription_plan' => $this->subscription_plan,
            'profile'         => $this->profile ? [
                'profile_id'     => $this->profile->profile_id,
                'dob'            => $this->profile->dob,
                'age'            => $this->profile->dob?->age,
                'height_cm'      => $this->profile->height_cm,
                'marital_status' => $this->profile->marital_status,
                'city'           => $this->profile->city,
                'state'          => $this->profile->state,
                'country'        => $this->profile->country,
                'is_verified'    => $this->faceScanSession?->status === 'approved',
                'last_seen_at'   => $this->profile->last_seen_at,
                'profile_completion_percentage' => $this->profile->profile_completion_percentage,
            ] : null,
            'religion'        => $this->religiousDetail?->religion,
            'caste'           => $this->religiousDetail?->caste,
            'education'       => $this->educationCareer?->highest_education,
            'profession'      => $this->educationCareer?->profession,
            'diet'            => $this->lifestyle?->diet,
            'primary_photo'   => $primaryPhoto?->file_path,
            'face_scan_status' => $this->faceScanSession?->status,
            'is_shortlisted'  => $this->when(
                $this->offsetExists('is_shortlisted'),
                (bool) $this->is_shortlisted
            ),
            'connection_status' => $this->when(
                $this->offsetExists('connection_status'),
                $this->connection_status
            ),
            'is_interest_sender' => $this->when(
                $this->offsetExists('is_interest_sender'),
                (bool) $this->is_interest_sender
            ),
            'can_send_interest' => $this->when(
                $this->offsetExists('can_send_interest'),
                (bool) $this->can_send_interest
            ),
        ];
    }
}

