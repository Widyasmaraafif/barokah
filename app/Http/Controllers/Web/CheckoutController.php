<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 4-step direct Buy wizard (spec §6.1/§18.5): Buyer Information →
 * Shipping → Payment → Confirmation. Buy Now starts at step 1 for the
 * chosen product; the order is created via POST /api/v1/orders and the
 * confirmation page reads the order by order_number.
 * Payment method selection (FPX/DuitNow) initiates a PayNet intent via
 * POST /api/v1/orders/{n}/payments (Task 8, TBC spec §24 item 1).
 */
class CheckoutController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $product = Product::query()
            ->active()
            ->where('slug', $slug)
            ->with(['seller', 'category', 'images'])
            ->firstOrFail();

        return Inertia::render('Checkout/Show', [
            'product' => new ProductResource($product),
            'profile' => $this->buyerDefaults($request),
        ]);
    }

    public function confirmation(Request $request, string $orderNumber): Response
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->with(['items', 'payment'])
            ->firstOrFail();

        $user = $request->user();

        // Owned orders require the owner (or seller/admin via policy);
        // guest orders (user_id null) stay reachable by order_number until
        // the guest token check lands (TBC spec §12).
        if ($order->user_id !== null && ($user === null || $user->cannot('view', $order))) {
            abort(404);
        }

        return Inertia::render('Checkout/Confirmation', [
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment?->status,
                'currency_code' => $order->currency_code,
                'customer_name' => $order->customer_name,
                'subtotal' => $order->subtotal,
                'shipping_fee' => $order->shipping_fee,
                'total' => $order->total,
                'shipping_method' => $order->shipping_method,
                'expired_at' => $order->expired_at,
                'created_at' => $order->created_at,
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name_snapshot,
                    'quantity' => $item->quantity,
                    'price' => $item->price_snapshot,
                    'subtotal' => $item->subtotal,
                ])->values(),
            ],
        ]);
    }

    /**
     * Prefill buyer fields for authenticated users from their profile;
     * guests receive empty defaults (spec §6.2).
     *
     * @return array<string, string|null>
     */
    protected function buyerDefaults(Request $request): array
    {
        $user = $request->user();

        return [
            'name' => $user?->name,
            'address' => $user?->address,
            'state' => $user?->state,
            'city' => $user?->city,
            'post_code' => $user?->post_code,
            'phone' => $user?->phone,
            'email' => $user?->email,
        ];
    }
}
