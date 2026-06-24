<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class EmailVerificationOtpService
{
    public const EXPIRY_MINUTES = 15;

    /**
     * Generate a new 6-digit OTP for the user and persist a hashed copy.
     */
    public function issue(User $user): string
    {
        EmailVerificationCode::where('user_id', $user->id)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::create([
            'user_id'    => $user->id,
            'code'       => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        return $code;
    }

    /**
     * Validate the OTP for the user. Deletes the code on success.
     */
    public function verify(User $user, string $code): bool
    {
        $record = EmailVerificationCode::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $record || ! Hash::check($code, $record->code)) {
            return false;
        }

        $record->delete();

        return true;
    }

    public function expiryMinutes(): int
    {
        return self::EXPIRY_MINUTES;
    }
}
