<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
     * Methods enabled via settings toggles: manual bank transfer, manual
     * static QR, and PayNet FPX/DuitNow when the PayNet group is on.
     *
     * @return array<int, string>
     */
    public function enabledMethods(): array
    {
        $methods = [];

        if ($this->isMethodEnabled('bank_transfer')) {
            $methods[] = PaymentMethod::BankTransfer->value;
        }

        if ($this->isMethodEnabled('qr_code')) {
            $methods[] = PaymentMethod::QrCode->value;
        }

        if ($this->isMethodEnabled('paynet')) {
            if ($this->isMethodEnabled('fpx')) {
                $methods[] = PaymentMethod::Fpx->value;
            }

            if ($this->isMethodEnabled('duitnow')) {
                $methods[] = PaymentMethod::DuitNow->value;
            }
        }

        return $methods;
    }

    /**
     * Manual payment details shown at checkout (bank account + static QR).
     *
     * @return array{bank_name: string|null, bank_account_name: string|null, bank_account_number: string|null, qr_code_url: string|null}
     */
    public function manualPaymentDetails(): array
    {
        return [
            'bank_name' => $this->stringSetting('payment.bank_name'),
            'bank_account_name' => $this->stringSetting('payment.bank_account_name'),
            'bank_account_number' => $this->stringSetting('payment.bank_account_number'),
            'qr_code_url' => $this->stringSetting('payment.qr_code_url'),
        ];
    }

    /**
     * Create (or reuse) a pending payment intent for a payable order.
     *
     * Manual methods (bank_transfer/qr_code) are stored as pending with a
     * manual gateway and no provider intent; admin verifies them via
     * markPaid/markFailed.
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

            if ($existing !== null && $existing->status === PaymentStatus::Pending && $existing->payment_method === $method) {
                return $existing;
            }

            if ($existing !== null && $existing->proof_path !== null && $existing->proof_path !== '' && $existing->payment_method !== $method) {
                Storage::disk('public')->delete($existing->proof_path);
            }

            if ($method->isManual()) {
                $attributes = [
                    'payment_gateway' => 'manual',
                    'payment_method' => $method,
                    'amount' => $locked->total,
                    'currency' => $locked->currency_code,
                    'status' => PaymentStatus::Pending,
                    'paid_at' => null,
                    'failed_at' => null,
                    'transaction_id' => null,
                    'payload' => ['manual' => true, 'method' => $method->value],
                    'callback_payload' => null,
                    'proof_path' => null,
                    'proof_uploaded_at' => null,
                ];

                if ($existing !== null) {
                    $existing->update($attributes);

                    return $existing->refresh();
                }

                return Payment::query()->create(array_merge(['order_id' => $locked->id], $attributes));
            }

            if ($existing !== null) {
                $existing->update([
                    'payment_gateway' => 'paynet',
                    'payment_method' => $method,
                    'amount' => $locked->total,
                    'currency' => $locked->currency_code,
                    'status' => PaymentStatus::Pending,
                    'paid_at' => null,
                    'failed_at' => null,
                    'callback_payload' => null,
                    'proof_path' => null,
                    'proof_uploaded_at' => null,
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
     * Store a buyer's proof image for a pending manual payment.
     *
     * Only manual (bank_transfer/qr_code) payments in pending state may
     * receive proof; the old file is removed when replaced.
     */
    public function storeProof(Payment $payment, UploadedFile $file): Payment
    {
        if (! $payment->payment_method instanceof PaymentMethod || ! $payment->payment_method->isManual()) {
            throw new InvalidArgumentException('Proof upload is only for manual payments.');
        }

        if ($payment->status !== PaymentStatus::Pending) {
            throw new InvalidArgumentException('Proof upload is only for pending payments.');
        }

        $path = $file->store('payment-proofs', 'public');

        if ($payment->proof_path !== null && $payment->proof_path !== '' && $payment->proof_path !== $path) {
            Storage::disk('public')->delete($payment->proof_path);
        }

        $payment->update([
            'proof_path' => $path,
            'proof_uploaded_at' => now(),
        ]);

        return $payment->refresh();
    }

    /**
     * Admin verifies a manual payment as paid; the pending_payment order
     * moves to paid as well.
     */
    public function markPaid(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'failed_at' => null,
            ]);

            /** @var Order $order */
            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->firstOrFail();

            if ($order->status === OrderStatus::PendingPayment) {
                $order->update(['status' => OrderStatus::Paid]);
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

    protected function stringSetting(string $key): ?string
    {
        $value = $this->settings->get($key);

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
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
