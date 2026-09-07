<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Admin payment management (spec §17): list PayNet transactions, view
 * callback payloads (audit), retry reminders.
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
            'payment_method' => ['nullable', 'string', 'in:fpx,duitnow'],
        ]);

        $payments = Payment::query()
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status instanceof \BackedEnum ? $status->value : $status))
            ->when($validated['payment_method'] ?? null, fn ($query, $method) => $query->where('payment_method', $method))
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
}
