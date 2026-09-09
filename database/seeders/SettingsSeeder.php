<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

/**
 * Seed default marketplace settings (Task 10.4, spec §17/§19).
 *
 * Defaults come from `config/marketplace.php` (`settings_defaults`) with
 * Barokah branding, MYR/RM currency, and English locale. Priority is
 * DB > env > default because env-backed values are baked into the config
 * defaults while database rows always win in SettingsService::all().
 *
 * Existing rows are never overwritten so re-running the seeder is safe.
 * Keys whose business behavior is undefined stay TBC (spec §24) and are
 * stored as toggles/defaults only.
 */
class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = app(SettingsService::class);
        $defaults = config('marketplace.settings_defaults', []);

        foreach ($defaults as $key => $definition) {
            $setting = Setting::query()->where('key', $key)->first();

            if ($setting !== null) {
                $setting->update([
                    'type' => $definition['type'] ?? $setting->type,
                    'group' => $definition['group'] ?? $setting->group,
                    'is_public' => $definition['is_public'] ?? $setting->is_public,
                ]);

                continue;
            }

            $settings->set($key, $definition['value'] ?? null);
        }

        $settings->forget();
    }
}
