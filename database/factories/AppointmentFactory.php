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
            'scheduled_at' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'status' => $this->faker->randomElement(['scheduled', 'confirmed', 'cancelled', 'completed']),
            'reason' => $this->faker->randomElement([
                'Khám sức khỏe tổng quát',
                'Tái khám định kỳ',
                'Kiểm tra huyết áp và tim mạch',
                'Sốt nhẹ, đau họng',
                'Tư vấn dinh dưỡng',
                'Đau dạ dày, đầy hơi',
            ]),
        ];
    }
}
