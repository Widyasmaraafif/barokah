<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Str;

/**
 * PayNet gateway placeholder (spec §15.1/§15.6).
 *
 * TBC (spec §24 item 1): exact PayNet API base URL, auth method, FPX vs
 * DuitNow request/response shapes, signature algorithm, and redirect vs QR
 * flows are all unresolved. This gateway never performs a real HTTP call:
 * it builds a deterministic intent payload marked TBC_PAYNET_* and verifies
 * callbacks with HMAC-SHA256 over the canonical payload using the shared
 * secret until the real algorithm is confirmed.
 */
class PayNetGateway
{
    /**
     * Build a payment intent without calling any external endpoint.
     *
     * @return array{provider_ref: string, redirect_url: string|null, qr_payload: string|null, payload: array<string, mixed>}
     */
    public function createIntent(Order $order, PaymentMethod $method): array
    {
        $providerRef = 'TBC_PAYNET_'.Str::upper(Str::random(12));

        $payload = [
            // TBC_PAYNET_API_BASE: real PayNet endpoint unknown.
            'gateway' => 'paynet',
            'method' => $method->value,
            'order_number' => $order->order_number,
            'amount' => (string) $order->total,
            'currency' => $order->currency_code,
            'provider_ref' => $providerRef,
            'sandbox' => config('paynet.sandbox', true),
        ];

        // TBC: redirect vs QR per method unconfirmed; FPX exposes a redirect
        // placeholder, DuitNow a QR payload placeholder.
        return [
            'provider_ref' => $providerRef,
            'redirect_url' => $method === PaymentMethod::Fpx ? $this->redirectPlaceholder($order, $providerRef) : null,
            'qr_payload' => $method === PaymentMethod::DuitNow ? $providerRef : null,
            'payload' => $payload,
        ];
    }

    /**
     * Verify a callback/webhook signature.
     *
     * TBC: real PayNet signature algorithm unconfirmed; HMAC-SHA256 over the
     * canonical JSON body (excluding the signature field) is the interim
     * contract for both our tests and any sandbox stub.
     */
    public function verifyCallback(array $payload, string $signature): bool
    {
        $secret = (string) config('paynet.secret', '');

        if ($secret === '' || $signature === '') {
            return false;
        }

        $canonical = $this->canonicalPayload($payload);

        $expected = hash_hmac('sha256', $canonical, $secret);

        return hash_equals($expected, strtolower($signature));
    }

    /**
     * Sign a payload with the interim HMAC contract (tests/stubs only).
     *
     * @param  array<string, mixed>  $payload
     */
    public function signPayload(array $payload): string
    {
        $secret = (string) config('paynet.secret', '');

        return hash_hmac('sha256', $this->canonicalPayload($payload), $secret);
    }

    /**
     * Look up a provider intent status (spec §15.1).
     *
     * TBC (spec §24 item 1): no PayNet status endpoint is known, so this
     * returns pending until real credentials/docs arrive. Never called
     * over HTTP today; kept so callers depend on the gateway contract.
     */
    public function getStatus(string $providerRef): PaymentStatus
    {
        return PaymentStatus::Pending;
    }

    /**
     * Map a provider status string to the domain enum.
     */
    public function mapStatus(?string $providerStatus): PaymentStatus
    {
        return match (strtolower((string) $providerStatus)) {
            'success', 'paid', 'completed', 'approved' => PaymentStatus::Paid,
            'failed', 'failure', 'declined', 'rejected' => PaymentStatus::Failed,
            'expired', 'timeout' => PaymentStatus::Expired,
            'cancelled', 'canceled', 'voided' => PaymentStatus::Cancelled,
            default => PaymentStatus::Pending,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function canonicalPayload(array $payload): string
    {
        $body = $payload;
        unset($body['signature']);

        ksort($body);

        return (string) json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function redirectPlaceholder(Order $order, string $providerRef): string
    {
        $base = rtrim((string) config('paynet.base_url', ''), '/');

        if ($base === '') {
            $base = 'TBC_PAYNET_API_BASE';
        }

        return $base.'/pay?ref='.$providerRef.'&order='.$order->order_number;
    }
}
