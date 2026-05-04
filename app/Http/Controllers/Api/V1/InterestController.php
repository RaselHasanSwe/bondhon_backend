<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\InterestReceived;
use App\Http\Requests\Interest\SendInterestRequest;
use App\Http\Resources\InterestResource;
use App\Models\Block;
use App\Models\Interest;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SubscriptionFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterestController extends ApiController
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly SubscriptionFeatureService $featureService,
    ) {}
    /**
     * POST /api/v1/interests
     * Send an interest to another user.
     */
    public function send(SendInterestRequest $request): JsonResponse
    {
        $sender     = $request->user();
        $receiverId = $request->validated()['receiver_id'];

        Log::info('[INTEREST - Send] Sender ID: ' . $sender->id . ' | Receiver ID: ' . $receiverId);

        // Cannot send interest to yourself
        if ($sender->id === $receiverId) {
            return $this->errorResponse('You cannot send an interest to yourself.', null, 422);
        }

        // Check daily interest limit
        $sentToday = Interest::where('sender_id', $sender->id)
            ->whereDate('created_at', today())
            ->count();

        if (! $this->featureService->withinDailyLimit($sender, 'send_interest_per_day', $sentToday)) {
            $limit = (int) $this->featureService->value($sender, 'send_interest_per_day');
            return $this->errorResponse(
                "You have reached your daily interest limit ({$limit}/day). Upgrade your plan to send more.",
                ['feature' => 'send_interest_per_day', 'limit' => $limit, 'used' => $sentToday],
                429
            );
        }

        // Check if blocked (either direction)
        $isBlocked = Block::where('blocker_id', $sender->id)->where('blocked_id', $receiverId)->exists()
            || Block::where('blocker_id', $receiverId)->where('blocked_id', $sender->id)->exists();

        if ($isBlocked) {
            return $this->errorResponse('You cannot send an interest to this user.', null, 403);
        }

        // Check for existing pending or accepted interest between this pair
        $existing = Interest::where(function ($q) use ($sender, $receiverId) {
                $q->where('sender_id', $sender->id)->where('receiver_id', $receiverId);
            })
            ->orWhere(function ($q) use ($sender, $receiverId) {
                $q->where('sender_id', $receiverId)->where('receiver_id', $sender->id);
            })
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existing) {
            $msg = $existing->status === 'accepted'
                ? 'You are already connected with this user.'
                : 'A pending interest already exists with this user.';
            return $this->errorResponse($msg, null, 422);
        }

        $interest = DB::transaction(function () use ($sender, $receiverId) {
            return Interest::create([
                'sender_id'   => $sender->id,
                'receiver_id' => $receiverId,
                'status'      => 'pending',
                'expires_at'  => now()->addDays(30),
            ]);
        });

        // Fire real-time event
        event(new InterestReceived($interest));

        Log::info('[INTEREST - Send] Success. Interest ID: ' . $interest->id . ' | Sender: ' . $sender->id . ' | Receiver: ' . $receiverId);

        $interest->load(['sender.profile', 'sender.photos', 'receiver.profile', 'receiver.photos']);

        return $this->successResponse(
            InterestResource::make($interest),
            'Interest sent successfully.',
            201
        );
    }

    /**
     * GET /api/v1/interests/received
     * List all interests received by the authenticated user.
     */
    public function received(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[INTEREST - Received] User ID: ' . $user->id);

        $interests = Interest::with([
                'sender',
                'sender.profile',
                'sender.religiousDetail',
                'sender.educationCareer',
                'sender.photos' => fn ($q) => $q->where('is_approved', true)->where('is_primary', true),
            ])
            ->where('receiver_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->successResponse(
            InterestResource::collection($interests)->response()->getData(true),
            'Received interests retrieved.'
        );
    }

    /**
     * GET /api/v1/interests/sent
     * List all interests sent by the authenticated user.
     */
    public function sent(Request $request): JsonResponse
    {
        $user = $request->user();
        Log::info('[INTEREST - Sent] User ID: ' . $user->id);

        $interests = Interest::with([
                'receiver',
                'receiver.profile',
                'receiver.religiousDetail',
                'receiver.educationCareer',
                'receiver.photos' => fn ($q) => $q->where('is_approved', true)->where('is_primary', true),
            ])
            ->where('sender_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->successResponse(
            InterestResource::collection($interests)->response()->getData(true),
            'Sent interests retrieved.'
        );
    }

    /**
     * PUT /api/v1/interests/{id}/accept
     * Accept a received interest.
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        return $this->updateStatus($request->user(), $id, 'accepted', 'Interest accepted.');
    }

    /**
     * PUT /api/v1/interests/{id}/decline
     * Decline a received interest.
     */
    public function decline(Request $request, int $id): JsonResponse
    {
        return $this->updateStatus($request->user(), $id, 'declined', 'Interest declined.');
    }

    /**
     * PUT /api/v1/interests/{id}/ignore
     * Ignore a received interest.
     */
    public function ignore(Request $request, int $id): JsonResponse
    {
        return $this->updateStatus($request->user(), $id, 'ignored', 'Interest ignored.');
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function updateStatus(User $user, int $interestId, string $newStatus, string $message): JsonResponse
    {
        Log::info('[INTEREST - UpdateStatus] User ID: ' . $user->id . ' | Interest ID: ' . $interestId . ' | Status: ' . $newStatus);

        $interest = Interest::where('id', $interestId)
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $interest) {
            Log::warning('[INTEREST - UpdateStatus] Not found or unauthorized. User ID: ' . $user->id . ' | Interest ID: ' . $interestId);
            return $this->errorResponse('Interest not found or you are not authorized to update it.', null, 404);
        }

        $interest->update(['status' => $newStatus]);

        Log::info('[INTEREST - UpdateStatus] Success. Interest ID: ' . $interestId . ' → ' . $newStatus);

        // Notify the sender when interest is accepted
        if ($newStatus === 'accepted') {
            $sender = User::find($interest->sender_id);
            if ($sender) {
                $this->notificationService->notifyInterestAccepted($sender, $user);
            }
        }

        $interest->load([
            'sender.profile',
            'sender.photos' => fn ($q) => $q->where('is_approved', true)->where('is_primary', true),
        ]);

        return $this->successResponse(InterestResource::make($interest), $message);
    }
}

