<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Invoice;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctor = Doctor::first() ?? Doctor::factory()->create();
        $patient = Patient::first() ?? Patient::factory()->create();
        $medicine = Medicine::first() ?? Medicine::factory()->create(['price' => 50000]);

        // 1. Seed examination without invoice (ready to test POST /api/invoices)
        $appointmentUninvoiced = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'scheduled_at' => now(),
        ]);

        $examUninvoiced = Examination::factory()->create([
            'appointment_id' => $appointmentUninvoiced->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'examined_at' => now(),
        ]);

        $prescUninvoiced = Prescription::create([
            'examination_id' => $examUninvoiced->id,
            'doctor_id' => $doctor->id,
            'notes' => 'Prescription for testing store invoice',
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescUninvoiced->id,
            'medicine_id' => $medicine->id,
            'quantity' => 2,
            'dosage' => '1 tablet daily',
            'usage_instruction' => 'Take with water',
        ]);

        // 2. Seed Unpaid Invoice
        $app1 = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'scheduled_at' => now()->subDay(),
        ]);

        $exam1 = Examination::factory()->create([
            'appointment_id' => $app1->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'examined_at' => now()->subDay(),
        ]);

        Invoice::firstOrCreate([
            'examination_id' => $exam1->id,
        ], [
            'invoice_code' => '2026-00001',
            'subtotal' => 200000,
            'discount' => 20000,
            'total' => 180000,
            'status' => 'unpaid',
            'issued_at' => now()->subDay(),
        ]);

        // 3. Seed Paid Invoice
        $app2 = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'scheduled_at' => now()->subDays(2),
        ]);

        $exam2 = Examination::factory()->create([
            'appointment_id' => $app2->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'examined_at' => now()->subDays(2),
        ]);

        Invoice::firstOrCreate([
            'examination_id' => $exam2->id,
        ], [
            'invoice_code' => '2026-00002',
            'subtotal' => 100000,
            'discount' => 0,
            'total' => 100000,
            'status' => 'paid',
            'issued_at' => now()->subDays(2),
        ]);

        // 4. Seed Cancelled Invoice
        $app3 = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'completed',
            'scheduled_at' => now()->subDays(3),
        ]);

        $exam3 = Examination::factory()->create([
            'appointment_id' => $app3->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'examined_at' => now()->subDays(3),
        ]);

        Invoice::firstOrCreate([
            'examination_id' => $exam3->id,
        ], [
            'invoice_code' => '2026-00003',
            'subtotal' => 150000,
            'discount' => 10000,
            'total' => 140000,
            'status' => 'cancelled',
            'issued_at' => now()->subDays(3),
        ]);
    }
}
