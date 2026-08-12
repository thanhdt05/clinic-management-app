<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctor1 = Doctor::find(1);
        $patient1 = Patient::where('code', 'PAT-0001')->first();
        $patient2 = Patient::where('code', 'PAT-0002')->first();

        if (! $doctor1 || ! $patient1 || ! $patient2) {
            return;
        }

        // 1. Confirmed appointment: Ready to test storing an examination (POST /api/examinations)
        Appointment::firstOrCreate([
            'doctor_id' => $doctor1->id,
            'patient_id' => $patient1->id,
            'status' => 'confirmed',
        ], [
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'reason' => 'Sore throat, fever, and persistent cough',
        ]);

        // 2. Completed appointment: Used to seed a valid examination ready for testing prescription store
        Appointment::firstOrCreate([
            'doctor_id' => $doctor1->id,
            'patient_id' => $patient2->id,
            'status' => 'completed',
        ], [
            'scheduled_at' => now()->subDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'reason' => 'Epigastric pain and heartburn',
        ]);

        Appointment::factory()
            ->count(30)
            ->recycle(Patient::all())
            ->recycle(Doctor::all())
            ->create();
    }
}
