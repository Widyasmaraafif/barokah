<?php

use App\Services\CurrencyFormatter;
use App\Services\SettingsService;

test('formats amounts with the configured currency symbol', function () {
    $formatter = app(CurrencyFormatter::class);

    expect($formatter->format(1234.5))->toBe(config('marketplace.currency.symbol').' 1,234.50');
});

test('reflects updated currency symbol from settings', function () {
    app(SettingsService::class)->set('currency.symbol', 'MYR');

    expect(app(CurrencyFormatter::class)->format(1234.5))->toBe('MYR 1,234.50');
    expect(app(CurrencyFormatter::class)->format(99))->toBe('MYR 99.00');
});

test('honours configured decimals', function () {
    app(SettingsService::class)->set('currency.decimals', 0);

    expect(app(CurrencyFormatter::class)->format(1234.5))->toBe(
        config('marketplace.currency.symbol').' 1,235'
    );
});
