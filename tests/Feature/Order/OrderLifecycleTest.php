<?php

use App\Enums\OrderStatus;
use App\Jobs\ExpirePendingOrders;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;

function createOrderSeller(): User
{
    $user = User::factory()->create(['is_active_as_seller' => true]);
    Seller::factory()->create(['user_id' => $user->id]);

    return $user->refresh();
}

function createOrderProduct(User $seller, array $overrides = []): Product
{
    return Product::factory()->create(array_merge([
        'seller_id' => $seller->seller->id,
        'stock' => 10,
    ], $overrides));
}

function createMultiSellerOrder(?User $buyer = null): Order
{
    $firstSeller = createOrderSeller();
    $secondSeller = createOrderSeller();
    $firstProduct = createOrderProduct($firstSeller, ['price' => '10.00']);
    $secondProduct = createOrderProduct($secondSeller, ['price' => '20.00']);

    $order = Order::factory()->create([
        'user_id' => $buyer?->id,
        'status' => OrderStatus::PendingPayment,
        'subtotal' => '50.00',
        'shipping_fee' => '5.00',
        'total' => '55.00',
        'expired_at' => now()->addMinutes(30),
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $firstProduct->id,
        'seller_id' => $firstSeller->seller->id,
        'quantity' => 1,
        'price_snapshot' => '10.00',
        'subtotal' => '10.00',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $secondProduct->id,
        'seller_id' => $secondSeller->seller->id,
        'quantity' => 2,
        'price_snapshot' => '20.00',
        'subtotal' => '40.00',
    ]);

    return $order->refresh();
}

test('buyer lists only their own orders', function () {
    $buyer = User::factory()->create();
    $other = User::factory()->create();
    $ownOrder = createMultiSellerOrder($buyer);
    $otherOrder = createMultiSellerOrder($other);

    $this->actingAs($buyer)->getJson('/api/v1/orders')
        ->assertOk()
        ->assertJsonFragment(['order_number' => $ownOrder->order_number])
        ->assertJsonMissing(['order_number' => $otherOrder->order_number]);
});

test('guest cannot list buyer orders', function () {
    $this->getJson('/api/v1/orders')->assertUnauthorized();
});

test('buyer views own order but not another buyer order', function () {
    $buyer = User::factory()->create();
    $other = User::factory()->create();
    $ownOrder = createMultiSellerOrder($buyer);
    $otherOrder = createMultiSellerOrder($other);

    $this->actingAs($buyer)->getJson('/api/v1/orders/'.$ownOrder->order_number)
        ->assertOk()
        ->assertJsonPath('data.order_number', $ownOrder->order_number)
        ->assertJsonCount(2, 'data.items');

    $this->actingAs($buyer)->getJson('/api/v1/orders/'.$otherOrder->order_number)
        ->assertNotFound();
});

test('seller views order scoped to own items only', function () {
    $firstSeller = createOrderSeller();
    $secondSeller = createOrderSeller();
    $firstProduct = createOrderProduct($firstSeller);
    $secondProduct = createOrderProduct($secondSeller);

    $order = Order::factory()->create(['status' => OrderStatus::PendingPayment]);
    $ownItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $firstProduct->id,
        'seller_id' => $firstSeller->seller->id,
    ]);
    $otherItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $secondProduct->id,
        'seller_id' => $secondSeller->seller->id,
    ]);

    $this->actingAs($firstSeller)->getJson('/api/v1/seller/orders/'.$order->order_number)
        ->assertOk()
        ->assertJsonCount(1, 'data.items')
        ->assertJsonFragment(['id' => $ownItem->id])
        ->assertJsonMissing(['id' => $otherItem->id]);
});

test('seller cannot view order without own items', function () {
    $seller = createOrderSeller();
    $other = createOrderSeller();
    $product = createOrderProduct($other);

    $order = Order::factory()->create(['status' => OrderStatus::PendingPayment]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'seller_id' => $other->seller->id,
    ]);

    $this->actingAs($seller)->getJson('/api/v1/seller/orders/'.$order->order_number)
        ->assertNotFound();
});

test('admin views all orders and filters by status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $pending = createMultiSellerOrder();
    $expired = createMultiSellerOrder();
    $expired->update(['status' => OrderStatus::Expired]);

    $this->actingAs($admin)->getJson('/api/v1/admin/orders')
        ->assertOk()
        ->assertJsonFragment(['order_number' => $pending->order_number])
        ->assertJsonFragment(['order_number' => $expired->order_number]);

    $this->actingAs($admin)->getJson('/api/v1/admin/orders?status=expired')
        ->assertOk()
        ->assertJsonFragment(['order_number' => $expired->order_number])
        ->assertJsonMissing(['order_number' => $pending->order_number]);

    $this->actingAs($admin)->getJson('/api/v1/admin/orders/'.$pending->order_number)
        ->assertOk()
        ->assertJsonCount(2, 'data.items');
});

test('buyer cannot access admin orders', function () {
    $buyer = User::factory()->create();
    $order = createMultiSellerOrder($buyer);

    $this->actingAs($buyer)->getJson('/api/v1/admin/orders')->assertForbidden();
    $this->actingAs($buyer)->getJson('/api/v1/admin/orders/'.$order->order_number)->assertForbidden();
});

test('order policy grants buyer seller and admin views', function () {
    $buyer = User::factory()->create();
    $seller = createOrderSeller();
    $product = createOrderProduct($seller);
    $admin = User::factory()->create(['is_admin' => true]);
    $stranger = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => OrderStatus::PendingPayment,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'seller_id' => $seller->seller->id,
    ]);

    expect($buyer->can('view', $order))->toBeTrue();
    expect($seller->can('view', $order))->toBeTrue();
    expect($admin->can('view', $order))->toBeTrue();
    expect($stranger->can('view', $order))->toBeFalse();
    expect($stranger->can('update', $order))->toBeFalse();
    expect($admin->can('update', $order))->toBeTrue();
});

test('expiry job expires overdue pending orders and restores stock', function () {
    $seller = createOrderSeller();
    $product = createOrderProduct($seller, ['stock' => 8, 'price' => '10.00']);

    $order = Order::factory()->create([
        'status' => OrderStatus::PendingPayment,
        'expired_at' => now()->subMinute(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'seller_id' => $seller->seller->id,
        'quantity' => 2,
        'price_snapshot' => '10.00',
        'subtotal' => '20.00',
    ]);

    $expired = app(ExpirePendingOrders::class)->handle();

    expect($expired)->toBe(1);
    expect($order->refresh()->status)->toBe(OrderStatus::Expired);
    expect($product->refresh()->stock)->toBe(10);
});

test('expiry job keeps unexpired and non-pending orders', function () {
    $pending = Order::factory()->create([
        'status' => OrderStatus::PendingPayment,
        'expired_at' => now()->addMinutes(30),
    ]);
    $paid = Order::factory()->create([
        'status' => OrderStatus::Paid,
        'expired_at' => now()->subMinute(),
    ]);

    $expired = app(ExpirePendingOrders::class)->handle();

    expect($expired)->toBe(0);
    expect($pending->refresh()->status)->toBe(OrderStatus::PendingPayment);
    expect($paid->refresh()->status)->toBe(OrderStatus::Paid);
});

test('expire pending orders command runs the job', function () {
    Order::factory()->create([
        'status' => OrderStatus::PendingPayment,
        'expired_at' => now()->subMinute(),
    ]);

    $this->artisan('orders:expire-pending')
        ->assertSuccessful()
        ->expectsOutputToContain('Expired 1 pending order(s).');

    expect(Order::query()->first()->status)->toBe(OrderStatus::Expired);
});
