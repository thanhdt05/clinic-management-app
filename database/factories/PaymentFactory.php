<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'amount' => fake()->randomFloat(2, 50000, 200000),
            'method' => 'paypal',
            'status' => 'pending',
            'provider' => 'paypal',
            'provider_order_id' => 'PAYPAL-ORD-'.fake()->unique()->numerify('#####'),
            'provider_capture_id' => null,
            'paid_at' => null,
            'note' => fake()->sentence(),
        ];
    }
}
