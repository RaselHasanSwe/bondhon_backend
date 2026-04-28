<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Block;
use App\Models\Interest;
use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BlockController extends ApiController
{
    /**
     * POST /api/v1/block/{userId}
     * Block a user. Cancels any pending interests between the two.
     */
    public function block(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();
        Log::info('[BLOCK - Block] User ID: ' . $user->id . ' | Target: ' . $userId);

        if ($user->id === $userId) {
            return $this->errorResponse('You cannot block yourself.', null, 422);
        }

        $target = User::where('id', $userId)->firstOrFail();

        DB::transaction(function () use ($user, $userId) {
            // Create block record (idempotent)
            Block::firstOrCreate([
                'blocker_id' => $user->id,
                'blocked_id' => $userId,
            ]);

            // Cancel any pending interests between the pair
            Interest::where(function ($q) use ($user, $userId) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $userId);
                })
                ->orWhere(function ($q) use ($user, $userId) {
                    $q->where('sender_id', $userId)->where('receiver_id', $user->id);
                })
                ->where('status', 'pending')
                ->update(['status' => 'declined']);

            // Remove from each other's shortlists
            Shortlist::where(function ($q) use ($user, $userId) {
                    $q->where('user_id', $user->id)->where('shortlisted_user_id', $userId);
                })
                ->orWhere(function ($q) use ($user, $userId) {
                    $q->where('user_id', $userId)->where('shortlisted_user_id', $user->id);
                })
                ->delete();
        });

        Log::info('[BLOCK - Block] Success. User: ' . $user->id . ' blocked: ' . $userId);

        return $this->successResponse(null, 'User blocked successfully.');
    }

    /**
     * DELETE /api/v1/block/{userId}
     * Unblock a previously blocked user.
     */
    public function unblock(Request $request, int $userId): JsonResponse
    {
        $user = $request->user();
        Log::info('[BLOCK - Unblock] User ID: ' . $user->id . ' | Target: ' . $userId);

        $deleted = Block::where('blocker_id', $user->id)
            ->where('blocked_id', $userId)
            ->delete();

        if (! $deleted) {
            return $this->errorResponse('Block record not found.', null, 404);
        }

        Log::info('[BLOCK - Unblock] Success. User: ' . $user->id . ' unblocked: ' . $userId);

        return $this->successResponse(null, 'User unblocked successfully.');
    }
}

