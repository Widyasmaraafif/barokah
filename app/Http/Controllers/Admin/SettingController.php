<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings UI shell with 11 category tabs (spec §17): general,
 * branding, currency, marketplace, checkout, payment, shipping,
 * localization, contact, seo, email.
 */
class SettingController extends Controller
{
    public function show(SettingsService $settingsService, ?string $group = null): Response
    {
        $groups = ['general', 'branding', 'currency', 'marketplace', 'checkout', 'payment', 'shipping', 'localization', 'contact', 'seo', 'email'];

        $active = in_array($group, $groups, true) ? $group : 'general';

        $settings = Setting::query()->where('group', $active)->orderBy('key')->get();
        $existingKeys = $settings->pluck('key')->all();
        $settings = $settings->concat(collect($settingsService->definitionsFor($active))
            ->reject(fn (array $definition, string $key): bool => in_array($key, $existingKeys, true))
            ->map(fn (array $definition, string $key): Setting => new Setting([
                'key' => $key,
                'value' => $definition['value'] ?? null,
                'type' => $definition['type'] ?? 'string',
                'group' => $active,
                'is_public' => $definition['is_public'] ?? false,
            ]))
            ->values());

        $settings->each(function (Setting $setting) use ($settingsService): void {
            $value = $settingsService->get($setting->key, $setting->value);

            if (in_array($setting->key, ['branding.logo_url', 'branding.favicon_url', 'payment.qr_code_url'], true)
                && is_string($value)
                && $value !== '') {
                $value = str_starts_with($value, 'http')
                    ? $value
                    : Storage::disk('public')->url($value);
            }

            $setting->setAttribute('value', $value);
        });

        return Inertia::render('Admin/Settings/Show', [
            'groups' => $groups,
            'activeGroup' => $active,
            'settings' => $settings,
        ]);
    }
}
