<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'scheduled_at' => $this->faker->dateTimeBetween('-1 day', '+1 day')->format('Y-m-d H:i:s'),
            'status' => $this->faker->randomElement(['scheduled', 'confirmed', 'cancelled', 'completed']),
            'reason' => $this->faker->randomElement([
                'Overdue check-up',
                'Regular follow-up',
                'Blood pressure and cardiovascular check-up',
                'Fever, sore throat',
                'Nutritional counseling',
                'Stomachache, bloating',
                'Check-up for flu',
                'Diabetes management',
                'Consultation for chronic pain',
                'Skin condition evaluation',
                'Pediatric check-up'
            ]),
        ];
    }
}
