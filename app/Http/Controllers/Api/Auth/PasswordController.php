<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * How long (in seconds) a reset code remains valid.
     */
    private const CODE_TTL = 600;

    /**
     * Issue a one-time reset code for a phone number and store it (hashed) in Redis.
     *
     * In a real app the code would be delivered by SMS. Outside production we return it
     * in the response so the flow can be exercised without an SMS provider.
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');
        $code = (string) random_int(100000, 999999);

        Redis::setex($this->codeKey($phone), self::CODE_TTL, Hash::make($code));

        return response()->json([
            'message' => 'A reset code has been sent to your phone.',
            'expires_in' => self::CODE_TTL,
            'code' => app()->isProduction() ? null : $code,
        ]);
    }

    /**
     * Verify the reset code from Redis and set a new password.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');
        $key = $this->codeKey($phone);
        $hashedCode = Redis::get($key);

        if (! $hashedCode || ! Hash::check($request->validated('code'), $hashedCode)) {
            throw ValidationException::withMessages([
                'code' => ['The reset code is invalid or has expired.'],
            ]);
        }

        $user = User::query()->where('phone', $phone)->firstOrFail();
        $user->update(['password' => $request->validated('password')]);

        // Burn the code and revoke every existing token so old sessions can't continue.
        Redis::del($key);
        $user->tokens()->delete();

        return response()->json(['message' => 'Password has been reset.']);
    }

    /**
     * Change the password for the currently authenticated user.
     */
    public function change(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->validated('password')]);

        // Revoke other tokens, keeping the one used for this request active.
        $user->tokens()
            ->where('id', '!=', $user->currentAccessToken()->getKey())
            ->delete();

        return response()->json(['message' => 'Password has been changed.']);
    }

    /**
     * Redis key under which a phone number's reset code is stored.
     */
    private function codeKey(string $phone): string
    {
        return "password_reset_code:{$phone}";
    }
}
