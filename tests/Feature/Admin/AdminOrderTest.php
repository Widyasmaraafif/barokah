<?php

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Seller;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function adminOrderViewer(): User
{
    return User::factory()->create(['is_admin' => true]);
}

test('admin views order detail with item table data', function () {
    $admin = adminOrderViewer();
    $sellerUser = User::factory()->create(['is_active_as_seller' => true]);
    $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
    $order = Order::factory()->create([
        'customer_city' => 'Petaling Jaya',
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'seller_id' => $seller->id,
        'product_name_snapshot' => 'Keripik Pisang',
        'price_snapshot' => '12.50',
        'quantity' => 2,
        'subtotal' => '25.00',
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => $order->total,
    ]);

    $this->withoutVite()->actingAs($admin)->get(route('admin.orders.show', $order->order_number))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Show')
            ->where('order.order_number', $order->order_number)
            ->where('order.customer_city', 'Petaling Jaya')
            ->has('order.items', 1)
            ->where('order.items.0.product_name', 'Keripik Pisang')
            ->where('order.items.0.seller', $seller->store_name)
            ->where('order.payment.payment_method', 'fpx')
        );

    expect($item->refresh()->product_name_snapshot)->toBe('Keripik Pisang');
});

test('admin order detail exposes shipping and proof for verification', function () {
    $admin = adminOrderViewer();
    $order = Order::factory()->create([
        'shipping_address' => 'No. 99, Jalan Tujuan',
        'shipping_state' => 'Selangor',
        'shipping_city' => 'Shah Alam',
        'shipping_post_code' => '40000',
    ]);
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'payment_gateway' => 'manual',
        'payment_method' => PaymentMethod::BankTransfer,
        'amount' => $order->total,
    ]);

    $this->withoutVite()->actingAs($admin)->get(route('admin.orders.show', $order->order_number))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Orders/Show')
            ->where('order.shipping_address', 'No. 99, Jalan Tujuan')
            ->where('order.shipping_city', 'Shah Alam')
            ->where('order.payment.id', $payment->id)
            ->has('order.payment.proof_url')
        );
});

test('guests and buyers cannot view admin order detail', function () {
    $order = Order::factory()->create();

    $this->get(route('admin.orders.show', $order->order_number))->assertRedirect('/login');

    $buyer = User::factory()->create();

    $this->actingAs($buyer)->get(route('admin.orders.show', $order->order_number))->assertForbidden();
});

test('admin updates order tracking fields', function () {
    $admin = adminOrderViewer();
    $order = Order::factory()->create();

    $this->actingAs($admin)->putJson('/api/v1/admin/orders/'.$order->order_number, [
        'courier' => 'JNE',
        'waybill_number' => 'JNE123',
        'tracking_url' => 'https://jne.co.id/track/JNE123',
        'tracking_status' => 'shipped',
    ])->assertOk()
        ->assertJsonPath('data.courier', 'JNE')
        ->assertJsonPath('data.waybill_number', 'JNE123')
        ->assertJsonPath('data.tracking_status', 'shipped');

    expect($order->refresh()->tracking_url)->toBe('https://jne.co.id/track/JNE123');
});

test('pending order can reopen checkout confirmation before expiry', function () {
    $order = Order::factory()->create(['expired_at' => now()->addHour()]);

    $this->get(route('checkout.resume', $order->order_number))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Confirmation')
            ->where('order.order_number', $order->order_number));
});

test('admin order detail returns not found for unknown number', function () {
    $admin = adminOrderViewer();

    $this->actingAs($admin)->get(route('admin.orders.show', 'BRK-NOT-FOUND'))->assertNotFound();
});
