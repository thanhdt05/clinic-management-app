<?php

namespace Database\Factories;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicine>
 */
class MedicineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 100;
        $sequence++;

        return [
            'code' => sprintf('MED-%04d', $sequence),
            'name' => $this->faker->name,
            'unit' => $this->faker->randomElement(['bottle', 'box', 'strip']),
            'price' => $this->faker->numberBetween(1000, 1000000),
            'stock' => $this->faker->numberBetween(0, 10000),
            'is_active' => $this->faker->boolean,
        ];
    }
}
