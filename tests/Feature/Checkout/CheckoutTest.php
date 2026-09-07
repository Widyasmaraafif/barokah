<?php

use App\Enums\ProductStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;

function createCheckoutSeller(): User
{
    $user = User::factory()->create(['is_active_as_seller' => true]);
    Seller::factory()->create(['user_id' => $user->id]);

    return $user->refresh();
}

function checkoutProduct(array $overrides = []): Product
{
    $seller = createCheckoutSeller();

    return Product::factory()->create(array_merge([
        'seller_id' => $seller->seller->id,
        'status' => ProductStatus::Active,
        'price' => '25.50',
        'stock' => 10,
    ], $overrides));
}

function validBuyerPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ahmad Buyer',
        'address' => '1 Jalan Merdeka',
        'state' => 'Selangor',
        'post_code' => '40000',
        'phone' => '0123456789',
    ], $overrides);
}

test('guest can checkout directly without cart backend', function () {
    $product = checkoutProduct();

    $response = $this->postJson('/api/v1/orders', [
        'product_id' => $product->id,
        'quantity' => 2,
        'buyer' => validBuyerPayload(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending_payment')
        ->assertJsonPath('data.customer_name', 'Ahmad Buyer')
        ->assertJsonPath('data.subtotal', '51.00')
        ->assertJsonPath('data.shipping_fee', '5.00')
        ->assertJsonPath('data.total', '56.00')
        ->assertJsonCount(1, 'data.items');

    $order = Order::query()->first();

    expect($order->user_id)->toBeNull();
    expect($order->order_number)->toStartWith('BRK-');
    expect($order->expired_at)->not->toBeNull();
    expect($order->items->first()->seller_id)->toBe($product->seller_id);
    expect($order->items->first()->product_name_snapshot)->toBe($product->name);
    expect($product->refresh()->stock)->toBe(8);
});

test('authenticated checkout links order to buyer', function () {
    $buyer = User::factory()->create();
    $product = checkoutProduct();

    $this->actingAs($buyer)->postJson('/api/v1/orders', [
        'product_id' => $product->id,
        'quantity' => 1,
        'buyer' => validBuyerPayload(),
    ])->assertCreated();

    expect(Order::query()->first()->user_id)->toBe($buyer->id);
});

test('checkout validates five buyer fields', function () {
    $product = checkoutProduct();

    $this->postJson('/api/v1/orders', [
        'product_id' => $product->id,
        'quantity' => 1,
        'buyer' => [],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors([
            'buyer.name',
            'buyer.address',
            'buyer.state',
            'buyer.post_code',
            'buyer.phone',
        ]);
});

test('checkout accepts optional buyer email', function () {
    $product = checkoutProduct();

    $this->postJson('/api/v1/orders', [
        'product_id' => $product->id,
        'quantity' => 1,
        'buyer' => validBuyerPayload(['email' => 'buyer@example.com']),
    ])->assertCreated()
        ->assertJsonPath('data.customer_email', 'buyer@example.com');
});

test('checkout rejects insufficient stock with 409 and keeps stock', function () {
    $product = checkoutProduct(['stock' => 1]);

    $this->postJson('/api/v1/orders', [
        'product_id' => $product->id,
        'quantity' => 2,
        'buyer' => validBuyerPayload(),
    ])->assertConflict();

    expect(Order::query()->count())->toBe(0);
    expect($product->refresh()->stock)->toBe(1);
});

test('checkout rejects inactive products with 409', function () {
    $product = checkoutProduct(['status' => ProductStatus::Draft]);

    $this->postJson('/api/v1/orders', [
        'product_id' => $product->id,
        'quantity' => 1,
        'buyer' => validBuyerPayload(),
    ])->assertConflict();

    expect(Order::query()->count())->toBe(0);
});

test('checkout creates multi-seller items with snapshots', function () {
    $first = checkoutProduct(['price' => '10.00', 'stock' => 5]);
    $second = checkoutProduct(['price' => '20.00', 'stock' => 5]);

    $response = $this->postJson('/api/v1/orders', [
        'items' => [
            ['product_id' => $first->id, 'quantity' => 1],
            ['product_id' => $second->id, 'quantity' => 2],
        ],
        'buyer' => validBuyerPayload(),
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.subtotal', '50.00')
        ->assertJsonPath('data.total', '55.00')
        ->assertJsonCount(2, 'data.items');

    $order = Order::query()->with('items')->first();

    expect($order->items->pluck('seller_id')->sort()->values()->all())
        ->toBe(collect([$first->seller_id, $second->seller_id])->sort()->values()->all());
    expect($first->refresh()->stock)->toBe(4);
    expect($second->refresh()->stock)->toBe(3);
});

test('checkout uses fixed shipping fee from settings', function () {
    $product = checkoutProduct(['price' => '10.00']);

    $this->postJson('/api/v1/orders', [
        'product_id' => $product->id,
        'quantity' => 1,
        'buyer' => validBuyerPayload(),
        'shipping_method' => 'fixed',
    ])->assertCreated()
        ->assertJsonPath('data.shipping_method', 'fixed')
        ->assertJsonPath('data.shipping_fee', '5.00');
});

test('checkout wizard page renders buyer form', function () {
    $product = checkoutProduct();

    $this->withoutVite()->get(route('checkout.show', $product->slug))
        ->assertOk()
        ->assertSee('Checkout', false)
        ->assertSee($product->name);
});

test('checkout confirmation page renders order totals', function () {
    $product = checkoutProduct();

    $orderNumber = $this->postJson('/api/v1/orders', [
        'product_id' => $product->id,
        'quantity' => 1,
        'buyer' => validBuyerPayload(),
    ])->assertCreated()->json('data.order_number');

    $this->withoutVite()->get(route('checkout.confirmation', $orderNumber))
        ->assertOk()
        ->assertSee($orderNumber);
});
