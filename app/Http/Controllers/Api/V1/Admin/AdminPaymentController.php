<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Admin payment management (spec §17): list transactions, view
 * callback payloads (audit), verify manual payments.
 *
 * Refunds are TBC (spec §17); no refund mutation is exposed here.
 * Payloads stay server-only per PaymentResource — callback payloads are
 * visible only through this admin endpoint, never the public API.
 */
class AdminPaymentController extends Controller
{
    /**
     * Paginated payment list with status/method filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
        ]);

        $payments = Payment::query()
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status instanceof \BackedEnum ? $status->value : $status))
            ->when($validated['payment_method'] ?? null, fn ($query, $method) => $query->where('payment_method', $method instanceof \BackedEnum ? $method->value : $method))
            ->with('order')
            ->latest()
            ->paginate(15);

        return PaymentResource::collection($payments);
    }

    /**
     * Show one payment with its order context.
     */
    public function show(Payment $payment): PaymentResource
    {
        return new PaymentResource($payment->load('order'));
    }

    /**
     * Verify a manual (bank_transfer/qr_code) payment as paid or failed.
     * PayNet intents are reconciled via callbacks, never here.
     */
    public function verify(Request $request, Payment $payment, PaymentService $payments): PaymentResource|JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:paid,failed'],
        ]);

        if (! $payment->payment_method instanceof PaymentMethod || ! $payment->payment_method->isManual()) {
            return response()->json(['message' => 'Only manual payments can be verified here.'], 422);
        }

        if ($payment->status !== PaymentStatus::Pending) {
            return response()->json(['message' => 'Only pending payments can be verified.'], 409);
        }

        $payment = $validated['status'] === 'paid'
            ? $payments->markPaid($payment)
            : $payments->markFailed($payment);

        return new PaymentResource($payment->load('order'));
    }
}
