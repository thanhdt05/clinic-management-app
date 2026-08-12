<?php

use App\Models\Specialty;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

it('receptionist can list specialties', function () {
    actingAsRole('RECEPTIONIST');

    $specialties = Specialty::factory()->count(5)->create();

    getJson('/api/specialties')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonFragment([
            'name' => $specialties->first()->name,
        ]);
});

it('admin can create specialty', function () {
    actingAsRole('ADMIN');

    postJson('/api/specialties', [
        'name' => 'Cardiology',
        'description' => 'Heart specialty',
    ])->assertCreated();

    assertDatabaseHas('specialties', [
        'name' => 'Cardiology',
    ]);
});

it('rejects duplicated specialty name', function () {
    actingAsRole('ADMIN');

    Specialty::factory()->create([
        'name' => 'Cardiology',
    ]);

    postJson('/api/specialties', [
        'name' => 'Cardiology',
        'description' => 'Heart specialty',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('receptionist cannot create specialty', function () {
    actingAsRole('RECEPTIONIST');

    postJson('/api/specialties', [
        'name' => 'Neurology',
    ])->assertForbidden();
});

it('admin can update specialty', function () {
    actingAsRole('ADMIN');

    $specialty = Specialty::factory()->create();

    patchJson("/api/specialties/{$specialty->id}", [
        'name' => 'Cardiology',
        'description' => 'Heart specialty',
    ])->assertOk();

    expect($specialty->fresh()->name)->toBe('Cardiology');
});
