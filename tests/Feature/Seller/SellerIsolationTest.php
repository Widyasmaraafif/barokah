<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Seller;
use App\Models\User;

function createActiveSeller(): User
{
    $user = User::factory()->create(['is_active_as_seller' => true]);
    Seller::factory()->create(['user_id' => $user->id]);

    return $user->refresh();
}

test('guest cannot access seller orders', function () {
    $this->getJson('/api/v1/seller/orders')->assertUnauthorized();
});

test('buyer without seller capability cannot access seller orders', function () {
    $buyer = User::factory()->create();

    $this->actingAs($buyer)->getJson('/api/v1/seller/orders')->assertForbidden();
});

test('seller sees only orders containing their own items', function () {
    $seller = createActiveSeller();
    $otherSeller = createActiveSeller();

    $ownOrder = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $ownOrder->id,
        'seller_id' => $seller->seller->id,
    ]);

    $mixedOrder = Order::factory()->create();
    $ownItem = OrderItem::factory()->create([
        'order_id' => $mixedOrder->id,
        'seller_id' => $seller->seller->id,
    ]);
    $otherItem = OrderItem::factory()->create([
        'order_id' => $mixedOrder->id,
        'seller_id' => $otherSeller->seller->id,
    ]);

    $otherOrder = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $otherOrder->id,
        'seller_id' => $otherSeller->seller->id,
    ]);

    $response = $this->actingAs($seller)->getJson('/api/v1/seller/orders');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['order_number' => $ownOrder->order_number])
        ->assertJsonFragment(['order_number' => $mixedOrder->order_number])
        ->assertJsonMissing(['order_number' => $otherOrder->order_number]);

    $mixedPayload = collect($response->json('data'))
        ->firstWhere('order_number', $mixedOrder->order_number);

    expect($mixedPayload['items'])->toHaveCount(1);
    expect($mixedPayload['items'][0]['id'])->toBe($ownItem->id);
    expect(collect($mixedPayload['items'])->pluck('id'))->not->toContain($otherItem->id);
});

test('seller with no orders receives an empty list', function () {
    $seller = createActiveSeller();

    $this->actingAs($seller)->getJson('/api/v1/seller/orders')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('guest is redirected from seller dashboard page', function () {
    $this->get(route('seller.dashboard'))->assertRedirect(route('login'));
});

test('buyer cannot visit seller dashboard page', function () {
    $buyer = User::factory()->create();

    $this->actingAs($buyer)->get(route('seller.dashboard'))->assertForbidden();
});

test('seller can visit seller dashboard page with scoped stats', function () {
    $seller = createActiveSeller();

    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'seller_id' => $seller->seller->id,
        'quantity' => 2,
        'price_snapshot' => '10.00',
        'subtotal' => '20.00',
    ]);

    $this->actingAs($seller)->get(route('seller.dashboard'))->assertOk();
});

test('seller policy isolates profiles between sellers', function () {
    $seller = createActiveSeller();
    $other = createActiveSeller();
    $admin = User::factory()->create(['is_admin' => true]);
    $buyer = User::factory()->create();

    expect($seller->can('view', $seller->seller))->toBeTrue();
    expect($seller->can('view', $other->seller))->toBeFalse();
    expect($seller->can('update', $seller->seller))->toBeTrue();
    expect($seller->can('update', $other->seller))->toBeFalse();
    expect($buyer->can('view', $seller->seller))->toBeFalse();
    expect($admin->can('view', $seller->seller))->toBeTrue();
    expect($admin->can('viewAny', Seller::class))->toBeTrue();
    expect($seller->can('viewAny', Seller::class))->toBeFalse();
});
