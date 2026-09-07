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
            $quote = $this->shipping->quote(
                [
                    'address' => $validated['address'],
                    'state' => $validated['state'],
                    'post_code' => $validated['post_code'],
                ],
                $subtotal,
                $lines,
                $validated['method'] ?? null,
            );
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
