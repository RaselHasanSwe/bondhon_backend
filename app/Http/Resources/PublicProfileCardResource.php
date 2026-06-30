<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal profile card for unauthenticated browse/search.
 * Exposes only fields safe for public listing.
 */
class PublicProfileCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User $this */
        /** Primary approved photo, or the first approved public photo as fallback. */
        $displayPhoto = $this->photos?->firstWhere('is_primary', true) ?? $this->photos?->first();

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'profile'       => $this->profile ? [
                'profile_id' => $this->profile->profile_id,
                'dob'       => $this->profile->dob,
                'age'       => $this->profile->dob?->age,
                'height_cm' => $this->profile->height_cm,
                'city'      => $this->profile->city,
                'state'     => $this->profile->state,
                'country'   => $this->profile->country,
            ] : null,
            'profession'    => $this->educationCareer?->profession,
            'is_verified'   => $this->faceScanSession?->status === 'approved',
            'primary_photo' => $displayPhoto?->file_path,
        ];
    }
}
