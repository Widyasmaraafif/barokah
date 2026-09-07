<?php

namespace App\Services\Shipping;

/**
 * Shipping provider contract (spec §16.2).
 *
 * Adding a new provider requires a new implementation of this contract
 * plus a settings entry — no order schema change (spec §16.4).
 *
 * @phpstan-type ShippingAddress array{address: string, state: string, post_code: string}
 * @phpstan-type ShippingLine array{product_id: int, quantity: int}
 * @phpstan-type ShippingQuoteResult array{method: string, provider: ?string, fee: float, meta: array<string, mixed>}
 */
interface ShippingProviderContract
{
    /**
     * Provider method key (fixed|external).
     */
    public function method(): string;

    /**
     * Quote a shipping fee for the given address and order subtotal.
     *
     * @param  ShippingAddress  $address
     * @param  array<int, ShippingLine>  $lines
     * @return ShippingQuoteResult
     */
    public function quote(array $address, float $subtotal, array $lines = []): array;
}
