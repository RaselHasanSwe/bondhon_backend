<?php

namespace App\Services;

use App\Models\Profile;
use Illuminate\Support\Facades\Log;

class ProfileService
{
    /**
     * Generate a unique profile ID for a user.
     * Uses a do-while loop to check for existing IDs and increment if needed.
     *
     * @param int $userId The user ID to generate the profile ID for
     * @return string The unique profile ID (e.g., BON-000001 or BON-000001-1)
     */
    public function generateUniqueProfileId(int $userId): string
    {
        $baseId = str_pad($userId, 6, '0', STR_PAD_LEFT);
        $counter = 0;
        $profileId = 'BON-' . $baseId;

        do {
            $existing = Profile::where('profile_id', $profileId)->first();
            if ($existing) {
                $counter++;
                $profileId = 'BON-' . $baseId . '-' . $counter;
            }
        } while ($existing);

        return $profileId;
    }

    /**
     * Create a new profile for a user with a unique profile ID.
     *
     * @param int $userId The user ID
     * @param array $data Additional profile data
     * @return Profile The created profile instance
     */
    public function createProfile(int $userId): Profile
    {
        $profileId = $this->generateUniqueProfileId($userId);

        return Profile::create([
            'user_id' => $userId,
            'profile_id' => $profileId,
        ]);
    }
}
