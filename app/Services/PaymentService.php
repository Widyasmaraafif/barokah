<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Payment orchestration over the PayNet abstraction (spec §15.2/§15.4).
 *
 * Owns payment creation, status mapping, retry, and idempotent callback
 * reconciliation. Credentials stay server-side; the frontend only receives
 * the redirect_url/QR placeholder plus the pending status.
 */
class PaymentService
{
    public function __construct(
        protected PayNetGateway $gateway,
        protected SettingsService $settings,
    ) {}

    /**
     * Methods enabled via settings toggles (spec §11.5: fpx/duitnow).
     *
     * @return array<int, string>
     */
    public function enabledMethods(): array
    {
        $methods = [];

        if ($this->isMethodEnabled('fpx')) {
            $methods[] = PaymentMethod::Fpx->value;
        }

        if ($this->isMethodEnabled('duitnow')) {
            $methods[] = PaymentMethod::DuitNow->value;
        }

        return $methods;
    }

    /**
     * Create (or reuse) a pending payment intent for a payable order.
     */
    public function initiate(Order $order, PaymentMethod $method): Payment
    {
        if (! $order->isPayable()) {
            throw new InvalidArgumentException('Order is not payable.');
        }

        if (! in_array($method->value, $this->enabledMethods(), true)) {
            throw new InvalidArgumentException('Payment method '.$method->value.' is not enabled.');
        }

        return DB::transaction(function () use ($order, $method) {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPayable()) {
                throw new InvalidArgumentException('Order is not payable.');
            }

            /** @var Payment|null $existing */
            $existing = Payment::query()->where('order_id', $locked->id)->lockForUpdate()->first();

            if ($existing !== null && $existing->status === PaymentStatus::Pending) {
                return $existing;
            }

            if ($existing !== null) {
                $existing->update([
                    'payment_method' => $method,
                    'amount' => $locked->total,
                    'currency' => $locked->currency_code,
                    'status' => PaymentStatus::Pending,
                    'paid_at' => null,
                    'failed_at' => null,
                ]);

                $intent = $this->gateway->createIntent($locked, $method);

                $existing->update([
                    'transaction_id' => $intent['provider_ref'],
                    'payload' => $this->intentPayload($intent),
                ]);

                return $existing->refresh();
            }

            $intent = $this->gateway->createIntent($locked, $method);

            return Payment::query()->create([
                'order_id' => $locked->id,
                'payment_gateway' => 'paynet',
                'payment_method' => $method,
                'amount' => $locked->total,
                'currency' => $locked->currency_code,
                'status' => PaymentStatus::Pending,
                'transaction_id' => $intent['provider_ref'],
                'payload' => $this->intentPayload($intent),
            ]);
        });
    }

    /**
     * Reconcile a verified provider callback idempotently.
     *
     * Duplicate callbacks for the same transaction_id are ignored once the
     * payment reaches a terminal state. Returns the payment (or null when
     * the order reference is unknown).
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(array $payload): ?Payment
    {
        $orderNumber = $payload['order_number'] ?? $payload['order_no'] ?? null;
        $providerRef = $payload['transaction_id'] ?? $payload['provider_ref'] ?? null;

        if (! is_string($orderNumber) || $orderNumber === '') {
            return null;
        }

        return DB::transaction(function () use ($orderNumber, $providerRef, $payload) {
            /** @var Order|null $order */
            $order = Order::query()->where('order_number', $orderNumber)->lockForUpdate()->first();

            if ($order === null) {
                return null;
            }

            /** @var Payment|null $payment */
            $payment = Payment::query()->where('order_id', $order->id)->lockForUpdate()->first();

            if ($payment === null) {
                return null;
            }

            // Idempotency: same provider ref on a terminal payment is a replay.
            if ($payment->isTerminal() && $providerRef !== null && $payment->transaction_id === $providerRef) {
                return $payment;
            }

            // A terminal payment never regresses to pending on retry noise.
            $status = $this->gateway->mapStatus($payload['status'] ?? null);

            if ($payment->isTerminal() && $status === PaymentStatus::Pending) {
                $payment->update(['callback_payload' => $payload]);

                return $payment->refresh();
            }

            $updates = ['callback_payload' => $payload];

            if (is_string($providerRef) && $providerRef !== '' && $payment->transaction_id === null) {
                $updates['transaction_id'] = $providerRef;
            } elseif (is_string($providerRef) && $providerRef !== '' && $payment->transaction_id !== $providerRef && $status !== PaymentStatus::Pending) {
                $updates['transaction_id'] = $providerRef;
            }

            match ($status) {
                PaymentStatus::Paid => $updates += [
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                    'failed_at' => null,
                ],
                PaymentStatus::Failed => $updates += [
                    'status' => PaymentStatus::Failed,
                    'failed_at' => now(),
                ],
                PaymentStatus::Expired => $updates += ['status' => PaymentStatus::Expired],
                PaymentStatus::Cancelled => $updates += ['status' => PaymentStatus::Cancelled],
                default => $updates += ['status' => PaymentStatus::Pending],
            };

            $payment->update($updates);

            // Payment paid triggers order paid (spec §14.2/§14.4); failure
            // keeps the order pending_payment for retry or expiry.
            if ($status === PaymentStatus::Paid && $order->status === OrderStatus::PendingPayment) {
                $order->update(['status' => OrderStatus::Paid]);
            }

            if (in_array($status, [PaymentStatus::Expired, PaymentStatus::Cancelled], true)
                && $order->status === OrderStatus::PendingPayment
                && $status === PaymentStatus::Expired) {
                $order->update(['status' => OrderStatus::Expired]);
            }

            return $payment->refresh();
        });
    }

    /**
     * Reconcile a verified provider webhook idempotently.
     *
     * Webhook and callback share reconciliation (spec §15.2/§15.4).
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): ?Payment
    {
        return $this->handleCallback($payload);
    }

    /**
     * Mark a payment failed without changing the order status.
     *
     * The order stays pending_payment for retry or expiry (spec §15.5).
     *
     * @param  array<string, mixed>  $payload
     */
    public function markFailed(Payment $payment, array $payload = []): Payment
    {
        $updates = [
            'status' => PaymentStatus::Failed,
            'failed_at' => now(),
        ];

        if ($payload !== []) {
            $updates['callback_payload'] = $payload;
        }

        $payment->update($updates);

        return $payment->refresh();
    }

    /**
     * Mark a payment expired; expire its pending_payment order too.
     */
    public function markExpired(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $locked->update(['status' => PaymentStatus::Expired]);

            /** @var Order $order */
            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->firstOrFail();

            if ($order->status === OrderStatus::PendingPayment) {
                $order->update(['status' => OrderStatus::Expired]);
            }

            return $locked->refresh();
        });
    }

    /**
     * Persist redirect/QR placeholders alongside the gateway payload so the
     * frontend can render them without ever seeing credentials.
     *
     * @param  array{provider_ref: string, redirect_url: string|null, qr_payload: string|null, payload: array<string, mixed>}  $intent
     * @return array<string, mixed>
     */
    protected function intentPayload(array $intent): array
    {
        return $intent['payload'] + [
            'redirect_url' => $intent['redirect_url'],
            'qr_payload' => $intent['qr_payload'],
        ];
    }

    protected function isMethodEnabled(string $method): bool
    {
        $setting = $this->settings->get('payment.'.$method.'_enabled', null);

        // TBC (spec §24 item 1): no admin payment UI yet; both methods stay
        // enabled by default until toggles are confirmed.
        if ($setting === null) {
            return true;
        }

        return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
    }
}
