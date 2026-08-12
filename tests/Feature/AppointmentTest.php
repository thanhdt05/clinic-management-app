<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
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

function makeAppointment(): array
{
    $doctorUser = userWithRole('DOCTOR');

    $doctor = Doctor::factory()->create([
        'user_id' => $doctorUser->id,
    ]);

    $patient = Patient::factory()->create();

    return [$doctorUser, $doctor, $patient];
}

it('receptionist can list appointments', function () {
    actingAsRole('RECEPTIONIST');

    Appointment::factory()->count(5)->create();

    getJson('/api/appointments')
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('receptionist can create scheduled appointment', function () {
    actingAsRole('RECEPTIONIST');

    [, $doctor, $patient] = makeAppointment();

    $payload = [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay(),
        'reason' => 'Regular checkup',
    ];

    $response = postJson('/api/appointments', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'scheduled');

    assertDatabaseHas('appointments', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'status' => 'scheduled',
    ]);
});

it('rejects appointment with deleted patient', function () {
    actingAsRole('RECEPTIONIST');

    [, $doctor, $patient] = makeAppointment();
    $patient->delete();

    postJson('/api/appointments', [
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay(),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('patient_id');
});

it('can update scheduled appointment', function () {
    actingAsRole('RECEPTIONIST');

    [, $doctor, $patient] = makeAppointment();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
    ]);

    patchJson("/api/appointments/{$appointment->id}", [
        'scheduled_at' => now()->addDays(2),
        'reason' => 'Follow-up',
    ])->assertOk();

    expect($appointment->fresh()->reason)->toBe('Follow-up');
});

it('cannot update confirmed appointment', function () {
    actingAsRole('RECEPTIONIST');

    [, $doctor, $patient] = makeAppointment();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'confirmed',
    ]);

    patchJson("/api/appointments/{$appointment->id}", [
        'reason' => 'Follow-up',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('can transition scheduled to confirmed', function () {
    actingAsRole('RECEPTIONIST');

    [, $doctor, $patient] = makeAppointment();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'scheduled',
    ]);

    patchJson("/api/appointments/{$appointment->id}/status", [
        'status' => 'confirmed',
    ])
        ->assertOk();

    expect($appointment->fresh()->status)->toBe('confirmed');
});

it('can cancel scheduled appointment', function () {
    actingAsRole('RECEPTIONIST');

    [, $doctor, $patient] = makeAppointment();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'scheduled',
    ]);

    patchJson(
        "/api/appointments/{$appointment->id}/status",
        ['status' => 'cancelled']
    )->assertOk();

    expect(
        $appointment->fresh()->status
    )->toBe('cancelled');
});

it('rejects invalid appointment transition', function () {
    actingAsRole('RECEPTIONIST');

    [, $doctor, $patient] = makeAppointment();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'completed',
    ]);

    patchJson(
        "/api/appointments/{$appointment->id}/status",
        ['status' => 'cancelled']
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('rejects booking appointment for same doctor with same time', function () {
    actingAsRole('RECEPTIONIST');

    [, $doctor, $patient] = makeAppointment();

    $start = now()
        ->addDay()
        ->setTime(9, 0, 0);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'scheduled',
        'scheduled_at' => $start,
    ]);

    $newPatient = Patient::factory()->create();

    postJson('/api/appointments', [
        'doctor_id' => $doctor->id,
        'patient_id' => $newPatient->id,
        'scheduled_at' => $start->copy()->addMinutes(10),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_at');
});

it('create adjacent appointment after 30 minutes', function () {
    actingAsRole('RECEPTIONIST');

    [, $doctor, $patient] = makeAppointment();

    $start = now()
        ->addDay()
        ->setTime(9, 0, 0);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'scheduled',
        'scheduled_at' => $start,
    ]);

    $newPatient = Patient::factory()->create();

    postJson('/api/appointments', [
        'doctor_id' => $doctor->id,
        'patient_id' => $newPatient->id,
        'scheduled_at' => $start->copy()->addMinutes(30),
    ])->assertCreated();

    assertDatabaseHas('appointments', [
        'scheduled_at' => $start->copy()->addMinutes(30),
    ]);
});

it('ignores cancelled appointment when checking conflict', function () {
    actingAsRole('RECEPTIONIST');

    [, $doctor, $patient] = makeAppointment();

    $start = now()
        ->addDay()
        ->setTime(9, 0, 0);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'cancelled',
        'scheduled_at' => $start,
    ]);

    postJson('/api/appointments', [
        'patient_id' => Patient::factory()->create()->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => $start,
    ])->assertCreated();

    assertDatabaseHas('appointments', [
        'scheduled_at' => $start,
    ]);
});
