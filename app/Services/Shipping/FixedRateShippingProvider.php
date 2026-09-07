<?php

namespace App\Services\Shipping;

use App\Services\SettingsService;

/**
 * Fixed Rate shipping provider (spec §16.1).
 *
 * Returns the admin-configured fixed fee, with an optional free-shipping
 * threshold (TBC default threshold until confirmed, spec §24 item 17).
 */
class FixedRateShippingProvider implements ShippingProviderContract
{
    public function __construct(protected SettingsService $settings) {}

    public function method(): string
    {
        return 'fixed';
    }

    /**
     * @param  array{address: string, state: string, post_code: string}  $address
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     * @return array{method: string, provider: ?string, fee: float, meta: array<string, mixed>}
     */
    public function quote(array $address, float $subtotal, array $lines = []): array
    {
        $rate = (float) $this->settings->get('shipping.fixed_rate', config('shipping.fixed_rate', '5.00'));
        $freeEnabled = filter_var(
            $this->settings->get('shipping.free_shipping_enabled', config('shipping.free_shipping_enabled', false)),
            FILTER_VALIDATE_BOOLEAN
        );
        $threshold = (float) $this->settings->get(
            'shipping.free_shipping_threshold',
            config('shipping.free_shipping_threshold', '100.00')
        );

        $freeApplied = $freeEnabled && $subtotal >= $threshold;
        $fee = $freeApplied ? 0.0 : $rate;

        return [
            'method' => 'fixed',
            'provider' => null,
            'fee' => round($fee, 2),
            'meta' => [
                'free_shipping_applied' => $freeApplied,
                'free_shipping_threshold' => number_format($threshold, 2, '.', ''),
            ],
        ];
    }
}
