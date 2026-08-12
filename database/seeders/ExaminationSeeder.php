<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Examination;
use Illuminate\Database\Seeder;

class ExaminationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy các lịch hẹn đã xác nhận hoặc hoàn thành và chưa có ca khám
        $appointments = Appointment::query()
            ->whereIn('status', ['confirmed', 'completed'])
            ->doesntHave('examination')
            ->get();

        foreach ($appointments as $appointment) {
            Examination::factory()
                ->recycle($appointment)
                ->recycle($appointment->doctor)
                ->recycle($appointment->patient)
                ->create([
                    'appointment_id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'patient_id' => $appointment->patient_id,
                ]);
        }
    }
}
