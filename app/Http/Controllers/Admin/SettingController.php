<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingsService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings UI shell with 11 category tabs (spec §17): general,
 * branding, currency, marketplace, checkout, payment, shipping,
 * localization, contact, seo, email.
 */
class SettingController extends Controller
{
    public function show(?string $group, SettingsService $settingsService): Response
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

        return Inertia::render('Admin/Settings/Show', [
            'groups' => $groups,
            'activeGroup' => $active,
            'settings' => $settings,
        ]);
    }
}
