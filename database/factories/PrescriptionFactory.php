<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'examination_id' => Examination::factory(),
            'doctor_id' => Doctor::factory(),
            'notes' => fake()->sentence(),
        ];
    }
}
