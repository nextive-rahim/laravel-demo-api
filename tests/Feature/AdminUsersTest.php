<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an admin can list all users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(3)->create();
    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'phone', 'is_admin']]]);
});

test('an admin can read dashboard stats', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(2)->create();
    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/stats')
        ->assertOk()
        ->assertJson(['total_users' => 3, 'admins' => 1, 'customers' => 2]);
});

test('a non-admin user is forbidden', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->getJson('/api/admin/users')->assertForbidden();
});

test('a guest is unauthenticated', function () {
    $this->getJson('/api/admin/users')->assertUnauthorized();
});

test('the seeded default admin can log in and reach admin endpoints', function () {
    $this->seed(\Database\Seeders\DefaultAdminSeeder::class);

    $token = $this->postJson('/api/auth/login', [
        'phone' => '01718663032',
        'password' => '123456',
    ])->assertOk()->json('token');

    $this->withToken($token)->getJson('/api/admin/users')->assertOk();
});
