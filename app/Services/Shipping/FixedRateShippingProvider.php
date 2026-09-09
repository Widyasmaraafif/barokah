<?php

namespace App\Services\Shipping;

use App\Models\Product;
use App\Models\ShippingRate;
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
     * @param  array{address: string, state: string, city?: string|null, post_code: string}  $address
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
        $sellerLocations = Product::query()
            ->whereIn('id', collect($lines)->pluck('product_id'))
            ->with('seller:id,state,city')
            ->get()
            ->map(fn (Product $product): array => [
                'state' => $product->seller?->state,
                'city' => $product->seller?->city,
            ])
            ->unique(fn (array $location): string => ($location['state'] ?? '').'|'.($location['city'] ?? ''));

        $configuredRate = ShippingRate::query()
            ->where('to_state', $address['state'])
            ->where('is_active', true)
            ->where(function ($query) use ($address): void {
                $query->where('to_city', $address['city'] ?? null)
                    ->orWhereNull('to_city');
            })
            ->where(function ($query) use ($sellerLocations): void {
                $query->whereNull('from_state');

                foreach ($sellerLocations as $location) {
                    if ($location['state'] === null) {
                        continue;
                    }

                    $query->orWhere(function ($origin) use ($location): void {
                        $origin->where('from_state', $location['state'])
                            ->where(function ($city) use ($location): void {
                                $city->where('from_city', $location['city'])
                                    ->orWhereNull('from_city');
                            });
                    });
                }
            })
            ->orderByRaw('to_city is null')
            ->orderByRaw('from_city is null')
            ->value('rate');
        $rate = $configuredRate !== null ? (float) $configuredRate : $rate;
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
