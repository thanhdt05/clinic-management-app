<?php

use App\Models\ActivityLog;
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

use function Pest\Laravel\getJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

it('unauthenticated user cannot view stats', function () {
    getJson('/api/stats')->assertUnauthorized();
});

it('user without stats permission cannot view stats', function () {
    actingAsRole('DOCTOR');

    getJson('/api/stats')->assertForbidden();
});

it('admin can retrieve aggregate dashboard stats', function () {
    actingAsRole('ADMIN');

    $patient = Patient::factory()->create();
    $doctorUser = userWithRole('DOCTOR');
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

    $appointment = Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->setTime(10, 0, 0),
        'status' => 'completed',
    ]);

    $examination = Examination::factory()->create([
        'appointment_id' => $appointment->id,
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
    ]);

    $medicine = Medicine::factory()->create([
        'price' => 25000,
        'stock' => 5,
        'is_active' => true,
    ]);

    $prescription = Prescription::factory()->create([
        'examination_id' => $examination->id,
        'doctor_id' => $doctor->id,
    ]);

    PrescriptionItem::factory()->create([
        'prescription_id' => $prescription->id,
        'medicine_id' => $medicine->id,
        'quantity' => 4,
    ]);

    Invoice::factory()->create([
        'examination_id' => $examination->id,
        'subtotal' => 200000,
        'discount' => 20000,
        'total' => 180000,
        'status' => 'paid',
        'issued_at' => now(),
    ]);

    ActivityLog::create([
        'user_id' => $doctorUser->id,
        'action' => 'EXAMINATION_CREATED',
        'subject_type' => Examination::class,
        'subject_id' => $examination->id,
    ]);

    $response = getJson('/api/stats');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'overview' => [
                    'total_patients',
                    'today_appointments',
                    'monthly_revenue',
                    'low_stock_medicines',
                ],
                'revenue_stream',
                'top_prescribed_medicines',
                'top_doctors',
                'recent_invoices',
                'recent_activities',
            ],
        ])
        ->assertJsonPath('data.overview.total_patients', 1)
        ->assertJsonPath('data.overview.today_appointments', 1)
        ->assertJsonPath('data.overview.monthly_revenue', 180000)
        ->assertJsonPath('data.overview.low_stock_medicines', 1);
});
