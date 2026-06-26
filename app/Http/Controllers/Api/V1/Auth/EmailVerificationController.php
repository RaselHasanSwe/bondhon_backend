<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\User;
use App\Services\EmailVerificationOtpService;
use App\Services\SiteSettingService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Email Verification', description: 'Email verification endpoints')]
class EmailVerificationController extends ApiController
{
    public function __construct(
        private readonly SiteSettingService $siteSettingService,
    ) {}

    #[OA\Get(
        path: '/api/v1/auth/email/verify/{id}/{hash}',
        summary: 'Verify user email address (public — no auth required)',
        tags: ['Email Verification'],
        parameters: [
            new OA\Parameter(name: 'id',   in: 'path',  required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'hash', in: 'path',  required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Email verified successfully'),
            new OA\Response(response: 403, description: 'Invalid or expired verification link'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        Log::info('[EMAIL VERIFICATION - Verify] Public attempt for User ID: ' . $id);

        try {
            // 1. Validate the signed URL (covers expiry + tamper protection automatically)
            if (! $request->hasValidSignature()) {
                Log::warning('[EMAIL VERIFICATION - Verify] Invalid/expired signature. User ID: ' . $id);
                return $this->errorResponse('This verification link is invalid or has expired.', null, 403);
            }

            // 2. Find the user
            $user = User::find($id);
            if (! $user) {
                Log::warning('[EMAIL VERIFICATION - Verify] User not found. ID: ' . $id);
                return $this->errorResponse('User not found.', null, 404);
            }

            // 3. Validate hash — must equal sha1 of the user's email (same as Laravel's default)
            if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
                Log::warning('[EMAIL VERIFICATION - Verify] Hash mismatch. User ID: ' . $id);
                return $this->errorResponse('This verification link is invalid.', null, 403);
            }

            // 4. Already verified?
            if ($user->hasVerifiedEmail()) {
                Log::info('[EMAIL VERIFICATION - Verify] Already verified. User ID: ' . $id);
                return $this->successResponse(null, 'Email already verified.');
            }

            // 5. Mark as verified and fire event
            $user->markEmailAsVerified();
            event(new Verified($user));

            Log::info('[EMAIL VERIFICATION - Verify] Successfully verified. User ID: ' . $id);
            return $this->successResponse(null, 'Email verified successfully.');

        } catch (\Throwable $e) {
            Log::error('[EMAIL VERIFICATION - Verify] Failed for User ID: ' . $id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Email verification failed. Please try again.');
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/email/resend',
        summary: 'Resend email verification link',
        security: [['sanctum' => []]],
        tags: ['Email Verification'],
        responses: [
            new OA\Response(response: 200, description: 'Verification link sent'),
            new OA\Response(response: 400, description: 'Email already verified'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function resend(Request $request): JsonResponse
    {
        Log::info('[EMAIL VERIFICATION - Resend] Request from User ID: ' . $request->user()->id);

        try {
            if ($request->user()->hasVerifiedEmail()) {
                Log::info('[EMAIL VERIFICATION - Resend] Already verified. User ID: ' . $request->user()->id);
                return $this->errorResponse('Email is already verified.', null, 400);
            }

            $request->user()->sendEmailVerificationNotification();

            Log::info('[EMAIL VERIFICATION - Resend] Verification email sent to User ID: ' . $request->user()->id);

            return $this->successResponse(null, 'Verification link has been sent to your email.');

        } catch (\Throwable $e) {
            Log::error('[EMAIL VERIFICATION - Resend] Failed for User ID: ' . $request->user()->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Failed to send verification email. Please try again.');
        }
    }

    #[OA\Post(
        path: '/api/v1/auth/email/verify-otp',
        summary: 'Verify email with 6-digit OTP code',
        security: [['sanctum' => []]],
        tags: ['Email Verification'],
        responses: [
            new OA\Response(response: 200, description: 'Email verified successfully'),
            new OA\Response(response: 422, description: 'Invalid or expired code'),
            new OA\Response(response: 400, description: 'Email already verified or verification disabled'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function verifyOtp(Request $request, EmailVerificationOtpService $otpService): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ]);

        /** @var User $user */
        $user = $request->user();

        Log::info('[EMAIL VERIFICATION - VerifyOtp] Attempt from User ID: ' . $user->id);

        try {
            if ($user->hasVerifiedEmail()) {
                return $this->successResponse(null, 'Email already verified.');
            }

            if (! $this->siteSettingService->boolean('email_verification_enabled', true)) {
                return $this->errorResponse('Email verification is not required.', null, 400);
            }

            if (! $otpService->verify($user, $request->code)) {
                Log::warning('[EMAIL VERIFICATION - VerifyOtp] Invalid code for User ID: ' . $user->id);
                return $this->errorResponse('Invalid or expired verification code.', null, 422);
            }

            $user->markEmailAsVerified();
            event(new Verified($user));

            Log::info('[EMAIL VERIFICATION - VerifyOtp] Successfully verified User ID: ' . $user->id);

            return $this->successResponse([
                'email_verified_at' => $user->fresh()->email_verified_at,
            ], 'Email verified successfully.');

        } catch (\Throwable $e) {
            Log::error('[EMAIL VERIFICATION - VerifyOtp] Failed for User ID: ' . $user->id . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Email verification failed. Please try again.');
        }
    }
}
