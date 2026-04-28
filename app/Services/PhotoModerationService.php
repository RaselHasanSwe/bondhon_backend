<?php

namespace App\Services;

use App\Models\ProfilePhoto;
use Illuminate\Support\Facades\Log;

/**
 * PhotoModerationService — Phase 5
 *
 * Handles photo approval/rejection workflow for admin moderation.
 */
class PhotoModerationService
{
    /**
     * TODO: Phase 5 — Approve a photo.
     */
    public function approve(ProfilePhoto $photo): void
    {
        Log::info('[PHOTO MODERATION - Approve] Phase 5 not yet implemented. Photo ID: ' . $photo->id);
    }

    /**
     * TODO: Phase 5 — Reject a photo.
     */
    public function reject(ProfilePhoto $photo, string $reason = ''): void
    {
        Log::info('[PHOTO MODERATION - Reject] Phase 5 not yet implemented. Photo ID: ' . $photo->id);
    }
}

