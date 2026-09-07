<?php

use App\Enums\ProductStatus;
use App\Exceptions\ShippingQuoteException;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\Shipping\ExternalShippingProvider;
use App\Services\Shipping\ShippingService;
use Illuminate\Support\Facades\Http;

function shippingTestProduct(array $overrides = []): Product
{
    $user = User::factory()->create(['is_active_as_seller' => true]);
    Seller::factory()->create(['user_id' => $user->id]);

    return Product::factory()->create(array_merge([
        'seller_id' => $user->refresh()->seller->id,
        'status' => ProductStatus::Active,
        'price' => '25.50',
        'stock' => 10,
    ], $overrides));
}

function shippingTestAddress(array $overrides = []): array
{
    return array_merge([
        'address' => '1 Jalan Merdeka',
        'state' => 'Selangor',
        'post_code' => '40000',
    ], $overrides);
}

test('quote returns fixed rate by default', function () {
    $response = $this->postJson('/api/v1/shipping/quote', shippingTestAddress());

    $response->assertOk()
        ->assertJsonPath('data.method', 'fixed')
        ->assertJsonPath('data.fee', 5)
        ->assertJsonPath('data.currency_code', 'MYR')
        ->assertJsonPath('data.formatted', 'RM 5.00');
});

test('quote derives subtotal from product lines', function () {
    $product = shippingTestProduct(['price' => '40.00']);

    app(SettingsService::class)->set('shipping.free_shipping_enabled', true);
    app(SettingsService::class)->set('shipping.free_shipping_threshold', '100.00');

    $this->postJson('/api/v1/shipping/quote', shippingTestAddress([
        'items' => [['product_id' => $product->id, 'quantity' => 3]],
    ]))->assertOk()->assertJsonPath('data.fee', 0);
});

test('fixed rate applies free shipping threshold and stays default in checkout', function () {
    app(SettingsService::class)->set('shipping.free_shipping_enabled', true);
    app(SettingsService::class)->set('shipping.free_shipping_threshold', '50.00');

    $product = shippingTestProduct(['price' => '30.00']);

    $this->postJson('/api/v1/shipping/quote', shippingTestAddress([
        'product_id' => $product->id,
        'quantity' => 2,
    ]))->assertOk()->assertJsonPath('data.fee', 0);

    $orderNumber = $this->postJson('/api/v1/orders', [
        'product_id' => $product->id,
        'quantity' => 2,
        'buyer' => [
            'name' => 'Ahmad Buyer',
            'address' => '1 Jalan Merdeka',
            'state' => 'Selangor',
            'post_code' => '40000',
            'phone' => '0123456789',
        ],
    ])->assertCreated()->json('data.order_number');

    $order = Order::query()->where('order_number', $orderNumber)->first();

    expect($order->shipping_method)->toBe('fixed');
    expect((float) $order->shipping_fee)->toBe(0.0);
    expect((float) $order->total)->toBe(60.0);
});

test('quote returns zero fee below threshold keeps fixed fee', function () {
    $response = $this->postJson('/api/v1/shipping/quote', shippingTestAddress([
        'subtotal' => 10.00,
    ]));

    $response->assertOk()->assertJsonPath('data.fee', 5);
});

test('external provider skeleton returns mocked quote without inventing provider', function () {
    // TBC (spec §24 item 2): generic fake HTTP response only; no real
    // provider, endpoint, or credential is assumed.
    config(['shipping.external.base_url' => 'https://TBC_SHIPPING_API_BASE.example']);
    config(['shipping.external.api_key' => 'TBC_SHIPPING_API_KEY']);
    app(SettingsService::class)->set('shipping.api_enabled', true);
    app(SettingsService::class)->set('shipping.provider_name', '');

    Http::fake(['*' => Http::response(['fee' => 12.50], 200)]);

    $service = app(ShippingService::class);

    $quote = $service->quote(shippingTestAddress(), 20.0, [], 'external');

    expect($quote['method'])->toBe('external');
    expect($quote['fee'])->toBe(12.50);
    expect($quote['provider'])->toBeNull();
});

test('external provider maps provider failures and missing config to domain exception', function () {
    app(SettingsService::class)->set('shipping.api_enabled', false);

    expect(fn () => app(ExternalShippingProvider::class)->quote(shippingTestAddress(), 10.0))
        ->toThrow(ShippingQuoteException::class);

    $this->postJson('/api/v1/shipping/quote', shippingTestAddress(['method' => 'external']))
        ->assertServerError();
});

test('external provider failure does not leak server-side credentials', function () {
    app(SettingsService::class)->set('shipping.api_enabled', true);
    config(['shipping.external.base_url' => 'https://TBC_SHIPPING_API_BASE.example']);
    config(['shipping.external.api_key' => 'super-secret-key']);

    Http::fake(['*' => Http::response('error', 500)]);

    $response = $this->postJson('/api/v1/shipping/quote', shippingTestAddress(['method' => 'external']));

    $response->assertServerError();
    expect($response->getContent())->not->toContain('super-secret-key');
});

test('quote validates address fields', function () {
    $this->postJson('/api/v1/shipping/quote', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['address', 'state', 'post_code']);
});
