<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = [
            [
                'code' => 'PAT-0001',
                'full_name' => 'John Doe',
                'gender' => 'male',
                'date_of_birth' => '1990-01-15',
                'phone' => '0901234567',
                'email' => 'john.doe@example.com',
                'address' => '123 Main Street, Ward 1, District 1',
            ],
            [
                'code' => 'PAT-0002',
                'full_name' => 'Jane Smith',
                'gender' => 'female',
                'date_of_birth' => '1985-05-20',
                'phone' => '0902345678',
                'email' => 'jane.smith@example.com',
                'address' => '456 High Street, Ward 2, District 3',
            ],
        ];

        foreach ($patients as $patient) {
            Patient::firstOrCreate(['code' => $patient['code']], $patient);
        }

        Patient::factory()->count(15)->create();
    }
}
