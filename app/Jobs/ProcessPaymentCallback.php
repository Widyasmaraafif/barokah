<?php

namespace App\Jobs;

use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Reconcile a verified PayNet callback idempotently (spec §15.4).
 *
 * Webhook failures are retried via queue backoff; unknown order
 * references are logged without failing the job.
 */
class ProcessPaymentCallback implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 300];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(PaymentService $payments): void
    {
        $payment = $payments->handleCallback($this->payload);

        if ($payment === null) {
            Log::warning('paynet.callback.unknown_order', [
                'order_number' => $this->payload['order_number'] ?? $this->payload['order_no'] ?? null,
            ]);

            return;
        }

        Log::info('paynet.callback.reconciled', [
            'order_number' => $this->payload['order_number'] ?? $this->payload['order_no'] ?? null,
            'status' => $payment->status instanceof \BackedEnum ? $payment->status->value : $payment->status,
        ]);
    }
}
