<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

/**
 * Demo product images (path-only rows, no binary files).
 *
 * Each demo product gets one primary image. Idempotent: matched by
 * product_id + path, safe to re-run.
 */
class ProductImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::query()->whereIn('slug', array_keys($this->demoImages()))->get();

        foreach ($products as $product) {
            $paths = $this->demoImages()[$product->slug];

            foreach ($paths as $sortOrder => $path) {
                ProductImage::query()->updateOrCreate(
                    ['product_id' => $product->id, 'path' => $path],
                    [
                        'sort_order' => $sortOrder,
                        'is_primary' => $sortOrder === 0,
                    ]
                );
            }
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function demoImages(): array
    {
        return [
            'keripik-pisang-original' => ['products/keripik-pisang-original-1.jpg'],
            'keripik-singkong-balado' => ['products/keripik-singkong-balado-1.jpg'],
            'hijab-paris-premium' => ['products/hijab-paris-premium-1.jpg'],
            'pashmina-kaos-basic' => ['products/pashmina-kaos-basic-1.jpg'],
            'kerudung-bergo-maryam' => ['products/kerudung-bergo-maryam-1.jpg'],
            'kerudung-segi-empat-voal' => ['products/kerudung-segi-empat-voal-1.jpg'],
        ];
    }
}
