<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Jobs\ProcessPaymentCallback;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\PayNetGateway;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

function createPaymentSeller(): User
{
    $user = User::factory()->create(['is_active_as_seller' => true]);
    Seller::factory()->create(['user_id' => $user->id]);

    return $user->refresh();
}

function createPayableOrder(array $overrides = []): Order
{
    $seller = createPaymentSeller();
    $product = Product::factory()->create([
        'seller_id' => $seller->seller->id,
        'price' => '25.50',
        'stock' => 10,
    ]);

    $order = Order::factory()->create(array_merge([
        'user_id' => null,
        'status' => OrderStatus::PendingPayment,
        'subtotal' => '25.50',
        'shipping_fee' => '5.00',
        'total' => '30.50',
        'expired_at' => now()->addMinutes(30),
    ], $overrides));

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'seller_id' => $seller->seller->id,
        'price_snapshot' => '25.50',
        'subtotal' => '25.50',
        'quantity' => 1,
    ]);

    return $order->refresh();
}

function signedCallback(array $payload): array
{
    $gateway = app(PayNetGateway::class);
    $payload['signature'] = $gateway->signPayload($payload);

    return $payload;
}

beforeEach(function () {
    config(['paynet.secret' => 'test-paynet-secret']);
});

test('guest can initiate fpx payment for pending order', function () {
    $order = createPayableOrder();

    $response = $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.payment_method', 'fpx')
        ->assertJsonPath('data.amount', '30.50')
        ->assertJsonPath('data.currency', 'MYR')
        ->assertJsonPath('data.order_number', $order->order_number);

    expect($response->json('data.redirect_url'))->toContain('TBC_PAYNET_API_BASE');

    $payment = Payment::query()->where('order_id', $order->id)->first();

    expect($payment)->not->toBeNull();
    expect($payment->transaction_id)->toStartWith('TBC_PAYNET_');
    expect($payment->payload)->toHaveKey('provider_ref');
});

test('guest can initiate duitnow payment with qr payload', function () {
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'duitnow',
    ])->assertCreated()
        ->assertJsonPath('data.payment_method', 'duitnow')
        ->assertJsonPath('data.redirect_url', null);

    expect(Payment::query()->first()->payload)->toHaveKey('qr_payload');
});

test('payment initiation validates method and payable state', function () {
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'card',
    ])->assertUnprocessable();

    $this->postJson('/api/v1/orders/NOPE-123/payments', [
        'payment_method' => 'fpx',
    ])->assertNotFound();

    $expired = createPayableOrder([
        'status' => OrderStatus::Expired,
        'expired_at' => now()->subMinute(),
    ]);

    $this->postJson('/api/v1/orders/'.$expired->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertConflict();

    expect(Payment::query()->count())->toBe(0);
});

test('repeat initiation reuses pending payment intent', function () {
    $order = createPayableOrder();

    $first = $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated()->json('data.transaction_id');

    $second = $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated()->json('data.transaction_id');

    expect($second)->toBe($first);
    expect(Payment::query()->count())->toBe(1);
});

test('payment status polling reflects current intent', function () {
    $order = createPayableOrder();

    $this->getJson('/api/v1/orders/'.$order->order_number.'/payment')->assertNotFound();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated();

    $this->getJson('/api/v1/orders/'.$order->order_number.'/payment')
        ->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.order_number', $order->order_number);
});

test('buyer cannot initiate payment for another buyer order', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $order = createPayableOrder(['user_id' => $owner->id]);

    $this->actingAs($stranger)->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertNotFound();

    $this->actingAs($stranger)->getJson('/api/v1/orders/'.$order->order_number.'/payment')->assertNotFound();
});

test('valid callback marks payment and order paid', function () {
    Queue::fake();
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated();

    $verified = signedCallback([
        'order_number' => $order->order_number,
        'transaction_id' => Payment::query()->firstOrFail()->transaction_id,
        'status' => 'success',
    ]);

    $this->postJson('/api/v1/payments/callback', $verified)
        ->assertOk()
        ->assertJsonPath('message', 'Callback accepted.');

    Queue::assertPushed(ProcessPaymentCallback::class);

    // Reconciliation runs on the sync queue in tests; invoke inline to
    // assert the paid transition.
    app(ProcessPaymentCallback::class, ['payload' => $verified])->handle(app(PaymentService::class));

    expect(Payment::query()->first()->status)->toBe(PaymentStatus::Paid);
    expect($order->refresh()->status)->toBe(OrderStatus::Paid);
    expect(Payment::query()->first()->paid_at)->not->toBeNull();
});

test('callback rejects invalid signature', function () {
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated();

    $this->postJson('/api/v1/payments/callback', [
        'order_number' => $order->order_number,
        'transaction_id' => 'TBC_PAYNET_FORGED',
        'status' => 'success',
        'signature' => 'invalid',
    ])->assertStatus(400);

    expect(Payment::query()->first()->status)->toBe(PaymentStatus::Pending);
    expect($order->refresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('duplicate callback is idempotent', function () {
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated();

    $transactionId = Payment::query()->firstOrFail()->transaction_id;
    $service = app(PaymentService::class);

    $payload = [
        'order_number' => $order->order_number,
        'transaction_id' => $transactionId,
        'status' => 'success',
    ];

    $first = $service->handleCallback($payload);
    $second = $service->handleCallback($payload);

    expect($first->status)->toBe(PaymentStatus::Paid);
    expect($second->status)->toBe(PaymentStatus::Paid);
    expect($order->refresh()->status)->toBe(OrderStatus::Paid);
    expect(Payment::query()->count())->toBe(1);
});

test('failed callback keeps order payable for retry', function () {
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated();

    $service = app(PaymentService::class);
    $payment = $service->handleCallback([
        'order_number' => $order->order_number,
        'transaction_id' => Payment::query()->firstOrFail()->transaction_id,
        'status' => 'failed',
    ]);

    expect($payment->status)->toBe(PaymentStatus::Failed);
    expect($payment->failed_at)->not->toBeNull();
    expect($order->refresh()->status)->toBe(OrderStatus::PendingPayment);

    // Retry creates a fresh pending intent on the same order.
    $retry = $service->initiate($order->refresh(), PaymentMethod::DuitNow);

    expect($retry->status)->toBe(PaymentStatus::Pending);
    expect($retry->payment_method)->toBe(PaymentMethod::DuitNow);
});

test('pending callback status stays pending', function () {
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated();

    $service = app(PaymentService::class);
    $payment = $service->handleCallback([
        'order_number' => $order->order_number,
        'transaction_id' => Payment::query()->firstOrFail()->transaction_id,
        'status' => 'pending',
    ]);

    expect($payment->status)->toBe(PaymentStatus::Pending);
    expect($order->refresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('webhook endpoint accepts signed payload', function () {
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'duitnow',
    ])->assertCreated();

    $this->postJson('/api/v1/payments/webhook', signedCallback([
        'order_number' => $order->order_number,
        'transaction_id' => Payment::query()->firstOrFail()->transaction_id,
        'status' => 'success',
    ]))->assertOk();
});

test('payment resource never exposes provider payloads', function () {
    $order = createPayableOrder();

    $response = $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated();

    expect($response->json('data'))->not->toHaveKeys(['payload', 'callback_payload', 'secret']);
});

test('confirmation page shows payment status', function () {
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated();

    $this->withoutVite()->get(route('checkout.confirmation', $order->order_number))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Confirmation')
            ->where('order.order_number', $order->order_number)
            ->where('order.payment_status', 'pending')
        );
});
