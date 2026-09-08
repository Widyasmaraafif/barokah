<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Demo product galleries: each product gets 3 real JPEG files stored
 * on the public disk under products/.
 *
 * Source priority per image:
 * 1. LoremFlickr keyword photo (topical: chips/snack, hijab/scarf).
 * 2. Picsum seeded photo (deterministic real photo fallback).
 * 3. Local GD placeholder (offline fallback, always works).
 *
 * Idempotent: matched by product_id + path, safe to re-run. Files
 * are only downloaded when missing from the disk.
 */
class ProductImageSeeder extends Seeder
{
    private const IMAGES_PER_PRODUCT = 3;

    private const WIDTH = 800;

    private const HEIGHT = 800;

    /**
     * @return array<string, string>
     */
    protected function keywords(): array
    {
        return [
            'keripik' => 'chips,snack',
            'hijab' => 'hijab,scarf',
            'kerudung' => 'hijab,veil',
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $disk = Storage::disk('public');

        foreach (Product::query()->with('category')->orderBy('id')->get() as $product) {
            $keyword = $this->keywords()[$product->category?->slug ?? ''] ?? 'product';

            for ($index = 0; $index < self::IMAGES_PER_PRODUCT; $index++) {
                $sortOrder = $index;
                $path = "products/{$product->slug}-".($index + 1).'.jpg';

                if (! $disk->exists($path)) {
                    $disk->put($path, $this->fetchImage($product->slug, $keyword, $index + 1, $product->name));
                }

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
     * Download a real photo, falling back to Picsum then GD.
     */
    protected function fetchImage(string $slug, string $keyword, int $shot, string $name): string
    {
        $lock = crc32($slug.'-'.$shot);

        foreach ($this->candidateUrls($slug, $keyword, $shot, $lock) as $url) {
            try {
                $response = Http::timeout(15)->get($url);

                if ($response->successful()) {
                    $contentType = (string) $response->header('Content-Type');

                    if (str_starts_with($contentType, 'image/') && strlen($response->body()) > 1024) {
                        return $response->body();
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $this->renderPlaceholder($name, $shot);
    }

    /**
     * @return array<int, string>
     */
    protected function candidateUrls(string $slug, string $keyword, int $shot, int $lock): array
    {
        $w = self::WIDTH;
        $h = self::HEIGHT;

        return [
            "https://loremflickr.com/{$w}/{$h}/{$keyword}?lock={$lock}",
            "https://picsum.photos/seed/{$slug}-{$shot}/{$w}/{$h}",
        ];
    }

    /**
     * Render a simple branded JPEG placeholder with GD.
     */
    protected function renderPlaceholder(string $name, int $shot): string
    {
        $width = self::WIDTH;
        $height = self::HEIGHT;

        $image = imagecreatetruecolor($width, $height);

        // Warm cream background with slight variation per shot.
        $bg = imagecolorallocate($image, 250 - ($shot * 6), 243 - ($shot * 4), 232);
        imagefill($image, 0, 0, $bg);

        // Brand band.
        $brand = imagecolorallocate($image, 14, 122, 87);
        imagefilledrectangle($image, 0, 0, $width, 150, $brand);

        $white = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 5, 30, 40, 'BAROKAH', $white);
        imagestring($image, 4, 30, 80, 'Marketplace Malaysia', $white);

        // Product name (wrapped).
        $ink = imagecolorallocate($image, 60, 50, 40);
        $words = explode(' ', $name.' #'.$shot);
        $lines = [''];
        foreach ($words as $word) {
            $last = count($lines) - 1;
            $candidate = trim($lines[$last].' '.$word);
            if (strlen($candidate) > 22) {
                $lines[] = $word;
            } else {
                $lines[$last] = $candidate;
            }
        }

        $y = (int) ($height / 2) - (count($lines) * 15);
        foreach ($lines as $line) {
            $textWidth = imagefontwidth(5) * strlen($line);
            imagestring($image, 5, (int) (($width - $textWidth) / 2), $y, $line, $ink);
            $y += 34;
        }

        // Soft frame.
        $frame = imagecolorallocate($image, 14, 122, 87);
        imagerectangle($image, 8, 8, $width - 9, $height - 9, $frame);
        imagerectangle($image, 14, 14, $width - 15, $height - 15, $frame);

        ob_start();
        imagejpeg($image, null, 82);
        $binary = (string) ob_get_clean();

        imagedestroy($image);

        return $binary;
    }
}
