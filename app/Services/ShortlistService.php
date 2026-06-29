<?php

namespace App\Services;

use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Support\Collection;

class ShortlistService
{
    /**
     * Set `is_shortlisted` on each user in the collection for the authenticated viewer.
     *
     * @param  Collection<int, User>|iterable<User>  $users
     */
    public function attachShortlistStatus(User $authUser, iterable $users): void
    {
        $collection = collect($users);
        if ($collection->isEmpty()) {
            return;
        }

        $shortlistedIds = Shortlist::query()
            ->where('user_id', $authUser->id)
            ->whereIn('shortlisted_user_id', $collection->pluck('id'))
            ->pluck('shortlisted_user_id')
            ->flip();

        $collection->each(function (User $candidate) use ($shortlistedIds) {
            $candidate->setAttribute('is_shortlisted', isset($shortlistedIds[$candidate->id]));
        });
    }
}
