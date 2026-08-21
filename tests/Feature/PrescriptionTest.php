<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
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

function prescriptionTestContext(): array
{
    $doctorUser = userWithRole('DOCTOR');
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'completed',
    ]);
    $examination = Examination::factory()->create([
        'appointment_id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
    ]);

    return [$doctorUser, $doctor, $examination, $patient];
}

it('doctor can list own prescriptions and admin can list all', function () {
    [$doctorUser, $doctor, $examination] = prescriptionTestContext();
    $prescription = Prescription::factory()->create([
        'examination_id' => $examination->id,
        'doctor_id' => $doctor->id,
    ]);

    // Another doctor's prescription
    $otherDoctorUser = userWithRole('DOCTOR');
    $otherDoctor = Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);
    $otherExam = Examination::factory()->create(['doctor_id' => $otherDoctor->id]);
    Prescription::factory()->create([
        'examination_id' => $otherExam->id,
        'doctor_id' => $otherDoctor->id,
    ]);

    // Doctor only sees own prescriptions
    Sanctum::actingAs($doctorUser);
    $response = getJson('/api/prescriptions');
    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $prescription->id);

    // Admin sees all
    actingAsRole('ADMIN');
    getJson('/api/prescriptions')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('doctor can create prescription with items and stock is automatically decremented', function () {
    [$doctorUser, $doctor, $examination] = prescriptionTestContext();
    $medicine = Medicine::factory()->create(['stock' => 50, 'is_active' => true]);

    Sanctum::actingAs($doctorUser);

    $payload = [
        'examination_id' => $examination->id,
        'notes' => 'Take rest and drink plenty of water',
        'items' => [
            [
                'medicine_id' => $medicine->id,
                'quantity' => 5,
                'dosage' => '1 tablet twice a day',
                'usage_instruction' => 'After breakfast and dinner',
            ],
        ],
    ];

    $response = postJson('/api/prescriptions', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.notes', 'Take rest and drink plenty of water')
        ->assertJsonPath('data.items.0.quantity', 5);

    assertDatabaseHas('prescriptions', [
        'examination_id' => $examination->id,
        'doctor_id' => $doctor->id,
    ]);

    assertDatabaseHas('prescription_items', [
        'medicine_id' => $medicine->id,
        'quantity' => 5,
    ]);

    expect($medicine->fresh()->stock)->toBe(45);
});

it('rejects prescription creation if medicine has insufficient stock or is inactive', function () {
    [$doctorUser, , $examination] = prescriptionTestContext();
    $outOfStockMed = Medicine::factory()->create(['stock' => 2, 'is_active' => true]);
    $inactiveMed = Medicine::factory()->create(['stock' => 50, 'is_active' => false]);

    Sanctum::actingAs($doctorUser);

    // Insufficient stock
    postJson('/api/prescriptions', [
        'examination_id' => $examination->id,
        'items' => [
            ['medicine_id' => $outOfStockMed->id, 'quantity' => 10, 'dosage' => '1 tab'],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');

    // Inactive medicine
    postJson('/api/prescriptions', [
        'examination_id' => $examination->id,
        'items' => [
            ['medicine_id' => $inactiveMed->id, 'quantity' => 2, 'dosage' => '1 tab'],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('medicine_id');
});

it('rejects duplicate prescription for the same examination', function () {
    [$doctorUser, $doctor, $examination] = prescriptionTestContext();
    Prescription::factory()->create([
        'examination_id' => $examination->id,
        'doctor_id' => $doctor->id,
    ]);

    Sanctum::actingAs($doctorUser);

    postJson('/api/prescriptions', [
        'examination_id' => $examination->id,
        'items' => [],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('examination_id');
});

it('doctor can view and update prescription notes', function () {
    [$doctorUser, $doctor, $examination] = prescriptionTestContext();
    $prescription = Prescription::factory()->create([
        'examination_id' => $examination->id,
        'doctor_id' => $doctor->id,
        'notes' => 'Original notes',
    ]);

    Sanctum::actingAs($doctorUser);

    getJson("/api/prescriptions/{$prescription->id}")
        ->assertOk()
        ->assertJsonPath('data.notes', 'Original notes');

    putJson("/api/prescriptions/{$prescription->id}", ['notes' => 'Updated notes'])
        ->assertOk()
        ->assertJsonPath('data.notes', 'Updated notes');

    expect($prescription->fresh()->notes)->toBe('Updated notes');
});

it('doctor can add item to existing prescription and medicine stock is reduced', function () {
    [$doctorUser, $doctor, $examination] = prescriptionTestContext();
    $prescription = Prescription::factory()->create([
        'examination_id' => $examination->id,
        'doctor_id' => $doctor->id,
    ]);

    $medicine = Medicine::factory()->create(['stock' => 30, 'is_active' => true]);

    Sanctum::actingAs($doctorUser);

    postJson("/api/prescriptions/{$prescription->id}/items", [
        'medicine_id' => $medicine->id,
        'quantity' => 10,
        'dosage' => '2 tabs daily',
        'usage_instruction' => 'Take after lunch with water',
    ])
        ->assertCreated()
        ->assertJsonPath('data.items.0.quantity', 10);

    expect($medicine->fresh()->stock)->toBe(20);
});

it('doctor can update item quantity and medicine stock adjusts accordingly', function () {
    [$doctorUser, $doctor, $examination] = prescriptionTestContext();
    $prescription = Prescription::factory()->create([
        'examination_id' => $examination->id,
        'doctor_id' => $doctor->id,
    ]);

    $medicine = Medicine::factory()->create(['stock' => 40]);
    $item = PrescriptionItem::factory()->create([
        'prescription_id' => $prescription->id,
        'medicine_id' => $medicine->id,
        'quantity' => 10,
    ]);

    Sanctum::actingAs($doctorUser);

    // Increase item quantity from 10 to 15 (decreases stock by 5 -> 35)
    patchJson("/api/prescriptions/{$prescription->id}/items/{$item->id}", [
        'quantity' => 15,
        'dosage' => '1 tab 3 times',
    ])->assertOk();

    expect($medicine->fresh()->stock)->toBe(35);

    // Decrease item quantity from 15 to 5 (restores stock by 10 -> 45)
    patchJson("/api/prescriptions/{$prescription->id}/items/{$item->id}", [
        'quantity' => 5,
    ])->assertOk();

    expect($medicine->fresh()->stock)->toBe(45);
});

it('doctor can remove prescription item and full stock is restored', function () {
    [$doctorUser, $doctor, $examination] = prescriptionTestContext();
    $prescription = Prescription::factory()->create([
        'examination_id' => $examination->id,
        'doctor_id' => $doctor->id,
    ]);

    $medicine = Medicine::factory()->create(['stock' => 20]);
    $item = PrescriptionItem::factory()->create([
        'prescription_id' => $prescription->id,
        'medicine_id' => $medicine->id,
        'quantity' => 8,
    ]);

    Sanctum::actingAs($doctorUser);

    deleteJson("/api/prescriptions/{$prescription->id}/items/{$item->id}")
        ->assertOk();

    assertDatabaseMissing('prescription_items', ['id' => $item->id]);
    expect($medicine->fresh()->stock)->toBe(28);
});

it('doctor cannot modify another doctor prescription', function () {
    [$doctorUser, $doctor, $examination] = prescriptionTestContext();
    $prescription = Prescription::factory()->create([
        'examination_id' => $examination->id,
        'doctor_id' => $doctor->id,
    ]);

    $otherDoctorUser = userWithRole('DOCTOR');
    Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);

    Sanctum::actingAs($otherDoctorUser);

    getJson("/api/prescriptions/{$prescription->id}")->assertForbidden();
    putJson("/api/prescriptions/{$prescription->id}", ['notes' => 'Hacked'])->assertForbidden();
});
