<?php

namespace App\Services\Shipping;

use App\Exceptions\ShippingQuoteException;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Http;

/**
 * External Shipping API provider skeleton (spec §16.1/§16.2).
 *
 * TBC (spec §24 item 2): provider name, base URL, auth method, request
 * (origin/postcode/weight) and response shapes, and Malaysia coverage are
 * all unresolved. Every integration detail below is a TBC_SHIPPING_*
 * placeholder and must not be treated as a real provider, endpoint, or
 * credential. No provider has been invented here.
 *
 * All credentials stay server-side via config/shipping.php env vars
 * (spec §20) and are never returned to the frontend.
 */
class ExternalShippingProvider implements ShippingProviderContract
{
    public function __construct(protected SettingsService $settings) {}

    public function method(): string
    {
        return 'external';
    }

    /**
     * @param  array{address: string, state: string, post_code: string}  $address
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     * @return array{method: string, provider: ?string, fee: float, meta: array<string, mixed>}
     */
    public function quote(array $address, float $subtotal, array $lines = []): array
    {
        $enabled = filter_var(
            $this->settings->get('shipping.api_enabled', config('shipping.api_enabled', false)),
            FILTER_VALIDATE_BOOLEAN
        );
        $baseUrl = (string) config('shipping.external.base_url', '');
        $apiKey = (string) config('shipping.external.api_key', '');

        if (! $enabled || $baseUrl === '') {
            throw new ShippingQuoteException(
                'External shipping provider is not configured (TBC_SHIPPING_* pending, spec §24 item 2).'
            );
        }

        // TBC (spec §24 item 2): quote path, payload, and response shapes
        // pending provider docs. Generic fee/rate/price mapping only.
        try {
            $response = Http::timeout((int) config('shipping.external.timeout_seconds', 10))
                ->withHeaders($apiKey !== '' ? ['Authorization' => 'Bearer '.$apiKey] : [])
                ->post(rtrim($baseUrl, '/').'/TBC_SHIPPING_QUOTE', [
                    'address' => $address,
                    'subtotal' => $subtotal,
                    'items' => $lines,
                ]);
        } catch (\Throwable $exception) {
            throw new ShippingQuoteException('External shipping quote failed.', previous: $exception);
        }

        if ($response->failed()) {
            throw new ShippingQuoteException('External shipping quote failed.');
        }

        $fee = $response->json('fee', $response->json('rate', $response->json('price')));

        if (! is_numeric($fee)) {
            throw new ShippingQuoteException(
                'External shipping response missing fee (TBC_SHIPPING_* response shape pending).'
            );
        }

        $providerName = (string) $this->settings->get(
            'shipping.provider_name',
            config('shipping.provider_name', '')
        );

        return [
            'method' => 'external',
            'provider' => $providerName !== '' ? $providerName : null,
            'fee' => round((float) $fee, 2),
            'meta' => ['tbc_shipping' => true],
        ];
    }
}
