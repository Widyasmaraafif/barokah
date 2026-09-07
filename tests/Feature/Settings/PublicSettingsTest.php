<?php

use App\Models\Setting;
use App\Services\SettingsService;

test('public settings endpoint returns only public settings', function () {
    Setting::create([
        'key' => 'branding.site_name',
        'value' => 'Barokah Test',
        'type' => 'string',
        'group' => 'branding',
        'is_public' => true,
    ]);
    Setting::create([
        'key' => 'payment.secret_key',
        'value' => 'super-secret',
        'type' => 'string',
        'group' => 'payment',
        'is_public' => false,
    ]);

    $response = $this->getJson('/api/v1/settings/public');

    $response->assertOk();

    $data = $response->json();

    // Note: keys are flat dotted strings, so assert via direct array access
    // (assertJsonPath would interpret dots as nested paths).
    expect($data['branding.site_name'] ?? null)->toBe('Barokah Test');
    expect($data)->not->toHaveKey('payment.secret_key');
});

test('database settings take precedence over config defaults', function () {
    $service = app(SettingsService::class);

    expect($service->get('currency.symbol'))->toBe(config('marketplace.currency.symbol'));

    Setting::create([
        'key' => 'currency.symbol',
        'value' => 'MYR',
        'type' => 'string',
        'group' => 'currency',
        'is_public' => true,
    ]);
    $service->forget();

    expect($service->get('currency.symbol'))->toBe('MYR');

    expect($this->getJson('/api/v1/settings/public')->json()['currency.symbol'] ?? null)->toBe('MYR');
});

test('updating a setting invalidates cached public settings', function () {
    $service = app(SettingsService::class);

    $service->allPublic();
    $service->set('currency.symbol', 'MYR');

    expect($service->allPublic()['currency.symbol'])->toBe('MYR');

    expect($this->getJson('/api/v1/settings/public')->json()['currency.symbol'] ?? null)->toBe('MYR');
});
