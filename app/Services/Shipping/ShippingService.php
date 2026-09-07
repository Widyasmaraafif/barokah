<?php

namespace App\Services\Shipping;

use App\Services\CurrencyFormatter;
use App\Services\SettingsService;

/**
 * ShippingService strategy abstraction (spec §16.2).
 *
 * Selects the provider per settings shipping.method and returns a quote
 * snapshot stored on the order. Fixed Rate stays the default so existing
 * checkout behavior is preserved (spec §16.1).
 */
class ShippingService
{
    public function __construct(
        protected SettingsService $settings,
        protected CurrencyFormatter $currency,
        protected FixedRateShippingProvider $fixed,
        protected ExternalShippingProvider $external,
    ) {}

    /**
     * Quote a shipping fee for checkout or the standalone quote endpoint.
     *
     * @param  array{address: string, state: string, post_code: string}  $address
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     * @return array{method: string, provider: ?string, fee: float, formatted: string, currency_code: string, meta: array<string, mixed>}
     */
    public function quote(array $address, float $subtotal = 0.0, array $lines = [], ?string $method = null): array
    {
        $method ??= (string) $this->settings->get('shipping.method', config('shipping.method', 'fixed'));

        $result = $method === 'external'
            ? $this->external->quote($address, $subtotal, $lines)
            : $this->fixed->quote($address, $subtotal, $lines);

        $fee = round((float) $result['fee'], 2);

        return [
            'method' => $result['method'],
            'provider' => $result['provider'],
            'fee' => $fee,
            'formatted' => $this->currency->format($fee),
            'currency_code' => $this->currency->code(),
            'meta' => $result['meta'] ?? [],
        ];
    }
}
