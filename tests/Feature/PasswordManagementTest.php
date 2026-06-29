<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

describe('forgot password', function () {
    test('issues a reset code stored in redis', function () {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/forgot-password', ['phone' => $user->phone])
            ->assertOk()
            ->assertJsonStructure(['message', 'expires_in', 'code']);

        $code = $response->json('code');
        expect($code)->not->toBeNull();

        $stored = Redis::get("password_reset_code:{$user->phone}");
        expect($stored)->not->toBeNull()
            ->and(Hash::check($code, $stored))->toBeTrue();

        Redis::del("password_reset_code:{$user->phone}");
    });

    test('rejects an unknown phone', function () {
        $this->postJson('/api/auth/forgot-password', ['phone' => '01999999999'])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('phone');
    });
});

describe('reset password', function () {
    test('resets the password with a valid code', function () {
        $user = User::factory()->create(['password' => 'old-password']);
        $user->createToken('api'); // an old session that should be revoked

        $code = $this->postJson('/api/auth/forgot-password', ['phone' => $user->phone])->json('code');

        $this->postJson('/api/auth/reset-password', [
            'phone' => $user->phone,
            'code' => $code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk();

        expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue()
            ->and($user->fresh()->tokens()->count())->toBe(0)
            ->and(Redis::get("password_reset_code:{$user->phone}"))->toBeNull();
    });

    test('rejects an invalid code', function () {
        $user = User::factory()->create();
        $this->postJson('/api/auth/forgot-password', ['phone' => $user->phone]);

        $this->postJson('/api/auth/reset-password', [
            'phone' => $user->phone,
            'code' => '000000',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('code');

        Redis::del("password_reset_code:{$user->phone}");
    });
});

describe('change password', function () {
    test('changes the password for an authenticated user', function () {
        $user = User::factory()->create(['password' => 'current-password']);
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/change-password', [
            'current_password' => 'current-password',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertOk();

        expect(Hash::check('brand-new-password', $user->fresh()->password))->toBeTrue();
    });

    test('rejects a wrong current password', function () {
        $user = User::factory()->create(['password' => 'current-password']);
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/change-password', [
            'current_password' => 'not-the-password',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('current_password');
    });

    test('requires authentication', function () {
        $this->postJson('/api/auth/change-password', [
            'current_password' => 'whatever',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertUnauthorized();
    });
});
