<?php

namespace App\Services;

class CurrencyFormatter
{
    public function __construct(public SettingsService $settings) {}

    public function format(int|float $amount, ?string $symbol = null, ?int $decimals = null): string
    {
        $symbol ??= (string) $this->settings->get('currency.symbol', config('marketplace.currency.symbol', 'RM'));
        $decimals ??= (int) $this->settings->get('currency.decimals', config('marketplace.currency.decimals', 2));

        return $symbol.' '.number_format((float) $amount, $decimals, '.', ',');
    }

    public function code(): string
    {
        return (string) $this->settings->get('currency.code', config('marketplace.currency.code', 'MYR'));
    }
}
