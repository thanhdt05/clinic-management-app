<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

it('admin can see user list', function () {
    actingAsRole('ADMIN');

    User::factory()->count(3)->create([
        'role_id' => Role::where('name', 'DOCTOR')->firstOrFail()->id,
        'is_active' => true,
    ]);

    getJson('api/users')->assertOk();
});

it('admin can create user', function () {
    actingAsRole('ADMIN');

    $role = Role::where('name', 'DOCTOR')->firstOrFail();

    $payload = [
        'name' => 'Doctor A',
        'email' => 'doctor@example.com',
        'password' => 'password123',
        'password_confirm' => 'password123',
        'role_id' => $role->id,
    ];

    $response = postJson('/api/users', $payload);

    $response->assertCreated();

    $user = User::where('email', $payload['email'])->firstOrFail();

    expect(Hash::check(
        'password123',
        $user->password
    )
    )->toBeTrue();
});

it('admin can update user', function () {
    actingAsRole('ADMIN');

    $user = userWithRole('RECEPTIONIST');

    putJson("/api/users/{$user->id}", [
        'name' => 'Updated',
    ])->assertOk();

    assertDatabaseHas('users', [
        'name' => 'Updated',
    ]);
});

it('admin can deactivate user', function () {
    actingAsRole('ADMIN');

    $user = userWithRole('RECEPTIONIST');

    deleteJson("/api/users/{$user->id}")->assertOk();

    assertDatabaseHas('users', [
        'id' => $user->id,
        'is_active' => false,
    ]);
});

it('admin can update user status', function () {
    actingAsRole('ADMIN');

    $user = userWithRole('RECEPTIONIST', [
        'is_active' => false,
    ]);

    patchJson("/api/users/{$user->id}/status", [
        'is_active' => true,
    ])->assertOk();

    expect(
        $user->fresh()->is_active
    )->toBeTrue();
});

it('admin cannot deactivate last admin', function () {
    $admin = actingAsRole('ADMIN');

    deleteJson("/api/users/{$admin->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role_id');

    expect(
        $admin->fresh()->is_active
    )->toBeTrue();
});

it('non admin cannot get list users', function () {
    actingAsRole('RECEPTIONIST');

    getJson('/api/users')
        ->assertForbidden();
});
