<?php

namespace Database\Seeders;

use App\Enums\SellerStatus;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo stores linked to the fixed seller accounts from UserSeeder.
 *
 * Idempotent: matched by user_id, safe to re-run.
 */
class SellerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->demoSellers() as $data) {
            $user = User::query()->where('email', $data['email'])->first();

            if ($user === null) {
                continue;
            }

            Seller::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'store_name' => $data['store_name'],
                    'slug' => $data['slug'],
                    'description' => $data['description'],
                    'status' => SellerStatus::Active,
                ]
            );

            $user->forceFill(['is_active_as_seller' => true])->save();
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function demoSellers(): array
    {
        return [
            [
                'email' => 'seller.keripik@barokah.local',
                'store_name' => 'Ahmad Kerepek',
                'slug' => 'ahmad-kerepek',
                'description' => 'Keripik pisang dan singkong homemade dari Selangor.',
            ],
            [
                'email' => 'seller.hijab@barokah.local',
                'store_name' => 'Siti Modest',
                'slug' => 'siti-modest',
                'description' => 'Hijab paris dan pashmina premium dari Johor.',
            ],
            [
                'email' => 'seller.kerudung@barokah.local',
                'store_name' => 'Aina Collection',
                'slug' => 'aina-collection',
                'description' => 'Kerudung bergo dan segi empat dari Pulau Pinang.',
            ],
        ];
    }
}
