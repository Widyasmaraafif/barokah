<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 500);
        $shippingFee = fake()->randomFloat(2, 0, 20);

        return [
            'order_number' => 'BRK-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'customer_name' => fake()->name(),
            'customer_address' => fake()->streetAddress(),
            'customer_state' => fake()->state(),
            'customer_post_code' => fake()->postcode(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_email' => fake()->safeEmail(),
            'currency_code' => 'MYR',
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'total' => $subtotal + $shippingFee,
            'status' => OrderStatus::PendingPayment,
            'shipping_method' => 'fixed',
            'shipping_provider' => null,
            'notes' => null,
            'expired_at' => null,
        ];
    }
}
