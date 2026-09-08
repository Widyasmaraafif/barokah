<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Database\Seeder;

/**
 * Fixed demo catalog (Keripik, Hijab, Kerudung).
 *
 * Idempotent: matched by slug, safe to re-run.
 */
class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->demoProducts() as $data) {
            $category = Category::query()->where('slug', $data['category_slug'])->first();
            $seller = Seller::query()->where('slug', $data['seller_slug'])->first();

            if ($category === null || $seller === null) {
                continue;
            }

            Product::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'seller_id' => $seller->id,
                    'category_id' => $category->id,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'stock' => $data['stock'],
                    'status' => ProductStatus::Active,
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function demoProducts(): array
    {
        return [
            [
                'name' => 'Keripik Pisang Original',
                'slug' => 'keripik-pisang-original',
                'description' => 'Keripik pisang renyah original, 250g.',
                'price' => '25.50',
                'stock' => 100,
                'category_slug' => 'keripik',
                'seller_slug' => 'ahmad-kerepek',
            ],
            [
                'name' => 'Keripik Singkong Balado',
                'slug' => 'keripik-singkong-balado',
                'description' => 'Keripik singkong balado pedas manis, 250g.',
                'price' => '22.00',
                'stock' => 80,
                'category_slug' => 'keripik',
                'seller_slug' => 'ahmad-kerepek',
            ],
            [
                'name' => 'Hijab Paris Premium',
                'slug' => 'hijab-paris-premium',
                'description' => 'Hijab paris premium bahan adem, ukuran 110x110.',
                'price' => '45.00',
                'stock' => 50,
                'category_slug' => 'hijab',
                'seller_slug' => 'siti-modest',
            ],
            [
                'name' => 'Pashmina Kaos Basic',
                'slug' => 'pashmina-kaos-basic',
                'description' => 'Pashmina kaos basic, 180x70 cm.',
                'price' => '35.00',
                'stock' => 60,
                'category_slug' => 'hijab',
                'seller_slug' => 'siti-modest',
            ],
            [
                'name' => 'Kerudung Bergo Maryam',
                'slug' => 'kerudung-bergo-maryam',
                'description' => 'Kerudung bergo tali, bahan jersey premium.',
                'price' => '28.00',
                'stock' => 70,
                'category_slug' => 'kerudung',
                'seller_slug' => 'aina-collection',
            ],
            [
                'name' => 'Kerudung Segi Empat Voal',
                'slug' => 'kerudung-segi-empat-voal',
                'description' => 'Kerudung segi empat voal ultrafine, 110x110.',
                'price' => '32.50',
                'stock' => 65,
                'category_slug' => 'kerudung',
                'seller_slug' => 'aina-collection',
            ],
        ];
    }
}
