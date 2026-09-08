<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Fixed catalog categories (Keripik, Hijab, Kerudung).
 *
 * Idempotent: matched by slug, safe to re-run.
 */
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->demoCategories() as $index => $data) {
            Category::query()->updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'is_active' => true,
                    'sort_order' => $index,
                ])
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function demoCategories(): array
    {
        return [
            [
                'name' => 'Keripik',
                'slug' => 'keripik',
                'description' => 'Keripik pisang, singkong, dan camilan khas Malaysia.',
            ],
            [
                'name' => 'Hijab',
                'slug' => 'hijab',
                'description' => 'Hijab paris, pashmina, dan hijab premium.',
            ],
            [
                'name' => 'Kerudung',
                'slug' => 'kerudung',
                'description' => 'Kerudung bergo, segi empat, dan kerudung harian.',
            ],
        ];
    }
}
