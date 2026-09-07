<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentCallback;
use App\Services\PayNetGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PayNet callback/webhook receiver (spec §11.5/§15.4).
 *
 * Public but signature-verified (interim HMAC contract, TBC algorithm per
 * spec §24 item 1). Invalid signatures return 400 and are logged; verified
 * payloads are dispatched to ProcessPaymentCallback and acknowledged fast.
 */
class PaymentCallbackController extends Controller
{
    public function __construct(protected PayNetGateway $gateway) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all() ?: $request->all();
        $signature = (string) ($request->header('X-PayNet-Signature') ?? $payload['signature'] ?? '');

        if (! $this->gateway->verifyCallback($payload, $signature)) {
            Log::warning('paynet.callback.invalid_signature', [
                'order_number' => $payload['order_number'] ?? $payload['order_no'] ?? null,
            ]);

            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        ProcessPaymentCallback::dispatch($payload);

        return response()->json(['message' => 'Callback accepted.']);
    }
}
