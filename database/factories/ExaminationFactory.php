<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Examination>
 */
class ExaminationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'doctor_id' => Doctor::factory(),
            'patient_id' => Patient::factory(),
            'diagnosis' => $this->faker->randomElement([
                'Acute pharyngitis',
                'Acute bronchitis',
                'Essential hypertension',
                'Type 2 diabetes mellitus',
                'Gastro-esophageal reflux disease',
                'Acute gastritis',
                'Allergic rhinitis',
                'Migraine without aura',
                'Dermatitis',
                'Sprain of ankle',
            ]),
            'notes' => $this->faker->randomElement([
                'Patient advised to rest for 3 days and drink plenty of fluids.',
                'Prescribed oral antibiotics for 5 days. Re-evaluation in 1 week.',
                'Dietary modification recommended. Low sodium diet.',
                'Continue current medication. Blood test scheduled next month.',
                'No signs of complications. Follow up as needed.',
                'Referred to specialist for further evaluation.',
            ]),
            'examined_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
