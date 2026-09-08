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
                    'phone' => $data['phone'],
                    'whatsapp' => $data['whatsapp'],
                    'store_location' => $data['store_location'],
                    'bank_account' => $data['bank_account'],
                    'state' => $data['state'],
                    'city' => $data['city'],
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
                'phone' => '03-55123456',
                'whatsapp' => '60123456789',
                'store_location' => 'No. 12, Jalan Meru, Klang, Selangor',
                'bank_account' => 'Maybank a.n. Ahmad Bin Yusof 1234567890',
                'state' => 'Selangor',
                'city' => 'Klang',
            ],
            [
                'email' => 'seller.hijab@barokah.local',
                'store_name' => 'Siti Modest',
                'slug' => 'siti-modest',
                'description' => 'Hijab paris dan pashmina premium dari Johor.',
                'phone' => '07-3312345',
                'whatsapp' => '60198765432',
                'store_location' => 'No. 8, Jalan Tebrau, Johor Bahru, Johor',
                'bank_account' => 'Maybank a.n. Siti Aminah Binti Ali 0987654321',
                'state' => 'Johor',
                'city' => 'Johor Bahru',
            ],
            [
                'email' => 'seller.kerudung@barokah.local',
                'store_name' => 'Aina Collection',
                'slug' => 'aina-collection',
                'description' => 'Kerudung bergo dan segi empat dari Pulau Pinang.',
                'phone' => '04-2612345',
                'whatsapp' => '60135557777',
                'store_location' => 'No. 25, Lebuh Campbell, George Town, Pulau Pinang',
                'bank_account' => 'Maybank a.n. Aina Binti Rahim 1122334455',
                'state' => 'Pulau Pinang',
                'city' => 'George Town',
            ],
        ];
    }
}
