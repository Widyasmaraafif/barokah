<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
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
            'order_id' => Order::factory(),
            'payment_gateway' => 'paynet',
            'payment_method' => PaymentMethod::Fpx,
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency' => 'MYR',
            'status' => PaymentStatus::Pending,
            'transaction_id' => null,
            'payload' => null,
            'callback_payload' => null,
            'paid_at' => null,
            'failed_at' => null,
        ];
    }
}
