<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Password Reset', description: 'Password reset endpoints')]
class ResetPasswordController extends ApiController
{
    #[OA\Post(
        path: '/api/v1/auth/password/reset',
        summary: 'Reset password using token from email',
        tags: ['Password Reset'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', example: 'abc123...'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'newpassword123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'newpassword123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password reset successfully'),
            new OA\Response(response: 422, description: 'Validation error or invalid/expired token'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Log::info('[PASSWORD RESET - Reset] Password reset attempt for email: ' . $request->email);

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, string $password) {
                    DB::transaction(function () use ($user, $password) {
                        $user->forceFill([
                            'password'       => Hash::make($password),
                            'remember_token' => Str::random(60),
                        ])->save();

                        // Revoke all existing tokens for security
                        $user->tokens()->delete();

                        event(new PasswordReset($user));
                        Log::info('[PASSWORD RESET - Reset] Password successfully reset for User ID: ' . $user->id);
                    });
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return $this->successResponse(null, 'Password has been reset successfully. Please log in with your new password.');
            }

            Log::warning('[PASSWORD RESET - Reset] Token invalid/expired for email: ' . $request->email . ' | Status: ' . $status);

            return $this->errorResponse(
                __($status),
                ['token' => 'The password reset token is invalid or has expired.'],
                422
            );

        } catch (\Throwable $e) {
            Log::error('[PASSWORD RESET - Reset] Unexpected error for email: ' . $request->email . ' | Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->serverErrorResponse('Password reset failed. Please try again.');
        }
    }
}

