<?php

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

it('admin can list roles', function () {
    actingAsRole('ADMIN');

    getJson('/api/roles')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'display_name',
                    'permissions',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'total',
                'per_page',
            ],
        ])
        ->assertJsonFragment([
            'name' => 'ADMIN',
        ])
        ->assertJsonFragment([
            'name' => 'RECEPTIONIST',
        ])
        ->assertJsonFragment([
            'name' => 'DOCTOR',
        ])
        ->assertJsonFragment([
            'name' => 'PHARMACIST',
        ])
        ->assertJsonFragment([
            'name' => 'CASHIER',
        ]);
});

it('non admin cannot get list roles', function (string $role) {
    actingAsRole($role);

    getJson('/api/roles')
        ->assertForbidden();
})->with([
    'RECEPTIONIST',
    'DOCTOR',
    'PHARMACIST',
    'CASHIER',
]);

it('unauthenticated user cannot get list roles', function () {
    getJson('/api/roles')
        ->assertUnauthorized();
});
