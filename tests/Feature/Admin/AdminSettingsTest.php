<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Http\Resources\Api\V1\SettingResource;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;

function adminUser(): User
{
    return User::factory()->create(['is_admin' => true]);
}

test('guests are redirected and buyers forbidden from admin area', function () {
    $this->get('/admin')->assertRedirect('/login');
    $this->getJson('/api/v1/admin/settings')->assertUnauthorized();

    $buyer = User::factory()->create();

    $this->actingAs($buyer)->get('/admin')->assertForbidden();
    $this->actingAs($buyer)->getJson('/api/v1/admin/settings')->assertForbidden();
});

test('admins can reach dashboard and all admin crud api routes', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get('/admin')->assertOk();
    $this->actingAs($admin)->getJson('/api/v1/admin/users')->assertOk();
    $this->actingAs($admin)->getJson('/api/v1/admin/sellers')->assertOk();
    $this->actingAs($admin)->getJson('/api/v1/admin/products')->assertOk();
    $this->actingAs($admin)->getJson('/api/v1/admin/categories')->assertOk();
    $this->actingAs($admin)->getJson('/api/v1/admin/orders')->assertOk();
    $this->actingAs($admin)->getJson('/api/v1/admin/payments')->assertOk();
    $this->actingAs($admin)->getJson('/api/v1/admin/settings')->assertOk();
});

test('buyers are forbidden from every admin crud api route', function () {
    $buyer = User::factory()->create();

    $this->actingAs($buyer)->getJson('/api/v1/admin/users')->assertForbidden();
    $this->actingAs($buyer)->putJson('/api/v1/admin/settings', ['settings' => []])->assertForbidden();
});

test('admin settings update validates and sanitizes per key', function () {
    $admin = adminUser();

    // Invalid color is rejected.
    $this->actingAs($admin)->putJson('/api/v1/admin/settings', [
        'settings' => [['key' => 'branding.primary_color', 'value' => 'not-a-color']],
    ])->assertUnprocessable();

    // Unknown keys are rejected.
    $this->actingAs($admin)->putJson('/api/v1/admin/settings', [
        'settings' => [['key' => 'nope.unknown', 'value' => 'x']],
    ])->assertUnprocessable();

    // HTML is stripped and whitespace trimmed on save.
    $response = $this->actingAs($admin)->putJson('/api/v1/admin/settings', [
        'settings' => [['key' => 'branding.site_name', 'value' => '  <b>Barokah</b>  ']],
    ]);

    $response->assertOk();

    expect(app(SettingsService::class)->get('branding.site_name'))->toBe('Barokah');
});

test('private settings are masked in admin api and absent from public api', function () {
    $service = app(SettingsService::class);
    $service->set('payment.secret_key', 'real-secret');
    $admin = adminUser();

    $response = $this->actingAs($admin)->getJson('/api/v1/admin/settings?group=payment');

    $response->assertOk();

    $entry = collect($response->json('data'))->firstWhere('key', 'payment.secret_key');

    expect($entry['value'])->toBe(SettingResource::MASKED_SENTINEL)
        ->and($entry['masked'])->toBeTrue();

    expect($this->getJson('/api/v1/settings/public')->json())->not->toHaveKey('payment.secret_key');

    // Sending the sentinel back keeps the stored secret untouched.
    $this->actingAs($admin)->putJson('/api/v1/admin/settings', [
        'settings' => [['key' => 'payment.secret_key', 'value' => SettingResource::MASKED_SENTINEL]],
    ])->assertOk();

    $service->forget();

    expect($service->get('payment.secret_key'))->toBe('real-secret');
});

test('settings cache invalidates on update and database wins over config', function () {
    $service = app(SettingsService::class);
    $admin = adminUser();

    $service->allPublic();

    $this->actingAs($admin)->putJson('/api/v1/admin/settings', [
        'key' => 'currency.symbol',
        'value' => 'RM',
    ])->assertOk();

    expect($service->get('currency.symbol'))->toBe('RM');
    expect($this->getJson('/api/v1/settings/public')->json()['currency.symbol'] ?? null)->toBe('RM');

    Setting::query()->where('key', 'currency.symbol')->delete();
    $service->forget();

    expect($service->get('currency.symbol'))->toBe(config('marketplace.currency.symbol'));
});

test('admin dashboard renders metrics and recent orders', function () {
    $admin = adminUser();
    $seller = Seller::factory()->create(['status' => SellerStatus::Active]);
    Product::factory()->create(['seller_id' => $seller->id, 'stock' => 2]);
    $order = Order::factory()->create(['status' => OrderStatus::Paid, 'total' => 120.50]);
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::Pending]);

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Dashboard')
        ->where('stats.orders_count', 1)
        ->where('stats.seller_count', 1)
        ->where('stats.product_count', 1)
        ->where('stats.low_stock_count', 1)
        ->where('stats.pending_payments', 1)
        ->has('stats.revenue')
        ->has('recent_orders', 1)
    );
});

test('admin user seller product category and payment flows work end to end', function () {
    $admin = adminUser();
    $user = User::factory()->create();
    $seller = Seller::factory()->create(['status' => SellerStatus::Pending]);
    $product = Product::factory()->create(['status' => ProductStatus::Draft]);
    $category = Category::factory()->create();
    $payment = Payment::factory()->create();

    $this->actingAs($admin)->putJson("/api/v1/admin/users/{$user->id}", ['is_admin' => true])->assertOk();
    $this->actingAs($admin)->putJson("/api/v1/admin/sellers/{$seller->id}", ['status' => SellerStatus::Active->value])->assertOk();
    $this->actingAs($admin)->putJson("/api/v1/admin/products/{$product->id}", ['status' => ProductStatus::Active->value])->assertOk();

    $this->actingAs($admin)->postJson('/api/v1/admin/categories', ['name' => 'Snacks'])->assertCreated();
    $this->actingAs($admin)->putJson("/api/v1/admin/categories/{$category->id}", ['name' => 'Drinks'])->assertOk();

    $this->actingAs($admin)->getJson('/api/v1/admin/orders')->assertOk();
    $this->actingAs($admin)->getJson("/api/v1/admin/payments/{$payment->id}")->assertOk();

    expect($user->refresh()->is_admin)->toBeTrue();
    expect($seller->refresh()->status)->toBe(SellerStatus::Active);
});
