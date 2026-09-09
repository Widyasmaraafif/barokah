<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SettingType;
use App\Jobs\ProcessPaymentCallback;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\PayNetGateway;
use App\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
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

test('guest can initiate bank transfer stored as manual pending', function () {
    $order = createPayableOrder();

    $response = $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'bank_transfer',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.payment_method', 'bank_transfer')
        ->assertJsonPath('data.payment_gateway', 'manual')
        ->assertJsonPath('data.redirect_url', null)
        ->assertJsonPath('data.qr_payload', null);

    $payment = Payment::query()->where('order_id', $order->id)->first();

    expect($payment)->not->toBeNull();
    expect($payment->transaction_id)->toBeNull();
    expect($payment->payload)->toMatchArray(['manual' => true, 'method' => 'bank_transfer']);
    expect($order->refresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('guest can initiate qr code stored as manual pending', function () {
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'qr_code',
    ])->assertCreated()
        ->assertJsonPath('data.payment_method', 'qr_code')
        ->assertJsonPath('data.payment_gateway', 'manual');

    expect(Payment::query()->first()->payment_method)->toBe(PaymentMethod::QrCode);
});

test('disabled payment method is rejected', function () {
    config(['marketplace.settings_defaults.payment.bank_transfer_enabled.value' => false]);
    app(SettingsService::class)->forget();
    Setting::query()->updateOrCreate(
        ['key' => 'payment.bank_transfer_enabled'],
        ['value' => '0', 'type' => SettingType::Boolean, 'group' => 'payment', 'is_public' => true],
    );

    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'bank_transfer',
    ])->assertUnprocessable();

    expect(Payment::query()->count())->toBe(0);
});

test('admin verifies manual payment as paid', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'bank_transfer',
    ])->assertCreated();

    $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

    $this->actingAs($admin)->postJson('/api/v1/admin/payments/'.$payment->id.'/verify', [
        'status' => 'paid',
    ])->assertOk()
        ->assertJsonPath('data.status', 'paid');

    expect($payment->refresh()->status)->toBe(PaymentStatus::Paid);
    expect($payment->refresh()->paid_at)->not->toBeNull();
    expect($order->refresh()->status)->toBe(OrderStatus::Paid);
});

test('admin verifies manual payment as failed', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'qr_code',
    ])->assertCreated();

    $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

    $this->actingAs($admin)->postJson('/api/v1/admin/payments/'.$payment->id.'/verify', [
        'status' => 'failed',
    ])->assertOk()
        ->assertJsonPath('data.status', 'failed');

    expect($payment->refresh()->status)->toBe(PaymentStatus::Failed);
    expect($order->refresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('admin cannot verify paynet payment manually', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated();

    $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

    $this->actingAs($admin)->postJson('/api/v1/admin/payments/'.$payment->id.'/verify', [
        'status' => 'paid',
    ])->assertUnprocessable();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending);
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

test('manual confirmation exposes proof and shipping details', function () {
    Storage::fake('public');
    $order = createPayableOrder([
        'shipping_address' => 'No. 99, Jalan Tujuan',
        'shipping_state' => 'Selangor',
        'shipping_city' => 'Shah Alam',
        'shipping_post_code' => '40000',
    ]);

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'bank_transfer',
    ])->assertCreated();

    $this->post('/api/v1/orders/'.$order->order_number.'/payment/proof', [
        'proof' => UploadedFile::fake()->image('receipt.jpg'),
    ])->assertOk();

    $this->withoutVite()->get(route('checkout.confirmation', $order->order_number))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Checkout/Confirmation')
            ->where('order.payment_method', 'bank_transfer')
            ->where('order.payment_gateway', 'manual')
            ->where('order.shipping_address', 'No. 99, Jalan Tujuan')
            ->where('order.shipping_city', 'Shah Alam')
            ->has('order.proof_url')
        );
});

test('switching pending payment method updates stored intent', function () {
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'bank_transfer',
    ])->assertCreated()
        ->assertJsonPath('data.payment_method', 'bank_transfer');

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'qr_code',
    ])->assertCreated()
        ->assertJsonPath('data.payment_method', 'qr_code')
        ->assertJsonPath('data.payment_gateway', 'manual');

    expect(Payment::query()->count())->toBe(1);
    expect(Payment::query()->first()->payment_method)->toBe(PaymentMethod::QrCode);
});

test('guest can upload proof for manual pending payment', function () {
    Storage::fake('public');
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'bank_transfer',
    ])->assertCreated();

    $response = $this->post('/api/v1/orders/'.$order->order_number.'/payment/proof', [
        'proof' => UploadedFile::fake()->image('receipt.jpg'),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.payment_method', 'bank_transfer');

    $proofUrl = $response->json('data.proof_url');

    expect($proofUrl)->not->toBeNull();

    $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

    expect($payment->proof_path)->not->toBeNull();
    expect($payment->proof_uploaded_at)->not->toBeNull();
    Storage::disk('public')->assertExists($payment->proof_path);
});

test('proof upload rejects non-manual and invalid files', function () {
    Storage::fake('public');
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'fpx',
    ])->assertCreated();

    $this->post('/api/v1/orders/'.$order->order_number.'/payment/proof', [
        'proof' => UploadedFile::fake()->image('receipt.jpg'),
    ])->assertConflict();

    $manual = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$manual->order_number.'/payments', [
        'payment_method' => 'qr_code',
    ])->assertCreated();

    $this->post('/api/v1/orders/'.$manual->order_number.'/payment/proof', [
        'proof' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
    ])->assertUnprocessable();
});

test('switching method clears previous proof file', function () {
    Storage::fake('public');
    $order = createPayableOrder();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'bank_transfer',
    ])->assertCreated();

    $this->post('/api/v1/orders/'.$order->order_number.'/payment/proof', [
        'proof' => UploadedFile::fake()->image('receipt.jpg'),
    ])->assertOk();

    $oldPath = Payment::query()->where('order_id', $order->id)->firstOrFail()->proof_path;

    expect($oldPath)->not->toBeNull();

    $this->postJson('/api/v1/orders/'.$order->order_number.'/payments', [
        'payment_method' => 'qr_code',
    ])->assertCreated();

    $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();

    expect($payment->payment_method)->toBe(PaymentMethod::QrCode);
    expect($payment->proof_path)->toBeNull();
    Storage::disk('public')->assertMissing($oldPath);
});
