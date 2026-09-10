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
            'cartCheckout' => false,
            'initialQuantity' => max(1, (int) $request->integer('quantity', 1)),
        ]);
    }

    public function cart(Request $request): Response
    {
        return Inertia::render('Checkout/Show', [
            'product' => null,
            'profile' => $this->buyerDefaults($request),
            'cartCheckout' => true,
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

        if ($order->isExpired()) {
            abort(404);
        }

        return Inertia::render('Checkout/Confirmation', [
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status instanceof \BackedEnum ? $order->status->value : $order->status,
                'payment_status' => $order->payment?->status instanceof \BackedEnum ? $order->payment->status->value : $order->payment?->status,
                'payment_method' => $order->payment?->payment_method instanceof \BackedEnum ? $order->payment->payment_method->value : $order->payment?->payment_method,
                'payment_gateway' => $order->payment?->payment_gateway,
                'proof_url' => $order->payment?->proofUrl(),
                'proof_uploaded_at' => $order->payment?->proof_uploaded_at,
                'currency_code' => $order->currency_code,
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'customer_email' => $order->customer_email,
                'customer_address' => $order->customer_address,
                'customer_state' => $order->customer_state,
                'customer_city' => $order->customer_city,
                'customer_post_code' => $order->customer_post_code,
                'shipping_address' => $order->shipping_address,
                'shipping_state' => $order->shipping_state,
                'shipping_city' => $order->shipping_city,
                'shipping_post_code' => $order->shipping_post_code,
                'subtotal' => $order->subtotal,
                'shipping_fee' => $order->shipping_fee,
                'total' => $order->total,
                'shipping_method' => $order->shipping_method,
                'shipping_provider' => $order->shipping_provider,
                'courier' => $order->courier,
                'waybill_number' => $order->waybill_number,
                'tracking_url' => $order->tracking_url,
                'tracking_status' => $order->tracking_status,
                'seller_trackings' => $order->sellerTrackings->keyBy('seller_id')->map(fn ($tracking) => [
                    'seller_id' => $tracking->seller_id,
                    'seller_name' => $order->items->firstWhere('seller_id', $tracking->seller_id)?->seller?->store_name,
                    'courier' => $tracking->courier,
                    'waybill_number' => $tracking->waybill_number,
                    'tracking_url' => $tracking->tracking_url,
                    'tracking_status' => $tracking->tracking_status,
                ])->values(),
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
