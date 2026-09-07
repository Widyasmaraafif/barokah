<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productName = fake()->words(3, true);
        $price = fake()->randomFloat(2, 5, 200);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'seller_id' => Seller::factory(),
            'product_name_snapshot' => $productName,
            'product_slug_snapshot' => Str::slug($productName).'-'.fake()->unique()->randomNumber(5),
            'price_snapshot' => $price,
            'quantity' => $quantity,
            'subtotal' => $price * $quantity,
        ];
    }
}
