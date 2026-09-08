<?php

namespace Database\Factories;

use App\Enums\SellerStatus;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seller>
 */
class SellerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $storeName = fake()->unique()->company();

        return [
            'user_id' => User::factory(),
            'store_name' => $storeName,
            'slug' => str()->slug($storeName).'-'.fake()->unique()->randomNumber(5),
            'description' => fake()->sentence(),
            'profile_photo_path' => null,
            'phone' => fake()->optional()->phoneNumber(),
            'whatsapp' => fake()->optional()->phoneNumber(),
            'store_location' => fake()->optional()->address(),
            'bank_account' => fake()->optional()->sentence(6),
            'state' => 'Selangor',
            'city' => 'Shah Alam',
            'status' => SellerStatus::Active,
        ];
    }
}
