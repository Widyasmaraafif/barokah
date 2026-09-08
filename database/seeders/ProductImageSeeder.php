<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Demo product galleries: each product gets 3 real images stored on the
 * public disk under products/.
 *
 * Image sources, in priority order:
 * 1. Local gallery folders committed at storage/app/public/products/
 *    (e.g. products/hijab for the hijab category). A few images are picked
 *    from the matching folder for each product, so the catalog feels real
 *    without hitting the network. "Hijab" and "keripik" ship with files;
 *    other categories fall through to the download/placeholder paths.
 * 2. LoremFlickr keyword photo (topical: chips/snack, hijab/scarf).
 * 3. Picsum seeded photo (deterministic real photo fallback).
 * 4. Local GD placeholder (offline fallback, always works).
 *
 * Idempotent: matched by product_id + path, safe to re-run. Files are
 * only copied/downloaded when the product image is missing on the disk.
 */
class ProductImageSeeder extends Seeder
{
    private const IMAGES_PER_PRODUCT = 3;

    private const WIDTH = 800;

    private const HEIGHT = 800;

    /**
     * Map a category slug to the committed local gallery folder name.
     *
     * @return array<string, string>
     */
    protected function categoryFolders(): array
    {
        return [
            'hijab' => 'hijab',
            'kerudung' => 'hijab',
            'keripik' => 'keripik',
        ];
    }

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
            $folder = $this->categoryFolders()[$product->category?->slug ?? ''] ?? null;
            $localFiles = $folder === null
                ? []
                : $this->localGalleryFiles($folder);

            for ($index = 0; $index < self::IMAGES_PER_PRODUCT; $index++) {
                $sortOrder = $index;
                $path = "products/{$product->slug}-".($index + 1).'.jpg';

                if (! $disk->exists($path)) {
                    if ($localFiles !== []) {
                        $source = $this->pickLocalFile($localFiles, $product->id, $index);
                        $disk->put($path, $disk->get($source));
                    } else {
                        $disk->put($path, $this->fetchImage($product->slug, $keyword, $index + 1, $product->name));
                    }
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
     * List the local gallery images available for a folder. Returns full
     * relative paths (e.g. "products/hijab/xxx.webp").
     *
     * @return list<string>
     */
    protected function localGalleryFiles(string $folder): array
    {
        $files = Storage::disk('public')->files("products/{$folder}");

        return array_values(array_filter($files, fn (string $file): bool => str_ends_with(strtolower($file), '.jpg') || str_ends_with(strtolower($file), '.jpeg') || str_ends_with(strtolower($file), '.webp') || str_ends_with(strtolower($file), '.png')));
    }

    /**
     * Pick a local file for a product shot. Uses a deterministic rotation
     * (shuffle once per product via an offset) so re-seeding stays stable,
     * while still spreading different images across products.
     *
     * @param  list<string>  $files
     */
    protected function pickLocalFile(array $files, int $productId, int $index): string
    {
        $offset = ($productId + $index) % count($files);

        return $files[$offset];
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
