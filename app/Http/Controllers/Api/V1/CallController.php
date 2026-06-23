<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\CallAnswered;
use App\Events\CallEnded;
use App\Events\CallInitiated;
use App\Http\Requests\Call\InitiateCallRequest;
use App\Http\Requests\Call\SignalCallRequest;
use App\Http\Resources\CallLogResource;
use App\Models\Block;
use App\Models\CallLog;
use App\Models\Interest;
use App\Services\SubscriptionFeatureService;
use App\Services\WebRTCSignalingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallController extends ApiController
{
    public function __construct(
        private readonly WebRTCSignalingService $signalingService,
        private readonly SubscriptionFeatureService $featureService,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // POST /calls/initiate
    // ─────────────────────────────────────────────────────────────────────────

    public function initiate(InitiateCallRequest $request): JsonResponse
    {
        $caller     = $request->user();
        $receiverId = $request->integer('receiver_id');
        $type       = $request->string('type')->toString();

        // Guard: cannot call yourself
        if ($caller->id === $receiverId) {
            return $this->errorResponse('You cannot call yourself.', null, 422);
        }

        // Guard: check subscription feature access
        $featureKey = $type === 'video' ? 'video_call_access' : 'audio_call_access';
        if (! $this->featureService->can($caller, $featureKey)) {
            $label = $type === 'video' ? 'Video Call' : 'Audio Call';
            return $this->errorResponse(
                "{$label} is not available on your current subscription plan.",
                ['feature' => $featureKey],
                403
            );
        }


        // Guard: mutual accepted interest required
        $mutual = Interest::where(function ($q) use ($caller, $receiverId) {
            $q->where('sender_id', $caller->id)->where('receiver_id', $receiverId);
        })->orWhere(function ($q) use ($caller, $receiverId) {
            $q->where('sender_id', $receiverId)->where('receiver_id', $caller->id);
        })->where('status', 'accepted')->exists();

        if (! $mutual) {
            return $this->errorResponse('Calls are only allowed between users with a mutually accepted interest.', null, 403);
        }

        // Guard: blocked relationship
        $blocked = Block::where(function ($q) use ($caller, $receiverId) {
            $q->where('blocker_id', $caller->id)->where('blocked_id', $receiverId);
        })->orWhere(function ($q) use ($caller, $receiverId) {
            $q->where('blocker_id', $receiverId)->where('blocked_id', $caller->id);
        })->exists();

        if ($blocked) {
            return $this->errorResponse('You cannot call this user.', null, 403);
        }

        // Guard: no active call already in progress
        $activeCall = CallLog::where(function ($q) use ($caller, $receiverId) {
            $q->where(function ($inner) use ($caller, $receiverId) {
                $inner->where('caller_id', $caller->id)->where('receiver_id', $receiverId);
            })->orWhere(function ($inner) use ($caller, $receiverId) {
                $inner->where('caller_id', $receiverId)->where('receiver_id', $caller->id);
            });
        })->whereIn('status', ['initiated', 'answered'])->first();

        if ($activeCall) {
            return $this->errorResponse(
                'There is already an active call between these users.',
                ['active_call_id' => $activeCall->id],
                409
            );
        }

        $callLog = DB::transaction(function () use ($caller, $receiverId, $type) {
            return CallLog::create([
                'caller_id'   => $caller->id,
                'receiver_id' => $receiverId,
                'type'        => $type,
                'status'      => 'initiated',
            ]);
        });

        $callLog->load(['caller', 'receiver']);
        broadcast(new CallInitiated($callLog));

        Log::info('[CALL - Initiated]', [
            'call_id'     => $callLog->id,
            'caller_id'   => $caller->id,
            'receiver_id' => $receiverId,
            'type'        => $type,
        ]);

        $iceServers = $this->signalingService->getIceServers($caller);

        return $this->successResponse([
            'call'        => new CallLogResource($callLog),
            'ice_servers' => $iceServers,
        ], 'Call initiated.', 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /calls/{id}/answer
    // ─────────────────────────────────────────────────────────────────────────

    public function answer(Request $request, int $id): JsonResponse
    {
        $user    = $request->user();
        $callLog = CallLog::find($id);

        if (! $callLog) {
            return $this->errorResponse('Call not found.', null, 404);
        }

        if ($callLog->receiver_id !== $user->id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        if ($callLog->status !== 'initiated') {
            return $this->errorResponse('This call cannot be answered.', ['status' => $callLog->status], 409);
        }

        $callLog = DB::transaction(function () use ($callLog) {
            $callLog->update([
                'status'     => 'answered',
                'started_at' => now(),
            ]);
            return $callLog->fresh();
        });

        $callLog->load(['caller', 'receiver']);
        broadcast(new CallAnswered($callLog));

        $iceServers = $this->signalingService->getIceServers($user);

        Log::info('[CALL - Answered]', ['call_id' => $callLog->id, 'user_id' => $user->id]);

        return $this->successResponse([
            'call'        => new CallLogResource($callLog),
            'ice_servers' => $iceServers,
        ], 'Call answered.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /calls/{id}/decline
    // ─────────────────────────────────────────────────────────────────────────

    public function decline(Request $request, int $id): JsonResponse
    {
        $user    = $request->user();
        $callLog = CallLog::find($id);

        if (! $callLog) {
            return $this->errorResponse('Call not found.', null, 404);
        }

        if ($callLog->receiver_id !== $user->id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        if (! in_array($callLog->status, ['initiated'])) {
            return $this->errorResponse('This call cannot be declined.', ['status' => $callLog->status], 409);
        }

        $callLog = DB::transaction(function () use ($callLog) {
            $callLog->update(['status' => 'declined']);
            return $callLog->fresh();
        });

        $callLog->load(['caller', 'receiver']);
        broadcast(new CallEnded($callLog));

        Log::info('[CALL - Declined]', ['call_id' => $callLog->id, 'user_id' => $user->id]);

        return $this->successResponse(['call' => new CallLogResource($callLog)], 'Call declined.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /calls/{id}/end
    // ─────────────────────────────────────────────────────────────────────────

    public function end(Request $request, int $id): JsonResponse
    {
        $user    = $request->user();
        $callLog = CallLog::find($id);

        if (! $callLog) {
            return $this->errorResponse('Call not found.', null, 404);
        }

        // Either caller or receiver may end
        if ($callLog->caller_id !== $user->id && $callLog->receiver_id !== $user->id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        if (! in_array($callLog->status, ['initiated', 'answered'])) {
            return $this->errorResponse('This call is already ended.', ['status' => $callLog->status], 409);
        }

        $callLog = DB::transaction(function () use ($callLog) {
            $endedAt          = now();
            $durationSeconds  = null;

            if ($callLog->started_at) {
                $durationSeconds = (int) $callLog->started_at->diffInSeconds($endedAt);
            }

            // Missed if caller ends while still 'initiated' (receiver never answered)
            $newStatus = ($callLog->status === 'initiated' && $callLog->receiver_id !== request()->user()->id)
                ? 'missed'
                : 'ended';

            $callLog->update([
                'status'           => $newStatus,
                'ended_at'         => $endedAt,
                'duration_seconds' => $durationSeconds,
            ]);

            return $callLog->fresh();
        });

        $callLog->load(['caller', 'receiver']);
        broadcast(new CallEnded($callLog));

        Log::info('[CALL - Ended]', [
            'call_id'          => $callLog->id,
            'user_id'          => $user->id,
            'status'           => $callLog->status,
            'duration_seconds' => $callLog->duration_seconds,
        ]);

        return $this->successResponse(['call' => new CallLogResource($callLog)], 'Call ended.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /calls/{id}/signal   — WebRTC signaling relay
    // ─────────────────────────────────────────────────────────────────────────

    public function signal(SignalCallRequest $request, int $id): JsonResponse
    {
        $user    = $request->user();
        $callLog = CallLog::find($id);

        if (! $callLog) {
            return $this->errorResponse('Call not found.', null, 404);
        }

        // Only participants may exchange signals
        if ($callLog->caller_id !== $user->id && $callLog->receiver_id !== $user->id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        if (! in_array($callLog->status, ['initiated', 'answered'])) {
            return $this->errorResponse('Call is no longer active.', null, 409);
        }

        $toUserId = $request->integer('to_user_id');

        // Validate target is the other participant
        $allowedTarget = $user->id === $callLog->caller_id
            ? $callLog->receiver_id
            : $callLog->caller_id;

        if ($toUserId !== $allowedTarget) {
            return $this->errorResponse('Invalid signal target.', null, 422);
        }

        $this->signalingService->relaySignal(
            $callLog,
            $user,
            $toUserId,
            $request->string('type')->toString(),
            $request->input('payload'),
        );

        return $this->successResponse(null, 'Signal relayed.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /calls/history
    // ─────────────────────────────────────────────────────────────────────────

    public function history(Request $request): JsonResponse
    {
        $user          = $request->user();
        $participantId = $request->integer('participant_id', 0);

        $query = CallLog::with(['caller', 'receiver'])
            ->where(function ($q) use ($user) {
                $q->where('caller_id', $user->id)
                  ->orWhere('receiver_id', $user->id);
            });

        // Optional: filter calls between current user and a specific participant
        if ($participantId > 0) {
            $query->where(function ($q) use ($user, $participantId) {
                $q->where(function ($inner) use ($user, $participantId) {
                    $inner->where('caller_id', $user->id)->where('receiver_id', $participantId);
                })->orWhere(function ($inner) use ($user, $participantId) {
                    $inner->where('caller_id', $participantId)->where('receiver_id', $user->id);
                });
            });
        }

        $calls = $query->orderByDesc('created_at')->paginate($participantId > 0 ? 100 : 20);

        return $this->successResponse([
            'data'       => CallLogResource::collection($calls->items()),
            'pagination' => [
                'current_page' => $calls->currentPage(),
                'last_page'    => $calls->lastPage(),
                'per_page'     => $calls->perPage(),
                'total'        => $calls->total(),
                'has_more'     => $calls->hasMorePages(),
            ],
        ], 'Call history retrieved.');
    }
}

