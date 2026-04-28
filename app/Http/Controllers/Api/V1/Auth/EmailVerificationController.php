<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Email Verification', description: 'Email verification endpoints')]
class EmailVerificationController extends ApiController
{
    #[OA\Get(
        path: '/api/v1/auth/email/verify/{id}/{hash}',
        summary: 'Verify user email address',
        security: [['sanctum' => []]],
        tags: ['Email Verification'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'hash', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Email verified successfully'),
            new OA\Response(response: 403, description: 'Invalid or expired verification link'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function verify(EmailVerificationRequest $request): JsonResponse
    {
        Log::info('[EMAIL VERIFICATION - Verify] Attempt for User ID: ' . $request->user()->id);

        try {
            if ($request->user()->hasVerifiedEmail()) {
                Log::info('[EMAIL VERIFICATION - Verify] Already verified. User ID: ' . $request->user()->id);
                return $this->successResponse(null, 'Email already verified.');
            }

            $request->fulfill();

            Log::info('[EMAIL VERIFICATION - Verify] Successfully verified. User ID: ' . $request->user()->id);

            return $this->successResponse(null, 'Email verified successfully.');

        } catch (\Throwable $e) {
            Log::error('[EMAIL VERIFICATION - Verify] Failed for User ID: ' . $request->user()->id . ' | Error: ' . $e->getMessage(), [
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
}
