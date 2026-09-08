<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BuyerInformationRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Services\SettingsService;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Direct Buy checkout (spec §11.4/§14.4).
 *
 * Guest checkout is allowed; authenticated orders link user_id. Single
 * product Buy Now uses product_id/quantity; items[] allows one checkout
 * with products from multiple sellers (spec §14.3). Each item carries
 * seller_id + product snapshots. Stock is reserved on order create inside
 * a DB transaction with pessimistic locks (TBC spec §24 item 10: restore
 * on expiry/failure lands in Task 7). PayNet intent is created separately
 * via POST /api/v1/orders/{n}/payments (Task 8).
 */
class CheckoutController extends Controller
{
    public function __construct(protected SettingsService $settings, protected ShippingService $shipping) {}

    /**
     * Create a pending_payment order from a direct Buy payload.
     */
    public function store(BuyerInformationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var array<int, array{product_id: int, quantity: int}> $lines */
        $lines = $validated['items']
            ?? [['product_id' => $validated['product_id'], 'quantity' => $validated['quantity']]];

        $shippingMethod = $validated['shipping_method'] ?? 'fixed';
        $currencyCode = (string) $this->settings->get('currency.code', config('marketplace.currency.code', 'MYR'));
        $expirationMinutes = (int) $this->settings->get('checkout.order_expiration_minutes', config('marketplace.checkout.order_expiration_minutes', 30));

        $order = DB::transaction(function () use ($request, $validated, $lines, $shippingMethod, $currencyCode, $expirationMinutes) {
            $subtotal = 0.0;

            /** @var array<int, array{product: Product, quantity: int, line_total: float}> $prepared */
            $prepared = [];

            foreach ($lines as $line) {
                /** @var Product|null $product */
                $product = Product::query()->whereKey($line['product_id'])->lockForUpdate()->first();

                if ($product === null || $product->status !== ProductStatus::Active) {
                    abort(409, 'Selected product is not available.');
                }

                if ($product->stock < $line['quantity']) {
                    abort(409, 'Insufficient stock for '.$product->name.'.');
                }

                $lineTotal = (float) $product->price * $line['quantity'];
                $subtotal += $lineTotal;
                $prepared[] = ['product' => $product, 'quantity' => $line['quantity'], 'line_total' => $lineTotal];
            }

            /** @var Order $order */
            // Snapshot the ShippingService quote on the order (spec §16.2);
            // Fixed Rate stays the default provider behavior (spec §16.1).
            $quote = $this->shipping->quote(
                [
                    'address' => $validated['buyer']['address'],
                    'state' => $validated['buyer']['state'],
                    'post_code' => $validated['buyer']['post_code'],
                ],
                round($subtotal, 2),
                $lines,
                $shippingMethod,
            );

            $order = Order::query()->create([
                'order_number' => $this->uniqueOrderNumber(),
                'user_id' => $request->user()?->id,
                'customer_name' => $validated['buyer']['name'],
                'customer_address' => $validated['buyer']['address'],
                'customer_state' => $validated['buyer']['state'],
                'customer_city' => $validated['buyer']['city'] ?? null,
                'customer_post_code' => $validated['buyer']['post_code'],
                'customer_phone' => $validated['buyer']['phone'],
                'customer_email' => $validated['buyer']['email'] ?? null,
                'currency_code' => $currencyCode,
                'subtotal' => $subtotal,
                'shipping_fee' => $quote['fee'],
                'total' => $subtotal + $quote['fee'],
                'status' => OrderStatus::PendingPayment,
                'shipping_method' => $quote['method'],
                'shipping_provider' => $quote['provider'],
                'expired_at' => now()->addMinutes($expirationMinutes),
            ]);

            foreach ($prepared as $row) {
                /** @var Product $product */
                $product = $row['product'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'product_name_snapshot' => $product->name,
                    'product_slug_snapshot' => $product->slug,
                    'price_snapshot' => $product->price,
                    'quantity' => $row['quantity'],
                    'subtotal' => $row['line_total'],
                ]);

                $product->decrement('stock', $row['quantity']);
            }

            return $order->load('items');
        });

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    /**
     * Human-readable order number (TBC spec §24 item 11: pattern
     * BRK-YYYYMMDD-XXXXXX until confirmed).
     */
    protected function uniqueOrderNumber(): string
    {
        do {
            $candidate = 'BRK-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }
}
