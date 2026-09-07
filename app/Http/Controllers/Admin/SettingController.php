<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings UI shell with 11 category tabs (spec §17): general,
 * branding, currency, marketplace, checkout, payment, shipping,
 * localization, contact, seo, email.
 */
class SettingController extends Controller
{
    public function show(?string $group = null): Response
    {
        $groups = ['general', 'branding', 'currency', 'marketplace', 'checkout', 'payment', 'shipping', 'localization', 'contact', 'seo', 'email'];

        $active = in_array($group, $groups, true) ? $group : 'general';

        $settings = Setting::query()->where('group', $active)->orderBy('key')->get();

        return Inertia::render('Admin/Settings/Show', [
            'groups' => $groups,
            'activeGroup' => $active,
            'settings' => $settings,
        ]);
    }
}
