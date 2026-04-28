<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Password Reset', description: 'Password reset endpoints')]
class ForgotPasswordController extends ApiController
{
    #[OA\Post(
        path: '/api/v1/auth/password/forgot',
        summary: 'Send password reset link to email',
        tags: ['Password Reset'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reset link sent (or silently ignored if email not found)'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 429, description: 'Too many requests'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function sendLink(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Log::info('[PASSWORD RESET - SendLink] Reset link requested for email: ' . $request->email);

        try {
            Password::sendResetLink($request->only('email'));

            // Always return success to prevent email enumeration
            Log::info('[PASSWORD RESET - SendLink] Reset link dispatched (email may or may not exist): ' . $request->email);

            return $this->successResponse(null, 'If that email address exists in our system, we have sent a password reset link.');
        } catch (\Throwable $e) {
            Log::error('[PASSWORD RESET - SendLink] Failed for email: ' . $request->email . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            // Still return success to prevent email enumeration even on failure
            return $this->successResponse(null, 'If that email address exists in our system, we have sent a password reset link.');
        }
    }
}
