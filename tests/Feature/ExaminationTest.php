<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Patient;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
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

function examinationContext(string $status = 'confirmed')
{
    $doctorUser = userWithRole('DOCTOR');

    $doctor = Doctor::factory()->create([
        'user_id' => $doctorUser->id,
    ]);

    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => $status,
        'scheduled_at' => now()->addDay(),
    ]);

    return [$doctorUser, $doctor, $patient, $appointment];
}

it('doctor creates examination from confirmed appointment', function () {
    [$doctorUser, $doctor, $patient, $appointment] = examinationContext('confirmed');

    Sanctum::actingAs($doctorUser);

    postJson('/api/examinations', [
        'appointment_id' => $appointment->id,
        'diagnosis' => 'Test diagnosis',
        'notes' => 'Follow up',
    ])->assertCreated();

    assertDatabaseHas('examinations', [
        'appointment_id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'diagnosis' => 'Test diagnosis',
    ]);
});

it('auto completes appointment when creating examination', function () {
    [$doctorUser, , , $appointment] = examinationContext('confirmed');

    Sanctum::actingAs($doctorUser);

    postJson('/api/examinations', [
        'appointment_id' => $appointment->id,
        'diagnosis' => 'Test diagnosis',
        'notes' => 'Follow up',
    ])->assertCreated();

    expect(
        $appointment->fresh()->status
    )->toBe('completed');
});

it('reject create if appointment is not confirmed', function () {
    [$doctorUser, , , $appointment] = examinationContext('scheduled');

    Sanctum::actingAs($doctorUser);

    postJson('/api/examinations', [
        'appointment_id' => $appointment->id,
        'diagnosis' => 'Test diagnosis',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('appointment_id');

    assertDatabaseMissing('examinations', [
        'appointment_id' => $appointment->id,
    ]);

    expect($appointment->fresh()->status)->toBe('scheduled');
});

it('cannot create two examinations for same appointment', function () {
    [$doctorUser, $doctor, $patient, $appointment] = examinationContext('confirmed');

    Examination::factory()->create([
        'appointment_id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
    ]);

    Sanctum::actingAs($doctorUser);

    postJson('/api/examinations', [
        'appointment_id' => $appointment->id,
        'diagnosis' => 'Test diagnosis',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('appointment_id');
});

it('doctor can update examination', function () {
    [$doctorUser, $doctor, $patient, $appointment] = examinationContext('completed');

    $examination = Examination::factory()->create([
        'appointment_id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
    ]);

    Sanctum::actingAs($doctorUser);

    patchJson("/api/examinations/{$examination->id}", [
        'diagnosis' => 'Updated diagnosis',
        'notes' => 'Updated notes',
    ])->assertOk();

    expect(
        $examination->fresh()->diagnosis
    )->toBe('Updated diagnosis');
});

it('cashier can view examination detail', function () {
    [, $doctor, $patient, $appointment] = examinationContext('completed');

    $examination = Examination::factory()->create([
        'appointment_id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
    ]);

    actingAsRole('CASHIER');

    getJson("/api/examinations/{$examination->id}")->assertOk();
});

it('cashier cannot update examination', function () {
    [, $doctor, $patient, $appointment] = examinationContext('completed');

    $examination = Examination::factory()->create([
        'appointment_id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
    ]);

    actingAsRole('CASHIER');

    patchJson("/api/examinations/{$examination->id}", [
        'diagnosis' => 'Updated diagnosis',
        'notes' => 'Updated notes',
    ])->assertForbidden();
});

it('cashier can list examinations', function () {
    actingAsRole('CASHIER');

    Examination::factory()->count(5)->create();

    getJson('/api/examinations')
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('cashier can list examinations with filters', function () {
    actingAsRole('CASHIER');

    $doctor = Doctor::factory()->create();
    $otherDoctor = Doctor::factory()->create();

    Examination::factory()->create([
        'doctor_id' => $doctor->id,
        'diagnosis' => 'Test diagnosis',
    ]);

    Examination::factory()->create([
        'doctor_id' => $otherDoctor->id,
        'diagnosis' => 'Other diagnosis',
    ]);

    $response = getJson("/api/examinations?doctor_id={$doctor->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'diagnosis' => 'Test diagnosis',
        ])
        ->assertJsonMissing([
            'diagnosis' => 'Other diagnosis',
        ]);
});
