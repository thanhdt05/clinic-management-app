<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(function () {
                $doctorRole = Role::where('name', 'DOCTOR')->first() ?? Role::first();
                return ['role_id' => $doctorRole?->id];
            }),
            'specialty_id' => Specialty::factory(),
            'license_number' => 'DOC-' . $this->faker->unique()->numerify('######'),
            'bio' => $this->faker->paragraph(),
        ];
    }
}
