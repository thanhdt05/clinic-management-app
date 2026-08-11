<?php

use App\Models\Patient;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\deleteJson;
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

it('receptionist can get list patients', function () {
    actingAsRole('RECEPTIONIST');

    Patient::factory()->count(5)->create();

    $response = getJson('/api/patients');

    $response->assertOk()
        ->assertJsonCount(5, 'data');
});

it('receptionist can create patient', function () {
    actingAsRole('RECEPTIONIST');

    $payload = [
        'full_name' => 'Nguyen Van A',
        'gender' => 'male',
        'date_of_birth' => '2000-01-01',
        'phone' => '0901234567',
        'email' => 'patient@test.com',
        'address' => 'Ha Noi',
    ];

    $response = postJson('/api/patients', $payload);

    $response->assertCreated();

    expect(
        $response->json('data.code')
    )->toStartWith('BN-');
});

it('receptionist can view patient detail', function () {
    actingAsRole('RECEPTIONIST');

    $patient = Patient::factory()->create();

    $response = getJson("/api/patients/{$patient->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $patient->id);
});

it('receptionist can update patient', function () {
    actingAsRole('RECEPTIONIST');

    $patient = Patient::factory()->create();

    patchJson("/api/patients/{$patient->id}", [
        'full_name' => 'name',
        'phone' => '696969',
    ])->assertOk();

    assertDatabaseHas('patients', [
        'id' => $patient->id,
        'full_name' => 'name',
        'phone' => '696969',
    ]);
});

it('admin can soft delete patient', function () {
    actingAsRole('ADMIN');

    $patient = Patient::factory()->create();

    deleteJson("/api/patients/{$patient->id}")->assertOK();

    assertSoftDeleted('patients', [
        'id' => $patient->id,
    ]);
});

it('can search patients by name', function () {
    actingAsRole('RECEPTIONIST');

    Patient::factory()->create([
        'full_name' => 'Nguyen A',
    ]);

    Patient::factory()->create([
        'full_name' => 'Nguyen B',
    ]);

    $response = getJson('/api/patients?search=Nguyen A');

    $response
        ->assertOk()
        ->assertJsonFragment([
            'full_name' => 'Nguyen A',
        ])
        ->assertJsonMissing([
            'full_name' => 'Nguyen B',
        ]);
});

it('doctor cannot create patient', function () {
    actingAsRole('DOCTOR');

    postJson('/api/patients', [
        'full_name' => 'Test',
        'gender' => 'male',
        'date_of_birth' => '2000-01-01',
        'phone' => '0000000000',
    ])->assertForbidden();
});
