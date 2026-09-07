<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * PayNet payment initiation and status polling (spec §11.5/§15).
 *
 * Guest checkout stays guest-friendly: payable orders without a user_id
 * can initiate and poll without auth until the guest token check lands
 * (TBC spec §12). Owned orders require the owner (or seller/admin policy).
 */
class PaymentController extends Controller
{
    public function __construct(protected PaymentService $payments) {}

    /**
     * Create (or reuse) a pending PayNet intent for fpx/duitnow.
     */
    public function store(Request $request, string $orderNumber): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', Rule::in($this->payments->enabledMethods())],
        ]);

        $order = Order::query()->where('order_number', $orderNumber)->with('payment')->first();

        if ($order === null || ! $this->canAccess($request, $order)) {
            abort(404);
        }

        if (! $order->isPayable()) {
            abort(409, 'Order is no longer payable.');
        }

        try {
            $payment = $this->payments->initiate($order, PaymentMethod::from($validated['payment_method']));
        } catch (InvalidArgumentException $e) {
            abort(409, $e->getMessage());
        }

        return (new PaymentResource($payment->load('order')))->response()->setStatusCode(201);
    }

    /**
     * Poll the payment status for pending UX (spec §15.5/§15.8).
     */
    public function show(Request $request, string $orderNumber): PaymentResource|JsonResponse
    {
        $order = Order::query()->where('order_number', $orderNumber)->with('payment')->first();

        if ($order === null || ! $this->canAccess($request, $order)) {
            abort(404);
        }

        if ($order->payment === null) {
            return response()->json(['message' => 'No payment found for this order.'], 404);
        }

        return new PaymentResource($order->payment->load('order'));
    }

    protected function canAccess(Request $request, Order $order): bool
    {
        // Guest orders (user_id null) stay reachable by order_number until
        // the guest token check lands (TBC spec §12).
        if ($order->user_id === null) {
            return true;
        }

        $user = $request->user();

        return $user !== null && $user->can('view', $order);
    }
}
