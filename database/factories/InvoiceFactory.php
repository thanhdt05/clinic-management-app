<?php

namespace Database\Factories;

use App\Models\Examination;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
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

        $subtotal = $this->faker->randomElement([100000, 150000, 200000, 350000, 500000]);
        $discount = $this->faker->randomElement([0, 10000, 20000, 50000]);
        $total = max(0, $subtotal - $discount);

        return [
            'examination_id' => Examination::factory(),
            'invoice_code' => sprintf('2026-%05d', $sequence),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'status' => 'unpaid',
            'issued_at' => now(),
        ];
    }
}
