<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CheckPhoneRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Step one of the flow: check whether a phone number already has an account.
     * The client uses this to decide between the password screen and the signup screen.
     */
    public function check(CheckPhoneRequest $request): JsonResponse
    {
        $exists = User::query()
            ->where('phone', $request->validated('phone'))
            ->exists();

        return response()->json([
            'exists' => $exists,
            'next' => $exists ? 'password' : 'signup',
        ]);
    }

    /**
     * Create a new account for a phone number that does not exist yet, and issue a token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->except('device_name', 'password_confirmation'));

        return response()->json([
            'user' => new UserResource($user),
            'token' => $this->issueToken($user, $request->validated('device_name')),
        ], 201);
    }

    /**
     * Authenticate an existing user by phone + password and issue a token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('phone', $request->validated('phone'))
            ->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'phone' => [__('auth.failed')],
            ]);
        }

        return response()->json([
            'user' => new UserResource($user),
            'token' => $this->issueToken($user, $request->validated('device_name')),
        ]);
    }

    /**
     * Return the currently authenticated user.
     */
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Revoke the token that was used to authenticate the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Issue a fresh Sanctum personal access token for the user.
     */
    private function issueToken(User $user, ?string $deviceName): string
    {
        return $user->createToken($deviceName ?: 'api')->plainTextToken;
    }
}
