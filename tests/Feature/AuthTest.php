<?php

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;
use function Pest\Laravel\withToken;

beforeEach(function () {
    seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

it('logs in with valid credentials', function () {
    $user = userWithRole('DOCTOR', [
        'email' => 'doctor@test.com',
        'password' => Hash::make('12345678'),
    ]);

    $response = postJson('/api/login', [
        'email' => $user->email,
        'password' => '12345678',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => [
                    'email' => $user->email,
                ],
            ],
        ]);

    expect($response->json('data.token'))->not()->toBeEmpty();
});

it('logs in with wrong password', function () {
    $user = userWithRole('RECEPTIONIST', [
        'password' => Hash::make('password123'),
    ]);

    postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertUnauthorized();
});

it('login with inactive user', function () {
    $user = userWithRole('RECEPTIONIST', [
        'is_active' => false,
        'password' => Hash::make('password123'),
    ]);

    postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password123',
    ])->assertUnauthorized();
});

it('can logout', function () {
    $user = userWithRole('RECEPTIONIST');

    $token = $user->createToken('test')->plainTextToken;

    withToken($token)
        ->postJson('/api/logout')
        ->assertOk();

    assertDatabaseMissing('personal_access_tokens', [
        'token' => $token,
    ]);
});

it('can get current user profile', function () {
    $user = userWithRole('RECEPTIONIST');

    Sanctum::actingAs($user);

    getJson('/api/me')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'User profile retrieved successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ],
        ]);
});
