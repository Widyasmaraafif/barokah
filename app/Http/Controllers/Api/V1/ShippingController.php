<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ShippingQuoteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ShippingQuoteRequest;
use App\Models\Product;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\JsonResponse;

/**
 * Shipping quote endpoint (spec §11.6/§16.2).
 *
 * Guest quoting is allowed. Credentials stay server-side (spec §20);
 * the response exposes only method, fee, formatted total, and currency.
 * Provider failures map to 500 without leaking secrets.
 */
class ShippingController extends Controller
{
    public function __construct(protected ShippingService $shipping) {}

    public function quote(ShippingQuoteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $lines = $this->lines($validated);
        $subtotal = $this->subtotal($validated, $lines);

        try {
            $address = [
                'address' => $validated['address'],
                'state' => $validated['state'],
                'city' => $validated['city'] ?? null,
                'post_code' => $validated['post_code'],
            ];
            $products = Product::query()->whereKey(array_column($lines, 'product_id'))->get()->keyBy('id');
            $quotes = collect($lines)->groupBy(fn (array $line): int => (int) $products[$line['product_id']]->seller_id)->map(
                fn ($sellerLines): array => $this->shipping->quote($address, $this->subtotal(['items' => $sellerLines->all()], $sellerLines->all()), $sellerLines->all(), $validated['method'] ?? null)
            );
            $quote = [
                'method' => $validated['method'] ?? 'fixed',
                'provider' => $quotes->pluck('provider')->filter()->first(),
                'fee' => round((float) $quotes->sum('fee'), 2),
                'formatted' => (string) $quotes->sum('fee'),
                'currency_code' => $quotes->first()['currency_code'] ?? config('marketplace.currency.code', 'MYR'),
                'meta' => ['breakdown' => $quotes->values()->map(fn (array $item, int $index): array => ['label' => 'Shipping '.($index + 1), 'fee' => $item['fee']])->all()],
            ];
        } catch (ShippingQuoteException $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        return response()->json(['data' => $quote]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array{product_id: int, quantity: int}>
     */
    protected function lines(array $validated): array
    {
        if (isset($validated['items'])) {
            return array_map(fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
            ], $validated['items']);
        }

        if (isset($validated['product_id'])) {
            return [[
                'product_id' => (int) $validated['product_id'],
                'quantity' => (int) ($validated['quantity'] ?? 1),
            ]];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<int, array{product_id: int, quantity: int}>  $lines
     */
    protected function subtotal(array $validated, array $lines): float
    {
        if (isset($validated['subtotal'])) {
            return round((float) $validated['subtotal'], 2);
        }

        if ($lines === []) {
            return 0.0;
        }

        $prices = Product::query()
            ->whereKey(array_column($lines, 'product_id'))
            ->pluck('price', 'id');

        $subtotal = 0.0;

        foreach ($lines as $line) {
            $subtotal += (float) ($prices[$line['product_id']] ?? 0) * $line['quantity'];
        }

        return round($subtotal, 2);
    }
}
