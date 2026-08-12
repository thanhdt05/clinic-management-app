<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Examination;
use Illuminate\Database\Seeder;

class ExaminationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctor1 = Doctor::find(1);
        $completedAppointment = Appointment::where('doctor_id', $doctor1?->id)
            ->where('status', 'completed')
            ->first();

        if (! $doctor1 || ! $completedAppointment) {
            return;
        }

        // Valid Examination without a prescription: Ready to test storing a prescription (POST /api/prescriptions)
        if (! Examination::where('appointment_id', $completedAppointment->id)->exists()) {
            Examination::create([
                'appointment_id' => $completedAppointment->id,
                'doctor_id' => $doctor1->id,
                'patient_id' => $completedAppointment->patient_id,
                'diagnosis' => 'Acute Gastritis',
                'notes' => 'Epigastric tenderness reported after meal. Ready for prescription creation.',
                'examined_at' => $completedAppointment->scheduled_at,
            ]);
        }
    }
}
