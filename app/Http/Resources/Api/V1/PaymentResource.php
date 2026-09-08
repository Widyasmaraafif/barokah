<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PayNet payment status (spec §11.5/§15).
 *
 * Provider payloads and secrets are never serialized; the frontend only
 * needs the status plus the redirect/QR placeholder from the intent.
 *
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = is_array($this->payload) ? $this->payload : [];

        return [
            'id' => $this->id,
            'order_number' => $this->whenLoaded('order', fn () => $this->order->order_number, $request->route('orderNumber')),
            'payment_gateway' => $this->payment_gateway,
            'payment_method' => $this->payment_method instanceof \BackedEnum ? $this->payment_method->value : $this->payment_method,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'redirect_url' => $payload['redirect_url'] ?? $this->redirectUrl($payload),
            'qr_payload' => $payload['qr_payload'] ?? null,
            'transaction_id' => $this->transaction_id,
            'paid_at' => $this->paid_at,
            'failed_at' => $this->failed_at,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function redirectUrl(array $payload): ?string
    {
        $method = $this->payment_method instanceof \BackedEnum ? $this->payment_method->value : (string) $this->payment_method;
        $ref = $this->transaction_id ?? ($payload['provider_ref'] ?? null);

        if ($method !== 'fpx' || ! is_string($ref) || $ref === '') {
            return null;
        }

        $base = rtrim((string) config('paynet.base_url', ''), '/');

        if ($base === '') {
            $base = 'TBC_PAYNET_API_BASE';
        }

        $orderNumber = $this->relationLoaded('order') ? $this->order->order_number : null;

        return $base.'/pay?ref='.$ref.($orderNumber ? '&order='.$orderNumber : '');
    }
}
