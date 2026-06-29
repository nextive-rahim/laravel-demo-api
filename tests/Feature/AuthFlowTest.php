<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('phone check', function () {
    test('reports an unknown phone as available for signup', function () {
        $this->postJson('/api/auth/check', ['phone' => '01700000000'])
            ->assertOk()
            ->assertJson(['exists' => false, 'next' => 'signup']);
    });

    test('reports an existing phone as ready for password', function () {
        $user = User::factory()->create();

        $this->postJson('/api/auth/check', ['phone' => $user->phone])
            ->assertOk()
            ->assertJson(['exists' => true, 'next' => 'password']);
    });

    test('requires a phone number', function () {
        $this->postJson('/api/auth/check', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('phone');
    });
});

describe('registration', function () {
    test('creates a user and returns a token', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'phone' => '01711111111',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.phone', '01711111111')
            ->assertJsonStructure(['user' => ['id', 'name', 'phone'], 'token']);

        $this->assertDatabaseHas('users', ['phone' => '01711111111', 'name' => 'Jane Doe']);
    });

    test('rejects a phone that already exists', function () {
        $user = User::factory()->create();

        $this->postJson('/api/auth/register', [
            'name' => 'Someone',
            'phone' => $user->phone,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('phone');
    });

    test('requires matching password confirmation', function () {
        $this->postJson('/api/auth/register', [
            'name' => 'Someone',
            'phone' => '01722222222',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('password');
    });
});

describe('login', function () {
    test('authenticates with the correct password', function () {
        $user = User::factory()->create(['password' => 'password123']);

        $this->postJson('/api/auth/login', [
            'phone' => $user->phone,
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['user', 'token']);
    });

    test('rejects an incorrect password', function () {
        $user = User::factory()->create(['password' => 'password123']);

        $this->postJson('/api/auth/login', [
            'phone' => $user->phone,
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('phone');
    });
});

describe('authenticated endpoints', function () {
    test('returns the current user', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    });

    test('logout revokes the current token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        expect($user->fresh()->tokens()->count())->toBe(0);
    });

    test('rejects unauthenticated access', function () {
        $this->getJson('/api/auth/user')->assertUnauthorized();
    });
});
