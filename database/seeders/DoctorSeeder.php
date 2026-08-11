<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctorUser = User::where('email', 'doctor@clinic.test')->first();
        $specialty = Specialty::where('name', 'General Internal Medicine')->first();

        if ($doctorUser && ! Doctor::where('user_id', $doctorUser->id)->exists()) {
            Doctor::create([
                'user_id' => $doctorUser->id,
                'specialty_id' => $specialty?->id ?? Specialty::first()?->id,
                'license_number' => 'DOC-000001',
                'bio' => 'Lead physician with over 10 years of clinical experience.',
            ]);
        }

        Doctor::factory()->count(10)->create();
    }
}
