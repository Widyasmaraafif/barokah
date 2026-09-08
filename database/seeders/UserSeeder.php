<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Fixed demo accounts (admin, sellers, customer).
 *
 * Idempotent: matched by email, safe to re-run. All demo passwords are
 * `password`. Role flags are applied via forceFill because they are not
 * mass-assignable on the User model.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->where('email', 'test@example.com')->delete();

        foreach ($this->demoUsers() as $data) {
            $flags = [
                'is_admin' => $data['is_admin'] ?? false,
                'is_active_as_seller' => $data['is_active_as_seller'] ?? false,
            ];
            unset($data['is_admin'], $data['is_active_as_seller']);

            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ])
            );

            $user->forceFill($flags)->save();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function demoUsers(): array
    {
        return [
            [
                'name' => 'Admin Barokah',
                'email' => 'admin@barokah.local',
                'phone' => '03-1234567',
                'address' => 'Level 1, Jalan Tuanku Abdul Rahman',
                'state' => 'Federal Territory of Kuala Lumpur',
                'post_code' => '50000',
                'is_admin' => true,
            ],
            [
                'name' => 'Ahmad Kerepek',
                'email' => 'seller.keripik@barokah.local',
                'phone' => '012-3456789',
                'address' => 'No. 12, Jalan Nangka, Shah Alam',
                'state' => 'Selangor',
                'post_code' => '40000',
                'is_active_as_seller' => true,
            ],
            [
                'name' => 'Siti Modest',
                'email' => 'seller.hijab@barokah.local',
                'phone' => '013-2345678',
                'address' => 'No. 5, Jalan Sutera, Johor Bahru',
                'state' => 'Johor',
                'post_code' => '80000',
                'is_active_as_seller' => true,
            ],
            [
                'name' => 'Aina Collection',
                'email' => 'seller.kerudung@barokah.local',
                'phone' => '014-3456789',
                'address' => 'No. 9, Lebuh Pantai, George Town',
                'state' => 'Pulau Pinang',
                'post_code' => '10000',
                'is_active_as_seller' => true,
            ],
            [
                'name' => 'Nurul Buyer',
                'email' => 'customer@barokah.local',
                'phone' => '017-1234567',
                'address' => 'No. 8, Jalan SS2, Petaling Jaya',
                'state' => 'Selangor',
                'post_code' => '47300',
            ],
        ];
    }
}
