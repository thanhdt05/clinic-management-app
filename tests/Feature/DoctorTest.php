<?php

use App\Models\Doctor;
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

it('receptionist can list doctors', function () {
    actingAsRole('RECEPTIONIST');

    $doctors = Doctor::factory()->count(5)->create();

    getJson('/api/doctors')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonFragment([
            'id' => $doctors->first()->user->id,
        ]);
});

it('admin can create doctor from user has role doctor', function () {
    actingAsRole('ADMIN');

    $doctorUser = userWithRole('DOCTOR');
    $specialty = Specialty::factory()->create();

    $payload = [
        'user_id' => $doctorUser->id,
        'specialty_id' => $specialty->id,
        'license_number' => 'DOC-001',
        'bio' => 'bio',
    ];

    postJson('/api/doctors', $payload)
        ->assertCreated();

    assertDatabaseHas('doctors', [
        'user_id' => $doctorUser->id,
        'specialty_id' => $specialty->id,
    ]);
});

it('reject create doctor when user has not role doctor', function () {
    actingAsRole('ADMIN');

    $user = userWithRole('CASHIER');
    $specialty = Specialty::factory()->create();

    $payload = [
        'user_id' => $user->id,
        'specialty_id' => $specialty->id,
        'license_number' => 'DOC-001',
        'bio' => 'bio',
    ];

    postJson('/api/doctors', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');
});

it('non admin cannot create doctor', function () {
    actingAsRole('RECEPTIONIST');

    $doctorUser = userWithRole('DOCTOR');
    $specialty = Specialty::factory()->create();

    $payload = [
        'user_id' => $doctorUser->id,
        'specialty_id' => $specialty->id,
        'license_number' => 'DOC-001',
        'bio' => 'bio',
    ];

    postJson('/api/doctors', $payload)
        ->assertForbidden();
});

it('admin can update doctor', function () {
    actingAsRole('ADMIN');

    $doctorUser = userWithRole('DOCTOR');
    $specialty = Specialty::factory()->create();

    $doctor = Doctor::factory()->create([
        'user_id' => $doctorUser->id,
        'specialty_id' => $specialty->id,
    ]);

    $payload = [
        'specialty_id' => $specialty->id,
        'license_number' => 'DOC-002',
        'bio' => 'Updated bio',
    ];

    patchJson("/api/doctors/{$doctor->id}", $payload)->assertOk();

    expect(
        $doctor->fresh()->license_number
    )->toBe('DOC-002');
});

it('receptionist can view doctor detail', function () {
    actingAsRole('RECEPTIONIST');

    $doctorUser = userWithRole('DOCTOR');
    $specialty = Specialty::factory()->create();

    $doctor = Doctor::factory()->create([
        'user_id' => $doctorUser->id,
        'specialty_id' => $specialty->id,
    ]);

    getJson("/api/doctors/{$doctor->id}")
        ->assertOk()
        ->assertJsonFragment([
            'id' => $doctorUser->id,
        ]);
});
