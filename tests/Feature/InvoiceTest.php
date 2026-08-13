<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\assertDatabaseHas;
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

function invoiceTestContext(): array
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

    return [$doctor, $patient, $examination];
}

it('cashier can list invoices with status filter and pagination', function () {
    actingAsRole('CASHIER');

    Invoice::factory()->create(['status' => 'unpaid']);
    Invoice::factory()->create(['status' => 'paid']);

    $response = getJson('/api/invoices?status=unpaid');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['status' => 'unpaid']);
});

it('cashier creates invoice automatically calculating subtotal and total from prescription items and consultation fee', function () {
    actingAsRole('CASHIER');

    [, , $examination] = invoiceTestContext();

    $medicine = Medicine::factory()->create(['price' => 50000]);
    $prescription = Prescription::factory()->create(['examination_id' => $examination->id]);
    PrescriptionItem::factory()->create([
        'prescription_id' => $prescription->id,
        'medicine_id' => $medicine->id,
        'quantity' => 2,
    ]);

    $response = postJson('/api/invoices', [
        'examination_id' => $examination->id,
        'discount' => 30000,
    ]);

    $response->assertCreated()
        ->assertJsonFragment([
            'subtotal' => '200000.00',
            'discount' => '30000.00',
            'total' => '170000.00',
            'status' => 'unpaid',
        ]);

    assertDatabaseHas('invoices', [
        'examination_id' => $examination->id,
        'subtotal' => 200000,
        'discount' => 30000,
        'total' => 170000,
        'status' => 'unpaid',
    ]);
});

it('rejects invoice creation for examination with duplicate invoice', function () {
    actingAsRole('CASHIER');

    [, , $examination] = invoiceTestContext();
    Invoice::factory()->create(['examination_id' => $examination->id]);

    postJson('/api/invoices', [
        'examination_id' => $examination->id,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('examination_id');
});

it('rejects invoice creation if discount exceeds subtotal', function () {
    actingAsRole('CASHIER');

    [, , $examination] = invoiceTestContext();

    postJson('/api/invoices', [
        'examination_id' => $examination->id,
        'discount' => 150000,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('discount');
});

it('cashier can view invoice detail', function () {
    actingAsRole('CASHIER');

    $invoice = Invoice::factory()->create();

    getJson("/api/invoices/{$invoice->id}")
        ->assertOk()
        ->assertJsonFragment([
            'id' => $invoice->id,
            'invoice_code' => $invoice->invoice_code,
        ]);
});

it('cashier can update discount on unpaid invoice', function () {
    actingAsRole('CASHIER');

    $invoice = Invoice::factory()->create([
        'subtotal' => 200000,
        'discount' => 10000,
        'total' => 190000,
        'status' => 'unpaid',
    ]);

    putJson("/api/invoices/{$invoice->id}", [
        'discount' => 50000,
    ])->assertOk()
        ->assertJsonFragment([
            'discount' => '50000.00',
            'total' => '150000.00',
        ]);

    expect($invoice->fresh()->total)->toEqual('150000.00');
});

it('cannot update discount if discount exceeds subtotal', function () {
    actingAsRole('CASHIER');

    $invoice = Invoice::factory()->create([
        'subtotal' => 100000,
        'discount' => 10000,
        'total' => 90000,
        'status' => 'unpaid',
    ]);

    putJson("/api/invoices/{$invoice->id}", [
        'discount' => 120000,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('discount');
});

it('cashier can cancel unpaid invoice', function () {
    actingAsRole('CASHIER');

    $invoice = Invoice::factory()->create([
        'status' => 'unpaid',
    ]);

    patchJson("/api/invoices/{$invoice->id}/status", [
        'status' => 'cancelled',
    ])->assertOk()
        ->assertJsonFragment([
            'status' => 'cancelled',
        ]);

    expect($invoice->fresh()->status)->toBe('cancelled');
});

it('cannot update or cancel invoice if status is not unpaid', function () {
    actingAsRole('CASHIER');

    $paidInvoice = Invoice::factory()->create(['status' => 'paid']);

    putJson("/api/invoices/{$paidInvoice->id}", [
        'discount' => 5000,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    patchJson("/api/invoices/{$paidInvoice->id}/status", [
        'status' => 'cancelled',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('unauthorized role cannot create or update invoice', function () {
    actingAsRole('DOCTOR');

    [, , $examination] = invoiceTestContext();

    postJson('/api/invoices', [
        'examination_id' => $examination->id,
    ])->assertForbidden();
});
